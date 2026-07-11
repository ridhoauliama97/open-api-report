<?php

namespace App\Services\Ascends\Shared\GeneralLedger\TrialBalance;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class HutangLainnyaReportService
{
    private const TITLE = 'Laporan Hutang Lain-Lain';

    private const SR_EXACT_1 = ['211.300.006', '211.100.205'];

    private const SR_EXACT_2 = ['211.300.008', '211.300.011', '212.100.401'];

    private const SR_PREFIXES_1 = ['211.200', '211.300', '211.400', '211.500', '212.100'];

    private const KODE_UC_ALL = ['212.100.101', '212.100.102', '212.100.104', '212.100.105', '212.100.107'];

    private const KODE_UC_GSU = ['212.100.103'];

    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel = 'request xml payload', array $filters = []): array
    {
        $allRows = $this->parseXml($xmlContents, $sourceLabel);

        if ($allRows === []) {
            throw new RuntimeException('Data tidak ditemukan pada XML.');
        }

        $dateStart = trim((string) ($filters['DateStart'] ?? ''));
        $dateEnd = trim((string) ($filters['DateEnd'] ?? ''));
        $periodLabel = $this->resolvePeriodLabel($dateStart, $dateEnd);

        $company = strtoupper(trim((string) ($filters['company'] ?? '')));

        $filtered = $this->applySelectionFormula($allRows, $company);

        $items = $this->groupByAccount($filtered);

        $grandBeginning = array_sum(array_column($items, 'beginning'));
        $grandDebit = array_sum(array_column($items, 'debit'));
        $grandCredit = array_sum(array_column($items, 'credit'));
        $grandEnding = array_sum(array_column($items, 'ending'));

        return [
            'title' => self::TITLE,
            'company' => '',
            'period_label' => $periodLabel,
            'items' => $items,
            'grand_beginning' => $grandBeginning,
            'grand_debit' => $grandDebit,
            'grand_credit' => $grandCredit,
            'grand_ending' => $grandEnding,
            'printed_by' => '',
        ];
    }

    private function parseXml(string $xmlContents, string $sourceLabel): array
    {
        if (trim($xmlContents) === '') {
            throw new RuntimeException('Data XML kosong.');
        }

        $reader = new XMLReader;
        if (! @$reader->XML($xmlContents, null, LIBXML_NOCDATA | LIBXML_NONET)) {
            throw new RuntimeException("File XML tidak valid: {$sourceLabel}");
        }

        $rows = [];
        while ($reader->read()) {
            if ($reader->nodeType !== XMLReader::ELEMENT || $reader->name !== 'Table1') {
                continue;
            }

            $recordXml = $reader->readOuterXml();
            if (! is_string($recordXml) || trim($recordXml) === '') {
                continue;
            }

            $node = @simplexml_load_string($recordXml, 'SimpleXMLElement', LIBXML_NOCDATA);
            if ($node === false) {
                continue;
            }

            $row = [];
            foreach ($node->children() as $key => $value) {
                $cleanKey = $this->cleanXmlKey((string) $key);
                $row[$cleanKey] = trim((string) $value);
            }

            if (($row['AccountCode1'] ?? '') !== '') {
                $rows[] = $row;
            }
        }

        $reader->close();

        return $rows;
    }

    private function cleanXmlKey(string $key): string
    {
        $key = str_replace('_x0020_', ' ', $key);
        $key = str_replace('_x0028_', '(', $key);
        $key = str_replace('_x0029_', ')', $key);

        return str_replace('_x002F_', '/', $key);
    }

    private function applySelectionFormula(array $rows, string $company): array
    {
        return array_values(array_filter($rows, function (array $row) use ($company): bool {
            $ac1 = (string) ($row['AccountCode1'] ?? '');

            if ($ac1 === '') {
                return false;
            }

            $sr = $this->evaluateSr($ac1, $company);

            return $sr === 1 && ! $this->isKodeUc($ac1, $company);
        }));
    }

    private function evaluateSr(string $ac1, string $company): int
    {
        if (in_array($ac1, self::SR_EXACT_1, true)) {
            return 1;
        }

        if (in_array($ac1, self::SR_EXACT_2, true)) {
            return 2;
        }

        if ($company === 'GSU' && $ac1 === '211.200.201') {
            return 2;
        }

        foreach (self::SR_PREFIXES_1 as $prefix) {
            if (str_starts_with($ac1, $prefix)) {
                return 1;
            }
        }

        return 0;
    }

    private function isKodeUc(string $ac1, string $company): bool
    {
        if (in_array($ac1, self::KODE_UC_ALL, true)) {
            return true;
        }

        if ($company === 'GSU' && in_array($ac1, self::KODE_UC_GSU, true)) {
            return true;
        }

        return false;
    }

    private function resolvePeriodLabel(string $dateStart, string $dateEnd): string
    {
        try {
            $start = $dateStart !== '' ? Carbon::parse($dateStart) : null;
            $end = $dateEnd !== '' ? Carbon::parse($dateEnd) : null;

            $startLabel = $start ? $start->locale('id')->isoFormat('DD-MMM-YY') : '';
            $endLabel = $end ? $end->locale('id')->isoFormat('DD-MMM-YY') : '';

            if ($startLabel !== '' && $endLabel !== '') {
                return 'Dari '.$startLabel.' s/d '.$endLabel;
            }

            return $endLabel !== '' ? 'Per Tanggal '.$endLabel : '';
        } catch (Throwable) {
            return '';
        }
    }

    private function groupByAccount(array $rows): array
    {
        $groups = [];

        foreach ($rows as $row) {
            $ac1 = (string) ($row['AccountCode1'] ?? '');
            $an1 = (string) ($row['AccountName1'] ?? '');
            $debit = (float) ($row['Mutation Debit'] ?? 0);
            $credit = (float) ($row['Mutation Credit'] ?? 0);
            $beginning = (float) ($row['Beginning'] ?? 0);
            $ending = (float) ($row['Ending'] ?? 0);

            if (! isset($groups[$ac1])) {
                $groups[$ac1] = [
                    'account_code' => $ac1,
                    'account_name' => $an1,
                    'debit' => 0.0,
                    'credit' => 0.0,
                    'beginning' => 0.0,
                    'ending' => 0.0,
                ];
            }

            $groups[$ac1]['debit'] += $debit;
            $groups[$ac1]['credit'] += $credit;
            $groups[$ac1]['beginning'] += $beginning;
            $groups[$ac1]['ending'] += $ending;
        }

        uasort($groups, fn ($a, $b) => strcmp($a['account_code'], $b['account_code']));

        foreach ($groups as &$group) {
            $group['debit'] = round($group['debit'], 2);
            $group['credit'] = round($group['credit'], 2);
            $group['beginning'] = round($group['beginning'], 2);
            $group['ending'] = round($group['ending'], 2);
        }

        return array_values($groups);
    }
}

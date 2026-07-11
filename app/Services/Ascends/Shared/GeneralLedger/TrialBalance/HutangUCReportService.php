<?php

namespace App\Services\Ascends\Shared\GeneralLedger\TrialBalance;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class HutangUCReportService
{
    private const TITLE = 'Laporan Hutang UC';

    private const KODE_UC_ACCOUNTS = [
        '212.100.101',
        '212.100.102',
        '212.100.103',
        '212.100.104',
        '212.100.105',
        '212.100.107',
        '212.100.108',
    ];

    private const SR_PREFIXES = ['211.300', '211.500', '212.100'];

    private const RU_EXCLUDE = ['212.100.103'];

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

        $grandDebit = array_sum(array_column($items, 'debit'));
        $grandCredit = array_sum(array_column($items, 'credit'));
        $grandBeginning = array_sum(array_column($items, 'beginning'));
        $grandEnding = array_sum(array_column($items, 'ending'));

        return [
            'title' => self::TITLE,
            'company' => '',
            'period_label' => $periodLabel,
            'items' => $items,
            'grand_debit' => $grandDebit,
            'grand_credit' => $grandCredit,
            'grand_beginning' => $grandBeginning,
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

            if (! $this->isSrAccount($ac1)) {
                return false;
            }

            if (! $this->isKodeUcAccount($ac1)) {
                return false;
            }

            if ($company === 'RU' && in_array($ac1, self::RU_EXCLUDE, true)) {
                return false;
            }

            return true;
        }));
    }

    private function isSrAccount(string $ac1): bool
    {
        $prefix7 = substr($ac1, 0, 7);

        return in_array($prefix7, self::SR_PREFIXES, true);
    }

    private function isKodeUcAccount(string $ac1): bool
    {
        return in_array($ac1, self::KODE_UC_ACCOUNTS, true);
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

            if ($group['account_code'] === '212.100.108') {
                $group['ending'] = -$group['ending'];
            }
        }

        return array_values($groups);
    }
}

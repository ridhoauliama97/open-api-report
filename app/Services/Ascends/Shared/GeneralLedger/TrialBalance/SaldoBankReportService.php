<?php

namespace App\Services\Ascends\Shared\GeneralLedger\TrialBalance;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class SaldoBankReportService
{
    private const TITLE = 'Laporan Saldo Bank';

    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel = 'request xml payload', array $filters = []): array
    {
        $allRows = $this->parseXml($xmlContents, $sourceLabel);

        if ($allRows === []) {
            throw new RuntimeException('Data tidak ditemukan pada XML.');
        }

        $dateEnd = trim((string) ($filters['DateEnd'] ?? ''));
        $periodLabel = $this->resolvePeriodLabel($dateEnd);

        $filtered = $this->applySelectionFormula($allRows);

        if ($filtered === []) {
            throw new RuntimeException('Tidak ada data bank yang memenuhi kriteria.');
        }

        $items = $this->groupByAccount($filtered);

        $grandTotal = array_sum(array_column($items, 'saldo'));

        return [
            'title' => self::TITLE,
            'company' => '',
            'period_label' => $periodLabel,
            'items' => $items,
            'grand_total' => $grandTotal,
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

    private function applySelectionFormula(array $rows): array
    {
        return array_values(array_filter($rows, function (array $row): bool {
            $ac1 = (string) ($row['AccountCode1'] ?? '');

            return $this->isBankAccount($ac1);
        }));
    }

    private function isBankAccount(string $ac1): bool
    {
        if ($ac1 === '') {
            return false;
        }

        if ($ac1 === '111.102.107') {
            return true;
        }

        $prefix7 = substr($ac1, 0, 7);

        return in_array($prefix7, ['111.102', '111.103'], true);
    }

    private function resolvePeriodLabel(string $dateEnd): string
    {
        if ($dateEnd === '') {
            return '';
        }

        try {
            $date = Carbon::parse($dateEnd);

            return 'Per Tanggal '.$date->locale('id')->isoFormat('DD-MMM-YY');
        } catch (Throwable) {
            return 'Per Tanggal '.$dateEnd;
        }
    }

    private function groupByAccount(array $rows): array
    {
        $groups = [];

        foreach ($rows as $row) {
            $ac1 = (string) ($row['AccountCode1'] ?? '');
            $an1 = (string) ($row['AccountName1'] ?? '');
            $ending = (float) ($row['Ending'] ?? 0);

            if (! isset($groups[$ac1])) {
                $groups[$ac1] = [
                    'account_code' => $ac1,
                    'account_name' => $an1,
                    'saldo' => 0.0,
                ];
            }

            $groups[$ac1]['saldo'] += $ending;
        }

        uasort($groups, fn ($a, $b) => strcmp($a['account_code'], $b['account_code']));

        foreach ($groups as &$group) {
            $group['saldo'] = round($group['saldo'], 2);
        }

        return array_values($groups);
    }
}

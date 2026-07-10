<?php

namespace App\Services\Ascends\Shared\GeneralLedger\JournalDetails;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class LabaKotorPerKategoriReportService
{
    private const TITLE = 'Laporan Laba Kotor';

    private const GROUP_MAPPING = [
        'A' => 'PL HOUSEWARE',
        'B' => 'PL FURNITURE',
        'C' => 'ENAMEL',
        'D' => 'STAINLESS',
        'E' => 'SAPU',
        'F' => 'LEMARI',
    ];

    private const TARGET_PREFIXES = ['411', '431', '451', '516'];

    private const NAME_GROUP_KEYWORDS = [
        'HOUSEWARE' => 'A',
        'FURNITURE' => 'B',
        'ENAMEL' => 'C',
        'STAINLESS' => 'D',
        'SAPU' => 'E',
        'LEMARI' => 'F',
    ];

    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel = 'request xml payload', array $filters = []): array
    {
        $allRows = $this->parseXml($xmlContents, $sourceLabel);

        if ($allRows === []) {
            throw new RuntimeException('Data jurnal tidak ditemukan pada XML.');
        }

        $startDate = trim((string) ($filters['Date.StartDate'] ?? $filters['Date_StartDate'] ?? $filters['StartDate'] ?? $filters['start_date'] ?? ''));
        $endDate = trim((string) ($filters['Date.EndDate'] ?? $filters['Date_EndDate'] ?? $filters['EndDate'] ?? $filters['end_date'] ?? ''));

        $filtered = $this->applyAccountFilter($allRows);

        if ($filtered === []) {
            throw new RuntimeException('Tidak ada data yang memenuhi kriteria.');
        }

        $comparisonDate = $endDate !== '' ? $endDate : $startDate;
        $currentMonth = $this->resolveMonthKey($comparisonDate);
        $previousMonth = $this->resolvePreviousMonthKey($comparisonDate);

        $groups = $this->aggregateGroups($filtered, $currentMonth, $previousMonth);

        $grandCurrent = 0.0;
        $grandPrevious = 0.0;
        foreach ($groups as &$group) {
            $group['current_total'] = round($group['current_total'], 2);
            $group['previous_total'] = round($group['previous_total'], 2);

            $group['current_rasio'] = $this->computeRasio($group['current_total'], $group['current_gross_sales']);
            $group['previous_rasio'] = $this->computeRasio($group['previous_total'], $group['previous_gross_sales']);

            $group['beda'] = $this->computeBeda($group['current_total'], $group['previous_total']);

            foreach ($group['items'] as &$item) {
                $item['current_amount'] = round($item['current_amount'], 2);
                $item['previous_amount'] = round($item['previous_amount'], 2);
            }

            $grandCurrent += $group['current_total'];
            $grandPrevious += $group['previous_total'];
        }
        unset($group);

        $period = $this->resolvePeriod($startDate, $endDate);

        $currentLabel = $this->formatMonthLabel($currentMonth);
        $previousLabel = $this->formatMonthLabel($previousMonth);

        return [
            'title' => self::TITLE,
            'company' => '',
            'period_label' => $period['label'],
            'current_month' => $currentLabel,
            'previous_month' => $previousLabel,
            'groups' => array_values($groups),
            'grand_current' => round($grandCurrent, 2),
            'grand_previous' => round($grandPrevious, 2),
            'printed_at' => Carbon::now()->locale('id')->translatedFormat('d F Y H:i'),
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
            if ($reader->nodeType !== XMLReader::ELEMENT || strtolower($reader->name) !== 'invoices') {
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

            if (($row['Account Code'] ?? '') !== '') {
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

    private function applyAccountFilter(array $rows): array
    {
        $result = [];

        foreach ($rows as $row) {
            $accountCode = (string) ($row['Account Code'] ?? '');
            $prefix = substr($accountCode, 0, 3);

            if (! in_array($prefix, self::TARGET_PREFIXES, true)) {
                continue;
            }

            $result[] = $row;
        }

        return $result;
    }

    private function resolveMonthKey(string $dateString): string
    {
        if ($dateString === '') {
            return now()->format('Y-m');
        }

        try {
            return Carbon::parse($dateString)->format('Y-m');
        } catch (Throwable) {
            return now()->format('Y-m');
        }
    }

    private function resolvePreviousMonthKey(string $dateString): string
    {
        if ($dateString === '') {
            return now()->subMonth()->format('Y-m');
        }

        try {
            return Carbon::parse($dateString)->subMonth()->format('Y-m');
        } catch (Throwable) {
            return now()->subMonth()->format('Y-m');
        }
    }

    private function formatMonthLabel(string $monthKey): string
    {
        try {
            $dt = Carbon::createFromFormat('Y-m', $monthKey);

            return $dt->locale('en')->isoFormat('MMM - YYYY');
        } catch (Throwable) {
            return $monthKey;
        }
    }

    private function applyGroupName(string $accountName): string
    {
        $nameUpper = strtoupper($accountName);

        foreach (self::NAME_GROUP_KEYWORDS as $keyword => $letter) {
            if (str_contains($nameUpper, $keyword)) {
                return $letter;
            }
        }

        return 'O';
    }

    private function applyNameGroup(string $groupLetter): string
    {
        return self::GROUP_MAPPING[$groupLetter] ?? 'O';
    }

    private function aggregateGroups(array $rows, string $currentMonth, string $previousMonth): array
    {
        $groups = [];

        foreach ($rows as $row) {
            $accountCode = (string) ($row['Account Code'] ?? '');
            $accountName = (string) ($row['Account Name'] ?? '');
            $voucherDate = (string) ($row['Voucher Date'] ?? '');
            $amountDb = (float) ($row['Amount DB'] ?? 0);
            $amountCr = (float) ($row['Amount CR'] ?? 0);

            $monthKey = '';
            if ($voucherDate !== '') {
                try {
                    $monthKey = Carbon::parse($voucherDate)->format('Y-m');
                } catch (Throwable) {
                    continue;
                }
            }

            if ($monthKey !== $currentMonth && $monthKey !== $previousMonth) {
                continue;
            }

            $groupLetter = $this->applyGroupName($accountName);
            $groupName = $this->applyNameGroup($groupLetter);

            if (! isset($groups[$groupName])) {
                $groups[$groupName] = [
                    'name' => $groupName,
                    'items' => [],
                    'current_total' => 0.0,
                    'previous_total' => 0.0,
                    'current_gross_sales' => 0.0,
                    'previous_gross_sales' => 0.0,
                ];
            }

            $netAmount = $amountCr - $amountDb;

            $isCurrent = $monthKey === $currentMonth;

            $itemKey = $accountCode.'|||'.$accountName;
            if (! isset($groups[$groupName]['items'][$itemKey])) {
                $groups[$groupName]['items'][$itemKey] = [
                    'account_code' => $accountCode,
                    'account_name' => $accountName,
                    'current_amount' => 0.0,
                    'previous_amount' => 0.0,
                ];
            }

            if ($isCurrent) {
                $groups[$groupName]['items'][$itemKey]['current_amount'] += $netAmount;
                $groups[$groupName]['current_total'] += $netAmount;

                if (substr($accountCode, 0, 3) === '411' && $netAmount > 0) {
                    $groups[$groupName]['current_gross_sales'] += $netAmount;
                }
            } else {
                $groups[$groupName]['items'][$itemKey]['previous_amount'] += $netAmount;
                $groups[$groupName]['previous_total'] += $netAmount;

                if (substr($accountCode, 0, 3) === '411' && $netAmount > 0) {
                    $groups[$groupName]['previous_gross_sales'] += $netAmount;
                }
            }
        }

        foreach ($groups as &$group) {
            $group['items'] = array_values($group['items']);

            usort($group['items'], function (array $a, array $b): int {
                $orderA = array_search(substr($a['account_code'], 0, 3), self::TARGET_PREFIXES);
                $orderB = array_search(substr($b['account_code'], 0, 3), self::TARGET_PREFIXES);

                if ($orderA !== $orderB) {
                    return $orderA <=> $orderB;
                }

                return strcmp($a['account_code'], $b['account_code']);
            });
        }
        unset($group);

        $orderedKeys = ['PL HOUSEWARE', 'PL FURNITURE', 'ENAMEL', 'STAINLESS', 'SAPU', 'LEMARI'];
        $ordered = [];
        foreach ($orderedKeys as $key) {
            if (isset($groups[$key])) {
                $ordered[$key] = $groups[$key];
                unset($groups[$key]);
            }
        }
        foreach ($groups as $key => $group) {
            $ordered[$key] = $group;
        }

        return $ordered;
    }

    private function computeRasio(float $total, float $grossSales): float
    {
        if ($grossSales == 0) {
            return 0.0;
        }

        return round($total / $grossSales * 100, 2);
    }

    private function computeBeda(float $current, float $previous): float
    {
        if ($previous == 0) {
            return 0.0;
        }

        return round(($current - $previous) / abs($previous) * 100, 2);
    }

    private function resolvePeriod(string $startDate, string $endDate): array
    {
        $startLabel = '';
        $endLabel = '';

        if ($startDate !== '') {
            try {
                $startLabel = $this->formatPeriodDate(Carbon::parse($startDate));
            } catch (Throwable) {
                $startLabel = $startDate;
            }
        }

        if ($endDate !== '') {
            try {
                $endLabel = $this->formatPeriodDate(Carbon::parse($endDate));
            } catch (Throwable) {
                $endLabel = $endDate;
            }
        }

        return [
            'start' => $startLabel,
            'end' => $endLabel,
            'label' => 'Dari '.$startLabel.' s/d '.$endLabel,
        ];
    }

    private function formatPeriodDate(Carbon $date): string
    {
        $months = [
            1 => 'Jan',
            2 => 'Feb',
            3 => 'Mar',
            4 => 'Apr',
            5 => 'Mei',
            6 => 'Jun',
            7 => 'Jul',
            8 => 'Agu',
            9 => 'Sep',
            10 => 'Okt',
            11 => 'Nov',
            12 => 'Des',
        ];

        return $date->format('d').'-'.$months[(int) $date->format('n')].'-'.$date->format('y');
    }
}

<?php

namespace App\Services\Ascends\Shared\GeneralLedger\JournalDetails;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class LabaKotorGsuReportService
{
    private const TITLE = 'Laporan Laba Kotor Tahunan';

    private const GROUP_MAPPING = [
        'A' => 'PL HOUSEWARE',
        'B' => 'PL FURNITURE',
        'C' => 'ENAMEL',
        'D' => 'STAINLESS',
        'E' => 'SAPU',
        'F' => 'LEMARI',
        'O' => 'LAINNYA',
    ];

    private const TARGET_PREFIXES = ['411', '451', '431', '516'];

    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel = 'request xml payload', array $filters = []): array
    {
        $allRows = $this->parseXml($xmlContents, $sourceLabel);

        if ($allRows === []) {
            throw new RuntimeException('Data jurnal tidak ditemukan pada XML.');
        }

        $startDate = trim((string) ($filters['Date.StartDate'] ?? $filters['Date_StartDate'] ?? $filters['StartDate'] ?? $filters['start_date'] ?? ''));
        $endDate = trim((string) ($filters['Date.EndDate'] ?? $filters['Date_EndDate'] ?? $filters['EndDate'] ?? $filters['end_date'] ?? ''));
        $months = $this->resolveMonths($startDate, $endDate);

        if ($months === []) {
            throw new RuntimeException('Parameter tanggal tidak valid.');
        }

        $filtered = $this->applyFilters($allRows, $startDate, $endDate);

        if ($filtered === []) {
            throw new RuntimeException('Tidak ada data yang memenuhi kriteria.');
        }

        $groups = $this->aggregateGroups($filtered, $months);

        if ($groups === []) {
            throw new RuntimeException('Tidak ada data yang memenuhi kriteria.');
        }

        $groups = $this->computeGroupMargins($groups, $months);
        $totals = $this->computeTotals($groups, $months);
        $totalMargins = $this->computeMargins($totals['total'], $totals['sales'], $months);
        $hppGlobal = $this->computeHppGlobal($totals, $months);
        $period = $this->resolvePeriod($startDate, $endDate);

        return [
            'title' => self::TITLE,
            'company' => '',
            'period_label' => $period['label'],
            'start_date' => $period['start'],
            'end_date' => $period['end'],
            'months' => $this->formatMonthLabels($months),
            'groups' => $groups,
            'total_margins' => $totalMargins['monthly'],
            'total_rata_rata' => $totalMargins['rata_rata'],
            'total_terendah' => $totalMargins['terendah'],
            'total_tertinggi' => $totalMargins['tertinggi'],
            'hpp_global' => $hppGlobal['monthly'],
            'hpp_global_rata_rata' => $hppGlobal['rata_rata'],
            'hpp_global_terendah' => $hppGlobal['terendah'],
            'hpp_global_tertinggi' => $hppGlobal['tertinggi'],
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

    private function applyFilters(array $rows, string $startDate, string $endDate): array
    {
        $start = null;
        $end = null;

        if ($startDate !== '') {
            try {
                $start = Carbon::parse($startDate)->startOfDay();
            } catch (Throwable) {
            }
        }

        if ($endDate !== '') {
            try {
                $end = Carbon::parse($endDate)->endOfDay();
            } catch (Throwable) {
            }
        }

        $result = [];

        foreach ($rows as $row) {
            $accountCode = (string) ($row['Account Code'] ?? '');

            if (! in_array(substr($accountCode, 0, 3), self::TARGET_PREFIXES, true)) {
                continue;
            }

            if ($start !== null || $end !== null) {
                $voucherDate = trim((string) ($row['Voucher Date'] ?? ''));
                if ($voucherDate === '') {
                    continue;
                }

                try {
                    $date = Carbon::parse($voucherDate);

                    if ($start !== null && $date->lessThan($start)) {
                        continue;
                    }

                    if ($end !== null && $date->greaterThan($end)) {
                        continue;
                    }
                } catch (Throwable) {
                    continue;
                }
            }

            $result[] = $row;
        }

        return $result;
    }

    private function resolveMonths(string $startDate, string $endDate): array
    {
        $months = [];

        try {
            $start = Carbon::parse($startDate)->startOfMonth();
            $end = Carbon::parse($endDate)->startOfMonth();

            $current = $start->copy();
            while ($current->lessThanOrEqualTo($end)) {
                $months[] = $current->format('Y-m');
                $current->addMonth();
            }
        } catch (Throwable) {
        }

        return $months;
    }

    private function formatMonthLabels(array $months): array
    {
        $labels = [];

        foreach ($months as $monthKey) {
            try {
                $labels[$monthKey] = Carbon::createFromFormat('Y-m', $monthKey)->locale('en')->isoFormat('MMM-YY');
            } catch (Throwable) {
                $labels[$monthKey] = $monthKey;
            }
        }

        return $labels;
    }

    private function aggregateGroups(array $rows, array $months): array
    {
        $monthSet = array_fill_keys($months, true);
        $groups = [];

        foreach ($rows as $row) {
            $accountCode = (string) ($row['Account Code'] ?? '');
            $accountName = (string) ($row['Account Name'] ?? '');
            $voucherDate = (string) ($row['Voucher Date'] ?? '');
            $amountDb = (float) ($row['Amount DB'] ?? 0);
            $amountCr = (float) ($row['Amount CR'] ?? 0);

            try {
                $monthKey = Carbon::parse($voucherDate)->format('Y-m');
            } catch (Throwable) {
                continue;
            }

            if (! isset($monthSet[$monthKey])) {
                continue;
            }

            $groupName = $this->resolveGroupName($accountName);

            if (! isset($groups[$groupName])) {
                $groups[$groupName] = [
                    'name' => $groupName,
                    'items' => [],
                    'monthly_total' => array_fill_keys($months, 0.0),
                    'monthly_sales' => array_fill_keys($months, 0.0),
                    'monthly_hpp' => array_fill_keys($months, 0.0),
                ];
            }

            $netAmount = $amountCr - $amountDb;
            $itemKey = $accountCode.'|||'.$accountName;

            if (! isset($groups[$groupName]['items'][$itemKey])) {
                $groups[$groupName]['items'][$itemKey] = [
                    'account_code' => $accountCode,
                    'account_name' => $accountName,
                    'monthly_amounts' => array_fill_keys($months, 0.0),
                    'total_amount' => 0.0,
                ];
            }

            $groups[$groupName]['items'][$itemKey]['monthly_amounts'][$monthKey] += $netAmount;
            $groups[$groupName]['items'][$itemKey]['total_amount'] += $netAmount;
            $groups[$groupName]['monthly_total'][$monthKey] += $netAmount;

            if (str_starts_with($accountCode, '411.') && $netAmount > 0) {
                $groups[$groupName]['monthly_sales'][$monthKey] += $netAmount;
            }

            if (str_starts_with($accountCode, '516.')) {
                $groups[$groupName]['monthly_hpp'][$monthKey] += $netAmount;
            }
        }

        foreach ($groups as &$group) {
            $group['items'] = array_values($group['items']);
            usort($group['items'], function (array $a, array $b): int {
                $orderA = array_search(substr((string) $a['account_code'], 0, 3), self::TARGET_PREFIXES, true);
                $orderB = array_search(substr((string) $b['account_code'], 0, 3), self::TARGET_PREFIXES, true);

                if ($orderA !== $orderB) {
                    return $orderA <=> $orderB;
                }

                return strcmp((string) $a['account_code'], (string) $b['account_code']);
            });
        }
        unset($group);

        $ordered = [];
        foreach (self::GROUP_MAPPING as $groupName) {
            if (isset($groups[$groupName])) {
                $ordered[] = $groups[$groupName];
            }
        }

        return $ordered;
    }

    private function resolveGroupName(string $accountName): string
    {
        $name = strtoupper($accountName);

        if (str_contains($name, 'HOUSEWARE')) {
            return self::GROUP_MAPPING['A'];
        }

        if (str_contains($name, 'FURNITURE')) {
            return self::GROUP_MAPPING['B'];
        }

        if (str_contains($name, 'ENAMEL')) {
            return self::GROUP_MAPPING['C'];
        }

        if (str_contains($name, 'STAINLESS')) {
            return self::GROUP_MAPPING['D'];
        }

        if (str_contains($name, 'SAPU')) {
            return self::GROUP_MAPPING['E'];
        }

        if (str_contains($name, 'LEMARI')) {
            return self::GROUP_MAPPING['F'];
        }

        return self::GROUP_MAPPING['O'];
    }

    private function computeGroupMargins(array $groups, array $months): array
    {
        foreach ($groups as &$group) {
            $margins = $this->computeMargins($group['monthly_total'], $group['monthly_sales'], $months);
            $group['monthly_margin'] = $margins['monthly'];
            $group['rata_rata'] = $margins['rata_rata'];
            $group['terendah'] = $margins['terendah'];
            $group['tertinggi'] = $margins['tertinggi'];
        }
        unset($group);

        return $groups;
    }

    private function computeMargins(array $monthlyTotal, array $monthlySales, array $months): array
    {
        $monthly = [];

        foreach ($months as $monthKey) {
            $sales = (float) ($monthlySales[$monthKey] ?? 0);
            $total = (float) ($monthlyTotal[$monthKey] ?? 0);
            $monthly[$monthKey] = $sales != 0.0 ? round($total / $sales * 100, 2) : 0.0;
        }

        $nonZero = array_values(array_filter($monthly, static fn (float $value): bool => $value != 0.0));

        return [
            'monthly' => $monthly,
            'rata_rata' => $nonZero !== [] ? round(array_sum($nonZero) / count($nonZero), 2) : 0.0,
            'terendah' => $monthly !== [] ? round(min($monthly), 2) : 0.0,
            'tertinggi' => $monthly !== [] ? round(max($monthly), 2) : 0.0,
        ];
    }

    private function computeTotals(array $groups, array $months): array
    {
        $total = array_fill_keys($months, 0.0);
        $sales = array_fill_keys($months, 0.0);
        $hpp = array_fill_keys($months, 0.0);

        foreach ($groups as $group) {
            foreach ($months as $monthKey) {
                $total[$monthKey] += (float) ($group['monthly_total'][$monthKey] ?? 0);
                $sales[$monthKey] += (float) ($group['monthly_sales'][$monthKey] ?? 0);
                $hpp[$monthKey] += (float) ($group['monthly_hpp'][$monthKey] ?? 0);
            }
        }

        return [
            'total' => $total,
            'sales' => $sales,
            'hpp' => $hpp,
        ];
    }

    private function computeHppGlobal(array $totals, array $months): array
    {
        $monthly = [];

        foreach ($months as $monthKey) {
            $sales = (float) ($totals['sales'][$monthKey] ?? 0);
            $hpp = (float) ($totals['hpp'][$monthKey] ?? 0);
            $monthly[$monthKey] = $sales != 0.0 ? round($hpp / $sales * 100, 2) : 0.0;
        }

        $nonZero = array_values(array_filter($monthly, static fn (float $value): bool => $value != 0.0));

        return [
            'monthly' => $monthly,
            'rata_rata' => $nonZero !== [] ? round(array_sum($nonZero) / count($nonZero), 2) : 0.0,
            'terendah' => $monthly !== [] ? round(min($monthly), 2) : 0.0,
            'tertinggi' => $monthly !== [] ? round(max($monthly), 2) : 0.0,
        ];
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

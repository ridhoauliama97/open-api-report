<?php

namespace App\Services\Ascends\Shared\CustomReport;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class BiayaMobilTrukReportService
{
    private const TITLE = 'Laporan Biaya Mobil / Truk (Periode 6 Bulanan)';

    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel, array $filters = []): array
    {
        $rows = $this->parseRows($xmlContents, $sourceLabel);

        if ($rows === []) {
            throw new RuntimeException('Data tidak ditemukan pada XML.');
        }

        $startDate = $this->resolveDateFilter($filters, 'StartDate');
        $endDate = $this->resolveDateFilter($filters, 'EndDate');

        $filteredRows = $this->filterByDateRange($rows, $startDate, $endDate);

        if ($filteredRows === []) {
            throw new RuntimeException('Tidak ada data dalam rentang tanggal yang dipilih.');
        }

        $months = $this->collectMonths($filteredRows, $startDate, $endDate);

        $grouped = $this->groupByLowestDescription($filteredRows);
        $sections = $this->buildSections($grouped, $months);
        $grandTotals = $this->buildGrandTotals($sections, $months);

        $printedBy = trim((string) ($filters['Sys_Username'] ?? $filters['sys_username'] ?? ''));
        $company = trim((string) ($filters['DB_CompanyName'] ?? $filters['company'] ?? ''));

        return [
            'title' => self::TITLE,
            'source_file' => $sourceLabel,
            'printed_by' => $printedBy,
            'company' => $company,
            'headerCompany' => $company,
            'headerTitle' => self::TITLE,
            'start_date' => $startDate?->locale('id')->isoFormat('DD-MMM-YY') ?? '',
            'end_date' => $endDate?->locale('id')->isoFormat('DD-MMM-YY') ?? '',
            'months' => array_map(fn (Carbon $m) => $m->locale('id')->isoFormat('MMMM'), $months),
            'months_raw' => $months,
            'sections' => $sections,
            'grand_totals' => $grandTotals,
            'total_sections' => count($sections),
        ];
    }

    private function parseRows(string $xmlContents, string $sourceLabel): array
    {
        if (trim($xmlContents) === '') {
            throw new RuntimeException('Data XML wajib dikirim.');
        }

        $reader = new XMLReader;
        if (! @$reader->XML($xmlContents, null, LIBXML_NOCDATA)) {
            throw new RuntimeException("File XML tidak valid ({$sourceLabel}).");
        }

        $rows = [];
        while ($reader->read()) {
            if ($reader->nodeType !== XMLReader::ELEMENT || strtolower($reader->name) !== 'table') {
                continue;
            }

            $nodeXml = $reader->readOuterXml();
            if (! is_string($nodeXml) || trim($nodeXml) === '') {
                continue;
            }

            $node = simplexml_load_string($nodeXml);
            if ($node === false) {
                continue;
            }

            $row = [];
            foreach ($node->children() as $key => $value) {
                $row[$key] = trim((string) $value);
            }

            $rows[] = $row;
        }

        $reader->close();

        return $rows;
    }

    private function resolveDateFilter(array $filters, string $key): ?Carbon
    {
        $value = trim((string) (
            $filters[$key]
            ?? $filters[strtolower($key)]
            ?? $filters['DateRange.'.$key]
            ?? $filters['DateRange_'.$key]
            ?? ''
        ));

        if ($value === '') {
            return null;
        }

        return $this->parseDate($value);
    }

    private function parseDate(string $value): ?Carbon
    {
        if (trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->startOfDay();
        } catch (Throwable) {
            return null;
        }
    }

    private function filterByDateRange(array $rows, ?Carbon $startDate, ?Carbon $endDate): array
    {
        if ($startDate === null && $endDate === null) {
            return $rows;
        }

        return array_values(array_filter(
            $rows,
            fn (array $row): bool => $this->isWithinDateRange($row, $startDate, $endDate)
        ));
    }

    private function isWithinDateRange(array $row, ?Carbon $startDate, ?Carbon $endDate): bool
    {
        $rowDate = $this->parseDate((string) ($row['VoucherDate'] ?? ''));
        if ($rowDate === null) {
            return false;
        }

        if ($startDate !== null && $rowDate->lessThan($startDate->startOfDay())) {
            return false;
        }

        if ($endDate !== null && $rowDate->greaterThan($endDate->endOfDay())) {
            return false;
        }

        return true;
    }

    private function collectMonths(array $rows, ?Carbon $startDate, ?Carbon $endDate): array
    {
        $months = [];
        foreach ($rows as $row) {
            $date = $this->parseDate((string) ($row['VoucherDate'] ?? ''));
            if ($date !== null) {
                $key = $date->format('Y-m');
                if (! isset($months[$key])) {
                    $months[$key] = $date->copy()->startOfMonth();
                }
            }
        }

        if ($startDate !== null && $endDate !== null && count($months) === 0) {
            $curr = $startDate->copy()->startOfMonth();
            $end = $endDate->copy()->startOfMonth();
            while ($curr->lessThanOrEqualTo($end)) {
                $months[$curr->format('Y-m')] = $curr->copy();
                $curr->addMonth();
            }
        }

        ksort($months);

        // If months exceed 6 or we want standard 6 monthly period from startDate
        if (count($months) > 6) {
            $months = array_slice($months, 0, 6, true);
        }

        return array_values($months);
    }

    private function groupByLowestDescription(array $rows): array
    {
        $grouped = [];
        foreach ($rows as $row) {
            $key = trim((string) ($row['LowestDescription'] ?? ''));
            if ($key === '') {
                $key = 'Lain-Lain';
            }
            $grouped[$key][] = $row;
        }

        ksort($grouped);

        return $grouped;
    }

    private function buildSections(array $grouped, array $months): array
    {
        $sections = [];

        foreach ($grouped as $lowestDesc => $groupRows) {
            // Group further by AccountName
            $accounts = [];
            foreach ($groupRows as $row) {
                $accName = trim((string) ($row['AccountName'] ?? ''));
                if ($accName === '') {
                    $accName = 'Akun Lain';
                }
                $accounts[$accName][] = $row;
            }

            ksort($accounts);

            $accountRows = [];
            $subtotalValues = array_fill(0, count($months), 0.0);
            $subtotalAmount = 0.0;

            foreach ($accounts as $accName => $accRows) {
                $monthlyValues = [];
                $accTotal = 0.0;

                foreach ($months as $month) {
                    $monthSum = 0.0;
                    foreach ($accRows as $row) {
                        $rowDate = $this->parseDate((string) ($row['VoucherDate'] ?? ''));
                        if ($rowDate !== null && $rowDate->format('Y-m') === $month->format('Y-m')) {
                            $monthSum += (float) ($row['Amt'] ?? 0);
                        }
                    }
                    $monthlyValues[] = $monthSum;
                    $accTotal += $monthSum;
                }

                $nonZeroMonthly = array_filter($monthlyValues, fn ($v) => $v != 0.0);
                $rata2 = count($months) > 0 ? $accTotal / count($months) : 0.0;
                $terendah = count($nonZeroMonthly) > 0 ? min($monthlyValues) : 0.0;
                $tertenggi = count($monthlyValues) > 0 ? max($monthlyValues) : 0.0;

                $accountRows[] = [
                    'account_name' => $accName,
                    'values' => $monthlyValues,
                    'total' => $accTotal,
                    'rata2' => $rata2,
                    'terendah' => $terendah,
                    'tertinggi' => $tertenggi,
                ];

                for ($i = 0; $i < count($months); $i++) {
                    $subtotalValues[$i] += $monthlyValues[$i];
                }
                $subtotalAmount += $accTotal;
            }

            $subNonZero = array_filter($subtotalValues, fn ($v) => $v != 0.0);
            $subRata2 = count($months) > 0 ? $subtotalAmount / count($months) : 0.0;
            $subTerendah = count($subNonZero) > 0 ? min($subtotalValues) : 0.0;
            $subTertinggi = count($subtotalValues) > 0 ? max($subtotalValues) : 0.0;

            $sections[] = [
                'lowest_description' => $lowestDesc,
                'rows' => $accountRows,
                'subtotal' => [
                    'values' => $subtotalValues,
                    'total' => $subtotalAmount,
                    'rata2' => $subRata2,
                    'terendah' => $subTerendah,
                    'tertinggi' => $subTertinggi,
                ],
            ];
        }

        return $sections;
    }

    private function buildGrandTotals(array $sections, array $months): array
    {
        $totals = array_fill(0, count($months), 0.0);
        $totalAmount = 0.0;

        foreach ($sections as $section) {
            $sub = $section['subtotal'];
            $totalAmount += $sub['total'];
            for ($i = 0; $i < count($months); $i++) {
                $totals[$i] += $sub['values'][$i];
            }
        }

        $nonZero = array_filter($totals, fn ($v) => $v != 0.0);
        $rata2 = count($months) > 0 ? $totalAmount / count($months) : 0.0;
        $terendah = count($nonZero) > 0 ? min($totals) : 0.0;
        $tertinggi = count($totals) > 0 ? max($totals) : 0.0;

        return [
            'values' => $totals,
            'total' => $totalAmount,
            'rata2' => $rata2,
            'terendah' => $terendah,
            'tertinggi' => $tertinggi,
        ];
    }
}

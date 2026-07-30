<?php

namespace App\Services\Ascends\Shared\CustomReport;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class PenjualanVsBiayaPerjalananDinasReportService
{
    private const TITLE = 'Laporan Perjalanan Dinas VS Penjualan (Periode 6 Bulan)';

    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel, array $filters = []): array
    {
        $rows = $this->parseRows($xmlContents, $sourceLabel);

        if ($rows === []) {
            throw new RuntimeException('Data tidak ditemukan pada XML.');
        }

        $filteredRows = $this->filterByDateRange($rows, $filters);

        if ($filteredRows === []) {
            throw new RuntimeException('Tidak ada data dalam rentang tanggal yang dipilih.');
        }

        $filteredRows = $this->filterExcludedSalesPersons($filteredRows);

        $months = $this->collectMonths($filteredRows);
        $grouped = $this->groupBySalesPerson($filteredRows);
        $sections = $this->buildSections($grouped, $months);
        $grandTotals = $this->buildGrandTotals($sections, $months);

        $startDate = $this->resolveDateFilter($filters, 'StartDate');
        $endDate = $this->resolveDateFilter($filters, 'EndDate');

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
            'total_salespersons' => count($sections),
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

    private function filterByDateRange(array $rows, array $filters): array
    {
        $startDate = $this->resolveDateFilter($filters, 'StartDate');
        $endDate = $this->resolveDateFilter($filters, 'EndDate');

        if ($startDate === null && $endDate === null) {
            return $rows;
        }

        return array_values(array_filter(
            $rows,
            fn (array $row): bool => $this->isWithinDateRange($row, $startDate, $endDate)
        ));
    }

    private function resolveDateFilter(array $filters, string $suffix): ?Carbon
    {
        $value = trim((string) (
            $filters[$suffix]
            ?? $filters['DateRange.'.$suffix]
            ?? $filters['DateRange_'.$suffix]
            ?? $filters['date_'.strtolower($suffix)]
            ?? $filters[strtolower($suffix)]
            ?? ''
        ));

        if ($value === '') {
            return null;
        }

        return $this->parseDate($value);
    }

    private function isWithinDateRange(array $row, ?Carbon $startDate, ?Carbon $endDate): bool
    {
        $rowDate = $this->parseDate((string) ($row['Date'] ?? ''));
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

    private function filterExcludedSalesPersons(array $rows): array
    {
        return array_values(array_filter(
            $rows,
            fn (array $row): bool => ! str_starts_with(
                strtoupper(trim((string) ($row['SalesPersonName'] ?? ''))),
                'EDIYANTO'
            )
        ));
    }

    private function collectMonths(array $rows): array
    {
        $months = [];
        foreach ($rows as $row) {
            $date = $this->parseDate((string) ($row['Date'] ?? ''));
            if ($date !== null) {
                $key = $date->format('Y-m');
                if (! isset($months[$key])) {
                    $months[$key] = $date->copy()->startOfMonth();
                }
            }
        }

        ksort($months);

        return array_values($months);
    }

    private function groupBySalesPerson(array $rows): array
    {
        $grouped = [];
        foreach ($rows as $row) {
            $name = trim((string) ($row['SalesPersonName'] ?? ''));
            if ($name === '') {
                continue;
            }
            $grouped[$name][] = $row;
        }

        ksort($grouped, SORT_NATURAL | SORT_FLAG_CASE);

        return $grouped;
    }

    private function buildSections(array $grouped, array $months): array
    {
        $monthKeys = array_map(fn (Carbon $m) => $m->format('Y-m'), $months);

        $sections = [];
        foreach ($grouped as $salesperson => $items) {
            $penjualanByFamily = [];
            $biayaMonthValues = array_fill(0, count($months), 0.0);

            foreach ($items as $item) {
                $status = trim((string) ($item['Status'] ?? ''));
                $family = trim((string) ($item['FamilyName'] ?? ''));
                $monthKey = $this->parseDate((string) ($item['Date'] ?? ''))?->format('Y-m');
                $rp = (float) ($item['Rp'] ?? 0);

                if ($monthKey === null) {
                    continue;
                }

                $monthIdx = array_search($monthKey, $monthKeys, true);
                if ($monthIdx === false) {
                    continue;
                }

                if ($status === 'BIAYA') {
                    $biayaMonthValues[$monthIdx] += $rp;
                } else {
                    if ($family === '') {
                        continue;
                    }
                    if (! isset($penjualanByFamily[$family])) {
                        $penjualanByFamily[$family] = array_fill(0, count($months), 0.0);
                    }
                    $penjualanByFamily[$family][$monthIdx] += $rp;
                }
            }

            $familyNames = array_keys($penjualanByFamily);
            sort($familyNames, SORT_NATURAL | SORT_FLAG_CASE);

            $penjualanRows = [];
            $penjualanTotalValues = array_fill(0, count($months), 0.0);

            foreach ($familyNames as $family) {
                $values = $penjualanByFamily[$family];
                $total = array_sum($values);
                for ($i = 0; $i < count($months); $i++) {
                    $penjualanTotalValues[$i] += $values[$i];
                }

                $penjualanRows[] = [
                    'family' => $family,
                    'values' => $values,
                    'total' => $total,
                    'rata2' => count($months) > 0 ? $total / count($months) : 0,
                    'terendah' => count($values) > 0 ? min($values) : 0,
                    'tertinggi' => count($values) > 0 ? max($values) : 0,
                ];
            }

            $penjualanTotal = count($months) > 0
                ? [
                    'values' => $penjualanTotalValues,
                    'total' => array_sum($penjualanTotalValues),
                    'rata2' => array_sum($penjualanTotalValues) / count($months),
                    'terendah' => min($penjualanTotalValues),
                    'tertinggi' => max($penjualanTotalValues),
                ]
                : null;

            $hasPenjualan = count($penjualanRows) > 0;
            $hasBiaya = array_sum($biayaMonthValues) > 0;

            $biayaTotal = $hasBiaya
                ? [
                    'values' => $biayaMonthValues,
                    'total' => array_sum($biayaMonthValues),
                    'rata2' => array_sum($biayaMonthValues) / count($months),
                    'terendah' => min($biayaMonthValues),
                    'tertinggi' => max($biayaMonthValues),
                ]
                : null;

            $percentage = [];
            for ($i = 0; $i < count($months); $i++) {
                $pj = $penjualanTotalValues[$i];
                $by = $biayaMonthValues[$i];
                if ($by == 0 || $pj == 0) {
                    $percentage[] = 0.0;
                } else {
                    $percentage[] = round(($by / $pj) * 100, 2);
                }
            }

            $sections[] = [
                'salesperson' => $salesperson,
                'has_penjualan' => $hasPenjualan,
                'has_biaya' => $hasBiaya,
                'penjualan_rows' => $penjualanRows,
                'penjualan_total' => $penjualanTotal,
                'biaya' => $biayaTotal,
                'percentage' => $percentage,
            ];
        }

        return $sections;
    }

    private function buildGrandTotals(array $sections, array $months): array
    {
        $monthCount = count($months);
        $grandPenjualanByFamily = [];
        $grandPenjualanTotalValues = array_fill(0, $monthCount, 0.0);
        $grandBiayaValues = array_fill(0, $monthCount, 0.0);

        foreach ($sections as $section) {
            foreach ($section['penjualan_rows'] as $row) {
                $family = $row['family'];
                if (! isset($grandPenjualanByFamily[$family])) {
                    $grandPenjualanByFamily[$family] = array_fill(0, $monthCount, 0.0);
                }
                for ($i = 0; $i < $monthCount; $i++) {
                    $grandPenjualanByFamily[$family][$i] += $row['values'][$i];
                    $grandPenjualanTotalValues[$i] += $row['values'][$i];
                }
            }

            if ($section['biaya'] !== null) {
                for ($i = 0; $i < $monthCount; $i++) {
                    $grandBiayaValues[$i] += $section['biaya']['values'][$i];
                }
            }
        }

        $familyNames = array_keys($grandPenjualanByFamily);
        sort($familyNames, SORT_NATURAL | SORT_FLAG_CASE);

        $penjualanRows = [];
        foreach ($familyNames as $family) {
            $values = $grandPenjualanByFamily[$family];
            $total = array_sum($values);
            $penjualanRows[] = [
                'family' => $family,
                'values' => $values,
                'total' => $total,
                'rata2' => $monthCount > 0 ? $total / $monthCount : 0,
                'terendah' => count($values) > 0 ? min($values) : 0,
                'tertinggi' => count($values) > 0 ? max($values) : 0,
            ];
        }

        $penjualanTotal = $monthCount > 0
            ? [
                'values' => $grandPenjualanTotalValues,
                'total' => array_sum($grandPenjualanTotalValues),
                'rata2' => array_sum($grandPenjualanTotalValues) / $monthCount,
                'terendah' => min($grandPenjualanTotalValues),
                'tertinggi' => max($grandPenjualanTotalValues),
            ]
            : null;

        $hasGrandBiaya = array_sum($grandBiayaValues) > 0;

        $biayaTotal = $hasGrandBiaya
            ? [
                'values' => $grandBiayaValues,
                'total' => array_sum($grandBiayaValues),
                'rata2' => array_sum($grandBiayaValues) / $monthCount,
                'terendah' => min($grandBiayaValues),
                'tertinggi' => max($grandBiayaValues),
            ]
            : null;

        $percentage = [];
        for ($i = 0; $i < $monthCount; $i++) {
            $pj = $grandPenjualanTotalValues[$i];
            $by = $grandBiayaValues[$i];
            if ($by == 0 || $pj == 0) {
                $percentage[] = 0.0;
            } else {
                $percentage[] = round(($by / $pj) * 100, 2);
            }
        }

        return [
            'has_penjualan' => count($penjualanRows) > 0,
            'has_biaya' => $hasGrandBiaya,
            'penjualan_rows' => $penjualanRows,
            'penjualan_total' => $penjualanTotal,
            'biaya' => $biayaTotal,
            'percentage' => $percentage,
        ];
    }
}

<?php

namespace App\Services\Ascends\Shared\CustomReport;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class PenjualanPerItemAnalisaSkuDetailReportService
{
    private const TITLE = 'Laporan Penjualan Per Item Barang';

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

        $months = $this->collectMonths($filteredRows);
        $grouped = $this->groupByFamily($filteredRows);
        $tableSections = $this->buildTableSections($grouped, $months);
        $grandTotals = $this->buildGrandTotals($tableSections, $months);

        $grandTotalQty = array_sum(array_map(fn (array $gt): float => $gt['qty'], $grandTotals));
        $grandTotalPenjualan = array_sum(array_map(fn (array $gt): float => $gt['penjualan'], $grandTotals));

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
            'months' => array_map(fn (Carbon $m) => $m->locale('id')->isoFormat('MMM-YYYY'), $months),
            'months_raw' => $months,
            'sections' => $tableSections,
            'grand_totals' => $grandTotals,
            'grand_total_qty' => $grandTotalQty,
            'grand_total_penjualan' => $grandTotalPenjualan,
            'total_items' => count($filteredRows),
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
        $rowDate = $this->parseDate((string) ($row['InvoiceDate'] ?? ''));
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

    private function collectMonths(array $rows): array
    {
        $months = [];
        foreach ($rows as $row) {
            $date = $this->parseDate((string) ($row['InvoiceDate'] ?? ''));
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

    private function groupByFamily(array $rows): array
    {
        $grouped = [];
        foreach ($rows as $row) {
            $family = (string) ($row['FamilyName'] ?? '');
            if ($family === '') {
                continue;
            }
            $grouped[$family][] = $row;
        }

        ksort($grouped, SORT_NATURAL | SORT_FLAG_CASE);

        return $grouped;
    }

    private function buildTableSections(array $grouped, array $months): array
    {
        $monthKeys = array_map(fn (Carbon $m) => $m->format('Y-m'), $months);

        $sections = [];
        foreach ($grouped as $family => $items) {
            $itemGroups = [];
            $familyTotals = $this->emptyMonthCells($months);

            foreach ($items as $item) {
                $itemName = (string) ($item['ItemName'] ?? '');
                $monthKey = $this->parseDate((string) ($item['InvoiceDate'] ?? ''))
                    ?->format('Y-m');
                $qty = (float) ($item['Qty'] ?? 0);
                $penjualan = (float) ($item['Penjualan'] ?? 0);

                if ($monthKey === null) {
                    continue;
                }

                $monthIdx = array_search($monthKey, $monthKeys, true);
                if ($monthIdx === false) {
                    continue;
                }

                if (! isset($itemGroups[$itemName])) {
                    $itemGroups[$itemName] = $this->emptyMonthCells($months);
                }

                $itemGroups[$itemName][$monthIdx]['qty'] += $qty;
                $itemGroups[$itemName][$monthIdx]['penjualan'] += $penjualan;

                $familyTotals[$monthIdx]['qty'] += $qty;
                $familyTotals[$monthIdx]['penjualan'] += $penjualan;
            }

            $itemNames = array_keys($itemGroups);
            sort($itemNames, SORT_NATURAL | SORT_FLAG_CASE);

            $rows = [];
            foreach ($itemNames as $itemName) {
                $cells = $itemGroups[$itemName];
                $rowTotalQty = array_sum(array_map(fn (array $c): float => $c['qty'], $cells));
                $rowTotalPenjualan = array_sum(array_map(fn (array $c): float => $c['penjualan'], $cells));

                $rows[] = [
                    'item' => $itemName,
                    'cells' => $cells,
                    'row_total_qty' => $rowTotalQty,
                    'row_total_penjualan' => $rowTotalPenjualan,
                ];
            }

            $sections[] = [
                'family' => $family,
                'rows' => $rows,
                'totals' => $familyTotals,
            ];
        }

        return $sections;
    }

    private function emptyMonthCells(array $months): array
    {
        $cells = [];
        foreach ($months as $month) {
            $cells[] = ['qty' => 0.0, 'penjualan' => 0.0];
        }

        return $cells;
    }

    private function buildGrandTotals(array $sections, array $months): array
    {
        $totals = $this->emptyMonthCells($months);

        foreach ($sections as $section) {
            foreach ($months as $i => $month) {
                $totals[$i]['qty'] += $section['totals'][$i]['qty'];
                $totals[$i]['penjualan'] += $section['totals'][$i]['penjualan'];
            }
        }

        return $totals;
    }
}

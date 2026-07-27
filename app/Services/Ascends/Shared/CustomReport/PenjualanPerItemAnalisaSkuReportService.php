<?php

namespace App\Services\Ascends\Shared\CustomReport;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class PenjualanPerItemAnalisaSkuReportService
{
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

        $grouped = $this->groupByFamily($filteredRows);
        $months = $this->collectMonths($filteredRows);
        $tableRows = $this->buildTableRows($grouped, $months);
        $totalsRow = $this->buildTotalsRow($tableRows, $months);

        $startDate = $this->resolveDateFilter($filters, 'StartDate');
        $endDate = $this->resolveDateFilter($filters, 'EndDate');

        $printedBy = trim((string) ($filters['Sys_Username'] ?? $filters['sys_username'] ?? ''));
        $company = trim((string) ($filters['DB_CompanyName'] ?? $filters['company'] ?? ''));

        return [
            'title' => 'Laporan Penjualan Per Item Barang & Analisa SKU',
            'source_file' => $sourceLabel,
            'printed_by' => $printedBy,
            'company' => $company,
            'headerCompany' => $company,
            'headerTitle' => 'Laporan Penjualan Per Item Barang & Analisa SKU',
            'start_date' => $startDate?->locale('id')->isoFormat('DD-MMM-YY') ?? '',
            'end_date' => $endDate?->locale('id')->isoFormat('DD-MMM-YY') ?? '',
            'months' => array_map(fn (Carbon $m) => $m->locale('id')->isoFormat('MMM-YY'), $months),
            'months_raw' => $months,
            'rows' => $tableRows,
            'totals' => $totalsRow,
            'total_rows' => count($tableRows),
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
        $rowDate = $this->parseDate((string) ($row['Mnth'] ?? ''));
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

    private function collectMonths(array $rows): array
    {
        $months = [];
        foreach ($rows as $row) {
            $date = $this->parseDate((string) ($row['Mnth'] ?? ''));
            if ($date !== null) {
                $key = $date->format('Y-m');
                $months[$key] = $date;
            }
        }

        ksort($months);

        return array_values($months);
    }

    private function buildTableRows(array $grouped, array $months): array
    {
        $tableRows = [];

        foreach ($grouped as $family => $items) {
            $row = [
                'family' => $family,
                'cells' => [],
            ];

            foreach ($months as $month) {
                $monthKey = $month->format('Y-m');
                $match = null;
                foreach ($items as $item) {
                    $itemDate = $this->parseDate((string) ($item['Mnth'] ?? ''));
                    if ($itemDate !== null && $itemDate->format('Y-m') === $monthKey) {
                        $match = $item;
                        break;
                    }
                }

                if ($match !== null) {
                    $sku = (int) ($match['SKU'] ?? 0);
                    $hasil = (int) ($match['Hasil'] ?? 0);
                    $percent = $sku > 0 ? round(($hasil / $sku) * 100, 1) : 0;

                    $row['cells'][] = [
                        'sku' => $sku,
                        'hasil' => $hasil,
                        'percent' => $percent,
                    ];
                } else {
                    $row['cells'][] = [
                        'sku' => 0,
                        'hasil' => 0,
                        'percent' => 0,
                    ];
                }
            }

            $tableRows[] = $row;
        }

        return $tableRows;
    }

    private function buildTotalsRow(array $tableRows, array $months): array
    {
        $totals = ['cells' => []];

        foreach ($months as $i => $month) {
            $totalSku = 0;
            $totalHasil = 0;

            foreach ($tableRows as $row) {
                $cell = $row['cells'][$i] ?? null;
                if ($cell !== null) {
                    $totalSku += $cell['sku'];
                    $totalHasil += $cell['hasil'];
                }
            }

            $totals['cells'][] = [
                'sku' => $totalSku,
                'hasil' => $totalHasil,
                'percent' => $totalSku > 0 ? round(($totalHasil / $totalSku) * 100, 1) : 0,
            ];
        }

        return $totals;
    }
}

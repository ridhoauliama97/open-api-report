<?php

namespace App\Services\Ascends\Shared\CustomReport;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class BudgetingRealisasiBiayaPromosiPenjualanReportService
{
    private const TITLE = 'Laporan Budgeting & Realisasi Biaya Promosi Penjualan (Periode 1 Tahun)';

    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel, array $filters = []): array
    {
        $rows = $this->parseRows($xmlContents, $sourceLabel);

        if ($rows === []) {
            throw new RuntimeException('Data tidak ditemukan pada XML.');
        }

        $filteredRows = $this->filterByYear($rows, $filters);

        if ($filteredRows === []) {
            throw new RuntimeException('Tidak ada data dalam rentang tahun yang dipilih.');
        }

        $monthLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        $monthLabelsFull = $monthLabels;

        $grouped = $this->groupByKetAkun($filteredRows);
        $rows = $this->buildRows($grouped);
        $total = $this->buildTotal($rows);

        $reportYear = $this->resolveYearFilter($filters);

        $printedBy = trim((string) ($filters['Sys_Username'] ?? $filters['sys_username'] ?? ''));
        $company = trim((string) ($filters['DB_CompanyName'] ?? $filters['company'] ?? ''));

        return [
            'title' => self::TITLE,
            'source_file' => $sourceLabel,
            'printed_by' => $printedBy,
            'company' => $company,
            'headerCompany' => $company,
            'headerTitle' => self::TITLE,
            'start_date' => $reportYear !== null ? (string) $reportYear : '',
            'month_labels' => $monthLabels,
            'month_labels_full' => $monthLabelsFull,
            'rows' => $rows,
            'total' => $total,
            'total_count' => count($rows),
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

    private function filterByYear(array $rows, array $filters): array
    {
        $year = $this->resolveYearFilter($filters);

        if ($year === null) {
            return $rows;
        }

        $startOfYear = Carbon::create($year, 1, 1, 0, 0, 0);
        $endOfYear = $startOfYear->copy()->endOfYear();

        return array_values(array_filter(
            $rows,
            fn (array $row): bool => $this->isWithinYear($row, $startOfYear, $endOfYear)
        ));
    }

    private function resolveYearFilter(array $filters): ?int
    {
        $value = trim((string) (
            $filters['StartDate']
            ?? $filters['start_date']
            ?? $filters['startdate']
            ?? ''
        ));

        if ($value === '') {
            return null;
        }

        if (ctype_digit($value) && strlen($value) === 4) {
            return (int) $value;
        }

        try {
            $date = Carbon::parse($value);

            return (int) $date->format('Y');
        } catch (Throwable) {
            return null;
        }
    }

    private function isWithinYear(array $row, Carbon $startOfYear, Carbon $endOfYear): bool
    {
        $rowDate = $this->parseDate((string) ($row['VoucherDate'] ?? ''));
        if ($rowDate === null) {
            return false;
        }

        return $rowDate->between($startOfYear, $endOfYear);
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

    private function getKetAkun(array $row): string
    {
        $description = trim((string) ($row['Description'] ?? ''));

        if ($description === '') {
            return 'Z.Biaya Promosi Non Akun';
        }

        return trim((string) ($row['CostCenter'] ?? ''));
    }

    private function getKetAkunName(array $row): string
    {
        $description = trim((string) ($row['Description'] ?? ''));

        if ($description === '') {
            return 'Biaya Promosi Non Akun';
        }

        return trim((string) ($row['LowestDescription'] ?? ''));
    }

    private function groupByKetAkun(array $rows): array
    {
        $grouped = [];
        $names = [];

        foreach ($rows as $row) {
            $key = $this->getKetAkun($row);
            if ($key === '') {
                continue;
            }

            $grouped[$key][] = $row;
            $names[$key] = $this->getKetAkunName($row);
        }

        uksort($grouped, function (string $a, string $b): int {
            $aIsZ = str_starts_with($a, 'Z.');
            $bIsZ = str_starts_with($b, 'Z.');

            if ($aIsZ && ! $bIsZ) {
                return 1;
            }
            if (! $aIsZ && $bIsZ) {
                return -1;
            }

            return strnatcasecmp($a, $b);
        });

        $result = [];
        foreach ($grouped as $key => $items) {
            $result[] = [
                'key' => $key,
                'name' => $names[$key],
                'items' => $items,
            ];
        }

        return $result;
    }

    private function buildRows(array $grouped): array
    {
        $monthCount = 12;
        $rows = [];

        foreach ($grouped as $group) {
            $monthValues = array_fill(0, $monthCount, 0.0);

            foreach ($group['items'] as $item) {
                $voucherDate = trim((string) ($item['VoucherDate'] ?? ''));
                if ($voucherDate === '') {
                    continue;
                }

                $date = $this->parseDate($voucherDate);
                if ($date === null) {
                    continue;
                }

                $monthIndex = (int) $date->format('n') - 1;
                $total = (float) ($item['Total'] ?? 0);

                $monthValues[$monthIndex] += $total;
            }

            $totalValue = array_sum($monthValues);

            $rows[] = [
                'name' => $group['name'],
                'values' => $monthValues,
                'total' => $totalValue,
            ];
        }

        return $rows;
    }

    private function buildTotal(array $rows): array
    {
        $monthCount = 12;
        $monthValues = array_fill(0, $monthCount, 0.0);

        foreach ($rows as $row) {
            for ($i = 0; $i < $monthCount; $i++) {
                $monthValues[$i] += $row['values'][$i];
            }
        }

        $totalValue = array_sum($monthValues);

        return [
            'values' => $monthValues,
            'total' => $totalValue,
        ];
    }
}

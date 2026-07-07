<?php

namespace App\Services\Ascends\Shared\FixedAsset\AssetSummary;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class PenyusutanAktivaReportService
{
    private const TITLE = 'Laporan Daftar Penyusutan Aktiva Tetap';

    private const EXCLUDED_CATEGORIES = [
        'MESIN & PERLATAN PABRIK',
    ];

    private const EXCLUDED_ASSET_CODE_PREFIXES = [
        'KP-004',
        'MS-1360',
        'MSP-141',
        'TN-1010',
    ];

    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel = 'request xml payload', array $filters = []): array
    {
        $allRows = $this->parseXml($xmlContents, $sourceLabel);

        if ($allRows === []) {
            throw new RuntimeException('Data aktiva tetap tidak ditemukan pada XML.');
        }

        $filteredRows = $this->applyFilters($allRows);

        if ($filteredRows === []) {
            throw new RuntimeException('Tidak ada data aktiva tetap yang memenuhi kriteria.');
        }

        $groupedRows = $this->groupByCategory($filteredRows);
        $categorySubtotals = $this->calculateCategorySubtotals($groupedRows);
        $grandTotals = $this->calculateGrandTotals($categorySubtotals);
        $period = $this->resolvePeriod($filters);

        return [
            'title' => self::TITLE,
            'headerTitle' => self::TITLE,
            'company' => '',
            'period_label' => $period['label'],
            'start_date' => $period['start'],
            'end_date' => $period['end'],
            'printed_at' => Carbon::now()->locale('id')->translatedFormat('d F Y H:i'),
            'printed_by' => '',
            'grouped_rows' => $groupedRows,
            'category_subtotals' => $categorySubtotals,
            'grand_totals' => $grandTotals,
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
            if ($reader->nodeType !== XMLReader::ELEMENT || strtolower($reader->name) !== 'dd') {
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
                $cleanKey = str_replace('_x0020_', ' ', (string) $key);
                $cleanKey = str_replace('_x0028_', '(', $cleanKey);
                $cleanKey = str_replace('_x0029_', ')', $cleanKey);
                $row[$cleanKey] = trim((string) $value);
            }

            if (($row['Asset Code'] ?? '') !== '') {
                $rows[] = $row;
            }
        }

        $reader->close();

        return $rows;
    }

    private function applyFilters(array $rows): array
    {
        return array_values(array_filter($rows, function (array $row): bool {
            $acqCost = (float) ($row['Acq. Cost'] ?? $row['Acquisition Cost'] ?? 0);
            if ($acqCost < 1) {
                return false;
            }

            $category = trim((string) ($row['Asset Category'] ?? ''));
            foreach (self::EXCLUDED_CATEGORIES as $excluded) {
                if (stripos($category, $excluded) !== false) {
                    return false;
                }
            }

            $assetCode = trim((string) ($row['Asset Code'] ?? ''));
            foreach (self::EXCLUDED_ASSET_CODE_PREFIXES as $prefix) {
                if (str_starts_with($assetCode, $prefix)) {
                    return false;
                }
            }

            return true;
        }));
    }

    private function groupByCategory(array $rows): array
    {
        $sorted = $rows;
        usort($sorted, static fn (array $a, array $b): int => strcasecmp(
            (string) ($a['Asset Category'] ?? ''),
            (string) ($b['Asset Category'] ?? '')
        ));

        $grouped = [];
        foreach ($sorted as $row) {
            $category = trim((string) ($row['Asset Category'] ?? 'Kategori Lain'));
            $formatted = $this->formatRow($row);
            $grouped[$category][] = $formatted;
        }

        return $grouped;
    }

    private function formatRow(array $row): array
    {
        $acqDate = trim((string) ($row['Acquisition Date'] ?? ''));
        if ($acqDate !== '') {
            try {
                $acqDate = Carbon::parse($acqDate)->format('d/m/Y');
            } catch (Throwable) {
                $acqDate = '-';
            }
        } else {
            $acqDate = '-';
        }

        return [
            'Asset Code' => trim((string) ($row['Asset Code'] ?? '')),
            'Asset Name' => trim((string) ($row['Asset Name'] ?? '')),
            'Asset Category' => trim((string) ($row['Asset Category'] ?? '')),
            'Acquisition Date' => $acqDate,
            'Acquisition Cost' => (float) ($row['Acquisition Cost'] ?? $row['Acq. Cost'] ?? 0),
            'Accum Depreciation' => (float) ($row['Accum. Depreciation'] ?? $row['Accum Depreciation'] ?? 0),
            'Depreciation' => (float) ($row['Depreciation'] ?? 0),
            'Ending Value' => (float) ($row['Ending Value'] ?? 0),
            'Age (Months)' => (int) ($row['Age (Months)'] ?? 0),
        ];
    }

    private function calculateCategorySubtotals(array $groupedRows): array
    {
        $subtotals = [];
        foreach ($groupedRows as $category => $rows) {
            $acq = 0.0;
            $accum = 0.0;
            $dep = 0.0;
            $end = 0.0;

            foreach ($rows as $row) {
                $acq += (float) ($row['Acquisition Cost'] ?? 0);
                $accum += (float) ($row['Accum Depreciation'] ?? 0);
                $dep += (float) ($row['Depreciation'] ?? 0);
                $end += (float) ($row['Ending Value'] ?? 0);
            }

            $subtotals[$category] = [
                'acquisition_cost' => round($acq, 2),
                'accum_depreciation' => round($accum, 2),
                'depreciation' => round($dep, 2),
                'ending_value' => round($end, 2),
            ];
        }

        return $subtotals;
    }

    private function calculateGrandTotals(array $categorySubtotals): array
    {
        $acq = 0.0;
        $accum = 0.0;
        $dep = 0.0;
        $end = 0.0;

        foreach ($categorySubtotals as $sub) {
            $acq += (float) ($sub['acquisition_cost'] ?? 0);
            $accum += (float) ($sub['accum_depreciation'] ?? 0);
            $dep += (float) ($sub['depreciation'] ?? 0);
            $end += (float) ($sub['ending_value'] ?? 0);
        }

        return [
            'acquisition_cost' => round($acq, 2),
            'accum_depreciation' => round($accum, 2),
            'depreciation' => round($dep, 2),
            'ending_value' => round($end, 2),
        ];
    }

    private function resolvePeriod(array $filters): array
    {
        $startDate = trim((string) ($filters['Date.StartDate'] ?? $filters['Date_StartDate'] ?? $filters['StartDate'] ?? $filters['start_date'] ?? ''));
        $endDate = trim((string) ($filters['Date.EndDate'] ?? $filters['Date_EndDate'] ?? $filters['EndDate'] ?? $filters['end_date'] ?? ''));

        if ($startDate !== '' && $endDate !== '') {
            try {
                $start = Carbon::parse($startDate);
                $end = Carbon::parse($endDate);

                return [
                    'start' => $start->locale('id')->translatedFormat('d-M-y'),
                    'end' => $end->locale('id')->translatedFormat('d-M-y'),
                    'label' => 'Dari '.$start->locale('id')->translatedFormat('d-M-y').' s/d '.$end->locale('id')->translatedFormat('d-M-y'),
                ];
            } catch (Throwable) {
            }
        }

        $now = Carbon::now()->locale('id');
        $label = $now->translatedFormat('d-M-y');

        return [
            'start' => $label,
            'end' => $label,
            'label' => 'Periode: '.$label,
        ];
    }
}

<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class BahanYangDihasilkanReportService
{
    private const EXPECTED_COLUMNS = [
        'Group',
        'NamaMesin',
        'Jenis',
        'Tebal',
        'Lebar',
        'Panjang',
        'JmlhBatang',
        'KubikIN',
    ];

    private const CATEGORY_ORDER = ['PROSES S4S', 'PROSES FJ', 'PROSES MLD', 'PROSES LMT', 'PROSES CCAKHIR', 'PROSES SND', 'PROSES PCK'];

    /**
     * @return array<string, mixed>
     */
    public function buildReportData(string $date): array
    {
        $rows = $this->fetchRows($date);
        $grouped = [];

        foreach ($rows as $row) {
            $category = trim((string) ($row['Group'] ?? ''));
            $category = $category !== '' ? $category : 'LAINNYA';

            if (!isset($grouped[$category])) {
                $grouped[$category] = [
                    'name' => $category,
                    'rows' => [],
                    'total_pcs' => 0.0,
                    'total_volume' => 0.0,
                ];
            }

            $pcs = $this->toFloat($row['JmlhBatang'] ?? null) ?? 0.0;
            $volume = $this->toFloat($row['KubikIN'] ?? null) ?? 0.0;

            $grouped[$category]['rows'][] = [
                'NamaMesin' => trim((string) ($row['NamaMesin'] ?? '')),
                'Jenis' => trim((string) ($row['Jenis'] ?? '')),
                'Tebal' => $this->toFloat($row['Tebal'] ?? null),
                'Lebar' => $this->toFloat($row['Lebar'] ?? null),
                'Panjang' => $this->toFloat($row['Panjang'] ?? null),
                'JmlhBatang' => $this->toFloat($row['JmlhBatang'] ?? null),
                'KubikIN' => $this->toFloat($row['KubikIN'] ?? null),
            ];
            $grouped[$category]['total_pcs'] += $pcs;
            $grouped[$category]['total_volume'] += $volume;
        }

        $orderedCategories = [];
        foreach (self::CATEGORY_ORDER as $categoryKey) {
            if (isset($grouped[$categoryKey])) {
                $orderedCategories[] = $grouped[$categoryKey];
            }
        }
        foreach ($grouped as $categoryKey => $group) {
            if (!in_array($categoryKey, self::CATEGORY_ORDER, true)) {
                $orderedCategories[] = $group;
            }
        }

        $summaryRows = [];
        $grandTotalPcs = 0.0;
        $grandTotalVolume = 0.0;

        foreach ($orderedCategories as $index => &$category) {
            $category['no'] = $index + 1;
            $summaryRows[] = [
                'No' => $index + 1,
                'Kategori' => $category['name'],
                'Jumlah' => count($category['rows']),
                'TotalPcs' => $category['total_pcs'],
                'TotalVolume' => $category['total_volume'],
            ];
            $grandTotalPcs += $category['total_pcs'];
            $grandTotalVolume += $category['total_volume'];
        }
        unset($category);

        return [
            'date' => $date,
            'categories' => $orderedCategories,
            'summary_rows' => $summaryRows,
            'grand_totals' => [
                'row_count' => count($rows),
                'category_count' => count($orderedCategories),
                'total_pcs' => $grandTotalPcs,
                'total_volume' => $grandTotalVolume,
            ],
            'summary' => [
                'row_count' => count($rows),
                'category_count' => count($orderedCategories),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function healthCheck(string $date): array
    {
        $rows = $this->fetchRows($date);
        $detectedColumns = array_keys($rows[0] ?? []);
        $missingColumns = array_values(array_diff(self::EXPECTED_COLUMNS, $detectedColumns));
        $extraColumns = array_values(array_diff($detectedColumns, self::EXPECTED_COLUMNS));

        return [
            'is_healthy' => $missingColumns === [],
            'expected_columns' => self::EXPECTED_COLUMNS,
            'detected_columns' => $detectedColumns,
            'missing_columns' => $missingColumns,
            'extra_columns' => $extraColumns,
            'row_count' => count($rows),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchRows(string $date): array
    {
        $connectionName = config('reports.bahan_yang_dihasilkan.database_connection');
        $procedure = (string) config('reports.bahan_yang_dihasilkan.stored_procedure', 'SPWps_LapBahanYangDihasilkan');

        if (!preg_match('/^[A-Za-z0-9_$.]+$/', $procedure)) {
            throw new RuntimeException('Nama stored procedure tidak valid.');
        }

        $rows = DB::connection($connectionName ?: null)
            ->select("SET NOCOUNT ON; EXEC {$procedure} ?", [$date]);

        return array_map(static fn($row): array => (array) $row, $rows);
    }

    private function toFloat(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }
}

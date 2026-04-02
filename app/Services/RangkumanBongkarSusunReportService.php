<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class RangkumanBongkarSusunReportService
{
    private const EXPECTED_COLUMNS = [
        'Category',
        'NoBongkarSusun',
        'Jenis',
        'InA',
        'OutA',
        'Keterangan',
    ];

    private const CATEGORY_ORDER = ['S4S', 'FJ', 'MLD', 'LMT', 'CCA', 'SND', 'BJ'];

    /**
     * @return array<string, mixed>
     */
    public function buildReportData(string $date): array
    {
        $rows = $this->fetchRows($date);
        $grouped = [];

        foreach ($rows as $row) {
            $category = trim((string) ($row['Category'] ?? ''));
            $category = $category !== '' ? $category : 'LAINNYA';

            if (!isset($grouped[$category])) {
                $grouped[$category] = [
                    'name' => $category,
                    'rows' => [],
                    'total_in' => 0.0,
                    'total_out' => 0.0,
                ];
            }

            $in = $this->toFloat($row['InA'] ?? null) ?? 0.0;
            $out = $this->toFloat($row['OutA'] ?? null) ?? 0.0;

            $grouped[$category]['rows'][] = [
                'NoBongkarSusun' => trim((string) ($row['NoBongkarSusun'] ?? '')),
                'Jenis' => trim((string) ($row['Jenis'] ?? '')),
                'InA' => $in,
                'OutA' => $out,
                'Keterangan' => trim((string) ($row['Keterangan'] ?? '')),
            ];
            $grouped[$category]['total_in'] += $in;
            $grouped[$category]['total_out'] += $out;
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
        $grandTotalIn = 0.0;
        $grandTotalOut = 0.0;

        foreach ($orderedCategories as $index => &$category) {
            $category['no'] = $index + 1;
            $summaryRows[] = [
                'No' => $index + 1,
                'Kategori' => $category['name'],
                'Jumlah' => count($category['rows']),
                'TotalIn' => $category['total_in'],
                'TotalOut' => $category['total_out'],
            ];
            $grandTotalIn += $category['total_in'];
            $grandTotalOut += $category['total_out'];
        }
        unset($category);

        return [
            'date' => $date,
            'categories' => $orderedCategories,
            'summary_rows' => $summaryRows,
            'grand_totals' => [
                'row_count' => count($rows),
                'category_count' => count($orderedCategories),
                'total_in' => $grandTotalIn,
                'total_out' => $grandTotalOut,
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
        $connectionName = config('reports.rangkuman_bongkar_susun.database_connection');
        $procedure = (string) config('reports.rangkuman_bongkar_susun.stored_procedure', 'SPWps_LapRangkumanBongkarSusun');

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

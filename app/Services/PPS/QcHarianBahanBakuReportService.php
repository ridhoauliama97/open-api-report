<?php

namespace App\Services\PPS;

use Illuminate\Support\Facades\DB;

class QcHarianBahanBakuReportService
{
    public function fetchByDate(string $reportDate): array
    {
        $connectionName = config('reports.pps_qc_harian_bahan_baku.database_connection');
        $connection = DB::connection($connectionName ?: null);

        $rows = $connection->select(
            <<<'SQL'
            SELECT
                h.NoBahanBaku,
                p.NoPallet,
                jp.Jenis,
                COALESCE(NULLIF(LTRIM(RTRIM(s.NmSupplier)), ''), NULLIF(LTRIM(RTRIM(s.Initial)), ''), '') AS Supplier,
                p.Density,
                p.Density2,
                p.Density3
            FROM BahanBaku_h h
            INNER JOIN BahanBakuPallet_h p ON p.NoBahanBaku = h.NoBahanBaku
            LEFT JOIN MstJenisPlastik jp ON jp.IdJenisPlastik = p.IdJenisPlastik
            LEFT JOIN MstSupplier s ON s.IdSupplier = h.IdSupplier
            WHERE CAST(h.DateCreate AS date) = ?
              AND (
                    ISNULL(p.Density, 0) <> 0
                 OR ISNULL(p.Density2, 0) <> 0
                 OR ISNULL(p.Density3, 0) <> 0
              )
            ORDER BY h.NoBahanBaku, p.NoPallet
            SQL,
            [$reportDate],
        );

        return array_map(function ($row): array {
            $item = (array) $row;
            $densityValues = $this->densityValuesFromRow($item);

            return [
                'NoBahanBaku' => (string) ($item['NoBahanBaku'] ?? ''),
                'NoPallet' => (int) ($item['NoPallet'] ?? 0),
                'Jenis' => (string) ($item['Jenis'] ?? ''),
                'Supplier' => (string) ($item['Supplier'] ?? ''),
                'AvgDensity' => $this->averageDensity($densityValues),
                'DensityValues' => $densityValues,
            ];
        }, $rows);
    }

    public function healthCheck(string $reportDate): array
    {
        $rows = $this->fetchByDate($reportDate);
        $detectedColumns = array_values(array_intersect(
            ['NoBahanBaku', 'NoPallet', 'Jenis', 'AvgDensity'],
            array_keys($rows[0] ?? []),
        ));
        $expectedColumns = config('reports.pps_qc_harian_bahan_baku.expected_columns', []);
        $expectedColumns = is_array($expectedColumns) ? array_values($expectedColumns) : [];

        return [
            'is_healthy' => empty(array_diff($expectedColumns, $detectedColumns)),
            'expected_columns' => $expectedColumns,
            'detected_columns' => $detectedColumns,
            'missing_columns' => array_values(array_diff($expectedColumns, $detectedColumns)),
            'extra_columns' => array_values(array_diff($detectedColumns, $expectedColumns)),
            'row_count' => count($rows),
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @return array<int, float>
     */
    private function densityValuesFromRow(array $row): array
    {
        $values = [];

        foreach (['Density', 'Density2', 'Density3'] as $key) {
            $value = $row[$key] ?? null;

            if (!is_numeric($value)) {
                continue;
            }

            $numeric = (float) $value;
            if ($numeric == 0.0) {
                continue;
            }

            $values[] = $numeric;
        }

        return $values;
    }

    /**
     * @param array<int, float> $densityValues
     */
    private function averageDensity(array $densityValues): ?float
    {
        if ($densityValues === []) {
            return null;
        }

        return array_sum($densityValues) / count($densityValues);
    }
}

<?php

namespace App\Services\PPS;

use Illuminate\Support\Facades\DB;

class QcHarianWashingReportService
{
    public function fetchByDate(string $reportDate): array
    {
        $connectionName = config('reports.pps_qc_harian_washing.database_connection');
        $connection = DB::connection($connectionName ?: null);

        $rows = $connection->select(
            <<<'SQL'
            SELECT
                COALESCE(m.NamaMesin, '') AS Mesin,
                jp.Jenis,
                h.NoWashing AS NoLabel,
                h.Moisture,
                h.Moisture2,
                h.Moisture3,
                h.Density,
                h.Density2,
                h.Density3
            FROM Washing_h h
            LEFT JOIN MstJenisPlastik jp ON jp.IdJenisPlastik = h.IdJenisPlastik
            OUTER APPLY (
                SELECT TOP 1 wp.IdMesin
                FROM WashingProduksiOutput wpo
                INNER JOIN WashingProduksi_h wp ON wp.NoProduksi = wpo.NoProduksi
                WHERE wpo.NoWashing = h.NoWashing
            ) prod
            LEFT JOIN MstMesin m ON m.IdMesin = prod.IdMesin
            WHERE CAST(h.DateCreate AS date) = ?
              AND (
                    ISNULL(h.Moisture, 0) <> 0
                 OR ISNULL(h.Moisture2, 0) <> 0
                 OR ISNULL(h.Moisture3, 0) <> 0
                 OR ISNULL(h.Density, 0) <> 0
                 OR ISNULL(h.Density2, 0) <> 0
                 OR ISNULL(h.Density3, 0) <> 0
              )
            ORDER BY m.NamaMesin, h.NoWashing
            SQL,
            [$reportDate],
        );

        return array_map(function ($row): array {
            $item = (array) $row;

            return [
                'Mesin' => (string) ($item['Mesin'] ?? ''),
                'Jenis' => (string) ($item['Jenis'] ?? ''),
                'NoLabel' => (string) ($item['NoLabel'] ?? ''),
                'Moisture' => $this->toFloat($item['Moisture'] ?? null),
                'Moisture2' => $this->toFloat($item['Moisture2'] ?? null),
                'Moisture3' => $this->toFloat($item['Moisture3'] ?? null),
                'Density' => $this->toFloat($item['Density'] ?? null),
                'Density2' => $this->toFloat($item['Density2'] ?? null),
                'Density3' => $this->toFloat($item['Density3'] ?? null),
            ];
        }, $rows);
    }

    public function healthCheck(string $reportDate): array
    {
        $rows = $this->fetchByDate($reportDate);
        $detectedColumns = array_values(array_intersect(
            ['Mesin', 'Jenis', 'NoLabel', 'Moisture', 'Moisture2', 'Moisture3', 'Density', 'Density2', 'Density3'],
            array_keys($rows[0] ?? []),
        ));
        $expectedColumns = config('reports.pps_qc_harian_washing.expected_columns', []);
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

    private function toFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            $numeric = (float) $value;

            return $numeric == 0.0 ? null : $numeric;
        }

        if (! is_string($value)) {
            return null;
        }

        $normalized = str_replace([',', ' '], ['.', ''], trim($value));

        if (! is_numeric($normalized)) {
            return null;
        }

        $numeric = (float) $normalized;

        return $numeric == 0.0 ? null : $numeric;
    }
}

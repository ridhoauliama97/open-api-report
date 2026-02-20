<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class StockOpnameKayuBulatReportService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetch(): array
    {
        $connectionName = config('reports.stock_opname_kayu_bulat.database_connection');
        $connection = DB::connection($connectionName ?: null);

        $sql = <<<'SQL'
SELECT
    KH.NoKayuBulat,
    CAST(KH.DateCreate AS DATE) AS Tanggal,
    JK.Jenis AS JenisKayu,
    MS.NmSupplier AS Supplier,
    KH.Suket AS NoSuket,
    KH.NoPlat,
    KH.NoTruk,
    D.Tebal,
    D.Lebar,
    D.Panjang,
    COUNT(1) AS Pcs,
    CAST(SUM((D.Tebal * D.Lebar * D.Panjang) / 7200.8) AS DECIMAL(18,4)) AS JmlhTon,
    MIN(D.NoLog) AS UrutNoLog
FROM KayuBulat_h KH
INNER JOIN KayuBulat_d D ON D.NoKayuBulat = KH.NoKayuBulat
LEFT JOIN MstJenisKayu JK ON JK.IdJenisKayu = KH.IdJenisKayu
LEFT JOIN MstSupplier MS ON MS.IdSupplier = KH.IdSupplier
WHERE CAST(KH.DateCreate AS DATE) BETWEEN DATEADD(DAY, -30, CAST(GETDATE() AS DATE)) AND CAST(GETDATE() AS DATE)
GROUP BY
    KH.NoKayuBulat,
    CAST(KH.DateCreate AS DATE),
    JK.Jenis,
    MS.NmSupplier,
    KH.Suket,
    KH.NoPlat,
    KH.NoTruk,
    D.Tebal,
    D.Lebar,
    D.Panjang
ORDER BY CAST(KH.DateCreate AS DATE) DESC, KH.NoKayuBulat DESC, UrutNoLog
SQL;

        $rows = $connection->select($sql);

        return array_map(static fn($row): array => (array) $row, $rows);
    }

    /**
     * @return array<string, mixed>
     */
    public function buildReportData(): array
    {
        $rows = $this->fetch();
        $grouped = $this->groupByNoKayuBulat($rows);

        return [
            'rows' => $rows,
            'grouped_rows' => $grouped,
            'summary' => $this->buildSummary($rows, $grouped),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function healthCheck(): array
    {
        $rows = $this->fetch();
        $detectedColumns = array_keys($rows[0] ?? []);
        $expectedColumns = config('reports.stock_opname_kayu_bulat.expected_columns', []);
        $expectedColumns = is_array($expectedColumns) ? array_values($expectedColumns) : [];
        $missingColumns = array_values(array_diff($expectedColumns, $detectedColumns));
        $extraColumns = array_values(array_diff($detectedColumns, $expectedColumns));

        return [
            'is_healthy' => empty($missingColumns),
            'expected_columns' => $expectedColumns,
            'detected_columns' => $detectedColumns,
            'missing_columns' => $missingColumns,
            'extra_columns' => $extraColumns,
            'row_count' => count($rows),
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array{no_kayu_bulat: string, rows: array<int, array<string, mixed>>}>
     */
    private function groupByNoKayuBulat(array $rows): array
    {
        $groups = [];
        foreach ($rows as $row) {
            $noKayu = trim((string) ($row['NoKayuBulat'] ?? ''));
            $noKayu = $noKayu !== '' ? $noKayu : 'Tanpa NoKayuBulat';
            $groups[$noKayu][] = $row;
        }

        krsort($groups, SORT_NATURAL);

        $result = [];
        foreach ($groups as $noKayu => $groupRows) {
            $result[] = [
                'no_kayu_bulat' => $noKayu,
                'rows' => array_values($groupRows),
            ];
        }

        return $result;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @param array<int, array{no_kayu_bulat: string, rows: array<int, array<string, mixed>>}> $grouped
     * @return array<string, mixed>
     */
    private function buildSummary(array $rows, array $grouped): array
    {
        $totalPcs = 0.0;
        $totalTon = 0.0;
        $perNoKayu = [];

        foreach ($rows as $row) {
            $totalPcs += is_numeric($row['Pcs'] ?? null) ? (float) $row['Pcs'] : 0.0;
            $totalTon += is_numeric($row['JmlhTon'] ?? null) ? (float) $row['JmlhTon'] : 0.0;
        }

        foreach ($grouped as $group) {
            $groupPcs = 0.0;
            $groupTon = 0.0;
            foreach ($group['rows'] as $row) {
                $groupPcs += is_numeric($row['Pcs'] ?? null) ? (float) $row['Pcs'] : 0.0;
                $groupTon += is_numeric($row['JmlhTon'] ?? null) ? (float) $row['JmlhTon'] : 0.0;
            }
            $perNoKayu[] = [
                'no_kayu_bulat' => $group['no_kayu_bulat'],
                'total_rows' => count($group['rows']),
                'total_pcs' => $groupPcs,
                'total_ton' => $groupTon,
            ];
        }

        return [
            'total_rows' => count($rows),
            'total_no_kayu_bulat' => count($grouped),
            'total_pcs' => $totalPcs,
            'total_ton' => $totalTon,
            'per_no_kayu_bulat' => $perNoKayu,
        ];
    }
}

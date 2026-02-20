<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class PenerimaanKayuBulatBulananPerSupplierReportService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetch(string $startDate, string $endDate): array
    {
        $rows = $this->runProcedureQuery($startDate, $endDate, false);

        return $this->normalizeRows($rows);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetchSubReport(string $startDate, string $endDate): array
    {
        $rows = $this->runProcedureQuery($startDate, $endDate, true);

        return $this->normalizeRows($rows);
    }

    /**
     * @return array<string, mixed>
     */
    public function buildReportData(string $startDate, string $endDate): array
    {
        $rows = $this->fetch($startDate, $endDate);
        $subRows = $this->fetchSubReport($startDate, $endDate);
        $detailRows = $this->fetchDetailRows($startDate, $endDate);

        $supplierColumn = $this->resolveSupplierColumn($rows);
        $subSupplierColumn = $this->resolveSupplierColumn($subRows);
        $detailSupplierColumn = $this->resolveSupplierColumn($detailRows);

        $groupedData = $this->groupRowsBySupplier($rows, $supplierColumn);
        $groupedSubData = $this->groupRowsBySupplier($subRows, $subSupplierColumn);
        $groupedDetailData = $this->groupRowsBySupplier($detailRows, $detailSupplierColumn);
        $detailSummary = $this->buildDetailSummary($groupedDetailData, $startDate, $endDate);
        $recapSummary = $this->buildRecapSummary($detailRows);

        return [
            'data' => $this->flattenGroupedRows($groupedData),
            'sub_data' => $this->flattenGroupedRows($groupedSubData),
            'detail_data' => $this->flattenGroupedRows($groupedDetailData),
            'grouped_data' => $groupedData,
            'grouped_sub_data' => $groupedSubData,
            'grouped_detail_data' => $groupedDetailData,
            'summary' => [
                'main' => $this->buildSummary($groupedData),
                'sub' => $this->buildSummary($groupedSubData),
                'detail' => $detailSummary,
                'recap' => $recapSummary,
            ],
            'supplier_column' => $supplierColumn,
            'sub_supplier_column' => $subSupplierColumn,
            'detail_supplier_column' => $detailSupplierColumn,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function healthCheck(string $startDate, string $endDate): array
    {
        $rows = $this->fetch($startDate, $endDate);
        $subRows = $this->fetchSubReport($startDate, $endDate);
        $detectedColumns = array_keys($rows[0] ?? []);
        $detectedSubColumns = array_keys($subRows[0] ?? []);
        $expectedColumns = config('reports.penerimaan_kayu_bulat_bulanan_per_supplier.expected_columns', []);
        $expectedSubColumns = config('reports.penerimaan_kayu_bulat_bulanan_per_supplier.expected_sub_columns', []);
        $expectedColumns = is_array($expectedColumns) ? array_values($expectedColumns) : [];
        $expectedSubColumns = is_array($expectedSubColumns) ? array_values($expectedSubColumns) : [];
        $missingColumns = array_values(array_diff($expectedColumns, $detectedColumns));
        $extraColumns = array_values(array_diff($detectedColumns, $expectedColumns));
        $missingSubColumns = array_values(array_diff($expectedSubColumns, $detectedSubColumns));
        $extraSubColumns = array_values(array_diff($detectedSubColumns, $expectedSubColumns));

        return [
            'is_healthy' => empty($missingColumns) && empty($missingSubColumns),
            'expected_columns' => $expectedColumns,
            'detected_columns' => $detectedColumns,
            'missing_columns' => $missingColumns,
            'extra_columns' => $extraColumns,
            'row_count' => count($rows),
            'expected_sub_columns' => $expectedSubColumns,
            'detected_sub_columns' => $detectedSubColumns,
            'missing_sub_columns' => $missingSubColumns,
            'extra_sub_columns' => $extraSubColumns,
            'sub_row_count' => count($subRows),
        ];
    }

    /**
     * @param array<int, object> $rows
     * @return array<int, array<string, mixed>>
     */
    private function normalizeRows(array $rows): array
    {
        return array_map(static fn($row): array => (array) $row, $rows);
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array{supplier: string, rows: array<int, array<string, mixed>>}>
     */
    private function groupRowsBySupplier(array $rows, ?string $supplierColumn): array
    {
        if ($rows === []) {
            return [];
        }

        $grouped = [];
        foreach ($rows as $row) {
            $supplier = $supplierColumn !== null
                ? trim((string) ($row[$supplierColumn] ?? ''))
                : '';
            $supplier = $supplier !== '' ? $supplier : 'Tanpa Supplier';
            $grouped[$supplier][] = $row;
        }

        ksort($grouped, SORT_NATURAL | SORT_FLAG_CASE);

        $result = [];
        foreach ($grouped as $supplier => $groupRows) {
            $result[] = [
                'supplier' => $supplier,
                'rows' => array_values($groupRows),
            ];
        }

        return $result;
    }

    /**
     * @param array<int, array{supplier: string, rows: array<int, array<string, mixed>>}> $groups
     * @return array<int, array<string, mixed>>
     */
    private function flattenGroupedRows(array $groups): array
    {
        $flattened = [];

        foreach ($groups as $group) {
            foreach ($group['rows'] as $row) {
                $flattened[] = $row;
            }
        }

        return $flattened;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    private function resolveSupplierColumn(array $rows): ?string
    {
        $keys = array_keys($rows[0] ?? []);
        if ($keys === []) {
            return null;
        }

        $candidates = [
            'Nama Supplier',
            'NamaSupplier',
            'Nama_Supplier',
            'Supplier',
            'supplier',
        ];

        foreach ($candidates as $candidate) {
            foreach ($keys as $key) {
                if (strcasecmp(trim($key), trim($candidate)) === 0) {
                    return $key;
                }
            }
        }

        foreach ($keys as $key) {
            if (str_contains(strtolower($key), 'supplier')) {
                return $key;
            }
        }

        return null;
    }

    /**
     * @param array<int, array{supplier: string, rows: array<int, array<string, mixed>>}> $groups
     * @return array<string, mixed>
     */
    private function buildSummary(array $groups): array
    {
        $totalRows = 0;
        $numericTotals = [];
        $suppliers = [];

        foreach ($groups as $group) {
            $supplier = $group['supplier'];
            $groupRows = $group['rows'];
            $totalRows += count($groupRows);

            $supplierNumericTotals = [];
            foreach ($groupRows as $row) {
                foreach ($row as $column => $value) {
                    $numeric = $this->toFloat($value);
                    if ($numeric === null) {
                        continue;
                    }

                    $numericTotals[$column] = ($numericTotals[$column] ?? 0.0) + $numeric;
                    $supplierNumericTotals[$column] = ($supplierNumericTotals[$column] ?? 0.0) + $numeric;
                }
            }

            $suppliers[] = [
                'supplier' => $supplier,
                'total_rows' => count($groupRows),
                'numeric_totals' => $supplierNumericTotals,
            ];
        }

        return [
            'total_suppliers' => count($groups),
            'total_rows' => $totalRows,
            'numeric_totals' => $numericTotals,
            'suppliers' => $suppliers,
        ];
    }

    private function toFloat(mixed $value): ?float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        if (!is_string($value)) {
            return null;
        }

        $normalized = str_replace([' ', ','], ['', '.'], trim($value));

        return is_numeric($normalized) ? (float) $normalized : null;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function resolveEffectiveTon(array $row): float
    {
        $tonKg = $this->toFloat($row['TonKG'] ?? 0) ?? 0.0;
        if ($tonKg > 0) {
            return $tonKg;
        }

        return $this->toFloat($row['TonKB'] ?? 0) ?? 0.0;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchDetailRows(string $startDate, string $endDate): array
    {
        $connectionName = config('reports.penerimaan_kayu_bulat_bulanan_per_supplier.database_connection');
        $connection = DB::connection($connectionName ?: null);

        $sql = <<<'SQL'
SELECT
    A.NoKayuBulat AS [NoKayuBulat],
    A.NoTruk AS [NoTruk],
    CAST(A.DateCreate AS DATE) AS [Tanggal],
    D.Jenis AS [JenisKayu],
    MKB.NamaGrade AS [NamaGrade],
    ISNULL(KG.JmlhBatang, ISNULL(KBPCS.JmlhPcs, 0)) AS [JmlhPcs],
    CAST(ISNULL(KB.TonKB, 0) AS DECIMAL(18,4)) AS [TonKB],
    CAST(ISNULL(KG.Berat, 0) / 1000.0 AS DECIMAL(18,4)) AS [TonKG],
    CASE
        WHEN A.IdSupplierAsalKayu IS NULL OR A.IdSupplierAsalKayu LIKE '% %'
            THEN C.NmSupplier
        ELSE F.NmSupplier + ' (' + C.NmSupplier + ')'
    END AS [NmSupplier]
FROM KayuBulat_h A
LEFT JOIN MstSupplier C ON C.IdSupplier = A.IdSupplier
LEFT JOIN MstSupplier F ON F.IdSupplier = A.IdSupplierAsalKayu
LEFT JOIN MstJenisKayu D ON D.IdJenisKayu = A.IdJenisKayu
LEFT JOIN KayuBulatKG_d KG ON KG.NoKayuBulat = A.NoKayuBulat
LEFT JOIN MstGradeKB MKB ON MKB.IdGradeKB = KG.IdGradeKB
LEFT JOIN (
    SELECT
        D2.NoKayuBulat,
        COUNT(1) AS JmlhPcs
    FROM KayuBulat_d D2
    GROUP BY D2.NoKayuBulat
) KBPCS ON KBPCS.NoKayuBulat = A.NoKayuBulat
LEFT JOIN (
    SELECT
        D1.NoKayuBulat,
        SUM(FLOOR(D1.Tebal * D1.Lebar * D1.Panjang / 7200.8 * 10000) / 10000.0) AS TonKB
    FROM KayuBulat_d D1
    GROUP BY D1.NoKayuBulat
) KB ON KB.NoKayuBulat = A.NoKayuBulat
WHERE CAST(A.DateCreate AS DATE) BETWEEN ? AND ?
ORDER BY
    CASE
        WHEN A.IdSupplierAsalKayu IS NULL OR A.IdSupplierAsalKayu LIKE '% %'
            THEN C.NmSupplier
        ELSE F.NmSupplier + ' (' + C.NmSupplier + ')'
    END,
    A.NoKayuBulat,
    CASE
        WHEN MKB.NamaGrade LIKE '%AFKIR%' THEN 1
        WHEN MKB.NamaGrade LIKE '%MC%' THEN 2
        WHEN MKB.NamaGrade LIKE '%STD%' THEN 3
        WHEN MKB.NamaGrade LIKE '%SAMSAM%' THEN 4
        ELSE 99
    END,
    KG.NoUrut
SQL;

        $rows = $connection->select($sql, [$startDate, $endDate]);

        return $this->normalizeRows($rows);
    }

    /**
     * @param array<int, array{supplier: string, rows: array<int, array<string, mixed>>}> $groups
     * @return array<string, mixed>
     */
    private function buildDetailSummary(array $groups, string $startDate, string $endDate): array
    {
        $totalRows = 0;
        $totalSuppliers = count($groups);
        $totalTonKg = 0.0;
        $totalTonKb = 0.0;
        $totalPcs = 0.0;
        $workingDays = max(
            1,
            ((int) floor((strtotime($endDate) - strtotime($startDate)) / 86400)) + 1,
        );
        $supplierSummaries = [];

        foreach ($groups as $group) {
            $rows = $group['rows'];
            $totalRows += count($rows);

            $tonKg = 0.0;
            $tonKb = 0.0;
            $pcs = 0.0;
            $trucks = [];
            foreach ($rows as $row) {
                $tonKg += $this->resolveEffectiveTon($row);
                $tonKb += $this->toFloat($row['TonKB'] ?? 0) ?? 0.0;
                $pcs += $this->toFloat($row['JmlhPcs'] ?? 0) ?? 0.0;
                $truck = trim((string) ($row['NoTruk'] ?? ''));
                if ($truck !== '') {
                    $trucks[$truck] = true;
                }
            }

            $totalTonKg += $tonKg;
            $totalTonKb += $tonKb;
            $totalPcs += $pcs;

            $supplierSummaries[] = [
                'supplier' => $group['supplier'],
                'total_rows' => count($rows),
                'total_trucks' => count($trucks),
                'total_hk' => $workingDays,
                'total_pcs' => $pcs,
                'ton_per_hk' => $tonKg / $workingDays,
                'total_ton_kg' => $tonKg,
                'total_ton_kb' => $tonKb,
            ];
        }

        return [
            'total_suppliers' => $totalSuppliers,
            'total_rows' => $totalRows,
            'total_hk' => $workingDays,
            'total_pcs' => $totalPcs,
            'total_ton_kg' => $totalTonKg,
            'total_ton_kb' => $totalTonKb,
            'ton_per_hk' => $totalTonKg / $workingDays,
            'suppliers' => $supplierSummaries,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<string, mixed>
     */
    private function buildRecapSummary(array $rows): array
    {
        $daily = [];

        foreach ($rows as $row) {
            $date = trim((string) ($row['Tanggal'] ?? ''));
            if ($date === '') {
                continue;
            }

            if (!isset($daily[$date])) {
                $daily[$date] = [
                    'tanggal' => $date,
                    'jabon_truk' => [],
                    'jabon_ton' => 0.0,
                    'jabon_tgtd_truk' => [],
                    'jabon_tgtd_ton' => 0.0,
                    'pulai_truk' => [],
                    'pulai_ton' => 0.0,
                    'rambung_truk' => [],
                    'rambung_super_ton' => 0.0,
                    'rambung_mc_ton' => 0.0,
                    'rambung_samsam_ton' => 0.0,
                    'rambung_afkir_ton' => 0.0,
                ];
            }

            $jenis = strtoupper(trim((string) ($row['JenisKayu'] ?? '')));
            $grade = strtoupper(trim((string) ($row['NamaGrade'] ?? '')));
            $truck = trim((string) ($row['NoTruk'] ?? ''));
            $ton = $this->resolveEffectiveTon($row);

            if ($jenis === 'JABON') {
                if ($truck !== '') {
                    $daily[$date]['jabon_truk'][$truck] = true;
                }
                $daily[$date]['jabon_ton'] += $ton;
                continue;
            }

            if (str_contains($jenis, 'JABON')) {
                if ($truck !== '') {
                    $daily[$date]['jabon_tgtd_truk'][$truck] = true;
                }
                $daily[$date]['jabon_tgtd_ton'] += $ton;
                continue;
            }

            if (str_contains($jenis, 'PULAI')) {
                if ($truck !== '') {
                    $daily[$date]['pulai_truk'][$truck] = true;
                }
                $daily[$date]['pulai_ton'] += $ton;
                continue;
            }

            if (str_contains($jenis, 'RAMBUNG')) {
                if ($truck !== '') {
                    $daily[$date]['rambung_truk'][$truck] = true;
                }
                if (str_contains($grade, 'MC')) {
                    $daily[$date]['rambung_mc_ton'] += $ton;
                } elseif (str_contains($grade, 'SAMSAM')) {
                    $daily[$date]['rambung_samsam_ton'] += $ton;
                } elseif (str_contains($grade, 'AFKIR')) {
                    $daily[$date]['rambung_afkir_ton'] += $ton;
                } else {
                    $daily[$date]['rambung_super_ton'] += $ton;
                }
            }
        }

        ksort($daily, SORT_NATURAL);

        $rowsOut = [];
        $totals = [
            'jabon_truk' => 0,
            'jabon_ton' => 0.0,
            'jabon_tgtd_truk' => 0,
            'jabon_tgtd_ton' => 0.0,
            'pulai_truk' => 0,
            'pulai_ton' => 0.0,
            'rambung_truk' => 0,
            'rambung_super_ton' => 0.0,
            'rambung_mc_ton' => 0.0,
            'rambung_samsam_ton' => 0.0,
            'rambung_afkir_ton' => 0.0,
        ];

        foreach ($daily as $date => $item) {
            $row = [
                'tanggal' => $date,
                'jabon_truk' => count($item['jabon_truk']),
                'jabon_ton' => $item['jabon_ton'],
                'jabon_tgtd_truk' => count($item['jabon_tgtd_truk']),
                'jabon_tgtd_ton' => $item['jabon_tgtd_ton'],
                'pulai_truk' => count($item['pulai_truk']),
                'pulai_ton' => $item['pulai_ton'],
                'rambung_truk' => count($item['rambung_truk']),
                'rambung_super_ton' => $item['rambung_super_ton'],
                'rambung_mc_ton' => $item['rambung_mc_ton'],
                'rambung_samsam_ton' => $item['rambung_samsam_ton'],
                'rambung_afkir_ton' => $item['rambung_afkir_ton'],
            ];
            $rowsOut[] = $row;

            foreach ($totals as $key => $value) {
                $totals[$key] += $row[$key];
            }
        }

        return [
            'rows' => $rowsOut,
            'totals' => $totals,
        ];
    }

    /**
     * @param array<int, string> $bindings
     * @return array<int, string>
     */
    private function resolveBindings(string $query, array $bindings): array
    {
        return str_contains($query, '?') ? $bindings : [];
    }

    /**
     * @return array<int, object>
     */
    private function runProcedureQuery(string $startDate, string $endDate, bool $isSubProcedure): array
    {
        $connectionName = config('reports.penerimaan_kayu_bulat_bulanan_per_supplier.database_connection');
        $procedure = (string) config(
            $isSubProcedure
                ? 'reports.penerimaan_kayu_bulat_bulanan_per_supplier.sub_stored_procedure'
                : 'reports.penerimaan_kayu_bulat_bulanan_per_supplier.stored_procedure'
        );
        $syntax = (string) config('reports.penerimaan_kayu_bulat_bulanan_per_supplier.call_syntax', 'exec');
        $customQuery = config(
            $isSubProcedure
                ? 'reports.penerimaan_kayu_bulat_bulanan_per_supplier.sub_query'
                : 'reports.penerimaan_kayu_bulat_bulanan_per_supplier.query'
        );

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException(
                $isSubProcedure
                    ? 'Stored procedure sub laporan penerimaan kayu bulat bulanan per supplier belum dikonfigurasi.'
                    : 'Stored procedure laporan penerimaan kayu bulat bulanan per supplier belum dikonfigurasi.',
            );
        }

        $connection = DB::connection($connectionName ?: null);
        $bindings = [$startDate, $endDate];
        $driver = $connection->getDriverName();

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan penerimaan kayu bulat bulanan per supplier dikonfigurasi untuk SQL Server. '
                . 'Set PENERIMAAN_KAYU_BULAT_BULANAN_PER_SUPPLIER_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException(
                    'PENERIMAAN_KAYU_BULAT_BULANAN_PER_SUPPLIER_REPORT_QUERY belum diisi. '
                    . 'Isi query manual jika menggunakan PENERIMAAN_KAYU_BULAT_BULANAN_PER_SUPPLIER_REPORT_CALL_SYNTAX=query '
                    . 'atau PENERIMAAN_KAYU_BULAT_BULANAN_PER_SUPPLIER_SUB_REPORT_QUERY untuk sub report.',
                );

            return $connection->select($query, $this->resolveBindings($query, $bindings));
        }

        if (!preg_match('/^[A-Za-z0-9_$.]+$/', $procedure)) {
            throw new RuntimeException('Nama stored procedure tidak valid.');
        }

        $sql = match ($syntax) {
            'exec' => "EXEC {$procedure} ?, ?",
            'call' => "CALL {$procedure}(?, ?)",
            default => $driver === 'sqlsrv'
                ? "EXEC {$procedure} ?, ?"
                : "CALL {$procedure}(?, ?)",
        };

        return $connection->select($sql, $bindings);
    }
}

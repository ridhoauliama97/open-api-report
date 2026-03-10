<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class PerbandinganKbMasukPeriode1Dan2KgReportService
{
    private const EPS = 0.0000001;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetch(
        string $period1StartDate,
        string $period1EndDate,
        string $period2StartDate,
        string $period2EndDate,
    ): array {
        $rows = $this->runProcedureQuery($period1StartDate, $period1EndDate, $period2StartDate, $period2EndDate);

        return array_map(static fn(object $row): array => (array) $row, $rows);
    }

    /**
     * @return array<string, mixed>
     */
    public function buildReportData(
        string $period1StartDate,
        string $period1EndDate,
        string $period2StartDate,
        string $period2EndDate,
    ): array {
        $rows = $this->fetch($period1StartDate, $period1EndDate, $period2StartDate, $period2EndDate);

        $supplierGroups = [];
        $grandTon1 = 0.0;
        $grandTon2 = 0.0;

        foreach ($rows as $row) {
            $supplier = trim((string) ($row['NmSupplier'] ?? ''));
            $phone = trim((string) ($row['NoTlp'] ?? ''));
            $grade = trim((string) ($row['NamaGrade'] ?? ''));
            $ton1 = $this->toFloat($row['BeratTon1'] ?? null) ?? 0.0;
            $ton2 = $this->toFloat($row['BeratTon2'] ?? null) ?? 0.0;

            $supplierKey = $supplier !== '' ? mb_strtolower($supplier) : 'tanpa-supplier';
            $phoneKey = $phone !== '' ? $phone : '-';
            $groupKey = "{$supplierKey}||{$phoneKey}";

            if (!isset($supplierGroups[$groupKey])) {
                $supplierGroups[$groupKey] = [
                    'supplier' => $supplier,
                    'phone' => $phone,
                    'rows' => [],
                    'totals' => [
                        'ton1' => 0.0,
                        'ton2' => 0.0,
                        'percent' => 0.0,
                    ],
                ];
            }

            if (!isset($supplierGroups[$groupKey]['rows'][$grade])) {
                $supplierGroups[$groupKey]['rows'][$grade] = [
                    'grade' => $grade,
                    'ton1' => 0.0,
                    'ton2' => 0.0,
                    'percent' => 0.0,
                ];
            }

            $supplierGroups[$groupKey]['rows'][$grade]['ton1'] += $ton1;
            $supplierGroups[$groupKey]['rows'][$grade]['ton2'] += $ton2;
            $supplierGroups[$groupKey]['totals']['ton1'] += $ton1;
            $supplierGroups[$groupKey]['totals']['ton2'] += $ton2;

            $grandTon1 += $ton1;
            $grandTon2 += $ton2;
        }

        // Finalize percent per grade + per supplier (keep stable ordering).
        foreach ($supplierGroups as $key => $group) {
            $rowsByGrade = is_array($group['rows'] ?? null) ? $group['rows'] : [];
            ksort($rowsByGrade, SORT_NATURAL | SORT_FLAG_CASE);

            $rowsList = [];
            foreach ($rowsByGrade as $line) {
                $t1 = (float) ($line['ton1'] ?? 0.0);
                $t2 = (float) ($line['ton2'] ?? 0.0);
                $line['percent'] = $this->calculatePercent($t1, $t2);
                $rowsList[] = $line;
            }

            $t1Total = (float) ($group['totals']['ton1'] ?? 0.0);
            $t2Total = (float) ($group['totals']['ton2'] ?? 0.0);
            $group['rows'] = $rowsList;
            $group['totals']['percent'] = $this->calculatePercent($t1Total, $t2Total);

            $supplierGroups[$key] = $group;
        }

        // Sort supplier groups by supplier name.
        $supplierGroups = array_values($supplierGroups);
        usort(
            $supplierGroups,
            static fn(array $a, array $b): int => strcasecmp((string) ($a['supplier'] ?? ''), (string) ($b['supplier'] ?? '')),
        );

        return [
            'rows' => $rows,
            'supplier_groups' => $supplierGroups,
            'summary' => [
                'total_rows' => count($rows),
                'columns' => array_keys($rows[0] ?? []),
                'total_suppliers' => count($supplierGroups),
                'grand_ton1' => $grandTon1,
                'grand_ton2' => $grandTon2,
                'grand_percent' => $this->calculatePercent($grandTon1, $grandTon2),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function healthCheck(
        string $period1StartDate,
        string $period1EndDate,
        string $period2StartDate,
        string $period2EndDate,
    ): array {
        $rows = $this->fetch($period1StartDate, $period1EndDate, $period2StartDate, $period2EndDate);
        $detectedColumns = array_keys($rows[0] ?? []);
        $expectedColumns = config('reports.perbandingan_kb_masuk_periode_1_dan_2_kg.expected_columns', []);
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
     * @return array<int, object>
     */
    private function runProcedureQuery(
        string $period1StartDate,
        string $period1EndDate,
        string $period2StartDate,
        string $period2EndDate,
    ): array {
        $configKey = 'reports.perbandingan_kb_masuk_periode_1_dan_2_kg';
        $connectionName = config("{$configKey}.database_connection");
        $procedure = (string) config("{$configKey}.stored_procedure", 'sp_LapPerbandinganKBMasukPeriode1dan2KG');
        $syntax = (string) config("{$configKey}.call_syntax", 'exec');
        $customQuery = config("{$configKey}.query");
        $parameterCount = (int) config("{$configKey}.parameter_count", 4);

        if ($parameterCount < 1 || $parameterCount > 4) {
            throw new RuntimeException('Jumlah parameter laporan perbandingan KB masuk (KG) harus antara 1 sampai 4.');
        }

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan perbandingan KB masuk periode 1 dan 2 (KG) belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan perbandingan KB masuk periode 1 dan 2 (KG) dikonfigurasi untuk SQL Server. '
                . 'Set PERBANDINGAN_KB_MASUK_PERIODE_1_DAN_2_KG_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        $bindings = [
            $period1StartDate,
            $period1EndDate,
            $period2StartDate,
            $period2EndDate,
        ];
        $bindings = array_slice($bindings, 0, $parameterCount);

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException(
                    'PERBANDINGAN_KB_MASUK_PERIODE_1_DAN_2_KG_REPORT_QUERY belum diisi. '
                    . 'Isi query manual jika menggunakan PERBANDINGAN_KB_MASUK_PERIODE_1_DAN_2_KG_REPORT_CALL_SYNTAX=query.',
                );

            $resolvedBindings = str_contains($query, '?') ? $bindings : [];

            return $connection->select($query, $resolvedBindings);
        }

        if (!preg_match('/^[A-Za-z0-9_$.]+$/', $procedure)) {
            throw new RuntimeException('Nama stored procedure tidak valid.');
        }

        $placeholders = implode(', ', array_fill(0, $parameterCount, '?'));

        $sql = match ($syntax) {
            'exec' => "EXEC {$procedure} {$placeholders}",
            'call' => "CALL {$procedure}({$placeholders})",
            default => $driver === 'sqlsrv'
                ? "EXEC {$procedure} {$placeholders}"
                : "CALL {$procedure}({$placeholders})",
        };

        return $connection->select($sql, $bindings);
    }

    private function calculatePercent(float $ton1, float $ton2): float
    {
        if (abs($ton1) < self::EPS && abs($ton2) < self::EPS) {
            return 0.0;
        }

        if (abs($ton1) < self::EPS) {
            // Keep the legacy behavior from the existing report.
            return 999.0;
        }

        return (($ton2 - $ton1) / $ton1) * 100.0;
    }

    private function toFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        if (!is_string($value)) {
            return null;
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }

        $trimmed = str_replace(',', '.', $trimmed);

        return is_numeric($trimmed) ? (float) $trimmed : null;
    }
}

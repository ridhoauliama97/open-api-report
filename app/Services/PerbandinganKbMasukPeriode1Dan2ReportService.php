<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class PerbandinganKbMasukPeriode1Dan2ReportService
{
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

        return [
            'rows' => $rows,
            'summary' => [
                'total_rows' => count($rows),
                'columns' => array_keys($rows[0] ?? []),
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
        $expectedColumns = config('reports.perbandingan_kb_masuk_periode_1_dan_2.expected_columns', []);
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
        $configKey = 'reports.perbandingan_kb_masuk_periode_1_dan_2';
        $connectionName = config("{$configKey}.database_connection");
        $procedure = (string) config("{$configKey}.stored_procedure", 'SP_LapPerbandinganKbMasukPeriode1dan2');
        $syntax = (string) config("{$configKey}.call_syntax", 'exec');
        $customQuery = config("{$configKey}.query");
        $parameterCount = (int) config("{$configKey}.parameter_count", 4);

        if ($parameterCount < 1 || $parameterCount > 4) {
            throw new RuntimeException('Jumlah parameter laporan perbandingan KB masuk harus antara 1 sampai 4.');
        }

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan perbandingan KB masuk periode 1 dan 2 belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan perbandingan KB masuk periode 1 dan 2 dikonfigurasi untuk SQL Server. '
                . 'Set PERBANDINGAN_KB_MASUK_PERIODE_1_DAN_2_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
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
                    'PERBANDINGAN_KB_MASUK_PERIODE_1_DAN_2_REPORT_QUERY belum diisi. '
                    . 'Isi query manual jika menggunakan PERBANDINGAN_KB_MASUK_PERIODE_1_DAN_2_REPORT_CALL_SYNTAX=query.',
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
}

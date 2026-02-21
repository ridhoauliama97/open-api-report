<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class KbKhususBangkangReportService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetch(string $startDate, string $endDate): array
    {
        $rows = $this->runProcedureQuery($startDate, $endDate);

        return array_map(static fn(object $row): array => (array) $row, $rows);
    }

    /**
     * @return array<string, mixed>
     */
    public function healthCheck(string $startDate, string $endDate): array
    {
        $rows = $this->fetch($startDate, $endDate);
        $detectedColumns = array_keys($rows[0] ?? []);
        $expectedColumns = config('reports.kb_khusus_bangkang.expected_columns', []);
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
    private function runProcedureQuery(string $startDate, string $endDate): array
    {
        $configKey = 'reports.kb_khusus_bangkang';
        $connectionName = config("{$configKey}.database_connection");
        $procedure = (string) config("{$configKey}.stored_procedure", 'SP_LapKBKhususBangkang');
        $syntax = (string) config("{$configKey}.call_syntax", 'exec');
        $customQuery = config("{$configKey}.query");
        $parameterCount = (int) config("{$configKey}.parameter_count", 0);

        if ($parameterCount < 0 || $parameterCount > 2) {
            throw new RuntimeException('Jumlah parameter laporan KB khusus bangkang harus antara 0 sampai 2.');
        }

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan KB khusus bangkang belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan KB khusus bangkang dikonfigurasi untuk SQL Server. '
                . 'Set KB_KHUSUS_BANGKANG_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        $bindings = array_slice([$startDate, $endDate], 0, $parameterCount);

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException(
                    'KB_KHUSUS_BANGKANG_REPORT_QUERY belum diisi. '
                    . 'Isi query manual jika menggunakan KB_KHUSUS_BANGKANG_REPORT_CALL_SYNTAX=query.',
                );

            $resolvedBindings = str_contains($query, '?') ? $bindings : [];

            return $connection->select($query, $resolvedBindings);
        }

        if (!preg_match('/^[A-Za-z0-9_$.]+$/', $procedure)) {
            throw new RuntimeException('Nama stored procedure tidak valid.');
        }

        $sql = match ($syntax) {
            'exec' => $parameterCount === 0
                ? "SET NOCOUNT ON; EXEC {$procedure}"
                : "SET NOCOUNT ON; EXEC {$procedure} " . implode(', ', array_fill(0, $parameterCount, '?')),
            'call' => $parameterCount === 0
                ? "CALL {$procedure}()"
                : "CALL {$procedure}(" . implode(', ', array_fill(0, $parameterCount, '?')) . ')',
            default => $driver === 'sqlsrv'
                ? ($parameterCount === 0
                    ? "SET NOCOUNT ON; EXEC {$procedure}"
                    : "SET NOCOUNT ON; EXEC {$procedure} " . implode(', ', array_fill(0, $parameterCount, '?')))
                : ($parameterCount === 0
                    ? "CALL {$procedure}()"
                    : "CALL {$procedure}(" . implode(', ', array_fill(0, $parameterCount, '?')) . ')'),
        };

        return $connection->select($sql, $bindings);
    }
}

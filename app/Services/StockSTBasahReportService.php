<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class StockSTBasahReportService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetch(string $endDate): array
    {
        $rows = $this->runProcedureQuery($endDate);

        return array_map(static fn($row): array => (array) $row, $rows);
    }

    /**
     * @return array<string, mixed>
     */
    public function healthCheck(string $endDate): array
    {
        $rows = $this->fetch($endDate);
        $detectedColumns = array_keys($rows[0] ?? []);
        $expectedColumns = config('reports.stock_st_basah.expected_columns', []);
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
    private function runProcedureQuery(string $endDate): array
    {
        $connectionName = config('reports.stock_st_basah.database_connection');
        $procedure = (string) config('reports.stock_st_basah.stored_procedure', 'SP_LapStockSTBasah');
        $syntax = (string) config('reports.stock_st_basah.call_syntax', 'exec');
        $customQuery = config('reports.stock_st_basah.query');

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan stock ST basah belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $bindings = [$endDate];
        $driver = $connection->getDriverName();

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan stock ST basah dikonfigurasi untuk SQL Server. '
                . 'Set STOCK_ST_BASAH_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException(
                    'STOCK_ST_BASAH_REPORT_QUERY belum diisi. '
                    . 'Isi query manual jika menggunakan STOCK_ST_BASAH_REPORT_CALL_SYNTAX=query.',
                );

            return $connection->select($query, str_contains($query, '?') ? $bindings : []);
        }

        if (!preg_match('/^[A-Za-z0-9_$.]+$/', $procedure)) {
            throw new RuntimeException('Nama stored procedure tidak valid.');
        }

        $sql = match ($syntax) {
            'exec' => "EXEC {$procedure} ?",
            'call' => "CALL {$procedure}(?)",
            default => $driver === 'sqlsrv' ? "EXEC {$procedure} ?" : "CALL {$procedure}(?)",
        };

        return $connection->select($sql, $bindings);
    }
}

<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class SalesReportService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetch(string $startDate, string $endDate): array
    {
        $connectionName = config('reports.sales.database_connection');
        $procedure = (string) config('reports.sales.stored_procedure');
        $syntax = (string) config('reports.sales.call_syntax', 'auto');
        $customQuery = config('reports.sales.query');

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan penjualan belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $bindings = [$startDate, $endDate];

        if ($syntax === 'query' && is_string($customQuery) && $customQuery !== '') {
            $rows = $connection->select($customQuery, $bindings);

            return $this->normalizeRows($rows);
        }

        if ($connection->getDriverName() === 'sqlite') {
            throw new RuntimeException(
                'SQLite tidak mendukung stored procedure. Gunakan DB lain (mysql/sqlsrv) '
                . 'atau set SALES_REPORT_CALL_SYNTAX=query dan SALES_REPORT_QUERY di file .env.',
            );
        }

        if (!preg_match('/^[A-Za-z0-9_$.]+$/', $procedure)) {
            throw new RuntimeException('Nama stored procedure tidak valid.');
        }

        $sql = match ($syntax) {
            'exec' => "EXEC {$procedure} ?, ?",
            'call' => "CALL {$procedure}(?, ?)",
            default => $connection->getDriverName() === 'sqlsrv'
            ? "EXEC {$procedure} ?, ?"
            : "CALL {$procedure}(?, ?)",
        };

        $rows = $connection->select($sql, $bindings);

        return $this->normalizeRows($rows);
    }

    /**
     * @param array<int, object> $rows
     * @return array<int, array<string, mixed>>
     */
    private function normalizeRows(array $rows): array
    {
        return array_map(
            static fn(object $row): array => get_object_vars($row),
            $rows,
        );
    }
}

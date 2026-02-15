<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class MutasiCrossCutReportService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetch(string $startDate, string $endDate): array
    {
        $connectionName = config('reports.mutasi_cross_cut.database_connection');
        $procedure = (string) config('reports.mutasi_cross_cut.stored_procedure');
        $syntax = (string) config('reports.mutasi_cross_cut.call_syntax', 'auto');
        $customQuery = config('reports.mutasi_cross_cut.query');

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan mutasi cross cut belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $bindings = [$startDate, $endDate];

        $driver = $connection->getDriverName();

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : (string) config(
                    'reports.mutasi_cross_cut.sqlite_fallback_query',
                    'SELECT * FROM mutasi_cross_cut ORDER BY jenis ASC',
                );

            $rows = $connection->select($query, $this->resolveBindings($query, $bindings));

            return $this->normalizeRows($rows);
        }

        if ($driver === 'sqlite') {
            $fallbackQuery = (string) config(
                'reports.mutasi_cross_cut.sqlite_fallback_query',
                'SELECT * FROM mutasi_cross_cut ORDER BY jenis ASC',
            );

            $rows = $connection->select($fallbackQuery, $this->resolveBindings($fallbackQuery, $bindings));

            return $this->normalizeRows($rows);
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

        $rows = $connection->select($sql, $bindings);

        return $this->normalizeRows($rows);
    }

    /**
     * @param array<int, object> $rows
     * @return array<int, array<string, mixed>>
     */
    private function normalizeRows(array $rows): array
    {
        return array_map(function ($row) {
            return (array) $row;
        }, $rows);
    }

    /**
     * @param array<int, string> $bindings
     * @return array<int, string>
     */
    private function resolveBindings(string $query, array $bindings): array
    {
        return str_contains($query, '?') ? $bindings : [];
    }
}

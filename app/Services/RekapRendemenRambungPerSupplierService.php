<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class RekapRendemenRambungPerSupplierService
{
    /**
     * @var array<int, string>
     */
    private const DEFAULT_SUB_COLUMNS = [
        'NmSupplier',
        'SLP',
        'Bansaw',
    ];

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
        return $this->normalizeRows($this->runProcedureQuery($startDate, $endDate, true));
    }

    /**
     * @return array<string, mixed>
     */
    public function healthCheck(string $startDate, string $endDate): array
    {
        $rows = $this->normalizeRows($this->runProcedureQuery($startDate, $endDate, false));
        $detectedColumns = array_keys($rows[0] ?? []);
        $expectedColumns = config('reports.rekap_rendemen_rambung_per_supplier.expected_columns', []);
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
     * @param array<int, object> $rows
     * @return array<int, array<string, mixed>>
     */
    private function normalizeRows(array $rows): array
    {
        // Ubah setiap baris object dari database menjadi array asosiatif.
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

    /**
     * @return array<int, object>
     */
    private function runProcedureQuery(string $startDate, string $endDate, bool $isSubProcedure): array
    {
        $connectionName = config('reports.rekap_rendemen_rambung_per_supplier.database_connection');
        $procedure = (string) config(
            $isSubProcedure
            ? 'reports.rekap_rendemen_rambung_per_supplier.sub_stored_procedure'
            : 'reports.rekap_rendemen_rambung_per_supplier.stored_procedure'
        );
        $syntax = (string) config('reports.rekap_rendemen_rambung_per_supplier.call_syntax', 'exec');
        $customQuery = config(
            $isSubProcedure
            ? 'reports.rekap_rendemen_rambung_per_supplier.sub_query'
            : 'reports.rekap_rendemen_rambung_per_supplier.query'
        );

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException(
                $isSubProcedure
                ? 'Stored procedure sub laporan rekap rendemen rambung per supplier belum dikonfigurasi.'
                : 'Stored procedure laporan rekap rendemen rambung per supplier belum dikonfigurasi.',
            );
        }

        $connection = DB::connection($connectionName ?: null);
        $bindings = [$startDate, $endDate];
        $driver = $connection->getDriverName();

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan rekap rendemen rambung per supplier dikonfigurasi untuk SQL Server. '
                . 'Set REKAP_RENDEMEN_RAMBUNG_PER_SUPPLIER_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException(
                    'REKAP_RENDEMEN_RAMBUNG_PER_SUPPLIER_REPORT_QUERY belum diisi. '
                    . 'Isi query manual jika menggunakan REKAP_RENDEMEN_RAMBUNG_PER_SUPPLIER_REPORT_CALL_SYNTAX=query '
                    . 'atau REKAP_RENDEMEN_RAMBUNG_PER_SUPPLIER_SUB_REPORT_QUERY untuk sub report.',
                );

            return $connection->select($query, $this->resolveBindings($query, $bindings));
        }

        if (!preg_match('/^[A-Za-z0-9_$.]+$/', $procedure)) {
            throw new RuntimeException('Nama stored procedure tidak valid.');
        }

        $sql = match ($syntax) {
            'exec' => "SET NOCOUNT ON; EXEC {$procedure} ?, ?",
            'call' => "CALL {$procedure}(?, ?)",
            default => $driver === 'sqlsrv'
            ? "SET NOCOUNT ON; EXEC {$procedure} ?, ?"
            : "CALL {$procedure}(?, ?)",
        };

        return $connection->select($sql, $bindings);
    }
}

<?php

namespace App\Services\PPS;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class RekapProduksiGilinganReportService
{
    public function fetch(string $startDate, string $endDate): array
    {
        $rows = $this->runProcedureQuery($startDate, $endDate);
        return array_map(static fn($row): array => (array) $row, $rows);
    }

    public function healthCheck(string $startDate, string $endDate): array
    {
        $rows = $this->fetch($startDate, $endDate);
        $detectedColumns = array_keys($rows[0] ?? []);
        $expectedColumns = config('reports.pps_rekap_produksi_gilingan.expected_columns', []);
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

    private function resolveBindings(string $query, array $bindings): array
    {
        return str_contains($query, '?') ? $bindings : [];
    }

    private function runProcedureQuery(string $startDate, string $endDate): array
    {
        $configPath = 'reports.pps_rekap_produksi_gilingan';
        $connectionName = config("{$configPath}.database_connection");
        $procedure = (string) config("{$configPath}.stored_procedure");
        $syntax = (string) config("{$configPath}.call_syntax", 'exec');
        $customQuery = config("{$configPath}.query");
        $parameterCount = max(0, (int) config("{$configPath}.parameter_count", 2));
        $singleParameterName = (string) config("{$configPath}.single_parameter_name", 'TglAkhir');

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan PPS Rekap Produksi Gilingan belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();
        $bindings = $this->buildBindings($parameterCount, $singleParameterName, $startDate, $endDate);

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan PPS Rekap Produksi Gilingan dikonfigurasi untuk SQL Server. '
                . 'Set PPS_REKAP_PRODUKSI_GILINGAN_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException(
                    'PPS_REKAP_PRODUKSI_GILINGAN_REPORT_QUERY belum diisi. '
                    . 'Isi query manual jika menggunakan PPS_REKAP_PRODUKSI_GILINGAN_REPORT_CALL_SYNTAX=query.',
                );

            return $connection->select($query, $this->resolveBindings($query, $bindings));
        }

        if (!preg_match('/^[A-Za-z0-9_$.]+$/', $procedure)) {
            throw new RuntimeException('Nama stored procedure tidak valid.');
        }

        $sql = $this->buildProcedureSql($driver, $syntax, $procedure, count($bindings));
        return $connection->select($sql, $bindings);
    }

    private function buildBindings(int $parameterCount, string $singleParameterName, string $startDate, string $endDate): array
    {
        return match ($parameterCount) {
            0 => [],
            1 => [strtolower($singleParameterName) === 'tglawal' ? $startDate : $endDate],
            default => [$startDate, $endDate],
        };
    }

    private function buildProcedureSql(string $driver, string $syntax, string $procedure, int $bindingCount): string
    {
        if ($syntax === 'call') {
            $placeholders = $bindingCount > 0 ? implode(', ', array_fill(0, $bindingCount, '?')) : '';
            return "CALL {$procedure}({$placeholders})";
        }
        if ($syntax === 'exec' || $driver === 'sqlsrv') {
            $placeholders = $bindingCount > 0 ? ' ' . implode(', ', array_fill(0, $bindingCount, '?')) : '';
            return "SET NOCOUNT ON; EXEC {$procedure}{$placeholders}";
        }
        $placeholders = $bindingCount > 0 ? implode(', ', array_fill(0, $bindingCount, '?')) : '';
        return "CALL {$procedure}({$placeholders})";
    }
}

<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class SupplierIntelReportService
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
        $expectedColumns = config('reports.supplier_intel.expected_columns', []);
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
        $configKey = 'reports.supplier_intel';
        $connectionName = config("{$configKey}.database_connection");
        $procedure = (string) config("{$configKey}.stored_procedure", 'SP_LapSupplierIntel');
        $syntax = (string) config("{$configKey}.call_syntax", 'exec');
        $customQuery = config("{$configKey}.query");
        $parameterCount = (int) config("{$configKey}.parameter_count", 2);
        $singleParameterName = trim((string) config("{$configKey}.single_parameter_name", 'TglAkhir'));

        if ($parameterCount < 0 || $parameterCount > 2) {
            throw new RuntimeException('Jumlah parameter laporan Supplier Intel harus antara 0 sampai 2.');
        }

        if ($singleParameterName === '' || !preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $singleParameterName)) {
            throw new RuntimeException('Konfigurasi single_parameter_name untuk laporan Supplier Intel tidak valid.');
        }

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan Supplier Intel belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan Supplier Intel dikonfigurasi untuk SQL Server. '
                . 'Set SUPPLIER_INTEL_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        $singleParameterUsesStartDate = in_array(strtolower($singleParameterName), ['tglawal', 'start_date'], true);
        $bindings = match ($parameterCount) {
            0 => [],
            1 => [$singleParameterUsesStartDate ? $startDate : $endDate],
            default => [$startDate, $endDate],
        };

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException(
                    'SUPPLIER_INTEL_REPORT_QUERY belum diisi. '
                    . 'Isi query manual jika menggunakan SUPPLIER_INTEL_REPORT_CALL_SYNTAX=query.',
                );

            $resolvedBindings = str_contains($query, '?') ? $bindings : [];

            return $connection->select($query, $resolvedBindings);
        }

        if (!preg_match('/^[A-Za-z0-9_$.]+$/', $procedure)) {
            throw new RuntimeException('Nama stored procedure tidak valid.');
        }

        $execSql = match ($parameterCount) {
            0 => "SET NOCOUNT ON; EXEC {$procedure}",
            1 => "SET NOCOUNT ON; EXEC {$procedure} @{$singleParameterName} = ?",
            default => "SET NOCOUNT ON; EXEC {$procedure} @TglAwal = ?, @TglAkhir = ?",
        };

        $callSql = $parameterCount === 0
            ? "CALL {$procedure}()"
            : "CALL {$procedure}(" . implode(', ', array_fill(0, $parameterCount, '?')) . ')';

        $sql = match ($syntax) {
            'exec' => $execSql,
            'call' => $callSql,
            default => $driver === 'sqlsrv' ? $execSql : $callSql,
        };

        return $connection->select($sql, $bindings);
    }
}



<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class TimelineKayuBulatHarianReportService
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
        $expectedColumns = config('reports.timeline_kayu_bulat_harian.expected_columns', []);
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
        $configKey = 'reports.timeline_kayu_bulat_harian';
        $connectionName = config("{$configKey}.database_connection");
        $procedure = (string) config("{$configKey}.stored_procedure", 'SP_LapTimelineKBHarian');
        $syntax = (string) config("{$configKey}.call_syntax", 'exec');
        $customQuery = config("{$configKey}.query");
        $parameterCount = (int) config("{$configKey}.parameter_count", 2);
        $singleParameterName = (string) config("{$configKey}.single_parameter_name", 'EndDate');
        $singleParameterName = ltrim($singleParameterName, '@');

        if ($parameterCount < 0 || $parameterCount > 2) {
            throw new RuntimeException('Jumlah parameter laporan timeline kayu bulat harian harus antara 0 sampai 2.');
        }
        if ($singleParameterName !== '' && preg_match('/^[A-Za-z0-9_]+$/', $singleParameterName) !== 1) {
            throw new RuntimeException('Nama parameter tunggal laporan timeline kayu bulat harian tidak valid.');
        }

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan timeline kayu bulat harian belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan timeline kayu bulat harian dikonfigurasi untuk SQL Server. '
                . 'Set TIMELINE_KAYU_BULAT_HARIAN_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        $bindings = match ($parameterCount) {
            0 => [],
            1 => [$endDate],
            default => [$startDate, $endDate],
        };

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException(
                    'TIMELINE_KAYU_BULAT_HARIAN_REPORT_QUERY belum diisi. '
                    . 'Isi query manual jika menggunakan TIMELINE_KAYU_BULAT_HARIAN_REPORT_CALL_SYNTAX=query.',
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
                : ($parameterCount === 1
                    ? "SET NOCOUNT ON; EXEC {$procedure} @{$singleParameterName} = ?"
                    : "SET NOCOUNT ON; EXEC {$procedure} @StartDate = ?, @EndDate = ?"),
            'call' => $parameterCount === 0
                ? "CALL {$procedure}()"
                : ($parameterCount === 1
                    ? "CALL {$procedure}(?)"
                    : "CALL {$procedure}(?, ?)"),
            default => $driver === 'sqlsrv'
                ? ($parameterCount === 0
                    ? "SET NOCOUNT ON; EXEC {$procedure}"
                    : ($parameterCount === 1
                        ? "SET NOCOUNT ON; EXEC {$procedure} @{$singleParameterName} = ?"
                        : "SET NOCOUNT ON; EXEC {$procedure} @StartDate = ?, @EndDate = ?"))
                : ($parameterCount === 0
                    ? "CALL {$procedure}()"
                    : ($parameterCount === 1
                        ? "CALL {$procedure}(?)"
                        : "CALL {$procedure}(?, ?)")),
        };

        return $connection->select($sql, $bindings);
    }
}

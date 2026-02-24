<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class LembarTallyHasilSawmillReportService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetch(string $noProduksi): array
    {
        $rows = $this->runProcedureQuery($noProduksi);

        return array_map(static fn(object $row): array => (array) $row, $rows);
    }

    /**
     * @return array<string, mixed>
     */
    public function healthCheck(string $noProduksi): array
    {
        $rows = $this->fetch($noProduksi);
        $detectedColumns = array_keys($rows[0] ?? []);
        $expectedColumns = config('reports.lembar_tally_hasil_sawmill.expected_columns', []);
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
    private function runProcedureQuery(string $noProduksi): array
    {
        $configKey = 'reports.lembar_tally_hasil_sawmill';
        $connectionName = config("{$configKey}.database_connection");
        $procedure = (string) config("{$configKey}.stored_procedure", 'SPWps_LapUpahSawmill');
        $syntax = (string) config("{$configKey}.call_syntax", 'exec');
        $customQuery = config("{$configKey}.query");
        $parameterCount = (int) config("{$configKey}.parameter_count", 1);

        if ($parameterCount < 1 || $parameterCount > 1) {
            throw new RuntimeException('Jumlah parameter laporan lembar tally hasil sawmill harus 1.');
        }

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan lembar tally hasil sawmill belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();
        $bindings = [$noProduksi];

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan lembar tally hasil sawmill dikonfigurasi untuk SQL Server. '
                . 'Set LEMBAR_TALLY_HASIL_SAWMILL_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException(
                    'LEMBAR_TALLY_HASIL_SAWMILL_REPORT_QUERY belum diisi. '
                    . 'Isi query manual jika menggunakan LEMBAR_TALLY_HASIL_SAWMILL_REPORT_CALL_SYNTAX=query.',
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


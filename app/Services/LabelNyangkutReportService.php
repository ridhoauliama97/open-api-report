<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class LabelNyangkutReportService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetch(): array
    {
        $rows = $this->runProcedureQuery();

        return $this->normalizeRows($rows);
    }

    /**
     * @return array<string, mixed>
     */
    public function healthCheck(): array
    {
        $rows = $this->fetch();
        $detectedColumns = array_keys($rows[0] ?? []);
        $expectedColumns = config('reports.label_nyangkut.expected_columns', []);
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
        return array_map(static fn($row): array => (array) $row, $rows);
    }

    /**
     * @return array<int, object>
     */
    private function runProcedureQuery(): array
    {
        $connectionName = config('reports.label_nyangkut.database_connection');
        $procedure = (string) config('reports.label_nyangkut.stored_procedure');
        $syntax = (string) config('reports.label_nyangkut.call_syntax', 'exec');
        $customQuery = config('reports.label_nyangkut.query');

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan label nyangkut belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan label nyangkut dikonfigurasi untuk SQL Server. '
                . 'Set LABEL_NYANGKUT_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException(
                    'LABEL_NYANGKUT_REPORT_QUERY belum diisi. '
                    . 'Isi query manual jika menggunakan LABEL_NYANGKUT_REPORT_CALL_SYNTAX=query.',
                );

            return $connection->select($query);
        }

        if (!preg_match('/^[A-Za-z0-9_$.]+$/', $procedure)) {
            throw new RuntimeException('Nama stored procedure tidak valid.');
        }

        $sql = match ($syntax) {
            'exec' => "EXEC {$procedure}",
            'call' => "CALL {$procedure}()",
            default => $driver === 'sqlsrv'
                ? "EXEC {$procedure}"
                : "CALL {$procedure}()",
        };

        return $connection->select($sql);
    }
}

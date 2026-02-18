<?php

namespace App\Services;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class BahanTerpakaiReportService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetch(string $date): array
    {
        $rows = $this->runProcedureQuery($date, false);

        return $this->normalizeRows($rows);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetchSubReport(string $date): array
    {
        $rows = $this->runProcedureQuery($date, true);

        return $this->normalizeRows($rows);
    }

    /**
     * @return array<string, mixed>
     */
    public function healthCheck(string $date): array
    {
        $rows = $this->fetch($date);
        $subRows = $this->fetchSubReport($date);
        $detectedColumns = array_keys($rows[0] ?? []);
        $detectedSubColumns = array_keys($subRows[0] ?? []);
        $expectedColumns = config('reports.bahan_terpakai.expected_columns', []);
        $expectedSubColumns = config('reports.bahan_terpakai.expected_sub_columns', []);
        $expectedColumns = is_array($expectedColumns) ? array_values($expectedColumns) : [];
        $expectedSubColumns = is_array($expectedSubColumns) ? array_values($expectedSubColumns) : [];
        $missingColumns = array_values(array_diff($expectedColumns, $detectedColumns));
        $extraColumns = array_values(array_diff($detectedColumns, $expectedColumns));
        $missingSubColumns = array_values(array_diff($expectedSubColumns, $detectedSubColumns));
        $extraSubColumns = array_values(array_diff($detectedSubColumns, $expectedSubColumns));

        return [
            'is_healthy' => empty($missingColumns) && empty($missingSubColumns),
            'expected_columns' => $expectedColumns,
            'detected_columns' => $detectedColumns,
            'missing_columns' => $missingColumns,
            'extra_columns' => $extraColumns,
            'row_count' => count($rows),
            'expected_sub_columns' => $expectedSubColumns,
            'detected_sub_columns' => $detectedSubColumns,
            'missing_sub_columns' => $missingSubColumns,
            'extra_sub_columns' => $extraSubColumns,
            'sub_row_count' => count($subRows),
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
    private function runProcedureQuery(string $date, bool $isSubProcedure): array
    {
        $connectionName = config('reports.bahan_terpakai.database_connection');
        $procedure = (string) config(
            $isSubProcedure
                ? 'reports.bahan_terpakai.sub_stored_procedure'
                : 'reports.bahan_terpakai.stored_procedure'
        );
        $syntax = (string) config('reports.bahan_terpakai.call_syntax', 'exec');
        $customQuery = config(
            $isSubProcedure
                ? 'reports.bahan_terpakai.sub_query'
                : 'reports.bahan_terpakai.query'
        );

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException(
                $isSubProcedure
                    ? 'Stored procedure sub laporan bahan terpakai belum dikonfigurasi.'
                    : 'Stored procedure laporan bahan terpakai belum dikonfigurasi.'
            );
        }

        $connection = DB::connection($connectionName ?: null);
        $bindings = [$date];
        $driver = $connection->getDriverName();

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan bahan terpakai dikonfigurasi untuk SQL Server. '
                . 'Set BAHAN_TERPAKAI_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException(
                    'BAHAN_TERPAKAI_REPORT_QUERY belum diisi. '
                    . 'Isi query manual jika menggunakan BAHAN_TERPAKAI_REPORT_CALL_SYNTAX=query.',
                );

            return $connection->select($query, $this->resolveBindings($query, $bindings));
        }

        if (!preg_match('/^[A-Za-z0-9_$.]+$/', $procedure)) {
            throw new RuntimeException('Nama stored procedure tidak valid.');
        }

        $sqlByBindingCount = match ($syntax) {
            'exec' => [
                1 => "EXEC {$procedure} ?",
                0 => "EXEC {$procedure}",
            ],
            'call' => [
                1 => "CALL {$procedure}(?)",
                0 => "CALL {$procedure}()",
            ],
            default => $driver === 'sqlsrv'
                ? [
                    1 => "EXEC {$procedure} ?",
                    0 => "EXEC {$procedure}",
                ]
                : [
                    1 => "CALL {$procedure}(?)",
                    0 => "CALL {$procedure}()",
                ],
        };

        $attempts = [
            [$sqlByBindingCount[1], [$date]],
            [$sqlByBindingCount[0], []],
        ];

        $lastException = null;

        foreach ($attempts as [$sql, $attemptBindings]) {
            try {
                return $connection->select($sql, $attemptBindings);
            } catch (QueryException $exception) {
                $lastException = $exception;
                if (!$this->isTooManyArgumentsError($exception)) {
                    throw $exception;
                }
            }
        }

        if ($lastException !== null) {
            throw $lastException;
        }

        throw new RuntimeException('Gagal menjalankan stored procedure laporan bahan terpakai.');
    }

    private function isTooManyArgumentsError(QueryException $exception): bool
    {
        $message = strtolower($exception->getMessage());

        return str_contains($message, 'too many arguments specified');
    }
}

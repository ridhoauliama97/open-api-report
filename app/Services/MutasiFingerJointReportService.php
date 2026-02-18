<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class MutasiFingerJointReportService
{
    /**
     * @var array<int, string>
     */
    private const DEFAULT_SUB_COLUMNS = [
        'Jenis',
        'CCAkhir',
        'S4S',
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
        $rows = $this->normalizeRows($this->runProcedureQuery($startDate, $endDate, true));
        $expectedColumns = config('reports.mutasi_finger_joint.expected_sub_columns', self::DEFAULT_SUB_COLUMNS);
        $expectedColumns = is_array($expectedColumns) && $expectedColumns !== []
            ? array_values($expectedColumns)
            : self::DEFAULT_SUB_COLUMNS;

        if ($rows === []) {
            return [];
        }

        $sample = $rows[0];
        $missingColumns = array_values(array_filter(
            $expectedColumns,
            static fn(string $column): bool => !array_key_exists($column, $sample),
        ));

        if ($missingColumns !== []) {
            throw new RuntimeException(
                'Output sub report finger joint tidak sesuai. Kolom tidak ditemukan: '
                . implode(', ', $missingColumns),
            );
        }

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    public function healthCheck(string $startDate, string $endDate): array
    {
        $rows = $this->normalizeRows($this->runProcedureQuery($startDate, $endDate, false));
        $detectedColumns = array_keys($rows[0] ?? []);
        $expectedColumns = config('reports.mutasi_finger_joint.expected_columns', []);
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
        $connectionName = config('reports.mutasi_finger_joint.database_connection');
        $procedure = (string) config(
            $isSubProcedure
                ? 'reports.mutasi_finger_joint.sub_stored_procedure'
                : 'reports.mutasi_finger_joint.stored_procedure'
        );
        $syntax = (string) config('reports.mutasi_finger_joint.call_syntax', 'exec');
        $customQuery = config(
            $isSubProcedure
                ? 'reports.mutasi_finger_joint.sub_query'
                : 'reports.mutasi_finger_joint.query'
        );

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException(
                $isSubProcedure
                    ? 'Stored procedure sub laporan mutasi finger joint belum dikonfigurasi.'
                    : 'Stored procedure laporan mutasi finger joint belum dikonfigurasi.',
            );
        }

        $connection = DB::connection($connectionName ?: null);
        $bindings = [$startDate, $endDate];
        $driver = $connection->getDriverName();

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan mutasi finger joint dikonfigurasi untuk SQL Server. '
                . 'Set MUTASI_FINGER_JOINT_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException(
                    'MUTASI_FINGER_JOINT_REPORT_QUERY belum diisi. '
                    . 'Isi query manual jika menggunakan MUTASI_FINGER_JOINT_REPORT_CALL_SYNTAX=query '
                    . 'atau MUTASI_FINGER_JOINT_SUB_REPORT_QUERY untuk sub report.',
                );

            return $connection->select($query, $this->resolveBindings($query, $bindings));
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

        return $connection->select($sql, $bindings);
    }
}

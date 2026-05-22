<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class RangkumanJlhLabelInputReportService
{
    private const PROCESS_ORDER = [
        ['Proses S4S', 'S4S'],
        ['Proses Finger Joint', 'FJ'],
        ['Proses Moulding', 'MLD'],
        ['Proses Laminating', 'LMT'],
        ['Proses Cross Cut Akhir', 'CCAKHIR'],
        ['Proses Sanding', 'Sanding'],
        ['Proses Packing', 'PACK'],
    ];

    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetch(string $startDate, string $endDate): array
    {
        $rows = $this->runProcedureQuery($startDate, $endDate);

        return $this->sortRowsByProcess($this->normalizeRows($rows));
    }

    /**
     * @return array<string, mixed>
     */
    public function healthCheck(string $startDate, string $endDate): array
    {
        $rows = $this->fetch($startDate, $endDate);
        $detectedColumns = array_keys($rows[0] ?? []);
        $expectedColumns = config('reports.rangkuman_jlh_label_input.expected_columns', []);
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
     * @param  array<int, object>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function normalizeRows(array $rows): array
    {
        return array_map(static fn ($row): array => (array) $row, $rows);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function sortRowsByProcess(array $rows): array
    {
        $groupColumn = $this->findGroupColumn(array_keys($rows[0] ?? []));

        if ($groupColumn === null) {
            return $rows;
        }

        uasort($rows, function (array $left, array $right) use ($groupColumn): int {
            return $this->processRank((string) ($left[$groupColumn] ?? ''))
                <=> $this->processRank((string) ($right[$groupColumn] ?? ''));
        });

        return array_values($rows);
    }

    /**
     * @param  array<int, string>  $columns
     */
    private function findGroupColumn(array $columns): ?string
    {
        foreach ($columns as $column) {
            $normalized = strtolower(str_replace([' ', '_'], '', trim($column)));

            if (in_array($normalized, ['namagroup', 'group', 'namaproses', 'proses'], true)) {
                return $column;
            }
        }

        return null;
    }

    private function processRank(string $process): int
    {
        $process = $this->normalizeProcessName($process);

        foreach (self::PROCESS_ORDER as $index => $processNames) {
            foreach ($processNames as $processName) {
                $alias = $this->normalizeProcessName($processName);

                if ($process === $alias || str_contains($process, $alias)) {
                    return $index;
                }
            }
        }

        return 999;
    }

    private function normalizeProcessName(string $name): string
    {
        $name = strtolower(trim($name));
        $name = preg_replace('/^proses\s+/i', '', $name) ?? $name;

        return preg_replace('/[^a-z0-9]+/', '', $name) ?? $name;
    }

    /**
     * @param  array<int, string>  $bindings
     * @return array<int, string>
     */
    private function resolveBindings(string $query, array $bindings): array
    {
        return str_contains($query, '?') ? $bindings : [];
    }

    /**
     * @return array<int, object>
     */
    private function runProcedureQuery(string $startDate, string $endDate): array
    {
        $connectionName = config('reports.rangkuman_jlh_label_input.database_connection');
        $procedure = (string) config('reports.rangkuman_jlh_label_input.stored_procedure');
        $syntax = (string) config('reports.rangkuman_jlh_label_input.call_syntax', 'exec');
        $customQuery = config('reports.rangkuman_jlh_label_input.query');

        if ($procedure === '' && ! is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan rangkuman jumlah label input belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $bindings = [$startDate, $endDate];
        $driver = $connection->getDriverName();

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan rangkuman jumlah label input dikonfigurasi untuk SQL Server. '
                .'Set RANGKUMAN_LABEL_INPUT_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException(
                    'RANGKUMAN_LABEL_INPUT_REPORT_QUERY belum diisi. '
                    .'Isi query manual jika menggunakan RANGKUMAN_LABEL_INPUT_REPORT_CALL_SYNTAX=query.',
                );

            return $connection->select($query, $this->resolveBindings($query, $bindings));
        }

        if (! preg_match('/^[A-Za-z0-9_$.]+$/', $procedure)) {
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

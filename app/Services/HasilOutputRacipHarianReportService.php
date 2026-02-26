<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class HasilOutputRacipHarianReportService
{
    private const CONFIG_KEY = 'reports.hasil_output_racip_harian';

    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetch(string $endDate): array
    {
        $rows = $this->runProcedureQuery($endDate);

        return $this->normalizeRows($rows);
    }

    /**
     * @return array<string, mixed>
     */
    public function buildReportData(string $endDate): array
    {
        $rows = $this->fetch($endDate);
        $columns = array_keys($rows[0] ?? []);
        $numericColumns = $this->detectNumericColumns($rows, $columns);
        $totals = $this->calculateTotals($rows, $numericColumns);

        return [
            'rows' => $rows,
            'columns' => $columns,
            'numeric_columns' => $numericColumns,
            'totals' => $totals,
            'summary' => [
                'total_rows' => count($rows),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function healthCheck(string $endDate): array
    {
        $rows = $this->fetch($endDate);
        $detectedColumns = array_keys($rows[0] ?? []);

        $expectedColumns = config(self::CONFIG_KEY . '.expected_columns', []);
        $expectedColumns = is_array($expectedColumns) ? array_values($expectedColumns) : [];

        $missingColumns = array_values(array_diff($expectedColumns, $detectedColumns));
        $extraColumns = array_values(array_diff($detectedColumns, $expectedColumns));

        return [
            'is_healthy' => $missingColumns === [],
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
        return array_map(static fn ($row): array => (array) $row, $rows);
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @param array<int, string> $columns
     * @return array<string, bool>
     */
    private function detectNumericColumns(array $rows, array $columns): array
    {
        $result = [];

        foreach ($columns as $column) {
            $result[$column] = $this->isNumericColumn($rows, $column);
        }

        return $result;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @param array<string, bool> $numericColumns
     * @return array<string, float>
     */
    private function calculateTotals(array $rows, array $numericColumns): array
    {
        $totals = [];

        foreach ($numericColumns as $column => $isNumeric) {
            if (!$isNumeric) {
                continue;
            }

            $totals[$column] = 0.0;
            foreach ($rows as $row) {
                $totals[$column] += $this->toFloat($row[$column] ?? null);
            }
        }

        return $totals;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    private function isNumericColumn(array $rows, string $column): bool
    {
        foreach ($rows as $row) {
            $value = $row[$column] ?? null;
            if ($value === null || $value === '') {
                continue;
            }

            return is_numeric($value) || $this->toFloat($value) !== 0.0;
        }

        return false;
    }

    private function toFloat(mixed $value): float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        if (!is_string($value)) {
            return 0.0;
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            return 0.0;
        }

        if (preg_match('/^-?\d{1,3}(\.\d{3})*(,\d+)?$/', $trimmed) === 1) {
            $trimmed = str_replace('.', '', $trimmed);
            $trimmed = str_replace(',', '.', $trimmed);
        } elseif (preg_match('/^-?\d{1,3}(,\d{3})*(\.\d+)?$/', $trimmed) === 1) {
            $trimmed = str_replace(',', '', $trimmed);
        } else {
            $trimmed = str_replace(',', '.', $trimmed);
        }

        return is_numeric($trimmed) ? (float) $trimmed : 0.0;
    }

    /**
     * @param array<int, mixed> $bindings
     * @return array<int, mixed>
     */
    private function resolveBindings(string $query, array $bindings): array
    {
        return str_contains($query, '?') ? $bindings : [];
    }

    /**
     * @return array<int, object>
     */
    private function runProcedureQuery(string $endDate): array
    {
        $connectionName = config(self::CONFIG_KEY . '.database_connection');
        $procedure = (string) config(self::CONFIG_KEY . '.stored_procedure');
        $syntax = (string) config(self::CONFIG_KEY . '.call_syntax', 'exec');
        $customQuery = config(self::CONFIG_KEY . '.query');
        $parameterCount = (int) config(self::CONFIG_KEY . '.parameter_count', 1);
        $parameterCount = max(0, min(1, $parameterCount));

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan hasil output racip harian belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();
        $bindings = array_slice([$endDate], 0, $parameterCount);

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan hasil output racip harian dikonfigurasi untuk SQL Server. '
                . 'Set HASIL_OUTPUT_RACIP_HARIAN_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException(
                    'HASIL_OUTPUT_RACIP_HARIAN_REPORT_QUERY belum diisi. '
                    . 'Isi query manual jika menggunakan HASIL_OUTPUT_RACIP_HARIAN_REPORT_CALL_SYNTAX=query.',
                );

            return $connection->select($query, $this->resolveBindings($query, $bindings));
        }

        if (!preg_match('/^[A-Za-z0-9_$.]+$/', $procedure)) {
            throw new RuntimeException('Nama stored procedure tidak valid.');
        }

        $placeholders = $parameterCount === 0 ? '' : '?';

        $sql = match ($syntax) {
            'exec' => $parameterCount === 0
                ? "SET NOCOUNT ON; EXEC {$procedure}"
                : "SET NOCOUNT ON; EXEC {$procedure} {$placeholders}",
            'call' => "CALL {$procedure}({$placeholders})",
            default => $driver === 'sqlsrv'
                ? ($parameterCount === 0
                    ? "SET NOCOUNT ON; EXEC {$procedure}"
                    : "SET NOCOUNT ON; EXEC {$procedure} {$placeholders}")
                : "CALL {$procedure}({$placeholders})",
        };

        return $connection->select($sql, $bindings);
    }
}

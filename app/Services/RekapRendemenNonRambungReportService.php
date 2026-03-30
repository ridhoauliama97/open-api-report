<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class RekapRendemenNonRambungReportService
{
    private const OUTPUT_SCHEMA = [
        ['key' => 'Tahun', 'label' => 'Tahun', 'type' => 'text'],
        ['key' => 'Bulan', 'label' => 'Bulan', 'type' => 'text'],
        ['key' => 'KB Keluar (Ton)', 'label' => 'KB Keluar (Ton)', 'type' => 'number', 'decimals' => 4],
        ['key' => 'ST Masuk (Ton)', 'label' => 'ST Masuk (Ton)', 'type' => 'number', 'decimals' => 4],
        ['key' => '%ST/KB', 'label' => '%ST/KB', 'type' => 'percent', 'decimals' => 2],
        ['key' => 'ST Keluar (M3)', 'label' => 'ST Keluar (M3)', 'type' => 'number', 'decimals' => 2],
        ['key' => 'WIP Masuk (M3)', 'label' => 'WIP Masuk (M3)', 'type' => 'number', 'decimals' => 4],
        ['key' => '%WIP/ST', 'label' => '%WIP/ST', 'type' => 'percent', 'decimals' => 2],
        ['key' => 'WIP Pemakaian Net (M3)', 'label' => 'WIP Pemakaian Net (M3)', 'type' => 'number', 'decimals' => 4],
        ['key' => 'BJ Masuk (M3)', 'label' => 'BJ Masuk (M3)', 'type' => 'number', 'decimals' => 4],
        ['key' => '%BJ/WIP', 'label' => '%BJ/WIP', 'type' => 'percent', 'decimals' => 2],
        ['key' => '%BJ/ST', 'label' => '%BJ/ST', 'type' => 'percent', 'decimals' => 2],
        ['key' => '%Total', 'label' => '%Total', 'type' => 'percent', 'decimals' => 2],
    ];

    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetch(string $year, string $month): array
    {
        $rows = $this->runProcedureQuery($year, $month);

        return array_values(array_map(static fn(object $row): array => (array) $row, $rows));
    }

    /**
     * @return array<string, mixed>
     */
    public function buildReportData(string $year, string $month): array
    {
        $rawRows = $this->fetch($year, $month);
        $rows = $this->mapRows($rawRows);
        $columnSchema = self::OUTPUT_SCHEMA;
        $numericTotals = $this->buildNumericTotals($columnSchema, $rows);

        return [
            'rows' => $rows,
            'raw_rows' => $rawRows,
            'column_order' => array_values(array_map(static fn(array $item): string => (string) $item['key'], $columnSchema)),
            'column_schema' => $columnSchema,
            'summary' => [
                'total_rows' => count($rows),
                'total_columns' => count($columnSchema),
                'numeric_totals' => $numericTotals,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function healthCheck(string $year, string $month): array
    {
        $rows = $this->fetch($year, $month);
        $expectedColumns = config('reports.rekap_rendemen_non_rambung.expected_columns', []);
        $expectedColumns = is_array($expectedColumns) ? array_values(array_filter(array_map('strval', $expectedColumns))) : [];
        $detectedColumns = $this->extractColumns($rows, []);
        $missingColumns = array_values(array_diff($expectedColumns, $detectedColumns));
        $extraColumns = empty($expectedColumns) ? [] : array_values(array_diff($detectedColumns, $expectedColumns));

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
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    private function mapRows(array $rows): array
    {
        $mapped = [];

        foreach ($rows as $row) {
            $tahun = $this->toInt($row['Tahun'] ?? null);
            $bulan = $this->toInt($row['Bulan'] ?? null);
            $kbKeluarTon = $this->toFloat($row['KBKeluarTon'] ?? null);
            $stMasukTon = $this->toFloat($row['STMasukTon'] ?? null);
            $stKeluar = $this->toFloat($row['STKeluarTon'] ?? null);
            $wipMasuk = $this->toFloat($row['WIPMasukM3'] ?? null);
            $wipPemakaianNet = $this->toFloat($row['WIPPemakaianNetM3'] ?? null);
            $bjMasuk = $this->toFloat($row['BJMasukM3'] ?? null);

            $percentStKb = $this->dividePercent($stMasukTon, $kbKeluarTon);
            $percentWipSt = $this->dividePercent($wipMasuk, $stKeluar);
            $percentBjWip = $this->dividePercent($bjMasuk, $wipPemakaianNet);
            $percentBjSt = $this->multiplyPercent($percentBjWip, $percentWipSt);
            $percentTotal = $this->multiplyPercent($percentBjSt, $percentStKb);

            $mapped[] = [
                'Tahun' => $tahun,
                'Bulan' => $bulan,
                'KB Keluar (Ton)' => $kbKeluarTon,
                'ST Masuk (Ton)' => $stMasukTon,
                '%ST/KB' => $percentStKb,
                'ST Keluar (M3)' => $stKeluar,
                'WIP Masuk (M3)' => $wipMasuk,
                '%WIP/ST' => $percentWipSt,
                'WIP Pemakaian Net (M3)' => $wipPemakaianNet,
                'BJ Masuk (M3)' => $bjMasuk,
                '%BJ/WIP' => $percentBjWip,
                '%BJ/ST' => $percentBjSt,
                '%Total' => $percentTotal,
            ];
        }

        return $mapped;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @param array<int, string> $expectedColumns
     * @return array<int, string>
     */
    private function extractColumns(array $rows, array $expectedColumns): array
    {
        $seen = [];
        $columns = [];

        foreach ($expectedColumns as $column) {
            $name = trim($column);
            if ($name === '') {
                continue;
            }

            $key = mb_strtolower($name);
            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $columns[] = $name;
        }

        foreach ($rows as $row) {
            foreach (array_keys($row) as $column) {
                $name = trim((string) $column);
                if ($name === '') {
                    continue;
                }

                $key = mb_strtolower($name);
                if (isset($seen[$key])) {
                    continue;
                }

                $seen[$key] = true;
                $columns[] = $name;
            }
        }

        return $columns;
    }

    /**
     * @param array<int, string> $columns
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    private function buildColumnSchema(array $columns, array $rows): array
    {
        $schema = [];

        foreach ($columns as $column) {
            $schema[] = [
                'key' => $column,
                'label' => $column,
                'type' => $this->detectColumnType($column, $rows),
                'decimals' => $this->detectDecimals($column),
            ];
        }

        return $schema;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    private function detectColumnType(string $column, array $rows): string
    {
        $normalized = strtolower(str_replace([' ', '_', '-'], '', $column));
        if (
            str_contains($normalized, 'tanggal')
            || str_starts_with($normalized, 'tgl')
            || str_contains($normalized, 'date')
        ) {
            return 'date';
        }

        if (
            str_contains($normalized, 'rendemen')
            || str_contains($normalized, 'persen')
            || str_contains($normalized, 'percent')
            || str_contains($normalized, 'ratio')
        ) {
            return 'percent';
        }

        foreach ($rows as $row) {
            $value = $row[$column] ?? null;
            if ($value === null || $value === '') {
                continue;
            }

            if ($this->toFloat($value) !== null) {
                return 'number';
            }

            break;
        }

        return 'text';
    }

    private function detectDecimals(string $column): int
    {
        $normalized = strtolower(str_replace([' ', '_', '-'], '', $column));

        if (
            str_contains($normalized, 'rendemen')
            || str_contains($normalized, 'persen')
            || str_contains($normalized, 'percent')
            || str_contains($normalized, 'ratio')
        ) {
            return 2;
        }

        return 2;
    }

    private function toInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        $raw = trim((string) $value);

        return ctype_digit($raw) ? (int) $raw : null;
    }

    private function dividePercent(?float $numerator, ?float $denominator): ?float
    {
        if ($numerator === null || $denominator === null || abs($denominator) < 0.0000001) {
            return null;
        }

        return ($numerator / $denominator) * 100.0;
    }

    private function multiplyPercent(?float $left, ?float $right): ?float
    {
        if ($left === null || $right === null) {
            return null;
        }

        return ($left / 100.0) * ($right / 100.0) * 100.0;
    }

    /**
     * @param array<int, array<string, mixed>> $columnSchema
     * @param array<int, array<string, mixed>> $rows
     * @return array<string, float>
     */
    private function buildNumericTotals(array $columnSchema, array $rows): array
    {
        $totals = [];

        foreach ($columnSchema as $column) {
            $type = (string) ($column['type'] ?? 'text');
            $key = (string) ($column['key'] ?? '');

            if ($type !== 'number' || $key === '') {
                continue;
            }

            $sum = 0.0;
            $hasValue = false;

            foreach ($rows as $row) {
                $number = $this->toFloat($row[$key] ?? null);
                if ($number === null) {
                    continue;
                }

                $sum += $number;
                $hasValue = true;
            }

            if ($hasValue) {
                $totals[$key] = $sum;
            }
        }

        return $totals;
    }

    private function toFloat(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        if (!is_string($value)) {
            return null;
        }

        $normalized = trim(str_replace(' ', '', $value));
        if ($normalized === '') {
            return null;
        }

        if (str_contains($normalized, ',') && str_contains($normalized, '.')) {
            if (strrpos($normalized, ',') > strrpos($normalized, '.')) {
                $normalized = str_replace('.', '', $normalized);
                $normalized = str_replace(',', '.', $normalized);
            } else {
                $normalized = str_replace(',', '', $normalized);
            }
        } elseif (str_contains($normalized, ',')) {
            $normalized = str_replace(',', '.', $normalized);
        }

        return is_numeric($normalized) ? (float) $normalized : null;
    }

    /**
     * @return array<int, object>
     */
    private function runProcedureQuery(string $year, string $month): array
    {
        $configKey = 'reports.rekap_rendemen_non_rambung';
        $connectionName = config("{$configKey}.database_connection");
        $procedure = (string) config("{$configKey}.stored_procedure", '');
        $syntax = (string) config("{$configKey}.call_syntax", 'exec');
        $customQuery = config("{$configKey}.query");
        $parameterCount = (int) config("{$configKey}.parameter_count", 1);

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan rekap rendemen non rambung belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();
        $bindings = array_slice([(int) $year, (int) $month], 0, max(0, min($parameterCount, 2)));

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan rekap rendemen non rambung dikonfigurasi untuk SQL Server. '
                . 'Set REKAP_RENDEMEN_NON_RAMBUNG_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException('Query manual laporan rekap rendemen non rambung belum diisi.');

            return $connection->select($query, str_contains($query, '?') ? $bindings : []);
        }

        if (!preg_match('/^[A-Za-z0-9_$.]+$/', $procedure)) {
            throw new RuntimeException('Nama stored procedure tidak valid.');
        }

        $sql = match ($syntax) {
            'exec' => $parameterCount >= 2 ? "EXEC {$procedure} @Tahun = ?, @Bulan = ?" : "EXEC {$procedure}",
            'call' => $parameterCount >= 2 ? "CALL {$procedure}(?, ?)" : "CALL {$procedure}()",
            default => $driver === 'sqlsrv'
                ? ($parameterCount >= 2 ? "EXEC {$procedure} @Tahun = ?, @Bulan = ?" : "EXEC {$procedure}")
                : ($parameterCount >= 2 ? "CALL {$procedure}(?, ?)" : "CALL {$procedure}()"),
        };

        return $connection->select($sql, $bindings);
    }
}

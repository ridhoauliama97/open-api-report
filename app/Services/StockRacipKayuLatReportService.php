<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class StockRacipKayuLatReportService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetch(string $endDate): array
    {
        $rows = $this->runProcedureQuery($endDate);

        return array_map(static fn ($row): array => (array) $row, $rows);
    }

    /**
     * @return array<string, mixed>
     */
    public function buildReportData(string $endDate): array
    {
        $sourceRows = $this->fetch($endDate);
        $rows = $this->normalizeRows($sourceRows);
        $groupedRows = $this->groupByJenis($rows);

        $summary = [
            'total_rows' => count($rows),
            'total_batang' => 0.0,
            'total_hasil' => 0.0,
        ];

        foreach ($rows as $row) {
            $summary['total_batang'] += $this->toFloat($row['JmlhBatang'] ?? null);
            $summary['total_hasil'] += $this->toFloat($row['Hasil'] ?? null);
        }

        return [
            'rows' => $rows,
            'grouped_rows' => $groupedRows,
            'summary' => $summary,
            'end_date_text' => $this->formatDate($endDate),
            'column_order' => ['Nomor', 'Tebal', 'Lebar', 'Panjang', 'Hasil'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function healthCheck(string $endDate): array
    {
        $rows = $this->fetch($endDate);
        $detectedColumns = array_keys($rows[0] ?? []);
        $expectedColumns = config('reports.stock_racip_kayu_lat.expected_columns', []);
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

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    private function normalizeRows(array $rows): array
    {
        $normalizedRows = [];
        foreach ($rows as $row) {
            $jenis = trim((string) ($row['Jenis'] ?? ''));
            $tebal = $this->toFloat($row['Tebal'] ?? null);
            $lebar = $this->toFloat($row['Lebar'] ?? null);
            $panjang = $this->toFloat($row['Panjang'] ?? null);
            $normalizedRows[] = [
                'Jenis' => $jenis,
                'Tebal' => $tebal,
                'Lebar' => $lebar,
                'Panjang' => $panjang,
                'Hasil' => $this->toFloat($row['Hasil'] ?? null),
                'JmlhBatang' => $this->toFloat($row['JmlhBatang'] ?? null),
            ];
        }

        usort(
            $normalizedRows,
            static fn (array $a, array $b): int => [$a['Jenis'], $a['Tebal'], $a['Lebar'], $a['Panjang']]
                <=> [$b['Jenis'], $b['Tebal'], $b['Lebar'], $b['Panjang']],
        );

        return $normalizedRows;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array{jenis: string, rows: array<int, array<string, mixed>>}>
     */
    private function groupByJenis(array $rows): array
    {
        $result = [];

        foreach ($rows as $row) {
            $jenis = (string) ($row['Jenis'] ?? '');
            if (!array_key_exists($jenis, $result)) {
                $result[$jenis] = [
                    'jenis' => $jenis,
                    'rows' => [],
                ];
            }

            $result[$jenis]['rows'][] = $row;
        }

        return array_values($result);
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

        return (float) $trimmed;
    }

    private function formatDate(string $date): string
    {
        $timestamp = strtotime($date);

        return $timestamp === false ? $date : date('d/m/Y', $timestamp);
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
        $connectionName = config('reports.stock_racip_kayu_lat.database_connection');
        $procedure = (string) config('reports.stock_racip_kayu_lat.stored_procedure');
        $syntax = (string) config('reports.stock_racip_kayu_lat.call_syntax', 'exec');
        $customQuery = config('reports.stock_racip_kayu_lat.query');
        $parameterCount = (int) config('reports.stock_racip_kayu_lat.parameter_count', 1);
        $parameterCount = max(0, min(1, $parameterCount));

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan stok racip kayu lat belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan stok racip kayu lat dikonfigurasi untuk SQL Server. '
                . 'Set STOCK_RACIP_KAYU_LAT_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        $allBindings = [$endDate];
        $bindings = array_slice($allBindings, 0, $parameterCount);

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException(
                    'STOCK_RACIP_KAYU_LAT_REPORT_QUERY belum diisi. '
                    . 'Isi query manual jika menggunakan STOCK_RACIP_KAYU_LAT_REPORT_CALL_SYNTAX=query.',
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

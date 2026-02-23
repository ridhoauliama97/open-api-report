<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class MutasiRacipDetailReportService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetch(string $startDate, string $endDate): array
    {
        $rows = $this->runProcedureQuery($startDate, $endDate);

        return array_map(static fn ($row): array => (array) $row, $rows);
    }

    /**
     * @return array<string, mixed>
     */
    public function buildReportData(string $startDate, string $endDate): array
    {
        $rows = $this->fetch($startDate, $endDate);
        $columns = array_keys($rows[0] ?? []);

        $expectedColumns = config('reports.mutasi_racip_detail.expected_columns', []);
        if ($columns === [] && is_array($expectedColumns)) {
            $columns = array_values($expectedColumns);
        }

        $detailColumns = [
            'Jenis',
            'Tebal',
            'Lebar',
            'Panjang',
            'Sawal',
            'SawalJlhBtg',
            'Masuk',
            'MskJlhBtg',
            'AdjusmentOutput',
            'AdjOutJlhBtg',
            'Keluar',
            'KeluarJlhBtg',
            'AdjusmentInput',
            'AdjInJlhBtg',
            'Akhir',
            'AkhirJlhBtg',
        ];
        $detailColumns = array_values(array_filter($detailColumns, static fn (string $column): bool => in_array($column, $columns, true)));
        $totalColumns = [
            'Sawal',
            'SawalJlhBtg',
            'Masuk',
            'MskJlhBtg',
            'AdjusmentOutput',
            'AdjOutJlhBtg',
            'Keluar',
            'KeluarJlhBtg',
            'AdjusmentInput',
            'AdjInJlhBtg',
            'Akhir',
            'AkhirJlhBtg',
        ];
        $totalColumns = array_values(array_filter($totalColumns, static fn (string $column): bool => in_array($column, $detailColumns, true)));

        $numericColumns = [];
        $totals = [];

        foreach ($detailColumns as $column) {
            $isNumeric = $this->isNumericColumn($column, $rows);
            $numericColumns[$column] = $isNumeric;
            if ($isNumeric && in_array($column, $totalColumns, true)) {
                $totals[$column] = 0.0;
            }
        }

        foreach ($rows as $row) {
            foreach ($totalColumns as $column) {
                if (($numericColumns[$column] ?? false) !== true || !array_key_exists($column, $totals)) {
                    continue;
                }

                if ($this->isBatangColumn($column)) {
                    $totals[$column] += $this->toInt($row[$column] ?? null);
                    continue;
                }

                $totals[$column] += round($this->toFloat($row[$column] ?? null), 4);
            }
        }

        foreach ($totals as $column => $totalValue) {
            if ($this->isBatangColumn($column)) {
                $totals[$column] = (int) round($totalValue);
                continue;
            }

            $totals[$column] = round((float) $totalValue, 4);
        }

        return [
            'rows' => $rows,
            'columns' => $columns,
            'detail_columns' => $detailColumns,
            'numeric_columns' => $numericColumns,
            'totals' => $totals,
            'start_date_text' => $this->formatDate($startDate),
            'end_date_text' => $this->formatDate($endDate),
        ];
    }

    private function isNumericColumn(string $column, array $rows): bool
    {
        foreach ($rows as $row) {
            $value = $row[$column] ?? null;
            if ($value === null || $value === '') {
                continue;
            }

            if (is_numeric($value)) {
                return true;
            }

            if (is_string($value) && is_numeric(str_replace(',', '.', trim($value)))) {
                return true;
            }

            return false;
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

        return (float) $trimmed;
    }

    private function toInt(mixed $value): int
    {
        return (int) round($this->toFloat($value));
    }

    private function isBatangColumn(string $column): bool
    {
        $normalized = strtolower(str_replace([' ', '_'], '', trim($column)));

        return str_contains($normalized, 'jlhbtg') || str_contains($normalized, 'jmlhbatang');
    }

    private function formatDate(string $date): string
    {
        $timestamp = strtotime($date);

        return $timestamp === false ? $date : date('d/m/Y', $timestamp);
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
    private function runProcedureQuery(string $startDate, string $endDate): array
    {
        $connectionName = config('reports.mutasi_racip_detail.database_connection');
        $procedure = (string) config('reports.mutasi_racip_detail.stored_procedure');
        $syntax = (string) config('reports.mutasi_racip_detail.call_syntax', 'exec');
        $customQuery = config('reports.mutasi_racip_detail.query');

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan mutasi racip detail belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $bindings = [$startDate, $endDate];
        $driver = $connection->getDriverName();

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan mutasi racip detail dikonfigurasi untuk SQL Server. '
                . 'Set MUTASI_RACIP_DETAIL_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException(
                    'MUTASI_RACIP_DETAIL_REPORT_QUERY belum diisi. '
                    . 'Isi query manual jika menggunakan MUTASI_RACIP_DETAIL_REPORT_CALL_SYNTAX=query.',
                );

            return $connection->select($query, $this->resolveBindings($query, $bindings));
        }

        if (!preg_match('/^[A-Za-z0-9_$.]+$/', $procedure)) {
            throw new RuntimeException('Nama stored procedure tidak valid.');
        }

        $sql = match ($syntax) {
            'exec' => "SET NOCOUNT ON; EXEC {$procedure} ?, ?",
            'call' => "CALL {$procedure}(?, ?)",
            default => $driver === 'sqlsrv'
                ? "SET NOCOUNT ON; EXEC {$procedure} ?, ?"
                : "CALL {$procedure}(?, ?)",
        };

        return $connection->select($sql, $bindings);
    }
}

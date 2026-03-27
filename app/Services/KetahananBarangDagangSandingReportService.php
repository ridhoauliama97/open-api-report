<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class KetahananBarangDagangSandingReportService
{
    /**
     * @return array<string, mixed>
     */
    public function buildReportData(string $startDate, string $endDate): array
    {
        $rows = $this->fetch($startDate, $endDate);

        return [
            'rows' => $rows,
            'summary' => [
                'total_rows' => count($rows),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function healthCheck(string $startDate, string $endDate): array
    {
        $raw = $this->runProcedureQuery($startDate, $endDate);
        $first = (array) ($raw[0] ?? []);
        $detectedColumns = array_keys($first);
        $expectedColumns = config('reports.ketahanan_barang_sanding.expected_columns', []);
        $expectedColumns = is_array($expectedColumns) ? array_values($expectedColumns) : [];

        return [
            'is_healthy' => empty(array_diff($expectedColumns, $detectedColumns)),
            'expected_columns' => $expectedColumns,
            'detected_columns' => $detectedColumns,
            'missing_columns' => array_values(array_diff($expectedColumns, $detectedColumns)),
            'extra_columns' => array_values(array_diff($detectedColumns, $expectedColumns)),
            'row_count' => count($raw),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetch(string $startDate, string $endDate): array
    {
        $raw = $this->runProcedureQuery($startDate, $endDate);

        $out = [];
        foreach ($raw as $row) {
            $item = (array) $row;
            foreach ($item as $k => $v) {
                if (is_string($v)) {
                    $item[$k] = trim($v);
                }
            }

            $stock = (float) ($this->toFloat(
                $item['Stock']
                    ?? $item['Stok']
                    ?? $item['Saldo']
                    ?? $item['StockTon']
                    ?? $item['StokTon']
                    ?? $item['Stockm3']
                    ?? $item['StockM3']
                    ?? $item['Stokm3']
                    ?? $item['StokM3']
                    ?? null,
            ) ?? 0.0);

            $penjualan = (float) ($this->toFloat(
                $item['Penjualan']
                    ?? $item['Jual']
                    ?? $item['Ton']
                    ?? $item['TonJual']
                    ?? $item['m3']
                    ?? $item['M3']
                    ?? $item['Jualm3']
                    ?? $item['JualM3']
                    ?? null,
            ) ?? 0.0);

            $avgPenjualan = (float) ($this->toFloat(
                $item['AvgPenjualan']
                    ?? $item['AvgJual']
                    ?? $item['RataPenjualan']
                    ?? null,
            ) ?? $penjualan);

            $ketahanan = $this->toFloat($item['Ketahanan'] ?? $item['HariTahan'] ?? null);
            if ($ketahanan === null) {
                $ketahanan = $avgPenjualan > 0.0 ? ($stock / $avgPenjualan) : 0.0;
            }

            $out[] = [
                'Jenis' => (string) ($item['Jenis'] ?? $item['GroupKayu'] ?? $item['NamaGrade'] ?? $item['Produk'] ?? ''),
                'Stock' => $stock,
                'Penjualan' => $penjualan,
                'AvgPenjualan' => $avgPenjualan,
                'Ketahanan' => (float) $ketahanan,
            ];
        }

        return $out;
    }

    private function toFloat(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }
        if (!is_string($value)) {
            return null;
        }
        $t = trim($value);
        if ($t === '' || $t === '-') {
            return null;
        }
        $t = str_replace(',', '', $t);
        if (!is_numeric($t)) {
            return null;
        }

        return (float) $t;
    }

    /**
     * @return array<int, object>
     */
    private function runProcedureQuery(string $startDate, string $endDate): array
    {
        $configKey = 'reports.ketahanan_barang_sanding';
        $connectionName = config("{$configKey}.database_connection");
        $procedure = (string) config("{$configKey}.stored_procedure", 'SP_LapKetahananBarangSanding');
        $syntax = (string) config("{$configKey}.call_syntax", 'exec');
        $customQuery = config("{$configKey}.query");
        $parameterCount = (int) config("{$configKey}.parameter_count", 2);

        if ($parameterCount !== 2) {
            throw new RuntimeException('Jumlah parameter laporan Ketahanan Barang Dagang Sanding harus 2 (Tanggal Awal dan Tanggal Akhir).');
        }

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan Ketahanan Barang Dagang Sanding belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan Ketahanan Barang Dagang Sanding dikonfigurasi untuk SQL Server. '
                . 'Set KETAHANAN_BARANG_SANDING_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException('Query manual laporan Ketahanan Barang Dagang Sanding belum diisi.');

            return $connection->select($query, str_contains($query, '?') ? [$startDate, $endDate] : []);
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

        return $connection->select($sql, [$startDate, $endDate]);
    }
}

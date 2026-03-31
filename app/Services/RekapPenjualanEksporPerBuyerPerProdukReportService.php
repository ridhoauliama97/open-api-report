<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class RekapPenjualanEksporPerBuyerPerProdukReportService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetch(string $startDate, string $endDate): array
    {
        $rows = $this->runProcedureQuery($startDate, $endDate);

        return array_values(array_map(static fn(object $row): array => (array) $row, $rows));
    }

    /**
     * @return array<string, mixed>
     */
    public function buildReportData(string $startDate, string $endDate): array
    {
        $rows = $this->fetch($startDate, $endDate);
        $normalizedRows = [];

        foreach ($rows as $row) {
            $buyer = trim((string) ($row['Pembeli'] ?? ''));
            if ($buyer === '') {
                continue;
            }

            $product = trim((string) ($row['Product'] ?? ''));
            $product = $product !== '' ? $product : '-';

            $normalizedRows[] = [
                'Pembeli' => $buyer,
                'Product' => $product,
                'Tebal' => $this->toFloat($row['Tebal'] ?? null),
                'Lebar' => $this->toFloat($row['Lebar'] ?? null),
                'Panjang' => $this->toFloat($row['Panjang'] ?? null),
                'JmlhBatang' => (int) round((float) ($this->toFloat($row['JmlhBatang'] ?? null) ?? 0.0)),
                'M3' => (float) ($this->toFloat($row['M3'] ?? null) ?? 0.0),
                'PembeliM3' => (float) ($this->toFloat($row['PembeliM3'] ?? null) ?? 0.0),
                'PembeliBJM3' => (float) ($this->toFloat($row['PembeliBJM3'] ?? null) ?? 0.0),
            ];
        }

        /** @var array<string, array<string, mixed>> $grouped */
        $grouped = [];
        foreach ($normalizedRows as $row) {
            $buyer = $row['Pembeli'];
            $product = $row['Product'];

            if (!isset($grouped[$buyer])) {
                $grouped[$buyer] = [
                    'name' => $buyer,
                    'products' => [],
                    'total_m3' => 0.0,
                ];
            }

            if (!isset($grouped[$buyer]['products'][$product])) {
                $grouped[$buyer]['products'][$product] = [
                    'name' => $product,
                    'rows' => [],
                    'total_m3' => 0.0,
                ];
            }

            $grouped[$buyer]['products'][$product]['rows'][] = $row;
            $grouped[$buyer]['products'][$product]['total_m3'] = (float) $row['PembeliBJM3'];
            $grouped[$buyer]['total_m3'] = (float) $row['PembeliM3'];
        }

        ksort($grouped);

        $buyers = [];
        $grandTotal = 0.0;
        foreach ($grouped as $buyerGroup) {
            $products = is_array($buyerGroup['products']) ? $buyerGroup['products'] : [];
            ksort($products);

            foreach ($products as &$productGroup) {
                $productTotal = (float) ($productGroup['total_m3'] ?? 0.0);

                foreach ($productGroup['rows'] as $rowIndex => &$row) {
                    $row['No'] = $rowIndex + 1;
                    $row['Ratio'] = $productTotal > 0 ? ((float) $row['M3'] / $productTotal) * 100.0 : null;
                }
                unset($row);

                $productGroup['summary_ratio'] = (float) ($buyerGroup['total_m3'] ?? 0.0) > 0
                    ? ($productTotal / (float) $buyerGroup['total_m3']) * 100.0
                    : null;
                $productGroup['rows'] = array_values($productGroup['rows']);
            }
            unset($productGroup);

            $buyerGroup['products'] = array_values($products);
            $buyers[] = $buyerGroup;
            $grandTotal += (float) ($buyerGroup['total_m3'] ?? 0.0);
        }

        foreach ($buyers as $index => &$buyer) {
            $buyer['number'] = $index + 1;
            $buyer['summary_ratio'] = $grandTotal > 0 ? ((float) ($buyer['total_m3'] ?? 0.0) / $grandTotal) * 100.0 : null;
        }
        unset($buyer);

        return [
            'buyers' => $buyers,
            'summary' => [
                'total_rows' => count($normalizedRows),
                'total_buyers' => count($buyers),
                'total_products' => array_sum(array_map(static fn(array $buyer): int => count($buyer['products'] ?? []), $buyers)),
                'grand_total_m3' => $grandTotal,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function healthCheck(string $startDate, string $endDate): array
    {
        $rows = $this->fetch($startDate, $endDate);
        $detectedColumns = array_keys($rows[0] ?? []);
        $expectedColumns = config('reports.rekap_penjualan_ekspor_per_buyer_per_produk.expected_columns', []);
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
    private function runProcedureQuery(string $startDate, string $endDate): array
    {
        $configKey = 'reports.rekap_penjualan_ekspor_per_buyer_per_produk';
        $connectionName = config("{$configKey}.database_connection");
        $procedure = (string) config("{$configKey}.stored_procedure", 'SP_LapJualPerBuyerPerProduk');
        $syntax = (string) config("{$configKey}.call_syntax", 'exec');
        $customQuery = config("{$configKey}.query");
        $parameterCount = (int) config("{$configKey}.parameter_count", 2);

        if ($parameterCount !== 2) {
            throw new RuntimeException('Jumlah parameter laporan Rekap Penjualan Ekspor Per-Buyer dan Per-Produk harus 2 (Tanggal Awal dan Tanggal Akhir).');
        }

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan Rekap Penjualan Ekspor Per-Buyer dan Per-Produk belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan Rekap Penjualan Ekspor Per-Buyer dan Per-Produk dikonfigurasi untuk SQL Server. '
                . 'Set REKAP_PENJUALAN_EKSPOR_PER_BUYER_PER_PRODUK_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException('Query manual laporan Rekap Penjualan Ekspor Per-Buyer dan Per-Produk belum diisi.');

            return $connection->select($query, [$startDate, $endDate]);
        }

        if (!preg_match('/^[A-Za-z0-9_$.]+$/', $procedure)) {
            throw new RuntimeException('Nama stored procedure tidak valid.');
        }

        $sql = match ($syntax) {
            'exec' => "SET NOCOUNT ON; EXEC {$procedure} ?, ?",
            'call' => "CALL {$procedure}(?, ?)",
            default => $driver === 'sqlsrv' ? "SET NOCOUNT ON; EXEC {$procedure} ?, ?" : "CALL {$procedure}(?, ?)",
        };

        return $connection->select($sql, [$startDate, $endDate]);
    }

    private function toFloat(mixed $value): ?float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        if (!is_string($value)) {
            return null;
        }

        $normalized = trim($value);
        if ($normalized === '') {
            return null;
        }

        $normalized = str_replace(' ', '', $normalized);

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
}

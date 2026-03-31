<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class RekapPenjualanEksporPerProdukPerBuyerReportService
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
            $product = trim((string) ($row['Product'] ?? ''));
            if ($product === '') {
                continue;
            }

            $buyer = trim((string) ($row['Pembeli'] ?? ''));
            $buyer = $buyer !== '' ? $buyer : '-';

            $normalizedRows[] = [
                'Product' => $product,
                'Pembeli' => $buyer,
                'Tebal' => $this->toFloat($row['Tebal'] ?? null),
                'Lebar' => $this->toFloat($row['Lebar'] ?? null),
                'Panjang' => $this->toFloat($row['Panjang'] ?? null),
                'JmlhBatang' => (int) round((float) ($this->toFloat($row['JmlhBatang'] ?? null) ?? 0.0)),
                'M3' => (float) ($this->toFloat($row['M3'] ?? null) ?? 0.0),
                'BJM3' => (float) ($this->toFloat($row['BJM3'] ?? null) ?? 0.0),
                'PembeliBJM3' => (float) ($this->toFloat($row['PembeliBJM3'] ?? null) ?? 0.0),
            ];
        }

        /** @var array<string, array<string, mixed>> $grouped */
        $grouped = [];
        foreach ($normalizedRows as $row) {
            $product = $row['Product'];
            $buyer = $row['Pembeli'];

            if (!isset($grouped[$product])) {
                $grouped[$product] = [
                    'name' => $product,
                    'buyers' => [],
                    'total_m3' => 0.0,
                ];
            }

            if (!isset($grouped[$product]['buyers'][$buyer])) {
                $grouped[$product]['buyers'][$buyer] = [
                    'name' => $buyer,
                    'rows' => [],
                    'total_m3' => 0.0,
                ];
            }

            $grouped[$product]['buyers'][$buyer]['rows'][] = $row;
            $grouped[$product]['buyers'][$buyer]['total_m3'] = (float) $row['PembeliBJM3'];
            $grouped[$product]['total_m3'] = (float) $row['BJM3'];
        }

        ksort($grouped);

        $products = [];
        $grandTotal = 0.0;
        foreach ($grouped as $productGroup) {
            $buyers = is_array($productGroup['buyers']) ? $productGroup['buyers'] : [];
            ksort($buyers);

            foreach ($buyers as &$buyerGroup) {
                $buyerTotal = (float) ($buyerGroup['total_m3'] ?? 0.0);

                foreach ($buyerGroup['rows'] as $rowIndex => &$row) {
                    $row['No'] = $rowIndex + 1;
                    $row['Ratio'] = $buyerTotal > 0 ? ((float) $row['M3'] / $buyerTotal) * 100.0 : null;
                }
                unset($row);

                $buyerGroup['summary_ratio'] = (float) ($productGroup['total_m3'] ?? 0.0) > 0
                    ? ($buyerTotal / (float) $productGroup['total_m3']) * 100.0
                    : null;
                $buyerGroup['rows'] = array_values($buyerGroup['rows']);
            }
            unset($buyerGroup);

            $productGroup['buyers'] = array_values($buyers);
            $products[] = $productGroup;
            $grandTotal += (float) ($productGroup['total_m3'] ?? 0.0);
        }

        foreach ($products as $index => &$product) {
            $product['number'] = $index + 1;
            $product['summary_ratio'] = $grandTotal > 0 ? ((float) ($product['total_m3'] ?? 0.0) / $grandTotal) * 100.0 : null;
        }
        unset($product);

        return [
            'products' => $products,
            'summary' => [
                'total_rows' => count($normalizedRows),
                'total_products' => count($products),
                'total_buyers' => array_sum(array_map(static fn(array $product): int => count($product['buyers'] ?? []), $products)),
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
        $expectedColumns = config('reports.rekap_penjualan_ekspor_per_produk_per_buyer.expected_columns', []);
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
        $configKey = 'reports.rekap_penjualan_ekspor_per_produk_per_buyer';
        $connectionName = config("{$configKey}.database_connection");
        $procedure = (string) config("{$configKey}.stored_procedure", 'SP_LapJualPerProdukPerBuyer');
        $syntax = (string) config("{$configKey}.call_syntax", 'exec');
        $customQuery = config("{$configKey}.query");
        $parameterCount = (int) config("{$configKey}.parameter_count", 2);

        if ($parameterCount !== 2) {
            throw new RuntimeException('Jumlah parameter laporan Rekap Penjualan Ekspor Per-Produk dan Per-Buyer harus 2 (Tanggal Awal dan Tanggal Akhir).');
        }

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan Rekap Penjualan Ekspor Per-Produk dan Per-Buyer belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan Rekap Penjualan Ekspor Per-Produk dan Per-Buyer dikonfigurasi untuk SQL Server. '
                . 'Set REKAP_PENJUALAN_EKSPOR_PER_PRODUK_PER_BUYER_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException('Query manual laporan Rekap Penjualan Ekspor Per-Produk dan Per-Buyer belum diisi.');

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

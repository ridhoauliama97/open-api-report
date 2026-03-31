<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class RekapPenjualanPerProdukReportService
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

            $normalizedRows[] = [
                'Product' => $product,
                'Tebal' => $this->toFloat($row['Tebal'] ?? null),
                'Lebar' => $this->toFloat($row['Lebar'] ?? null),
                'Panjang' => $this->toFloat($row['Panjang'] ?? null),
                'JmlhBatang' => (int) round((float) ($this->toFloat($row['JmlhBatang'] ?? null) ?? 0.0)),
                'M3' => (float) ($this->toFloat($row['M3'] ?? null) ?? 0.0),
                'BJM3' => (float) ($this->toFloat($row['BJM3'] ?? null) ?? 0.0),
            ];
        }

        $grouped = [];
        foreach ($normalizedRows as $row) {
            $product = $row['Product'];

            if (!isset($grouped[$product])) {
                $grouped[$product] = [
                    'name' => $product,
                    'rows' => [],
                    'total_m3' => 0.0,
                    'milestones' => [],
                ];
            }

            $grouped[$product]['rows'][] = $row;
            $grouped[$product]['total_m3'] += (float) $row['M3'];
        }

        $products = array_values($grouped);
        $grandTotal = array_sum(array_map(static fn(array $product): float => (float) ($product['total_m3'] ?? 0.0), $products));

        foreach ($products as $productIndex => &$product) {
            $cumulative = 0.0;

            foreach ($product['rows'] as $index => &$row) {
                $ratio = $product['total_m3'] > 0 ? ($row['M3'] / $product['total_m3']) * 100.0 : null;
                $row['No'] = $index + 1;
                $row['Ratio'] = $ratio;
                $row['CumulativeRatio'] = null;
                $row['DisplayCumulative'] = false;

                if ($ratio !== null) {
                    $cumulative += $ratio;
                    $row['CumulativeRatio'] = $cumulative;
                    $row['DisplayCumulative'] = $cumulative <= 70.0000001;
                }
            }
            unset($row);

            $product['roman'] = $this->toRoman($productIndex + 1);
            $product['summary_ratio'] = $grandTotal > 0 ? ($product['total_m3'] / $grandTotal) * 100.0 : null;
        }
        unset($product);

        return [
            'rows' => $normalizedRows,
            'products' => $products,
            'summary' => [
                'total_rows' => count($normalizedRows),
                'total_products' => count($products),
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
        $expectedColumns = config('reports.rekap_penjualan_per_produk.expected_columns', []);
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
        $connectionName = config('reports.rekap_penjualan_per_produk.database_connection');
        $procedure = (string) config('reports.rekap_penjualan_per_produk.stored_procedure', 'SP_LapJualPerProduk');
        $syntax = (string) config('reports.rekap_penjualan_per_produk.call_syntax', 'exec');
        $customQuery = config('reports.rekap_penjualan_per_produk.query');

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan rekap penjualan per produk belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan rekap penjualan per produk dikonfigurasi untuk SQL Server. '
                . 'Set REKAP_PENJUALAN_PER_PRODUK_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException(
                    'REKAP_PENJUALAN_PER_PRODUK_REPORT_QUERY belum diisi. '
                    . 'Isi query manual jika menggunakan REKAP_PENJUALAN_PER_PRODUK_REPORT_CALL_SYNTAX=query.',
                );

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

    private function toRoman(int $number): string
    {
        $map = [
            'M' => 1000,
            'CM' => 900,
            'D' => 500,
            'CD' => 400,
            'C' => 100,
            'XC' => 90,
            'L' => 50,
            'XL' => 40,
            'X' => 10,
            'IX' => 9,
            'V' => 5,
            'IV' => 4,
            'I' => 1,
        ];

        $result = '';
        foreach ($map as $roman => $value) {
            while ($number >= $value) {
                $result .= $roman;
                $number -= $value;
            }
        }

        return $result;
    }
}

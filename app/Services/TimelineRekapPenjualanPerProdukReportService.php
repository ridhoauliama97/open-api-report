<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TimelineRekapPenjualanPerProdukReportService
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
        $monthKeys = $this->buildMonthKeys($startDate, $endDate);
        $products = [];
        $normalizedRows = [];

        foreach ($rows as $row) {
            $product = trim((string) ($row['Product'] ?? ''));
            if ($product === '') {
                continue;
            }

            $tebal = $this->toFloat($row['Tebal'] ?? null);
            $lebar = $this->toFloat($row['Lebar'] ?? null);
            $panjang = $this->toFloat($row['Panjang'] ?? null);
            $m3 = (float) ($this->toFloat($row['M3'] ?? null) ?? 0.0);
            $monthKey = $this->monthKey($row['TglJual'] ?? null);
            $detailKey = implode('|', [
                $tebal !== null ? number_format($tebal, 4, '.', '') : '',
                $lebar !== null ? number_format($lebar, 4, '.', '') : '',
                $panjang !== null ? number_format($panjang, 4, '.', '') : '',
            ]);

            $normalizedRows[] = [
                'Product' => $product,
                'Tebal' => $tebal,
                'Lebar' => $lebar,
                'Panjang' => $panjang,
                'JmlhBatang' => (int) round((float) ($this->toFloat($row['JmlhBatang'] ?? null) ?? 0.0)),
                'M3' => $m3,
                'BJM3' => (float) ($this->toFloat($row['BJM3'] ?? null) ?? 0.0),
                'TglJual' => $row['TglJual'] ?? null,
            ];

            if (!isset($products[$product])) {
                $products[$product] = [
                    'name' => $product,
                    'rows' => [],
                    'total' => 0.0,
                ];
            }

            if (!isset($products[$product]['rows'][$detailKey])) {
                $products[$product]['rows'][$detailKey] = [
                    'Tebal' => $tebal,
                    'Lebar' => $lebar,
                    'Panjang' => $panjang,
                    'months' => array_fill_keys(array_column($monthKeys, 'key'), 0.0),
                    'Total' => 0.0,
                    'Ratio' => null,
                ];
            }

            if ($monthKey !== null && array_key_exists($monthKey, $products[$product]['rows'][$detailKey]['months'])) {
                $products[$product]['rows'][$detailKey]['months'][$monthKey] += $m3;
            }

            $products[$product]['rows'][$detailKey]['Total'] += $m3;
            $products[$product]['total'] += $m3;
        }

        $grandTotal = 0.0;
        $summaryMonths = array_fill_keys(array_column($monthKeys, 'key'), 0.0);
        $productList = [];
        $roman = 1;

        foreach ($products as $productName => $product) {
            $rowsByProduct = array_values($product['rows']);
            $productMonthTotals = array_fill_keys(array_column($monthKeys, 'key'), 0.0);
            foreach ($rowsByProduct as &$detailRow) {
                foreach ($detailRow['months'] as $key => $value) {
                    $productMonthTotals[$key] += $value;
                }

                $detailRow['Ratio'] = $product['total'] > 0 ? ($detailRow['Total'] / $product['total']) * 100.0 : null;
            }
            unset($detailRow);

            $tebalGroups = [];
            foreach ($rowsByProduct as $detailRow) {
                $tebalKey = $detailRow['Tebal'] !== null ? number_format((float) $detailRow['Tebal'], 4, '.', '') : '';

                if (!isset($tebalGroups[$tebalKey])) {
                    $tebalGroups[$tebalKey] = [
                        'tebal' => $detailRow['Tebal'],
                        'rows' => [],
                    ];
                }

                $tebalGroups[$tebalKey]['rows'][] = $detailRow;
            }

            foreach ($productMonthTotals as $key => $value) {
                $summaryMonths[$key] += $value;
            }

            $grandTotal += $product['total'];

            $productList[] = [
                'roman' => $this->toRoman($roman++),
                'name' => $productName,
                'rows' => $rowsByProduct,
                'tebal_groups' => array_values($tebalGroups),
                'month_totals' => $productMonthTotals,
                'total' => $product['total'],
                'ratio' => null,
            ];
        }

        foreach ($productList as &$product) {
            $product['ratio'] = $grandTotal > 0 ? ($product['total'] / $grandTotal) * 100.0 : null;
        }
        unset($product);

        return [
            'rows' => $normalizedRows,
            'products' => $productList,
            'month_keys' => $monthKeys,
            'summary' => [
                'total_rows' => count($normalizedRows),
                'total_products' => count($productList),
                'grand_total' => $grandTotal,
                'month_totals' => $summaryMonths,
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
        $expectedColumns = config('reports.timeline_rekap_penjualan_per_produk.expected_columns', []);
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
     * @return array<int, array{key:string,label:string}>
     */
    private function buildMonthKeys(string $startDate, string $endDate): array
    {
        $start = Carbon::parse($startDate)->startOfMonth();
        $end = Carbon::parse($endDate)->startOfMonth();
        $result = [];
        $cursor = $start->copy();

        while ($cursor <= $end) {
            $result[] = [
                'key' => $cursor->format('Y-m'),
                'label' => $cursor->format('F'),
                'short' => $cursor->format('M'),
            ];
            $cursor->addMonth();
        }

        return $result;
    }

    private function monthKey(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse((string) $value)->format('Y-m');
        } catch (\Throwable $exception) {
            return null;
        }
    }

    /**
     * @return array<int, object>
     */
    private function runProcedureQuery(string $startDate, string $endDate): array
    {
        $connectionName = config('reports.timeline_rekap_penjualan_per_produk.database_connection');
        $procedure = (string) config('reports.timeline_rekap_penjualan_per_produk.stored_procedure', 'SP_LapJualPerProdukTimeLine');
        $syntax = (string) config('reports.timeline_rekap_penjualan_per_produk.call_syntax', 'exec');
        $customQuery = config('reports.timeline_rekap_penjualan_per_produk.query');

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan timeline rekap penjualan per produk belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan timeline rekap penjualan per produk dikonfigurasi untuk SQL Server. '
                . 'Set TIMELINE_REKAP_PENJUALAN_PER_PRODUK_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException(
                    'TIMELINE_REKAP_PENJUALAN_PER_PRODUK_REPORT_QUERY belum diisi. '
                    . 'Isi query manual jika menggunakan TIMELINE_REKAP_PENJUALAN_PER_PRODUK_REPORT_CALL_SYNTAX=query.',
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

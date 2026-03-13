<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class SaldoStHidupPerProdukReportService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetch(): array
    {
        $rows = $this->runProcedureQuery();

        return array_map(function ($row): array {
            $item = (array) $row;

            return [
                'Group' => $item['Group'] ?? null,
                'Produk' => $item['Produk'] ?? null,
                'Tebal' => $this->toFloat($item['Tebal'] ?? null),
                'Lebar' => $this->toFloat($item['Lebar'] ?? null),
                'UOM' => $item['UOM'] ?? null,
                'BasahTon' => $this->toFloat($item['BasahTon'] ?? null) ?? 0.0,
                'KDTon' => $this->toFloat($item['KDTon'] ?? null) ?? 0.0,
                'KeringTon' => $this->toFloat($item['KeringTon'] ?? null) ?? 0.0,
                'TotalTon' => $this->toFloat($item['TotalTon'] ?? null) ?? 0.0,
            ];
        }, $rows);
    }

    /**
     * @return array<string, mixed>
     */
    public function buildReportData(): array
    {
        $rows = $this->fetch();

        $groups = [];
        $grand = ['basah' => 0.0, 'kd' => 0.0, 'kering' => 0.0, 'total' => 0.0];

        foreach ($rows as $row) {
            $groupName = trim((string) ($row['Group'] ?? ''));
            $groupName = $groupName !== '' ? $groupName : 'Tanpa Group';

            $produkName = trim((string) ($row['Produk'] ?? ''));
            $produkName = $produkName !== '' ? $produkName : 'Tanpa Produk';

            if (!isset($groups[$groupName])) {
                $groups[$groupName] = [
                    'name' => $groupName,
                    'products' => [],
                    'totals' => ['basah' => 0.0, 'kd' => 0.0, 'kering' => 0.0, 'total' => 0.0],
                ];
            }

            if (!isset($groups[$groupName]['products'][$produkName])) {
                $groups[$groupName]['products'][$produkName] = [
                    'name' => $produkName,
                    'rows' => [],
                    'totals' => ['basah' => 0.0, 'kd' => 0.0, 'kering' => 0.0, 'total' => 0.0],
                ];
            }

            $groups[$groupName]['products'][$produkName]['rows'][] = [
                'Tebal' => $row['Tebal'],
                'Lebar' => $row['Lebar'],
                'UOM' => $row['UOM'],
                'BasahTon' => (float) ($row['BasahTon'] ?? 0.0),
                'KDTon' => (float) ($row['KDTon'] ?? 0.0),
                'KeringTon' => (float) ($row['KeringTon'] ?? 0.0),
                'TotalTon' => (float) ($row['TotalTon'] ?? 0.0),
            ];

            foreach (['basah' => 'BasahTon', 'kd' => 'KDTon', 'kering' => 'KeringTon', 'total' => 'TotalTon'] as $k => $col) {
                $val = (float) ($row[$col] ?? 0.0);
                $groups[$groupName]['products'][$produkName]['totals'][$k] += $val;
                $groups[$groupName]['totals'][$k] += $val;
                $grand[$k] += $val;
            }
        }

        // Sort group names and product names for predictable output.
        ksort($groups, SORT_NATURAL | SORT_FLAG_CASE);

        foreach ($groups as &$group) {
            ksort($group['products'], SORT_NATURAL | SORT_FLAG_CASE);

            foreach ($group['products'] as &$product) {
                usort($product['rows'], static function (array $a, array $b): int {
                    $ta = (float) ($a['Tebal'] ?? 0);
                    $tb = (float) ($b['Tebal'] ?? 0);
                    if ($ta !== $tb) {
                        return $ta <=> $tb;
                    }

                    $la = (float) ($a['Lebar'] ?? 0);
                    $lb = (float) ($b['Lebar'] ?? 0);

                    return $la <=> $lb;
                });
            }
        }
        unset($group, $product);

        return [
            'groups' => array_values(array_map(static function (array $g): array {
                $g['products'] = array_values($g['products']);

                return $g;
            }, $groups)),
            'summary' => [
                'total_rows' => count($rows),
                'total_groups' => count($groups),
                'grand_totals' => $grand,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function healthCheck(): array
    {
        $rows = $this->fetch();
        $detectedColumns = array_keys($rows[0] ?? []);
        $expectedColumns = config('reports.saldo_st_hidup_per_produk.expected_columns', []);
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
    private function runProcedureQuery(): array
    {
        $connectionName = config('reports.saldo_st_hidup_per_produk.database_connection');
        $procedure = (string) config('reports.saldo_st_hidup_per_produk.stored_procedure', 'SPWps_LapSTHidupPerProduk');
        $syntax = (string) config('reports.saldo_st_hidup_per_produk.call_syntax', 'exec');
        $customQuery = config('reports.saldo_st_hidup_per_produk.query');

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan saldo ST hidup per produk belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan saldo ST hidup per produk dikonfigurasi untuk SQL Server. '
                . 'Set SALDO_ST_HIDUP_PER_PRODUK_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException(
                    'SALDO_ST_HIDUP_PER_PRODUK_REPORT_QUERY belum diisi. '
                    . 'Isi query manual jika menggunakan SALDO_ST_HIDUP_PER_PRODUK_REPORT_CALL_SYNTAX=query.',
                );

            return $connection->select($query);
        }

        if (!preg_match('/^[A-Za-z0-9_$.]+$/', $procedure)) {
            throw new RuntimeException('Nama stored procedure tidak valid.');
        }

        $sql = match ($syntax) {
            'exec' => "EXEC {$procedure}",
            'call' => "CALL {$procedure}()",
            default => $driver === 'sqlsrv' ? "EXEC {$procedure}" : "CALL {$procedure}()",
        };

        return $connection->select($sql);
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


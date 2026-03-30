<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class SaldoBarangJadiHidupPerJenisPerProdukReportService
{
    /**
     * @var array<int, string>
     */
    private const EXPECTED_COLUMNS = [
        'Jenis',
        'NamaBarangJadi',
        'Tebal',
        'Lebar',
        'Panjang',
        'Pcs',
        'M3',
    ];

    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetch(): array
    {
        $rows = $this->runProcedureQuery();

        return $this->normalizeReportRows(array_map(static fn($row): array => (array) $row, $rows));
    }

    /**
     * @return array<string, mixed>
     */
    public function buildReportData(): array
    {
        $rows = $this->fetch();

        if ($rows === []) {
            return [
                'groups' => [],
                'summary' => [
                    'total_rows' => 0,
                    'total_jenis' => 0,
                    'total_produk' => 0,
                    'total_pcs' => 0,
                    'total_m3' => 0.0,
                ],
            ];
        }

        $groups = [];
        $totalProduk = 0;
        $totalPcs = 0;
        $totalM3 = 0.0;

        foreach ($rows as $row) {
            $jenis = trim((string) ($row['Jenis'] ?? ''));
            $jenis = $jenis !== '' ? $jenis : 'LAINNYA';
            $produk = trim((string) ($row['NamaBarangJadi'] ?? ''));
            $produk = $produk !== '' ? $produk : '-';

            if (!isset($groups[$jenis])) {
                $groups[$jenis] = [
                    'name' => $jenis,
                    'products' => [],
                    'total_pcs' => 0,
                    'total_m3' => 0.0,
                ];
            }

            if (!isset($groups[$jenis]['products'][$produk])) {
                $groups[$jenis]['products'][$produk] = [
                    'name' => $produk,
                    'rows' => [],
                    'total_pcs' => 0,
                    'total_m3' => 0.0,
                ];
                $totalProduk++;
            }

            $pcs = $this->toInt($row['Pcs'] ?? null);
            $m3 = $this->toFloat($row['M3'] ?? null);

            $groups[$jenis]['products'][$produk]['rows'][] = [
                'Tebal' => $this->toFloat($row['Tebal'] ?? null),
                'Lebar' => $this->toFloat($row['Lebar'] ?? null),
                'Panjang' => $this->toFloat($row['Panjang'] ?? null),
                'Pcs' => $pcs,
                'M3' => $m3,
            ];
            $groups[$jenis]['products'][$produk]['total_pcs'] += $pcs;
            $groups[$jenis]['products'][$produk]['total_m3'] += $m3;
            $groups[$jenis]['total_pcs'] += $pcs;
            $groups[$jenis]['total_m3'] += $m3;
            $totalPcs += $pcs;
            $totalM3 += $m3;
        }

        ksort($groups, SORT_NATURAL | SORT_FLAG_CASE);
        foreach ($groups as &$jenisGroup) {
            ksort($jenisGroup['products'], SORT_NATURAL | SORT_FLAG_CASE);
            foreach ($jenisGroup['products'] as &$productGroup) {
                usort($productGroup['rows'], static function (array $a, array $b): int {
                    foreach (['Tebal', 'Lebar', 'Panjang'] as $field) {
                        $cmp = (float) ($a[$field] ?? 0) <=> (float) ($b[$field] ?? 0);
                        if ($cmp !== 0) {
                            return $cmp;
                        }
                    }

                    return 0;
                });
            }
            unset($productGroup);
            $jenisGroup['products'] = array_values($jenisGroup['products']);
        }
        unset($jenisGroup);

        return [
            'groups' => array_values($groups),
            'summary' => [
                'total_rows' => count($rows),
                'total_jenis' => count($groups),
                'total_produk' => $totalProduk,
                'total_pcs' => $totalPcs,
                'total_m3' => $totalM3,
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
        $missingColumns = array_values(array_diff(self::EXPECTED_COLUMNS, $detectedColumns));
        $extraColumns = array_values(array_diff($detectedColumns, self::EXPECTED_COLUMNS));

        return [
            'is_healthy' => empty($missingColumns),
            'expected_columns' => self::EXPECTED_COLUMNS,
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
        $connectionName = config('reports.saldo_barang_jadi_hidup_per_jenis_per_produk.database_connection');
        $procedure = (string) config('reports.saldo_barang_jadi_hidup_per_jenis_per_produk.stored_procedure', 'SP_LapBJHidupPerProduk');
        $syntax = (string) config('reports.saldo_barang_jadi_hidup_per_jenis_per_produk.call_syntax', 'exec');
        $customQuery = config('reports.saldo_barang_jadi_hidup_per_jenis_per_produk.query');

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan saldo barang jadi hidup per-jenis per-produk belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan saldo barang jadi hidup per-jenis per-produk dikonfigurasi untuk SQL Server. '
                . 'Set SALDO_BARANG_JADI_HIDUP_PER_JENIS_PER_PRODUK_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException(
                    'SALDO_BARANG_JADI_HIDUP_PER_JENIS_PER_PRODUK_REPORT_QUERY belum diisi. '
                    . 'Isi query manual jika menggunakan SALDO_BARANG_JADI_HIDUP_PER_JENIS_PER_PRODUK_REPORT_CALL_SYNTAX=query.',
                );

            return $connection->select($query);
        }

        if (!preg_match('/^[A-Za-z0-9_$.]+$/', $procedure)) {
            throw new RuntimeException('Nama stored procedure tidak valid.');
        }

        $sql = match ($syntax) {
            'exec' => "SET NOCOUNT ON; EXEC {$procedure}",
            'call' => "CALL {$procedure}()",
            default => $driver === 'sqlsrv' ? "SET NOCOUNT ON; EXEC {$procedure}" : "CALL {$procedure}()",
        };

        return $connection->select($sql);
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    private function normalizeReportRows(array $rows): array
    {
        if ($rows === []) {
            return [];
        }

        $sample = $rows[0];
        $missingColumns = array_values(array_filter(
            self::EXPECTED_COLUMNS,
            static fn(string $column): bool => !array_key_exists($column, $sample),
        ));

        if ($missingColumns !== []) {
            throw new RuntimeException(
                'Output SP_LapBJHidupPerProduk tidak sesuai. Kolom tidak ditemukan: '
                . implode(', ', $missingColumns),
            );
        }

        $normalized = [];
        foreach ($rows as $row) {
            $entry = [];
            foreach (self::EXPECTED_COLUMNS as $column) {
                $entry[$column] = $row[$column] ?? null;
            }
            $normalized[] = $entry;
        }

        return $normalized;
    }

    private function toFloat(mixed $value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        return 0.0;
    }

    private function toInt(mixed $value): int
    {
        if (is_numeric($value)) {
            return (int) round((float) $value);
        }

        return 0;
    }
}

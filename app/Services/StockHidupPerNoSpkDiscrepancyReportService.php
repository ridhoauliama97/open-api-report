<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class StockHidupPerNoSpkDiscrepancyReportService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetch(string $tanggalAkhir): array
    {
        $rows = $this->runProcedureQuery($tanggalAkhir);

        return array_map(static fn($row): array => (array) $row, $rows);
    }

    /**
     * @return array<string, mixed>
     */
    public function buildReportData(string $tanggalAkhir): array
    {
        $rows = $this->fetch($tanggalAkhir);

        if ($rows === []) {
            return [
                'tanggal_akhir' => $tanggalAkhir,
                'categories' => [],
                'summary' => [
                    'total_rows' => 0,
                    'total_categories' => 0,
                    'total_spk' => 0,
                    'grand_total' => 0.0,
                ],
            ];
        }

        $first = $rows[0];
        $columns = array_keys($first);

        $kategoriCol = $this->pickColumn($columns, ['Kategori', 'Category']);
        $noSpkCol = $this->pickColumn($columns, ['NoSPK', 'NoSpk', 'SPK', 'No_SPK']);
        $jenisCol = $this->pickColumn($columns, ['Jenis', 'JenisKayu', 'NamaGrade', 'Grade']);
        $tebalCol = $this->pickColumn($columns, ['Tebal']);
        $lebarCol = $this->pickColumn($columns, ['Lebar']);
        $panjangCol = $this->pickColumn($columns, ['Panjang']);
        $pcsCol = $this->pickColumn($columns, ['Pcs', 'PCS']);
        $umurCol = $this->pickColumn($columns, ['Umur', 'Aging']);
        $totalCol = $this->pickColumn($columns, ['Total', 'Ton', 'M3', 'Qty']);
        $buyerCol = $this->pickColumn($columns, ['Buyer']);

        $missing = array_values(array_filter([
            $kategoriCol ? null : 'Kategori',
            $noSpkCol ? null : 'NoSPK',
            $jenisCol ? null : 'Jenis',
            $tebalCol ? null : 'Tebal',
            $lebarCol ? null : 'Lebar',
            $panjangCol ? null : 'Panjang',
            $totalCol ? null : 'Total',
        ]));

        if ($missing !== []) {
            throw new RuntimeException(
                'Kolom wajib tidak ditemukan pada output SP_LapSemuaStockHidupPerSPK. '
                . 'Kolom terdeteksi: ' . implode(', ', $columns) . '. '
                . 'Kolom wajib: ' . implode(', ', $missing) . '.'
            );
        }

        $categories = [];
        $spkCount = 0;
        $grandTotal = 0.0;

        foreach ($rows as $row) {
            $kategori = trim((string) ($row[$kategoriCol] ?? ''));
            $kategori = $kategori !== '' ? $kategori : 'LAINNYA';

            $noSpk = trim((string) ($row[$noSpkCol] ?? ''));
            $noSpk = $noSpk !== '' ? $noSpk : '-';

            if (!isset($categories[$kategori])) {
                $categories[$kategori] = [
                    'name' => $kategori,
                    'spks' => [],
                    'total' => 0.0,
                ];
            }

            if (!isset($categories[$kategori]['spks'][$noSpk])) {
                $categories[$kategori]['spks'][$noSpk] = [
                    'no_spk' => $noSpk,
                    'buyer' => trim((string) ($buyerCol ? ($row[$buyerCol] ?? '') : '')),
                    'rows' => [],
                    'total' => 0.0,
                ];
                $spkCount++;
            }

            $total = (float) ($this->toFloat($row[$totalCol] ?? null) ?? 0.0);

            $categories[$kategori]['spks'][$noSpk]['rows'][] = [
                'Jenis' => trim((string) ($row[$jenisCol] ?? '')),
                'Tebal' => $this->toFloat($row[$tebalCol] ?? null),
                'Lebar' => $this->toFloat($row[$lebarCol] ?? null),
                'Panjang' => $this->toFloat($row[$panjangCol] ?? null),
                'Pcs' => $this->toFloat($pcsCol ? ($row[$pcsCol] ?? null) : null),
                'Umur' => $this->toFloat($umurCol ? ($row[$umurCol] ?? null) : null),
                'Total' => $total,
            ];

            $categories[$kategori]['spks'][$noSpk]['total'] += $total;
            $categories[$kategori]['total'] += $total;
            $grandTotal += $total;
        }

        ksort($categories, SORT_NATURAL | SORT_FLAG_CASE);

        foreach ($categories as &$category) {
            uasort($category['spks'], static function (array $left, array $right): int {
                $leftNo = (string) ($left['no_spk'] ?? '');
                $rightNo = (string) ($right['no_spk'] ?? '');

                if ($leftNo === '-' && $rightNo !== '-') {
                    return 1;
                }

                if ($rightNo === '-' && $leftNo !== '-') {
                    return -1;
                }

                return strnatcasecmp($leftNo, $rightNo);
            });

            foreach ($category['spks'] as &$spk) {
                usort($spk['rows'], static function (array $a, array $b): int {
                    $jenis = strcmp((string) ($a['Jenis'] ?? ''), (string) ($b['Jenis'] ?? ''));
                    if ($jenis !== 0) {
                        return $jenis;
                    }

                    foreach (['Tebal', 'Lebar', 'Panjang'] as $field) {
                        $cmp = (float) ($a[$field] ?? 0) <=> (float) ($b[$field] ?? 0);
                        if ($cmp !== 0) {
                            return $cmp;
                        }
                    }

                    return 0;
                });
            }
            unset($spk);

            $category['spks'] = array_values($category['spks']);
        }
        unset($category);

        return [
            'tanggal_akhir' => $tanggalAkhir,
            'categories' => array_values($categories),
            'summary' => [
                'total_rows' => count($rows),
                'total_categories' => count($categories),
                'total_spk' => $spkCount,
                'grand_total' => $grandTotal,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function healthCheck(string $tanggalAkhir): array
    {
        $rows = $this->fetch($tanggalAkhir);
        $detectedColumns = array_keys($rows[0] ?? []);
        $expectedColumns = config('reports.stock_hidup_per_nospk_discrepancy.expected_columns', []);
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
     * @param array<int, string> $columns
     */
    private function pickColumn(array $columns, array $candidates): ?string
    {
        $set = array_fill_keys($columns, true);
        foreach ($candidates as $name) {
            if (isset($set[$name])) {
                return $name;
            }
        }

        return null;
    }

    /**
     * @return array<int, object>
     */
    private function runProcedureQuery(string $tanggalAkhir): array
    {
        $connectionName = config('reports.stock_hidup_per_nospk_discrepancy.database_connection');
        $procedure = (string) config('reports.stock_hidup_per_nospk_discrepancy.stored_procedure', 'SP_LapSemuaStockHidupPerSPK');
        $syntax = (string) config('reports.stock_hidup_per_nospk_discrepancy.call_syntax', 'exec');
        $customQuery = config('reports.stock_hidup_per_nospk_discrepancy.query');
        $usingMode = (int) config('reports.stock_hidup_per_nospk_discrepancy.using_mode', 1);

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan stock hidup per NoSPK (Discrepancy) belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan stock hidup per NoSPK (Discrepancy) dikonfigurasi untuk SQL Server. '
                . 'Set STOCK_HIDUP_PER_NOSPK_DISCREPANCY_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException(
                    'STOCK_HIDUP_PER_NOSPK_DISCREPANCY_REPORT_QUERY belum diisi. '
                    . 'Isi query manual jika menggunakan STOCK_HIDUP_PER_NOSPK_DISCREPANCY_REPORT_CALL_SYNTAX=query.',
                );

            return $connection->select($query, [$tanggalAkhir, $usingMode]);
        }

        if (!preg_match('/^[A-Za-z0-9_$.]+$/', $procedure)) {
            throw new RuntimeException('Nama stored procedure tidak valid.');
        }

        $sql = match ($syntax) {
            'exec' => "SET NOCOUNT ON; EXEC {$procedure} ?, ?",
            'call' => "CALL {$procedure}(?, ?)",
            default => $driver === 'sqlsrv' ? "SET NOCOUNT ON; EXEC {$procedure} ?, ?" : "CALL {$procedure}(?, ?)",
        };

        return $connection->select($sql, [$tanggalAkhir, $usingMode]);
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

<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class StHidupPerSpkReportService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetch(): array
    {
        $rows = $this->runProcedureQuery();

        return array_map(function ($row): array {
            $item = (array) $row;

            return $item;
        }, $rows);
    }

    /**
     * @return array<string, mixed>
     */
    public function buildReportData(): array
    {
        $rows = $this->fetch();

        if (empty($rows)) {
            return [
                'groups' => [],
                'summary' => [
                    'total_rows' => 0,
                    'total_jenis' => 0,
                    'total_produk' => 0,
                    'total_spk' => 0,
                ],
            ];
        }

        $first = $rows[0];
        $columns = array_keys($first);

        $jenisCol = $this->pickColumn($columns, ['JenisKayu', 'Jenis', 'Group', 'GroupKayu', 'GroupJenis']);
        $produkCol = $this->pickColumn($columns, ['Produk', 'NamaGrade', 'Grade', 'NamaProduk']);
        $spkCol = $this->pickColumn($columns, ['NoSPK', 'NoSpk', 'SPK', 'No_SPK']);
        $tebalCol = $this->pickColumn($columns, ['Tebal']);
        $lebarCol = $this->pickColumn($columns, ['Lebar']);
        $uomCol = $this->pickColumn($columns, ['UOM', 'Uom']);
        $basahCol = $this->pickColumn($columns, ['BasahTon', 'Basah', 'Basah_Ton']);
        $kdCol = $this->pickColumn($columns, ['KDTon', 'KD', 'KD_Ton']);
        $keringCol = $this->pickColumn($columns, ['KeringTon', 'Kering', 'Kering_Ton']);
        $totalCol = $this->pickColumn($columns, ['TotalTon', 'Total', 'Total_Ton', 'TonTotal']);

        $missing = array_values(array_filter([
            $jenisCol ? null : 'JenisKayu/Group',
            $produkCol ? null : 'Produk',
            $spkCol ? null : 'NoSPK',
            $tebalCol ? null : 'Tebal',
            $lebarCol ? null : 'Lebar',
            $uomCol ? null : 'UOM',
            $basahCol ? null : 'BasahTon',
            $kdCol ? null : 'KDTon',
            $keringCol ? null : 'KeringTon',
            $totalCol ? null : 'TotalTon',
        ]));

        if (!empty($missing)) {
            throw new RuntimeException(
                'Kolom wajib tidak ditemukan pada output SPWps_LapSTHidupPerProdukV2. '
                . 'Kolom terdeteksi: ' . implode(', ', $columns) . '. '
                . 'Kolom wajib: ' . implode(', ', $missing) . '.'
            );
        }

        $groups = [];
        $produkCount = 0;
        $spkCount = 0;

        foreach ($rows as $row) {
            $jenis = trim((string) ($row[$jenisCol] ?? ''));
            $jenis = $jenis !== '' ? $jenis : 'Tanpa Jenis';

            $produk = trim((string) ($row[$produkCol] ?? ''));
            $produk = $produk !== '' ? $produk : 'Tanpa Produk';

            $spk = trim((string) ($row[$spkCol] ?? ''));
            $spk = $spk !== '' ? $spk : '-';

            if (!isset($groups[$jenis])) {
                $groups[$jenis] = [
                    'name' => $jenis,
                    'products' => [],
                ];
            }

            if (!isset($groups[$jenis]['products'][$produk])) {
                $groups[$jenis]['products'][$produk] = [
                    'name' => $produk,
                    'spks' => [],
                ];
                $produkCount++;
            }

            if (!isset($groups[$jenis]['products'][$produk]['spks'][$spk])) {
                $groups[$jenis]['products'][$produk]['spks'][$spk] = [
                    'no_spk' => $spk,
                    'rows' => [],
                ];
                $spkCount++;
            }

            $groups[$jenis]['products'][$produk]['spks'][$spk]['rows'][] = [
                'Tebal' => $this->toFloat($row[$tebalCol] ?? null),
                'Lebar' => $this->toFloat($row[$lebarCol] ?? null),
                'UOM' => (string) ($row[$uomCol] ?? ''),
                'BasahTon' => (float) ($this->toFloat($row[$basahCol] ?? null) ?? 0.0),
                'KDTon' => (float) ($this->toFloat($row[$kdCol] ?? null) ?? 0.0),
                'KeringTon' => (float) ($this->toFloat($row[$keringCol] ?? null) ?? 0.0),
                'TotalTon' => (float) ($this->toFloat($row[$totalCol] ?? null) ?? 0.0),
            ];
        }

        ksort($groups, SORT_NATURAL | SORT_FLAG_CASE);
        foreach ($groups as &$jenisGroup) {
            ksort($jenisGroup['products'], SORT_NATURAL | SORT_FLAG_CASE);
            foreach ($jenisGroup['products'] as &$product) {
                ksort($product['spks'], SORT_NATURAL | SORT_FLAG_CASE);
                foreach ($product['spks'] as &$spkGroup) {
                    usort($spkGroup['rows'], static function (array $a, array $b): int {
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
        }
        unset($jenisGroup, $product, $spkGroup);

        $finalGroups = array_values(array_map(static function (array $g): array {
            $g['products'] = array_values(array_map(static function (array $p): array {
                $p['spks'] = array_values($p['spks']);
                return $p;
            }, $g['products']));
            return $g;
        }, $groups));

        return [
            'groups' => $finalGroups,
            'summary' => [
                'total_rows' => count($rows),
                'total_jenis' => count($groups),
                'total_produk' => $produkCount,
                'total_spk' => $spkCount,
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
        $expectedColumns = config('reports.st_hidup_per_spk.expected_columns', []);
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
    private function runProcedureQuery(): array
    {
        $connectionName = config('reports.st_hidup_per_spk.database_connection');
        $procedure = (string) config('reports.st_hidup_per_spk.stored_procedure', 'SPWps_LapSTHidupPerProdukV2');
        $syntax = (string) config('reports.st_hidup_per_spk.call_syntax', 'exec');
        $customQuery = config('reports.st_hidup_per_spk.query');

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan ST hidup per SPK belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan ST hidup per SPK dikonfigurasi untuk SQL Server. '
                . 'Set ST_HIDUP_PER_SPK_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException(
                    'ST_HIDUP_PER_SPK_REPORT_QUERY belum diisi. '
                    . 'Isi query manual jika menggunakan ST_HIDUP_PER_SPK_REPORT_CALL_SYNTAX=query.',
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

<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class KoordinatTanahReportService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetch(string $noSpk): array
    {
        $noSpk = trim($noSpk);
        if ($noSpk === '') {
            throw new RuntimeException('No SPK wajib diisi.');
        }

        $rows = DB::connection(config('reports.koordinat_tanah.database_connection'))
            ->select('SET NOCOUNT ON; EXEC SP_PrintCariKoordinatTanah ?', [$noSpk]);

        return array_values(array_map(static fn (object $row): array => (array) $row, $rows));
    }

    /**
     * @return array<string, mixed>
     */
    public function buildReportData(string $noSpk): array
    {
        $rows = $this->fetch($noSpk);
        $percentageRows = $this->fetchPercentageRows($noSpk);
        $header = $this->buildHeader($rows, $noSpk);
        $products = $this->buildProducts($rows);
        $lands = $this->buildLands($rows);
        $gpsPercentages = $this->buildGpsPercentages($percentageRows);

        return [
            'no_spk' => $noSpk,
            'header' => $header,
            'products' => $products,
            'lands' => $lands,
            'gps_percentages' => $gpsPercentages,
            'summary' => [
                'raw_rows' => count($rows),
                'product_rows' => count($products),
                'land_rows' => count($lands),
                'gps_percentage_rows' => count($gpsPercentages),
                'period_count' => count(array_unique(array_filter(array_map(static fn (array $row): string => trim((string) ($row['Periode'] ?? '')), $rows)))),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function healthCheck(string $noSpk): array
    {
        $rows = $this->fetch($noSpk);
        $detectedColumns = array_keys($rows[0] ?? []);
        $expectedColumns = config('reports.koordinat_tanah.expected_columns', []);
        $expectedColumns = is_array($expectedColumns) ? array_values($expectedColumns) : [];
        $missingColumns = array_values(array_diff($expectedColumns, $detectedColumns));
        $extraColumns = array_values(array_diff($detectedColumns, $expectedColumns));

        $percentageRows = $this->fetchPercentageRows($noSpk);
        $detectedPercentageColumns = array_keys($percentageRows[0] ?? []);
        $expectedPercentageColumns = config('reports.koordinat_tanah.expected_percentage_columns', []);
        $expectedPercentageColumns = is_array($expectedPercentageColumns) ? array_values($expectedPercentageColumns) : [];
        $missingPercentageColumns = array_values(array_diff($expectedPercentageColumns, $detectedPercentageColumns));
        $extraPercentageColumns = array_values(array_diff($detectedPercentageColumns, $expectedPercentageColumns));

        return [
            'is_healthy' => empty($missingColumns) && empty($missingPercentageColumns),
            'expected_columns' => $expectedColumns,
            'detected_columns' => $detectedColumns,
            'missing_columns' => $missingColumns,
            'extra_columns' => $extraColumns,
            'row_count' => count($rows),
            'expected_percentage_columns' => $expectedPercentageColumns,
            'detected_percentage_columns' => $detectedPercentageColumns,
            'missing_percentage_columns' => $missingPercentageColumns,
            'extra_percentage_columns' => $extraPercentageColumns,
            'percentage_row_count' => count($percentageRows),
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<string, mixed>
     */
    private function buildHeader(array $rows, string $noSpk): array
    {
        $first = $rows[0] ?? [];

        return [
            'NoSPK' => trim((string) ($first['NoSPK'] ?? $noSpk)),
            'Tanggal' => $first['Tanggal'] ?? null,
            'Buyer' => trim((string) ($first['Buyer'] ?? '')),
            'Tujuan' => trim((string) ($first['Tujuan'] ?? '')),
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    private function buildProducts(array $rows): array
    {
        $grouped = [];

        foreach ($rows as $row) {
            $key = implode('|', [
                trim((string) ($row['Jenis'] ?? '')),
                trim((string) ($row['NamaBarangJadi'] ?? '')),
                trim((string) ($row['Tebal'] ?? '')),
                trim((string) ($row['Lebar'] ?? '')),
                trim((string) ($row['Panjang'] ?? '')),
                trim((string) ($row['Bundle'] ?? '')),
                trim((string) ($row['PcsPerBundle'] ?? '')),
                trim((string) ($row['Keterangan'] ?? '')),
            ]);

            if ($key === '|||||||') {
                continue;
            }

            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'Jenis' => trim((string) ($row['Jenis'] ?? '')),
                    'NamaBarangJadi' => trim((string) ($row['NamaBarangJadi'] ?? '')),
                    'Tebal' => $this->toFloat($row['Tebal'] ?? null),
                    'Lebar' => $this->toFloat($row['Lebar'] ?? null),
                    'Panjang' => $this->toFloat($row['Panjang'] ?? null),
                    'Bundle' => $this->toFloat($row['Bundle'] ?? null),
                    'PcsPerBundle' => $this->toFloat($row['PcsPerBundle'] ?? null),
                    'Keterangan' => trim((string) ($row['Keterangan'] ?? '')),
                ];
            }
        }

        $products = array_values($grouped);
        usort($products, static function (array $left, array $right): int {
            foreach (['Jenis', 'NamaBarangJadi'] as $field) {
                $compare = strnatcasecmp((string) ($left[$field] ?? ''), (string) ($right[$field] ?? ''));
                if ($compare !== 0) {
                    return $compare;
                }
            }

            foreach (['Tebal', 'Lebar', 'Panjang'] as $field) {
                $compare = ((float) ($left[$field] ?? 0)) <=> ((float) ($right[$field] ?? 0));
                if ($compare !== 0) {
                    return $compare;
                }
            }

            return 0;
        });

        return $products;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    private function buildLands(array $rows): array
    {
        $grouped = [];

        foreach ($rows as $row) {
            $key = implode('|', [
                trim((string) ($row['NamaTanah'] ?? '')),
                trim((string) ($row['NamaPemilik'] ?? '')),
                trim((string) ($row['DesaKelurahan'] ?? '')),
                trim((string) ($row['KabupatenKota'] ?? '')),
                trim((string) ($row['Provinsi'] ?? '')),
                trim((string) ($row['NoSuratTanah'] ?? '')),
                trim((string) ($row['Luas'] ?? '')),
                trim((string) ($row['Koordinat'] ?? '')),
                trim((string) ($row['Periode'] ?? '')),
            ]);

            if ($key === '||||||||') {
                continue;
            }

            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'NamaTanah' => trim((string) ($row['NamaTanah'] ?? '')),
                    'NamaPemilik' => trim((string) ($row['NamaPemilik'] ?? '')),
                    'DesaKelurahan' => trim((string) ($row['DesaKelurahan'] ?? '')),
                    'KabupatenKota' => trim((string) ($row['KabupatenKota'] ?? '')),
                    'Provinsi' => trim((string) ($row['Provinsi'] ?? '')),
                    'NoSuratTanah' => trim((string) ($row['NoSuratTanah'] ?? '')),
                    'Luas' => $this->toFloat($row['Luas'] ?? null),
                    'Koordinat' => trim((string) ($row['Koordinat'] ?? '')),
                    'Periode' => $row['Periode'] ?? null,
                ];
            }
        }

        $lands = array_values($grouped);
        usort($lands, static function (array $left, array $right): int {
            $periodCompare = strnatcasecmp((string) ($left['Periode'] ?? ''), (string) ($right['Periode'] ?? ''));
            if ($periodCompare !== 0) {
                return $periodCompare;
            }

            $ownerCompare = strnatcasecmp((string) ($left['NamaPemilik'] ?? ''), (string) ($right['NamaPemilik'] ?? ''));
            if ($ownerCompare !== 0) {
                return $ownerCompare;
            }

            return strnatcasecmp((string) ($left['NamaTanah'] ?? ''), (string) ($right['NamaTanah'] ?? ''));
        });

        return $lands;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchPercentageRows(string $noSpk): array
    {
        $rows = DB::connection(config('reports.koordinat_tanah.database_connection'))
            ->select('SET NOCOUNT ON; EXEC SP_PrintPersentaseCariKoordinat ?', [$noSpk]);

        return array_values(array_map(static fn (object $row): array => (array) $row, $rows));
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    private function buildGpsPercentages(array $rows): array
    {
        return array_values(array_map(function (array $row): array {
            return [
                'Jenis' => trim((string) ($row['Jenis'] ?? '')),
                'Koordinat' => trim((string) ($row['Koordinat'] ?? '')),
                'NamaPemilik' => trim((string) ($row['NamaPemilik'] ?? '')),
                'Tahun' => trim((string) ($row['Tahun'] ?? '')),
                'Total' => round($this->toFloat($row['Total'] ?? null) ?? 0.0, 4),
                'Persen' => round($this->toFloat($row['Persen'] ?? null) ?? 0.0, 2),
            ];
        }, $rows));
    }

    private function toFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        if (!is_string($value)) {
            return null;
        }

        $normalized = str_replace([',', ' '], ['', ''], trim($value));

        return is_numeric($normalized) ? (float) $normalized : null;
    }
}

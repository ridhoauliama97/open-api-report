<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class PenerimaanKayuBulatKgReportService
{
    /**
     * @return array<string, mixed>
     */
    public function fetch(string $noKayuBulat): array
    {
        $configKey = 'reports.penerimaan_kayu_bulat_kg';
        $connection = DB::connection(config("{$configKey}.database_connection"));
        $procedure = (string) config("{$configKey}.stored_procedure", 'SP_PenKBInTon_KG');
        $syntax = (string) config("{$configKey}.call_syntax", 'exec');

        if ($noKayuBulat === '') {
            throw new RuntimeException('Nomor kayu bulat wajib diisi.');
        }

        if (! preg_match('/^[A-Za-z0-9._\\/-]+$/', $noKayuBulat)) {
            throw new RuntimeException('Nomor kayu bulat tidak valid.');
        }

        if ($procedure === '' || ! preg_match('/^[A-Za-z0-9_$.]+$/', $procedure)) {
            throw new RuntimeException('Stored procedure laporan penerimaan kayu bulat KG belum dikonfigurasi dengan benar.');
        }

        if ($syntax !== 'exec') {
            throw new RuntimeException('Laporan penerimaan kayu bulat KG saat ini hanya mendukung call syntax exec.');
        }

        try {
            $detailRows = $connection->select(
                "SET NOCOUNT ON; EXEC {$procedure} @NoKayuBulat = ?",
                [$noKayuBulat],
            );

            $headerRow = $connection->selectOne(
                <<<'SQL'
                SELECT TOP 1
                    kb.NoKayuBulat,
                    kb.NoPlat,
                    kb.NoTruk,
                    kb.DateCreate,
                    kb.Suket,
                    kg.Bruto,
                    kg.Tara,
                    kb.IdPengukuran,
                    supplier.NmSupplier AS SupplierUtama,
                    supplier_asal.NmSupplier AS SupplierAsalKayu,
                    jenis.Jenis AS JenisKayu,
                    jenis.Singkatan AS SingkatanJenisKayu,
                    pengukuran.Kategori AS KategoriPengukuran
                FROM KayuBulat_h kb
                LEFT JOIN KayuBulatKG_h kg ON kg.NoKayuBulat = kb.NoKayuBulat
                LEFT JOIN MstSupplier supplier ON supplier.IdSupplier = kb.IdSupplier
                LEFT JOIN MstSupplier supplier_asal ON supplier_asal.IdSupplier = kb.IdSupplierAsalKayu
                LEFT JOIN MstJenisKayu jenis ON jenis.IdJenisKayu = kb.IdJenisKayu
                LEFT JOIN MstGolPengukuran pengukuran ON pengukuran.IdPengukuran = kb.IdPengukuran
                WHERE kb.NoKayuBulat = ?
                SQL,
                [$noKayuBulat],
            );
        } catch (\Throwable $exception) {
            throw new RuntimeException('Gagal mengambil data laporan penerimaan kayu bulat KG: '.$exception->getMessage(), 0, $exception);
        }

        $rows = array_map(fn (object $row): array => $this->normalizeDetailRow((array) $row), $detailRows);

        if ($rows === []) {
            throw new RuntimeException("Data penerimaan kayu bulat KG tidak ditemukan untuk nomor {$noKayuBulat}.");
        }

        $header = $this->buildHeaderData(is_object($headerRow) ? (array) $headerRow : [], $rows, $noKayuBulat);
        $groupedRows = $this->buildGroupedRows($rows);

        return [
            'header' => $header,
            'groups' => $groupedRows['groups'],
            'summary' => $groupedRows['summary'],
            'rows' => $rows,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function healthCheck(string $noKayuBulat): array
    {
        $reportData = $this->fetch($noKayuBulat);
        $rows = is_array($reportData['rows'] ?? null) ? $reportData['rows'] : [];
        $detectedColumns = array_keys($rows[0] ?? []);
        $expectedColumns = config('reports.penerimaan_kayu_bulat_kg.expected_columns', []);
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
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function normalizeDetailRow(array $row): array
    {
        return [
            'NoKayuBulat' => trim((string) ($row['NoKayuBulat'] ?? '')),
            'NoUrut' => (int) ($row['NoUrut'] ?? 0),
            'NamaGrade' => trim((string) ($row['NamaGrade'] ?? '')),
            'JmlhBatang' => (int) round((float) ($row['JmlhBatang'] ?? 0)),
            'Berat' => (float) ($row['Berat'] ?? 0),
            'Bruto' => (float) ($row['Bruto'] ?? 0),
            'Tara' => (float) ($row['Tara'] ?? 0),
        ];
    }

    /**
     * @param  array<string, mixed>  $headerRow
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<string, mixed>
     */
    private function buildHeaderData(array $headerRow, array $rows, string $noKayuBulat): array
    {
        $firstRow = $rows[0] ?? [];
        $supplierAsal = trim((string) ($headerRow['SupplierAsalKayu'] ?? ''));
        $supplierUtama = trim((string) ($headerRow['SupplierUtama'] ?? ''));
        $supplierUtamaCompact = preg_replace('/\s+/', '', $supplierUtama) ?? $supplierUtama;
        $jenisKayu = trim((string) ($headerRow['JenisKayu'] ?? ''));
        $singkatanJenis = trim((string) ($headerRow['SingkatanJenisKayu'] ?? ''));
        $kategoriPengukuran = trim((string) ($headerRow['KategoriPengukuran'] ?? ''));
        $noTruk = trim((string) ($headerRow['NoTruk'] ?? ''));

        $supplierDisplayParts = array_filter([
            $supplierAsal,
            $supplierUtamaCompact !== '' ? '('.$supplierUtamaCompact.')' : '',
            trim(implode('', array_filter([
                $singkatanJenis,
                $kategoriPengukuran !== '' ? '-'.$kategoriPengukuran : '',
            ]))),
            $noTruk,
        ], static fn (string $value): bool => $value !== '');

        $supplierDisplay = implode(' ', $supplierDisplayParts);

        return [
            'no_kayu_bulat' => trim((string) ($headerRow['NoKayuBulat'] ?? $noKayuBulat)),
            'tanggal' => trim((string) ($headerRow['DateCreate'] ?? '')),
            'supplier' => $supplierDisplay !== '' ? $supplierDisplay : ($supplierAsal !== '' ? $supplierAsal : $supplierUtama),
            'jenis_kayu' => $jenisKayu,
            'no_plat' => trim((string) ($headerRow['NoPlat'] ?? '')),
            'no_suket' => trim((string) ($headerRow['Suket'] ?? '')),
            'bruto' => (float) ($headerRow['Bruto'] ?? ($firstRow['Bruto'] ?? 0)),
            'tara' => (float) ($headerRow['Tara'] ?? ($firstRow['Tara'] ?? 0)),
            'supplier_utama' => $supplierUtama,
            'supplier_asal' => $supplierAsal,
            'singkatan_jenis_kayu' => $singkatanJenis,
            'kategori_pengukuran' => $kategoriPengukuran,
            'no_truk' => $noTruk,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{groups: array<int, array<string, mixed>>, summary: array<string, int|float>}
     */
    private function buildGroupedRows(array $rows): array
    {
        $groups = [];
        $totalPcs = 0;
        $totalBerat = 0.0;

        foreach ($rows as $row) {
            $pcs = (int) ($row['JmlhBatang'] ?? 0);
            $berat = (float) ($row['Berat'] ?? 0);

            $groups[] = [
                'grade_name' => (string) ($row['NamaGrade'] ?? ''),
                'rows' => [[
                    'no' => (int) ($row['NoUrut'] ?? 0),
                    'pcs' => $pcs,
                    'berat' => $berat,
                ]],
                'totals' => [
                    'pcs' => $pcs,
                    'berat' => $berat,
                ],
            ];

            $totalPcs += $pcs;
            $totalBerat += $berat;
        }

        return [
            'groups' => $groups,
            'summary' => [
                'total_pcs' => $totalPcs,
                'total_berat' => $totalBerat,
            ],
        ];
    }
}

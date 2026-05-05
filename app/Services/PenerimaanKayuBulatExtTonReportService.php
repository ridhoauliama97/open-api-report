<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class PenerimaanKayuBulatExtTonReportService
{
    /**
     * @return array<string, mixed>
     */
    public function fetch(string $noKayuBulat): array
    {
        $configKey = 'reports.penerimaan_kayu_bulat_ext_ton';
        $connection = DB::connection(config("{$configKey}.database_connection"));
        $procedure = (string) config("{$configKey}.stored_procedure", 'SP_PenKBOutTon');
        $syntax = (string) config("{$configKey}.call_syntax", 'exec');

        if ($noKayuBulat === '') {
            throw new RuntimeException('Nomor kayu bulat wajib diisi.');
        }

        if (!preg_match('/^[A-Za-z0-9._\\/-]+$/', $noKayuBulat)) {
            throw new RuntimeException('Nomor kayu bulat tidak valid.');
        }

        if ($procedure === '' || !preg_match('/^[A-Za-z0-9_$.]+$/', $procedure)) {
            throw new RuntimeException('Stored procedure laporan penerimaan kayu bulat Ext Ton belum dikonfigurasi dengan benar.');
        }

        if ($syntax !== 'exec') {
            throw new RuntimeException('Laporan penerimaan kayu bulat Ext Ton saat ini hanya mendukung call syntax exec.');
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
                    kb.IdPengukuran,
                    supplier.NmSupplier AS SupplierUtama,
                    supplier_asal.NmSupplier AS SupplierAsalKayu,
                    jenis.Jenis AS JenisKayu,
                    jenis.Singkatan AS SingkatanJenisKayu,
                    pengukuran.Kategori AS KategoriPengukuran
                FROM KayuBulat_h kb
                LEFT JOIN MstSupplier supplier ON supplier.IdSupplier = kb.IdSupplier
                LEFT JOIN MstSupplier supplier_asal ON supplier_asal.IdSupplier = kb.IdSupplierAsalKayu
                LEFT JOIN MstJenisKayu jenis ON jenis.IdJenisKayu = kb.IdJenisKayu
                LEFT JOIN MstGolPengukuran pengukuran ON pengukuran.IdPengukuran = kb.IdPengukuran
                WHERE kb.NoKayuBulat = ?
                SQL,
                [$noKayuBulat],
            );
        } catch (\Throwable $exception) {
            throw new RuntimeException('Gagal mengambil data laporan penerimaan kayu bulat Ext Ton: ' . $exception->getMessage(), 0, $exception);
        }

        $rows = array_map(fn(object $row): array => $this->normalizeDetailRow((array) $row), $detailRows);

        if ($rows === []) {
            throw new RuntimeException("Data penerimaan kayu bulat Ext Ton tidak ditemukan untuk nomor {$noKayuBulat}.");
        }

        $header = $this->buildHeaderData(is_object($headerRow) ? (array) $headerRow : [], $noKayuBulat);

        return [
            'header' => $header,
            'rows' => $rows,
            'summary' => [
                'total_logs' => count($rows),
                'total_ton' => array_sum(array_map(static fn(array $row): float => (float) ($row['Ton'] ?? 0), $rows)),
            ],
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
        $expectedColumns = config('reports.penerimaan_kayu_bulat_ext_ton.expected_columns', []);
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
            'NoLog' => (int) ($row['NoLog'] ?? 0),
            'Tebal' => (float) ($row['Tebal'] ?? 0),
            'Lebar' => (float) ($row['Lebar'] ?? 0),
            'Panjang' => (float) ($row['Panjang'] ?? 0),
            'Ton' => (float) ($row['Ton'] ?? 0),
            'Ket' => trim((string) ($row['Ket'] ?? '')),
        ];
    }

    /**
     * @param  array<string, mixed>  $headerRow
     * @return array<string, mixed>
     */
    private function buildHeaderData(array $headerRow, string $noKayuBulat): array
    {
        $supplierAsal = trim((string) ($headerRow['SupplierAsalKayu'] ?? ''));
        $supplierUtama = trim((string) ($headerRow['SupplierUtama'] ?? ''));
        $supplierUtamaCompact = preg_replace('/\s+/', '', $supplierUtama) ?? $supplierUtama;
        $jenisKayu = trim((string) ($headerRow['JenisKayu'] ?? ''));
        $singkatanJenis = trim((string) ($headerRow['SingkatanJenisKayu'] ?? ''));
        $kategoriPengukuran = trim((string) ($headerRow['KategoriPengukuran'] ?? ''));
        $noTruk = trim((string) ($headerRow['NoTruk'] ?? ''));

        $supplierDisplayParts = array_filter([
            $supplierAsal !== '' ? $supplierAsal : $supplierUtama,
            $supplierAsal !== '' && $supplierUtamaCompact !== '' ? '(' . $supplierUtamaCompact . ')' : '',
            trim(implode('', array_filter([
                $singkatanJenis,
                $kategoriPengukuran !== '' ? '-' . $kategoriPengukuran : '',
            ]))),
            $noTruk,
        ], static fn(string $value): bool => $value !== '');

        return [
            'no_kayu_bulat' => trim((string) ($headerRow['NoKayuBulat'] ?? $noKayuBulat)),
            'tanggal' => trim((string) ($headerRow['DateCreate'] ?? '')),
            'supplier' => implode(' ', $supplierDisplayParts),
            'jenis_kayu' => $jenisKayu,
            'no_plat' => trim((string) ($headerRow['NoPlat'] ?? '')),
            'no_suket' => trim((string) ($headerRow['Suket'] ?? '')),
            'supplier_utama' => $supplierUtama,
            'supplier_asal' => $supplierAsal,
            'singkatan_jenis_kayu' => $singkatanJenis,
            'kategori_pengukuran' => $kategoriPengukuran,
            'no_truk' => $noTruk,
        ];
    }
}

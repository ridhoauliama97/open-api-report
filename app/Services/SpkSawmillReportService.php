<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class SpkSawmillReportService
{
    /**
     * @return array<string, mixed>
     */
    public function buildReportData(string $noSpk, int $idProduk): array
    {
        $headerRows = $this->fetchHeader($noSpk, $idProduk);
        $detailRows = $this->fetchDetails($noSpk, $idProduk);

        if ($headerRows === []) {
            throw new RuntimeException('Data header laporan SPK Sawmill tidak ditemukan untuk No SPK dan Id Produk yang dipilih.');
        }

        $firstHeader = $headerRows[0];

        return [
            'header_rows' => $headerRows,
            'detail_rows' => $detailRows,
            'header' => [
                'no_spk' => (string) ($firstHeader['NoSPK'] ?? $noSpk),
                'tanggal' => (string) ($firstHeader['Tanggal'] ?? ''),
                'id_produk' => (int) ($firstHeader['IdProdukSPK'] ?? $idProduk),
                'produk' => (string) ($firstHeader['NamaProduk'] ?? ''),
                'jenis_kayu' => (string) ($firstHeader['NamaGroup'] ?? ''),
                'permintaan_racip' => (float) ($firstHeader['Ton'] ?? 0),
            ],
            'summary' => [
                'header_rows' => count($headerRows),
                'detail_rows' => count($detailRows),
                'total_racip' => array_sum(array_map(static fn(array $row): float => (float) ($row['Ton'] ?? 0), $detailRows)),
                'saldo_terakhir' => $detailRows === [] ? 0.0 : (float) ($detailRows[array_key_last($detailRows)]['SaldoTerakhir'] ?? 0),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function healthCheck(string $noSpk, int $idProduk): array
    {
        $headerRows = $this->fetchHeader($noSpk, $idProduk);
        $detailRows = $this->fetchDetails($noSpk, $idProduk);

        $expectedHeaderColumns = config('reports.spk_sawmill.expected_header_columns', []);
        $expectedDetailColumns = config('reports.spk_sawmill.expected_detail_columns', []);
        $expectedHeaderColumns = is_array($expectedHeaderColumns) ? array_values($expectedHeaderColumns) : [];
        $expectedDetailColumns = is_array($expectedDetailColumns) ? array_values($expectedDetailColumns) : [];

        $detectedHeaderColumns = array_keys($headerRows[0] ?? []);
        $detectedDetailColumns = array_keys($detailRows[0] ?? []);

        return [
            'is_healthy' => empty(array_diff($expectedHeaderColumns, $detectedHeaderColumns))
                && ($detailRows === [] || empty(array_diff($expectedDetailColumns, $detectedDetailColumns))),
            'header' => [
                'expected_columns' => $expectedHeaderColumns,
                'detected_columns' => $detectedHeaderColumns,
                'missing_columns' => array_values(array_diff($expectedHeaderColumns, $detectedHeaderColumns)),
                'extra_columns' => array_values(array_diff($detectedHeaderColumns, $expectedHeaderColumns)),
                'row_count' => count($headerRows),
            ],
            'detail' => [
                'expected_columns' => $expectedDetailColumns,
                'detected_columns' => $detectedDetailColumns,
                'missing_columns' => array_values(array_diff($expectedDetailColumns, $detectedDetailColumns)),
                'extra_columns' => array_values(array_diff($detectedDetailColumns, $expectedDetailColumns)),
                'row_count' => count($detailRows),
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetchHeader(string $noSpk, int $idProduk): array
    {
        $rows = $this->runProcedureQuery('header', $noSpk, $idProduk);

        $normalized = array_map(fn(object $row): array => $this->normalizeHeaderRow((array) $row), $rows);
        usort($normalized, static function (array $a, array $b): int {
            $tebalCompare = ((float) ($a['Tebal'] ?? 0)) <=> ((float) ($b['Tebal'] ?? 0));

            return $tebalCompare !== 0
                ? $tebalCompare
                : ((float) ($a['Lebar'] ?? 0)) <=> ((float) ($b['Lebar'] ?? 0));
        });

        return array_values($normalized);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetchDetails(string $noSpk, int $idProduk): array
    {
        $rows = $this->runProcedureQuery('detail', $noSpk, $idProduk);

        $normalized = array_map(fn(object $row): array => $this->normalizeDetailRow((array) $row), $rows);
        usort($normalized, static fn(array $a, array $b): int => strcmp((string) ($a['TglSawmill'] ?? ''), (string) ($b['TglSawmill'] ?? '')));

        return array_values($normalized);
    }

    /**
     * @return array<int, object>
     */
    private function runProcedureQuery(string $section, string $noSpk, int $idProduk): array
    {
        $configKey = 'reports.spk_sawmill';
        $connectionName = config("{$configKey}.database_connection");
        $procedure = (string) config("{$configKey}.{$section}_stored_procedure");
        $syntax = (string) config("{$configKey}.call_syntax", 'exec');
        $customQuery = config("{$configKey}.{$section}_query");

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan SPK Sawmill belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan SPK Sawmill dikonfigurasi untuk SQL Server. '
                . 'Set SPK_SAWMILL_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException('Query manual laporan SPK Sawmill belum diisi.');

            return $connection->select($query, str_contains($query, '?') ? [$noSpk, $idProduk] : []);
        }

        if (!preg_match('/^[A-Za-z0-9_$.]+$/', $procedure)) {
            throw new RuntimeException('Nama stored procedure laporan SPK Sawmill tidak valid.');
        }

        $sql = match ($syntax) {
            'exec' => "SET NOCOUNT ON; EXEC {$procedure} @NoSPK = ?, @IdProduk = ?",
            'call' => "CALL {$procedure}(?, ?)",
            default => $driver === 'sqlsrv'
                ? "SET NOCOUNT ON; EXEC {$procedure} @NoSPK = ?, @IdProduk = ?"
                : "CALL {$procedure}(?, ?)",
        };

        try {
            return $connection->select($sql, [$noSpk, $idProduk]);
        } catch (\Throwable $exception) {
            throw new RuntimeException('Gagal mengambil data laporan SPK Sawmill: ' . $exception->getMessage(), 0, $exception);
        }
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function normalizeHeaderRow(array $row): array
    {
        return [
            'NoSPK' => trim((string) ($row['NoSPK'] ?? '')),
            'Tanggal' => trim((string) ($row['Tanggal'] ?? '')),
            'IdProdukSPK' => (int) round($this->toFloat($row['IdProdukSPK'] ?? null) ?? 0),
            'NamaProduk' => trim((string) ($row['NamaProduk'] ?? '')),
            'NamaGroup' => trim((string) ($row['NamaGroup'] ?? '')),
            'Tebal' => $this->toFloat($row['Tebal'] ?? null) ?? 0.0,
            'Lebar' => $this->toFloat($row['Lebar'] ?? null) ?? 0.0,
            'Ton' => $this->toFloat($row['Ton'] ?? null) ?? 0.0,
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function normalizeDetailRow(array $row): array
    {
        return [
            'NoSPK' => trim((string) ($row['NoSPK'] ?? '')),
            'IdProdukSPK' => (int) round($this->toFloat($row['IdProdukSPK'] ?? null) ?? 0),
            'TglSawmill' => trim((string) ($row['TglSawmill'] ?? '')),
            'Ton' => $this->toFloat($row['Ton'] ?? null) ?? 0.0,
            'SaldoTerakhir' => $this->toFloat($row['SaldoTerakhir'] ?? null) ?? 0.0,
        ];
    }

    private function toFloat(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        if (!is_string($value)) {
            return null;
        }

        $normalized = trim(str_replace(' ', '', $value));
        if ($normalized === '') {
            return null;
        }

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

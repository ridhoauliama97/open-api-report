<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SuratJalanReportService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetch(string $noJual): array
    {
        return array_values(array_map(static fn (object $row): array => (array) $row, $this->runProcedureQuery($noJual)));
    }

    /**
     * @return array<string, mixed>
     */
    public function buildReportData(string $noJual): array
    {
        $rows = $this->normalizeRows($this->fetch($noJual));

        if ($rows === []) {
            throw new RuntimeException('Data surat jalan tidak ditemukan untuk No Jual yang dipilih.');
        }

        return [
            'no_jual' => $noJual,
            'rows' => $rows,
            'header' => $this->buildHeader($rows[0], $noJual),
            'summary' => $this->buildSummary($rows),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function healthCheck(string $noJual): array
    {
        $rows = $this->fetch($noJual);
        $detectedColumns = array_keys($rows[0] ?? []);
        $expectedColumns = config('reports.surat_jalan.expected_columns', []);
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
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function normalizeRows(array $rows): array
    {
        $normalized = [];
        $previousDate = null;

        foreach ($rows as $row) {
            $tanggal = $this->formatDateValue($row['DateCreate'] ?? null);

            $normalized[] = [
                'Tanggal' => $tanggal,
                'DisplayTanggal' => $tanggal !== $previousDate ? $tanggal : '',
                'NoST' => trim((string) ($row['NoST'] ?? '')),
                'JenisKayu' => trim((string) ($row['Jenis'] ?? '')),
                'Tebal' => $this->toFloat($row['Tebal'] ?? null),
                'Lebar' => $this->toFloat($row['Lebar'] ?? null),
                'UOMTblLebar' => trim((string) ($row['UOMTblLebar'] ?? '')),
                'Panjang' => $this->toFloat($row['Panjang'] ?? null),
                'UOMPanjang' => trim((string) ($row['UOMPanjang'] ?? '')),
                'Pcs' => (int) round($this->toFloat($row['JmlhBatang'] ?? null) ?? 0.0),
                'M3' => (float) ($this->toFloat($row['M3'] ?? null) ?? 0.0),
                'Ton' => (float) ($this->toFloat($row['Ton'] ?? null) ?? 0.0),
                'TglJual' => $this->formatDateValue($row['TglJual'] ?? null),
                'NoSJ' => trim((string) ($row['NoSJ'] ?? '')),
                'NoPlat' => trim((string) ($row['NoPlat'] ?? '')),
                'Buyer' => trim((string) ($row['Buyer'] ?? '')),
                'JenisKendaraan' => trim((string) ($row['JenisKendaraan'] ?? '')),
            ];

            $previousDate = $tanggal;
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, string>
     */
    private function buildHeader(array $row, string $noJual): array
    {
        return [
            'tanggal' => (string) ($row['TglJual'] ?: $row['Tanggal'] ?: ''),
            'no_surat_jalan' => (string) ($row['NoSJ'] ?: $noJual),
            'buyer' => (string) ($row['Buyer'] ?: '-'),
            'no_plat' => (string) ($row['NoPlat'] ?: '-'),
            'jenis_kendaraan' => (string) ($row['JenisKendaraan'] ?: '-'),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<string, mixed>
     */
    private function buildSummary(array $rows): array
    {
        return [
            'total_rows' => count($rows),
            'total_pcs' => array_sum(array_map(static fn (array $row): int => (int) ($row['Pcs'] ?? 0), $rows)),
            'total_m3' => round(array_sum(array_map(static fn (array $row): float => (float) ($row['M3'] ?? 0.0), $rows)), 4),
            'total_ton' => round(array_sum(array_map(static fn (array $row): float => (float) ($row['Ton'] ?? 0.0), $rows)), 4),
        ];
    }

    /**
     * @return array<int, object>
     */
    private function runProcedureQuery(string $noJual): array
    {
        $configKey = 'reports.surat_jalan';
        $connectionName = config("{$configKey}.database_connection");
        $procedure = (string) config("{$configKey}.stored_procedure", 'SP_CetakSuratjalan');
        $syntax = (string) config("{$configKey}.call_syntax", 'exec');
        $customQuery = config("{$configKey}.query");

        if ($procedure === '' && ! is_string($customQuery)) {
            throw new RuntimeException('Stored procedure surat jalan belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan surat jalan dikonfigurasi untuk SQL Server. '
                .'Set SURAT_JALAN_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException('Query manual surat jalan belum diisi.');

            return $connection->select($query, str_contains($query, '?') ? [$noJual] : []);
        }

        if (! preg_match('/^[A-Za-z0-9_$.]+$/', $procedure)) {
            throw new RuntimeException('Nama stored procedure tidak valid.');
        }

        $sql = match ($syntax) {
            'exec' => "SET NOCOUNT ON; EXEC {$procedure} ?",
            'call' => "CALL {$procedure}(?)",
            default => $driver === 'sqlsrv' ? "SET NOCOUNT ON; EXEC {$procedure} ?" : "CALL {$procedure}(?)",
        };

        try {
            return $connection->select($sql, [$noJual]);
        } catch (\Throwable $exception) {
            throw new RuntimeException('Gagal mengambil data surat jalan: '.$exception->getMessage(), 0, $exception);
        }
    }

    private function formatDateValue(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return trim((string) $value);
        }
    }

    private function toFloat(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        if (! is_string($value)) {
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

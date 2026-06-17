<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class StokOpnameStDetailKdReportService
{
    /**
     * @return array<string, mixed>
     */
    public function buildReportData(string $noProcKd): array
    {
        $rows = $this->fetch($noProcKd);

        if ($rows === []) {
            throw new RuntimeException("Data Stok Opname ST Detail Pada KD tidak ditemukan untuk NoProcKD {$noProcKd}.");
        }

        $summary = [
            'total_rows' => count($rows),
            'total_pcs' => array_sum(array_map(static fn (array $row): int => (int) ($row['JmlhBatang'] ?? 0), $rows)),
            'total_ton' => array_sum(array_map(static fn (array $row): float => (float) ($row['Ton'] ?? 0.0), $rows)),
            'total_no_st' => count(array_unique(array_filter(array_map(static fn (array $row): string => (string) ($row['NoST'] ?? ''), $rows)))),
        ];

        return [
            'no_proc_kd' => $noProcKd,
            'header' => $this->fetchHeader($noProcKd),
            'rows' => $rows,
            'summary' => $summary,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function healthCheck(string $noProcKd): array
    {
        $rows = $this->fetch($noProcKd);
        $detectedColumns = array_keys($rows[0] ?? []);
        $expectedColumns = config('reports.stok_opname_st_detail_kd.expected_columns', []);
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
     * @return array<int, array<string, mixed>>
     */
    private function fetch(string $noProcKd): array
    {
        $rows = $this->runProcedureQuery($noProcKd);

        return array_map(function (object $row): array {
            $item = (array) $row;

            return [
                'NoST' => trim((string) ($item['NoST'] ?? '')),
                'DateCreate' => trim((string) ($item['DateCreate'] ?? '')),
                'Jenis' => trim((string) ($item['Jenis'] ?? '')),
                'NoKayuBulat' => trim((string) ($item['NoKayuBulat'] ?? '')),
                'Tebal' => $this->toFloat($item['Tebal'] ?? null) ?? 0.0,
                'Lebar' => $this->toFloat($item['Lebar'] ?? null) ?? 0.0,
                'Panjang' => $this->toFloat($item['Panjang'] ?? null) ?? 0.0,
                'JmlhBatang' => (int) round($this->toFloat($item['JmlhBatang'] ?? null) ?? 0.0),
                'UOMLebar' => trim((string) ($item['UOMLebar'] ?? '')),
                'UOMPanjang' => trim((string) ($item['UOMPanjang'] ?? '')),
                'Ton' => $this->toFloat($item['Ton'] ?? null) ?? 0.0,
            ];
        }, $rows);
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchHeader(string $noProcKd): array
    {
        $connectionName = config('reports.stok_opname_st_detail_kd.database_connection');
        $connection = DB::connection($connectionName ?: null);

        try {
            $header = $connection->selectOne(
                'SELECT TOP 1 NoProcKD, NoRuangKD, TglMasuk, TglKeluar FROM dbo.KD_h WHERE NoProcKD = ?',
                [$noProcKd],
            );
        } catch (\Throwable) {
            $header = null;
        }

        $data = (array) ($header ?? []);

        return [
            'NoProcKD' => trim((string) ($data['NoProcKD'] ?? $noProcKd)),
            'NoRuangKD' => $data['NoRuangKD'] ?? null,
            'TglMasuk' => trim((string) ($data['TglMasuk'] ?? '')),
            'TglKeluar' => trim((string) ($data['TglKeluar'] ?? '')),
        ];
    }

    /**
     * @return array<int, object>
     */
    private function runProcedureQuery(string $noProcKd): array
    {
        $configKey = 'reports.stok_opname_st_detail_kd';
        $connectionName = config("{$configKey}.database_connection");
        $procedure = (string) config("{$configKey}.stored_procedure", 'SP_LapStokOpnameSTDetail');
        $syntax = (string) config("{$configKey}.call_syntax", 'exec');
        $customQuery = config("{$configKey}.query");

        if ($procedure === '' && ! is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan Stok Opname ST Detail Pada KD belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan Stok Opname ST Detail Pada KD dikonfigurasi untuk SQL Server. '
                .'Set STOK_OPNAME_ST_DETAIL_KD_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException('Query manual laporan Stok Opname ST Detail Pada KD belum diisi.');

            return $connection->select($query, str_contains($query, '?') ? [$noProcKd] : []);
        }

        if (! preg_match('/^[A-Za-z0-9_$.]+$/', $procedure)) {
            throw new RuntimeException('Nama stored procedure laporan Stok Opname ST Detail Pada KD tidak valid.');
        }

        $sql = match ($syntax) {
            'exec' => "SET NOCOUNT ON; EXEC {$procedure} @NoProcKD = ?",
            'call' => "CALL {$procedure}(?)",
            default => $driver === 'sqlsrv'
                ? "SET NOCOUNT ON; EXEC {$procedure} @NoProcKD = ?"
                : "CALL {$procedure}(?)",
        };

        try {
            return $connection->select($sql, [$noProcKd]);
        } catch (\Throwable $exception) {
            throw new RuntimeException('Gagal mengambil data laporan Stok Opname ST Detail Pada KD: '.$exception->getMessage(), 0, $exception);
        }
    }

    private function toFloat(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }

        if (is_int($value) || is_float($value)) {
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

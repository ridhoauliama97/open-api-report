<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TracingStReportService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetch(string $noProduk): array
    {
        return $this->normalizeRows($this->runProcedureQuery($noProduk));
    }

    /**
     * @return array<string, mixed>
     */
    public function buildReportData(string $noProduk): array
    {
        $rows = $this->fetch($noProduk);

        if ($rows === []) {
            throw new RuntimeException("Data tracing ST tidak ditemukan untuk No Produk {$noProduk}.");
        }

        return [
            'no_produk' => $noProduk,
            'rows' => $rows,
            'summary' => [
                'total_rows' => count($rows),
                'suppliers' => array_values(array_unique(array_filter(array_map(
                    static fn (array $row): string => (string) ($row['NmSupplier'] ?? ''),
                    $rows
                )))),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function healthCheck(string $noProduk): array
    {
        $rows = $this->fetch($noProduk);
        $detectedColumns = array_keys($rows[0] ?? []);
        $expectedColumns = config('reports.tracing_st.expected_columns', []);
        $expectedColumns = is_array($expectedColumns) ? array_values($expectedColumns) : [];

        return [
            'is_healthy' => empty(array_diff($expectedColumns, $detectedColumns)),
            'expected_columns' => $expectedColumns,
            'detected_columns' => $detectedColumns,
            'missing_columns' => array_values(array_diff($expectedColumns, $detectedColumns)),
            'extra_columns' => array_values(array_diff($detectedColumns, $expectedColumns)),
            'row_count' => count($rows),
        ];
    }

    /**
     * @param  array<int, object>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function normalizeRows(array $rows): array
    {
        return array_map(function (object $row): array {
            $item = (array) $row;

            return [
                'NoKayuBulat' => trim((string) ($item['NoKayuBulat'] ?? '')),
                'NoST' => trim((string) ($item['NoST'] ?? '')),
                'NmSupplier' => trim((string) ($item['NmSupplier'] ?? '')),
                'NoTruk' => $this->toInt($item['NoTruk'] ?? null),
                'TglMasuk' => $this->formatDate($item['TglMasuk'] ?? null),
                'TglMulai' => $this->formatDate($item['TglMulai'] ?? null),
                'UT' => $this->toInt($item['UT'] ?? null),
                'TglSelesai' => $this->formatDate($item['TglSelesai'] ?? null),
                'UR' => $this->toInt($item['UR'] ?? null),
                'TglStick' => $this->formatDate($item['TglStick'] ?? null),
                'U-Stick' => $this->toInt($item['U-Stick'] ?? null),
                'BalokToStick' => $this->toInt($item['BalokToStick'] ?? null),
                'TglMasukKD' => $this->formatDate($item['TglMasukKD'] ?? null),
                'UT-KD' => $this->toInt($item['UT-KD'] ?? null),
                'TglKeluar' => $this->formatDate($item['TglKeluar'] ?? null),
                'LamaKD' => $this->toInt($item['LamaKD'] ?? null),
            ];
        }, $rows);
    }

    /**
     * @return array<int, object>
     */
    private function runProcedureQuery(string $noProduk): array
    {
        $configKey = 'reports.tracing_st';
        $connectionName = config("{$configKey}.database_connection");
        $procedure = (string) config("{$configKey}.stored_procedure", 'SP_LapTracingST');
        $syntax = (string) config("{$configKey}.call_syntax", 'exec');
        $customQuery = config("{$configKey}.query");

        if ($procedure === '' && ! is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan tracing ST belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan tracing ST dikonfigurasi untuk SQL Server. '
                .'Set TRACING_ST_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException('Query manual laporan tracing ST belum diisi.');

            return $connection->select($query, str_contains($query, '?') ? [$noProduk] : []);
        }

        if (! preg_match('/^[A-Za-z0-9_$.]+$/', $procedure)) {
            throw new RuntimeException('Nama stored procedure tidak valid.');
        }

        $sql = match ($syntax) {
            'exec' => "SET NOCOUNT ON; EXEC {$procedure} @NoProduk = ?",
            'call' => "CALL {$procedure}(?)",
            default => $driver === 'sqlsrv' ? "SET NOCOUNT ON; EXEC {$procedure} @NoProduk = ?" : "CALL {$procedure}(?)",
        };

        try {
            return $connection->select($sql, [$noProduk]);
        } catch (\Throwable $exception) {
            throw new RuntimeException('Gagal mengambil data laporan tracing ST: '.$exception->getMessage(), 0, $exception);
        }
    }

    private function formatDate(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        try {
            return Carbon::parse($value)->locale('id')->translatedFormat('d-M-y');
        } catch (\Throwable) {
            return trim((string) $value);
        }
    }

    private function toInt(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }
}

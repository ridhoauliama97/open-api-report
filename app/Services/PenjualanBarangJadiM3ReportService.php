<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PenjualanBarangJadiM3ReportService
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
            throw new RuntimeException('Data penjualan barang jadi tidak ditemukan untuk No Jual yang dipilih.');
        }

        return [
            'no_jual' => $noJual,
            'rows' => $rows,
            'header' => $this->buildHeader($rows[0]),
            'jenis_groups' => $this->buildJenisGroups($rows),
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
        $expectedColumns = config('reports.penjualan_barang_jadi_m3.expected_columns', []);
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

        foreach ($rows as $index => $row) {
            $normalized[] = [
                'No' => $index + 1,
                'NoBJJual' => trim((string) ($row['NoBJJual'] ?? '')),
                'TglJual' => $this->formatDateValue($row['TglJual'] ?? null),
                'NoSPK' => trim((string) ($row['NoSPK'] ?? '')),
                'Buyer' => trim((string) ($row['Buyer'] ?? '')),
                'NamaBarangJadi' => trim((string) ($row['NamaBarangJadi'] ?? '')),
                'Keterangan' => trim((string) ($row['Keterangan'] ?? '')),
                'Tebal' => $this->toFloat($row['Tebal'] ?? null),
                'Lebar' => $this->toFloat($row['Lebar'] ?? null),
                'Panjang' => $this->toFloat($row['Panjang'] ?? null),
                'Pcs' => (int) round($this->toFloat($row['JmlhBatang'] ?? null) ?? 0.0),
                'M3' => (float) ($this->toFloat($row['M3'] ?? null) ?? 0.0),
                'Jenis' => trim((string) ($row['Jenis'] ?? '')),
            ];
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, string>
     */
    private function buildHeader(array $row): array
    {
        return [
            'tanggal' => (string) ($row['TglJual'] ?? ''),
            'no_spk' => (string) ($row['NoSPK'] ?? '-'),
            'buyer' => (string) ($row['Buyer'] ?? '-'),
            'no_bj_jual' => (string) ($row['NoBJJual'] ?? '-'),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function buildJenisGroups(array $rows): array
    {
        $groups = [];

        foreach ($rows as $row) {
            $jenis = (string) ($row['Jenis'] !== '' ? $row['Jenis'] : 'Tanpa Jenis');
            $namaBarangJadi = (string) ($row['NamaBarangJadi'] !== '' ? $row['NamaBarangJadi'] : 'Tanpa Nama');

            if (! isset($groups[$jenis])) {
                $groups[$jenis] = [
                    'jenis' => $jenis,
                    'rows' => [],
                    'product_totals' => [],
                    'total_m3' => 0.0,
                ];
            }

            $groups[$jenis]['rows'][] = $row;
            $groups[$jenis]['product_totals'][$namaBarangJadi] = (float) ($groups[$jenis]['product_totals'][$namaBarangJadi] ?? 0.0)
                + (float) $row['M3'];
            $groups[$jenis]['total_m3'] += (float) $row['M3'];
        }

        foreach ($groups as &$group) {
            foreach ($group['product_totals'] as &$total) {
                $total = round((float) $total, 4);
            }
            unset($total);

            $group['total_m3'] = round((float) $group['total_m3'], 4);
        }
        unset($group);

        return array_values($groups);
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
            'grand_total_m3' => round(array_sum(array_map(static fn (array $row): float => (float) ($row['M3'] ?? 0.0), $rows)), 4),
        ];
    }

    /**
     * @return array<int, object>
     */
    private function runProcedureQuery(string $noJual): array
    {
        $configKey = 'reports.penjualan_barang_jadi_m3';
        $connectionName = config("{$configKey}.database_connection");
        $procedure = (string) config("{$configKey}.stored_procedure", 'SP_LapPenjualanBarangJadi');
        $syntax = (string) config("{$configKey}.call_syntax", 'exec');
        $customQuery = config("{$configKey}.query");

        if ($procedure === '' && ! is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan penjualan barang jadi belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan penjualan barang jadi dikonfigurasi untuk SQL Server. '
                .'Set PENJUALAN_BARANG_JADI_M3_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException('Query manual laporan penjualan barang jadi belum diisi.');

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
            throw new RuntimeException('Gagal mengambil data laporan penjualan barang jadi: '.$exception->getMessage(), 0, $exception);
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

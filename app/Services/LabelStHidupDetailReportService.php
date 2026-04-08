<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class LabelStHidupDetailReportService
{
    /**
     * @return array<string, mixed>
     */
    public function buildReportData(): array
    {
        $rows = $this->fetch();

        $grandTotal = 0.0;
        foreach ($rows as $r) {
            $grandTotal += (float) ($r['Total'] ?? 0.0);
        }

        return [
            'rows' => $rows,
            'summary' => [
                'total_rows' => count($rows),
                'grand_total' => $grandTotal,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function healthCheck(): array
    {
        $raw = $this->runProcedureQuery();
        $first = (array) ($raw[0] ?? []);
        $detectedColumns = array_keys($first);
        $expectedColumns = config('reports.label_st_hidup_detail.expected_columns', []);
        $expectedColumns = is_array($expectedColumns) ? array_values($expectedColumns) : [];

        return [
            'is_healthy' => empty(array_diff($expectedColumns, $detectedColumns)),
            'expected_columns' => $expectedColumns,
            'detected_columns' => $detectedColumns,
            'missing_columns' => array_values(array_diff($expectedColumns, $detectedColumns)),
            'extra_columns' => array_values(array_diff($detectedColumns, $expectedColumns)),
            'row_count' => count($raw),
        ];
    }

    /**
     * Normalize stored procedure output into a stable shape for Blade.
     *
     * @return array<int, array<string, mixed>>
     */
    private function fetch(): array
    {
        $raw = $this->runProcedureQuery();

        $out = [];
        foreach ($raw as $row) {
            $item = [];
            foreach ((array) $row as $k => $v) {
                if (is_string($v)) {
                    $v = trim($v);
                }
                $item[(string) $k] = $v;
            }

            $out[] = [
                'NoST' => $this->pickString($item, ['NoST', 'NoSt', 'No_ST', 'NoSTJual', 'NoLabelST']),
                'Date' => $this->pickString($item, ['Date', 'Tanggal', 'Tgl', 'TglLaporan', 'DateCreate', 'TanggalST']),
                'NoSPK' => $this->pickString($item, ['NoSPK', 'NoSpk', 'SPK', 'NoKontrak']),
                'Jenis' => $this->pickString($item, ['Jenis', 'NamaGrade', 'Produk', 'JenisKayu', 'NamaJenis']),
                'Tebal' => $this->pickFloatOrRaw($item, ['Tebal']),
                'Lebar' => $this->pickFloatOrRaw($item, ['Lebar']),
                'Panjang' => $this->pickFloatOrRaw($item, ['Panjang', 'PanjangFt']),
                'JmlhBatang' => (int) ($this->pickFloat($item, ['JmlhBatang', 'JumlahBatang', 'Batang', 'Pcs']) ?? 0),
                'Lokasi' => $this->pickString($item, ['Lokasi', 'IdLokasi', 'Ruang', 'NoRuang', 'KodeLokasi']),
                // SP_LapLabelSTHidupDetail uses "Awal" as the ton/total-like measure.
                'Total' => (float) ($this->pickFloat($item, ['Total', 'Awal', 'Ton', 'TonST', 'TotalTon']) ?? 0.0),
            ];
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $item
     * @param array<int, string> $keys
     */
    private function pickString(array $item, array $keys): string
    {
        foreach ($keys as $key) {
            if (!array_key_exists($key, $item)) {
                continue;
            }

            $value = $item[$key];
            if ($value === null) {
                return '';
            }

            return is_string($value) ? trim($value) : (string) $value;
        }

        return '';
    }

    /**
     * @param array<string, mixed> $item
     * @param array<int, string> $keys
     */
    private function pickFloat(array $item, array $keys): ?float
    {
        foreach ($keys as $key) {
            if (!array_key_exists($key, $item)) {
                continue;
            }

            $value = $this->toFloat($item[$key]);
            if ($value !== null) {
                return $value;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $item
     * @param array<int, string> $keys
     */
    private function pickFloatOrRaw(array $item, array $keys): mixed
    {
        foreach ($keys as $key) {
            if (!array_key_exists($key, $item)) {
                continue;
            }

            return $this->toFloat($item[$key]) ?? $item[$key];
        }

        return null;
    }

    private function toFloat(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }
        if (!is_string($value)) {
            return null;
        }
        $t = trim($value);
        if ($t === '') {
            return null;
        }
        $t = str_replace(',', '', $t);
        if (!is_numeric($t)) {
            return null;
        }

        return (float) $t;
    }

    /**
     * @return array<int, object>
     */
    private function runProcedureQuery(): array
    {
        $configKey = 'reports.label_st_hidup_detail';
        $connectionName = config("{$configKey}.database_connection");
        $procedure = (string) config("{$configKey}.stored_procedure", 'SP_LapLabelSTHidupDetail');
        $syntax = (string) config("{$configKey}.call_syntax", 'exec');
        $customQuery = config("{$configKey}.query");
        $parameterCount = (int) config("{$configKey}.parameter_count", 0);

        if ($parameterCount !== 0) {
            throw new RuntimeException('Jumlah parameter laporan Label ST (Hidup) Detail harus 0.');
        }

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan Label ST (Hidup) Detail belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan Label ST (Hidup) Detail dikonfigurasi untuk SQL Server. '
                . 'Set LABEL_ST_HIDUP_DETAIL_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException('Query manual laporan Label ST (Hidup) Detail belum diisi.');

            return $connection->select($query);
        }

        if (!preg_match('/^[A-Za-z0-9_$.]+$/', $procedure)) {
            throw new RuntimeException('Nama stored procedure tidak valid.');
        }

        $sql = match ($syntax) {
            'exec' => "SET NOCOUNT ON; EXEC {$procedure}",
            'call' => "CALL {$procedure}()",
            default => $driver === 'sqlsrv'
            ? "SET NOCOUNT ON; EXEC {$procedure}"
            : "CALL {$procedure}()",
        };

        return $connection->select($sql);
    }
}

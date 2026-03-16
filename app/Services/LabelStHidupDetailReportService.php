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
            $item = (array) $row;
            foreach ($item as $k => $v) {
                if (is_string($v)) {
                    $item[$k] = trim($v);
                }
            }

            $out[] = [
                'NoST' => (string) ($item['NoST'] ?? $item['NoSt'] ?? $item['No_ST'] ?? $item['NoSTJual'] ?? ''),
                'Date' => (string) ($item['Date'] ?? $item['Tanggal'] ?? $item['Tgl'] ?? $item['TglLaporan'] ?? $item['DateCreate'] ?? ''),
                'NoSPK' => (string) ($item['NoSPK'] ?? $item['NoSpk'] ?? $item['SPK'] ?? ''),
                'Jenis' => (string) ($item['Jenis'] ?? $item['NamaGrade'] ?? $item['Produk'] ?? ''),
                'Tebal' => $this->toFloat($item['Tebal'] ?? null) ?? $item['Tebal'] ?? null,
                'Lebar' => $this->toFloat($item['Lebar'] ?? null) ?? $item['Lebar'] ?? null,
                'Panjang' => $this->toFloat($item['Panjang'] ?? null) ?? $item['Panjang'] ?? null,
                'JmlhBatang' => (int) ($this->toFloat($item['JmlhBatang'] ?? $item['JumlahBatang'] ?? null) ?? 0),
                'Lokasi' => (string) ($item['Lokasi'] ?? $item['IdLokasi'] ?? $item['Ruang'] ?? $item['NoRuang'] ?? ''),
                // SP_LapLabelSTHidupDetail uses "Awal" as the ton/total-like measure.
                'Total' => (float) ($this->toFloat($item['Total'] ?? $item['Awal'] ?? $item['Ton'] ?? $item['TonST'] ?? null) ?? 0.0),
            ];
        }

        return $out;
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

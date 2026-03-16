<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class StHidupKeringReportService
{
    /**
     * @return array<string, mixed>
     */
    public function buildReportData(int $hari, string $mode): array
    {
        $rows = $this->fetch($hari, $mode);

        return [
            'rows' => $rows,
            'summary' => [
                'total_rows' => count($rows),
                'hari' => $hari,
                'mode' => $mode,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function healthCheck(int $hari, string $mode): array
    {
        $raw = $this->runProcedureQuery($hari, $mode);
        $first = (array) ($raw[0] ?? []);
        $detectedColumns = array_keys($first);
        $expectedColumns = config('reports.st_hidup_kering.expected_columns', []);
        $expectedColumns = is_array($expectedColumns) ? array_values($expectedColumns) : [];
        $missingColumns = array_values(array_diff($expectedColumns, $detectedColumns));
        $extraColumns = array_values(array_diff($detectedColumns, $expectedColumns));

        return [
            'is_healthy' => empty($missingColumns),
            'expected_columns' => $expectedColumns,
            'detected_columns' => $detectedColumns,
            'missing_columns' => $missingColumns,
            'extra_columns' => $extraColumns,
            'row_count' => count($raw),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetch(int $hari, string $mode): array
    {
        $raw = $this->runProcedureQuery($hari, $mode);

        $out = [];
        foreach ($raw as $row) {
            $item = (array) $row;
            foreach ($item as $k => $v) {
                if (is_string($v)) {
                    $item[$k] = trim($v);
                }
            }

            $out[] = [
                'NoST' => (string) ($item['NoST'] ?? ''),
                'Tebal' => (float) ($this->toFloat($item['Tebal'] ?? null) ?? 0.0),
                'Lebar' => (float) ($this->toFloat($item['Lebar'] ?? null) ?? 0.0),
                'JmlhBatang' => (int) ($this->toFloat($item['JmlhBatang'] ?? null) ?? 0.0),
                'IdLokasi' => (string) ($item['IdLokasi'] ?? ''),
                'UsiaHari' => (int) ($this->toFloat($item['UsiaHari'] ?? null) ?? 0.0),
                'Jenis' => (string) ($item['Jenis'] ?? ''),
                'BB' => (string) ($item['BB'] ?? ''),
            ];
        }

        return array_values($out);
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
    private function runProcedureQuery(int $hari, string $mode): array
    {
        $configKey = 'reports.st_hidup_kering';
        $connectionName = config("{$configKey}.database_connection");
        $procedure = (string) config("{$configKey}.stored_procedure", 'SP_LapSTHidupKering');
        $syntax = (string) config("{$configKey}.call_syntax", 'exec');
        $customQuery = config("{$configKey}.query");
        $parameterCount = (int) config("{$configKey}.parameter_count", 2);

        if ($parameterCount !== 2) {
            throw new RuntimeException('Jumlah parameter laporan ST Hidup Kering harus 2 (Hari dan Mode).');
        }

        $mode = strtoupper(trim($mode));
        if (!in_array($mode, ['INCLUDE', 'EXCLUDE'], true)) {
            $mode = 'INCLUDE';
        }

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan ST Hidup Kering belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan ST Hidup Kering dikonfigurasi untuk SQL Server. '
                . 'Set ST_HIDUP_KERING_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException('Query manual laporan ST Hidup Kering belum diisi.');

            return $connection->select($query, str_contains($query, '?') ? [$hari, $mode] : []);
        }

        if (!preg_match('/^[A-Za-z0-9_$.]+$/', $procedure)) {
            throw new RuntimeException('Nama stored procedure tidak valid.');
        }

        $sql = match ($syntax) {
            'exec' => "SET NOCOUNT ON; EXEC {$procedure} ?, ?",
            'call' => "CALL {$procedure}(?, ?)",
            default => $driver === 'sqlsrv'
                ? "SET NOCOUNT ON; EXEC {$procedure} ?, ?"
                : "CALL {$procedure}(?, ?)",
        };

        return $connection->select($sql, [$hari, $mode]);
    }
}


<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class MutasiKdReportService
{
    private const EPS = 0.0000001;

    /**
     * @return array<string, mixed>
     */
    public function buildReportData(string $startDate, string $endDate): array
    {
        $rows = $this->fetch($startDate, $endDate);

        /** @var array<int, array<int, array<string, mixed>>> $byKd */
        $byKd = [];
        foreach ($rows as $row) {
            $kd = (int) ($row['NoRuangKD'] ?? 0);
            if ($kd <= 0) {
                continue;
            }
            $byKd[$kd][] = $row;
        }

        $kds = array_keys($byKd);
        sort($kds, SORT_NUMERIC);

        $groups = [];
        $grandIn = 0.0;
        $grandOut = 0.0;

        foreach ($kds as $kd) {
            $kdRows = $byKd[$kd] ?? [];
            usort($kdRows, static function (array $a, array $b): int {
                $inA = (string) ($a['TglMasuk'] ?? '');
                $inB = (string) ($b['TglMasuk'] ?? '');
                $c = strcmp($inA, $inB);
                if ($c !== 0) {
                    return $c;
                }
                $outA = (string) ($a['TglKeluar'] ?? '');
                $outB = (string) ($b['TglKeluar'] ?? '');
                return strcmp($outA, $outB);
            });

            $sumIn = array_reduce($kdRows, static fn (float $c, array $r): float => $c + (float) ($r['TonIn'] ?? 0.0), 0.0);
            $sumOut = array_reduce($kdRows, static fn (float $c, array $r): float => $c + (float) ($r['TonOut'] ?? 0.0), 0.0);

            $grandIn += $sumIn;
            $grandOut += $sumOut;

            $groups[] = [
                'no_ruang_kd' => $kd,
                'rows' => array_values($kdRows),
                'totals' => [
                    'ton_in' => $sumIn,
                    'ton_out' => $sumOut,
                ],
            ];
        }

        return [
            'groups' => $groups,
            'summary' => [
                'total_groups' => count($groups),
                'grand_ton_in' => $grandIn,
                'grand_ton_out' => $grandOut,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function healthCheck(string $startDate, string $endDate): array
    {
        $rows = $this->fetch($startDate, $endDate);
        $detectedColumns = array_keys($rows[0] ?? []);
        $expectedColumns = config('reports.mutasi_kd.expected_columns', []);
        $expectedColumns = is_array($expectedColumns) ? array_values($expectedColumns) : [];
        $missingColumns = array_values(array_diff($expectedColumns, $detectedColumns));

        return [
            'is_healthy' => empty($missingColumns),
            'expected_columns' => $expectedColumns,
            'detected_columns' => $detectedColumns,
            'missing_columns' => $missingColumns,
            'row_count' => count($rows),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetch(string $startDate, string $endDate): array
    {
        $raw = $this->runProcedureQuery($startDate, $endDate);

        $out = [];
        foreach ($raw as $row) {
            $item = (array) $row;
            foreach ($item as $k => $v) {
                if (is_string($v)) {
                    $item[$k] = trim($v);
                }
            }

            $item['NoRuangKD'] = (int) ($item['NoRuangKD'] ?? 0);
            $item['TonIn'] = (float) ($this->toFloat($item['TonIn'] ?? null) ?? 0.0);
            $item['TonOut'] = (float) ($this->toFloat($item['TonOut'] ?? null) ?? 0.0);

            $out[] = $item;
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
    private function runProcedureQuery(string $startDate, string $endDate): array
    {
        $configKey = 'reports.mutasi_kd';
        $connectionName = config("{$configKey}.database_connection");
        $procedure = (string) config("{$configKey}.stored_procedure", 'SP_LapMutasiKD');
        $syntax = (string) config("{$configKey}.call_syntax", 'exec');
        $customQuery = config("{$configKey}.query");
        $parameterCount = (int) config("{$configKey}.parameter_count", 2);

        if ($parameterCount !== 2) {
            throw new RuntimeException('Jumlah parameter laporan Mutasi KD harus 2 (Tanggal Awal dan Tanggal Akhir).');
        }

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan Mutasi KD belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan Mutasi KD dikonfigurasi untuk SQL Server. '
                . 'Set MUTASI_KD_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException('Query manual laporan Mutasi KD belum diisi.');

            return $connection->select($query, str_contains($query, '?') ? [$startDate, $endDate] : []);
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

        return $connection->select($sql, [$startDate, $endDate]);
    }
}

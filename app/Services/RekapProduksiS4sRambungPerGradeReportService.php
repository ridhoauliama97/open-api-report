<?php

namespace App\Services;

use Carbon\CarbonPeriod;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RekapProduksiS4sRambungPerGradeReportService
{
    private const EPS = 0.0000001;

    /**
     * @return array<string, mixed>
     */
    public function buildReportData(string $startDate, string $endDate): array
    {
        $raw = $this->runProcedureQuery($startDate, $endDate);

        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->startOfDay();

        $inputCols = ['RAMBUNG - MC 1', 'RAMBUNG - MC 2', 'RAMBUNG - STD'];
        $outputCols = ['A/A', 'A/B', 'A/C', 'BELAH', 'C/C', 'MISS TEBAL'];

        /** @var array<string, array{input: array<string,float>, output: array<string,float>}> $byDate */
        $byDate = [];

        foreach ($raw as $rowObj) {
            $r = (array) $rowObj;

            $tglRaw = $r['Tanggal'] ?? $r['DATE'] ?? $r['Tgl'] ?? $r['Date'] ?? null;
            $type = strtoupper(trim((string) ($r['Type'] ?? '')));
            $jenis = trim((string) ($r['Jenis'] ?? ''));
            $total = $this->toFloat($r['Total'] ?? null);

            if ($tglRaw === null || $type === '' || $jenis === '' || $total === null) {
                continue;
            }

            $dateKey = '';
            try {
                $dateKey = Carbon::parse((string) $tglRaw)->toDateString();
            } catch (\Throwable) {
                $dateKey = '';
            }
            if ($dateKey === '') {
                continue;
            }

            if (!isset($byDate[$dateKey])) {
                $byDate[$dateKey] = [
                    'input' => array_fill_keys($inputCols, 0.0),
                    'output' => array_fill_keys($outputCols, 0.0),
                ];
            }

            if ($type === 'INPUT') {
                if (!array_key_exists($jenis, $byDate[$dateKey]['input'])) {
                    // Ignore unexpected jenis for layout stability.
                    continue;
                }
                $byDate[$dateKey]['input'][$jenis] = (float) ($byDate[$dateKey]['input'][$jenis] ?? 0.0) + $total;
            } elseif ($type === 'OUTPUT') {
                if (!array_key_exists($jenis, $byDate[$dateKey]['output'])) {
                    continue;
                }
                $byDate[$dateKey]['output'][$jenis] = (float) ($byDate[$dateKey]['output'][$jenis] ?? 0.0) + $total;
            }
        }

        $dates = [];
        foreach (CarbonPeriod::create($start, $end) as $d) {
            $dates[] = $d->toDateString();
        }

        $rows = [];
        $inputTotals = array_fill_keys($inputCols, 0.0);
        $outputTotals = array_fill_keys($outputCols, 0.0);

        foreach ($dates as $dateKey) {
            $vals = $byDate[$dateKey] ?? [
                'input' => array_fill_keys($inputCols, 0.0),
                'output' => array_fill_keys($outputCols, 0.0),
            ];

            $inputSum = array_sum($vals['input']);
            $outputSum = array_sum($vals['output']);

            $inputCells = [];
            foreach ($inputCols as $c) {
                $v = (float) ($vals['input'][$c] ?? 0.0);
                $inputCells[$c] = [
                    'total' => $v,
                    'ratio' => $inputSum > 0.0 ? (($v / $inputSum) * 100.0) : 0.0,
                ];
                $inputTotals[$c] += $v;
            }

            $outputCells = [];
            foreach ($outputCols as $c) {
                $v = (float) ($vals['output'][$c] ?? 0.0);
                $outputCells[$c] = [
                    'total' => $v,
                    'ratio' => $outputSum > 0.0 ? (($v / $outputSum) * 100.0) : 0.0,
                ];
                $outputTotals[$c] += $v;
            }

            $rows[] = [
                'date' => $dateKey,
                'input' => $inputCells,
                'output' => $outputCells,
            ];
        }

        $inputGrand = array_sum($inputTotals);
        $outputGrand = array_sum($outputTotals);

        $totalRow = [
            'input' => [],
            'output' => [],
        ];

        foreach ($inputCols as $c) {
            $v = (float) ($inputTotals[$c] ?? 0.0);
            $totalRow['input'][$c] = [
                'total' => $v,
                'ratio' => $inputGrand > 0.0 ? (($v / $inputGrand) * 100.0) : 0.0,
            ];
        }
        foreach ($outputCols as $c) {
            $v = (float) ($outputTotals[$c] ?? 0.0);
            $totalRow['output'][$c] = [
                'total' => $v,
                'ratio' => $outputGrand > 0.0 ? (($v / $outputGrand) * 100.0) : 0.0,
            ];
        }

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'input_columns' => $inputCols,
            'output_columns' => $outputCols,
            'rows' => $rows,
            'total' => $totalRow,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function healthCheck(string $startDate, string $endDate): array
    {
        $raw = $this->runProcedureQuery($startDate, $endDate);
        $first = (array) ($raw[0] ?? []);
        $detectedColumns = array_keys($first);
        $expectedColumns = config('reports.rekap_produksi_s4s_rambung_per_grade.expected_columns', []);
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
        if ($t === '' || $t === '-') {
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
        $configKey = 'reports.rekap_produksi_s4s_rambung_per_grade';
        $connectionName = config("{$configKey}.database_connection");
        $procedure = (string) config("{$configKey}.stored_procedure", 'SP_LapRekapProduksiS4SRambungPerGrade');
        $syntax = (string) config("{$configKey}.call_syntax", 'exec');
        $customQuery = config("{$configKey}.query");
        $parameterCount = (int) config("{$configKey}.parameter_count", 2);

        if ($parameterCount !== 2) {
            throw new RuntimeException('Jumlah parameter laporan Rekap Produksi Rambung Per Grade harus 2 (Tanggal Awal dan Tanggal Akhir).');
        }

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan Rekap Produksi Rambung Per Grade belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan Rekap Produksi Rambung Per Grade dikonfigurasi untuk SQL Server. '
                . 'Set REKAP_PRODUKSI_S4S_RAMBUNG_PER_GRADE_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException('Query manual laporan Rekap Produksi Rambung Per Grade belum diisi.');

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


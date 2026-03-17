<?php

namespace App\Services;

use Carbon\CarbonPeriod;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class GradeAbcHarianReportService
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

        // Canonical order as per referensi.
        $gradeKeys = ['GRADE A', 'GRADE AB/AC', 'GRADE CC', 'GRADE CUT'];

        /** @var array<string, array<string, float>> $byDate */
        $byDate = [];

        foreach ($raw as $rowObj) {
            $r = (array) $rowObj;

            $tglRaw = $r['Tanggal'] ?? $r['Tgl'] ?? $r['TglProduksi'] ?? $r['Date'] ?? $r['DATE'] ?? $r['TanggalProduksi'] ?? null;
            $gradeRaw = $r['Grade'] ?? $r['NamaGrade'] ?? $r['Jenis'] ?? $r['GroupGrade'] ?? $r['Group'] ?? null;
            $pcsRaw = $r['Pcs'] ?? $r['PCS'] ?? $r['JmlhBatang'] ?? $r['JumlahBatang'] ?? $r['Qty'] ?? $r['Jumlah'] ?? null;

            if ($tglRaw === null || $gradeRaw === null) {
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

            $gradeKey = $this->normalizeGradeKey((string) $gradeRaw);
            if ($gradeKey === null) {
                continue;
            }

            $pcs = $this->toFloat($pcsRaw) ?? 0.0;

            if (!isset($byDate[$dateKey])) {
                $byDate[$dateKey] = array_fill_keys($gradeKeys, 0.0);
            }
            $byDate[$dateKey][$gradeKey] = (float) ($byDate[$dateKey][$gradeKey] ?? 0.0) + $pcs;
        }

        // Build full date series rows (including empty days).
        $dates = [];
        foreach (CarbonPeriod::create($start, $end) as $d) {
            $dates[] = $d->toDateString();
        }

        $rows = [];
        $totals = array_fill_keys($gradeKeys, 0.0);
        $grandTotal = 0.0;

        foreach ($dates as $dateKey) {
            $vals = $byDate[$dateKey] ?? array_fill_keys($gradeKeys, 0.0);
            $dayTotal = array_sum($vals);

            $cells = [];
            foreach ($gradeKeys as $gk) {
                $v = (float) ($vals[$gk] ?? 0.0);
                $cells[$gk] = [
                    'pcs' => $v,
                    'percent' => $dayTotal > 0.0 ? (($v / $dayTotal) * 100.0) : 0.0,
                ];
                $totals[$gk] += $v;
            }

            $grandTotal += $dayTotal;

            $rows[] = [
                'date' => $dateKey,
                'cells' => $cells,
                'total_pcs' => $dayTotal,
            ];
        }

        $totalCells = [];
        foreach ($gradeKeys as $gk) {
            $v = (float) ($totals[$gk] ?? 0.0);
            $totalCells[$gk] = [
                'pcs' => $v,
                'percent' => $grandTotal > 0.0 ? (($v / $grandTotal) * 100.0) : 0.0,
            ];
        }

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'grades' => $gradeKeys,
            'rows' => $rows,
            'total' => [
                'cells' => $totalCells,
                'total_pcs' => $grandTotal,
            ],
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
        $expectedColumns = config('reports.grade_abc_harian.expected_columns', []);
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

    private function normalizeGradeKey(string $raw): ?string
    {
        $t = strtoupper(trim($raw));
        if ($t === '') {
            return null;
        }

        // Allow common variations from SP.
        if (str_contains($t, 'AB') || str_contains($t, 'A/B') || str_contains($t, 'AB/AC') || str_contains($t, 'A B') || str_contains($t, 'A/B/AC')) {
            return 'GRADE AB/AC';
        }
        if ($t === 'A' || $t === 'GRADE A' || str_contains($t, 'GRADE A ')) {
            return 'GRADE A';
        }
        if (str_contains($t, 'CC')) {
            return 'GRADE CC';
        }
        if (str_contains($t, 'CUT')) {
            return 'GRADE CUT';
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
        $configKey = 'reports.grade_abc_harian';
        $connectionName = config("{$configKey}.database_connection");
        $procedure = (string) config("{$configKey}.stored_procedure", 'SPWps_LapGradeABCHarian');
        $syntax = (string) config("{$configKey}.call_syntax", 'exec');
        $customQuery = config("{$configKey}.query");
        $parameterCount = (int) config("{$configKey}.parameter_count", 2);

        if ($parameterCount !== 2) {
            throw new RuntimeException('Jumlah parameter laporan Grade ABC Harian harus 2 (Tanggal Awal dan Tanggal Akhir).');
        }

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan Grade ABC Harian belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan Grade ABC Harian dikonfigurasi untuk SQL Server. '
                . 'Set GRADE_ABC_HARIAN_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException('Query manual laporan Grade ABC Harian belum diisi.');

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

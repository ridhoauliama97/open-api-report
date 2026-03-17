<?php

namespace App\Services;

use Carbon\CarbonPeriod;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class OutputProduksiS4sPerGradeReportService
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

        // Normalize: group by machine -> date -> jns -> jenis
        $byMachine = [];
        foreach ($raw as $rowObj) {
            $r = (array) $rowObj;

            $machine = trim((string) ($r['NamaMesin'] ?? ''));
            $tgl = (string) ($r['Tanggal'] ?? '');
            if ($machine === '' || $tgl === '') {
                continue;
            }

            $dateKey = Carbon::parse($tgl)->toDateString();
            $jns = trim((string) ($r['Jns'] ?? ''));
            $jenis = trim((string) ($r['Jenis'] ?? ''));

            $target = $this->toFloat($r['Target'] ?? null);
            $output = $this->toFloat($r['Output'] ?? null);

            if (!isset($byMachine[$machine])) {
                $byMachine[$machine] = [
                    'target_by_date' => [],
                    'rows' => [],
                    'jns_grades' => [],
                ];
            }

            // Target per tanggal per mesin: ambil yang pertama non-null.
            if ($target !== null && !isset($byMachine[$machine]['target_by_date'][$dateKey])) {
                $byMachine[$machine]['target_by_date'][$dateKey] = $target;
            }

            // Track kolom (Jns/Grade) walaupun Output null/'-' supaya header tetap muncul sesuai template.
            if ($jns !== '' && $jenis !== '') {
                $byMachine[$machine]['jns_grades'][$jns][$jenis] = true;
            }

            if ($jns === '' || $jenis === '' || $output === null) {
                continue;
            }

            $byMachine[$machine]['rows'][$dateKey][$jns][$jenis] = ($byMachine[$machine]['rows'][$dateKey][$jns][$jenis] ?? 0.0) + $output;
        }

        $machines = [];
        foreach ($byMachine as $machineName => $m) {
            // Layout kolom mengikuti pola aplikasi perusahaan (fixed order).
            $jnsCols = $this->buildLayoutColumns($machineName, (array) ($m['jns_grades'] ?? []));

            // Order wood type groups as they appear in the output (insertion order),
            // but PHP arrays preserve insertion so above foreach keeps it.

            // Build full date series rows (including empty days).
            $dates = [];
            foreach (CarbonPeriod::create($start, $end) as $d) {
                $dates[] = $d->toDateString();
            }

            $tableRows = [];
            foreach ($dates as $dateKey) {
                $tableRows[] = $this->buildComputedRow(
                    $dateKey,
                    (float) ($m['target_by_date'][$dateKey] ?? 0.0),
                    $m['rows'][$dateKey] ?? [],
                    $jnsCols,
                );
            }

            $totalRow = $this->reduceRows($tableRows, 'sum');
            $avgRow = $this->reduceRows($tableRows, 'avg');
            $minRow = $this->reduceRows($tableRows, 'min');
            $maxRow = $this->reduceRows($tableRows, 'max');

            $targetDefault = 0.0;
            foreach (($m['target_by_date'] ?? []) as $t) {
                $tv = (float) $t;
                if ($tv > 0.0) {
                    $targetDefault = $tv;
                    break;
                }
            }

            $machines[] = [
                'nama_mesin' => $machineName,
                'jns_columns' => $jnsCols,
                'dates' => $dates,
                'target_default' => $targetDefault,
                'rows' => $tableRows,
                'summary_rows' => [
                    ['label' => 'Total', 'row' => $totalRow],
                    ['label' => 'Avg', 'row' => $avgRow],
                    ['label' => 'Min', 'row' => $minRow],
                    ['label' => 'Max', 'row' => $maxRow],
                ],
            ];
        }

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'machines' => $machines,
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
        $expectedColumns = config('reports.output_produksi_s4s_per_grade.expected_columns', []);
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
     * @param array<string, array<string, float>> $jnsToGradesMap For each jns, map grade->value
     * @param array<int, array{jns:string, grades:array<int,string>}> $jnsCols
     * @return array<string, mixed>
     */
    private function buildComputedRow(
        string $dateKey,
        float $target,
        array $jnsToGradesMap,
        array $jnsCols,
    ): array {
        // Normalize to float map
        $cells = [];
        $grandTotal = 0.0;

        foreach ($jnsCols as $group) {
            $jns = $group['jns'];
            $grades = $group['grades'];

            $jnsTotal = 0.0;
            foreach ($grades as $g) {
                $val = (float) ($jnsToGradesMap[$jns][$g] ?? 0.0);
                $cells[$jns][$g] = [
                    'value' => $val,
                    'percent' => 0.0, // computed after total known
                ];
                $jnsTotal += $val;
            }
            $cells[$jns]['__TOTAL__'] = [
                'value' => $jnsTotal,
                'percent' => 100.0,
            ];
            $grandTotal += $jnsTotal;

            // compute percents within jns
            foreach ($grades as $g) {
                $v = $cells[$jns][$g]['value'];
                $cells[$jns][$g]['percent'] = $jnsTotal > 0.0 ? (($v / $jnsTotal) * 100.0) : 0.0;
            }
        }

        $totalMinusTarget = $grandTotal - $target;

        return [
            'date' => $dateKey,
            'target' => $target,
            'cells' => $cells,
            'grand_total' => $grandTotal,
            'total_minus_target' => $totalMinusTarget,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @param 'sum'|'avg'|'min'|'max' $mode
     * @return array<string, mixed>
     */
    private function reduceRows(array $rows, string $mode): array
    {
        if ($rows === []) {
            return [
                'date' => '',
                'target' => 0.0,
                'cells' => [],
                'grand_total' => 0.0,
                'total_minus_target' => 0.0,
            ];
        }

        // Collect all jns/grade keys
        $keys = [];
        foreach ($rows as $r) {
            $cells = is_array($r['cells'] ?? null) ? $r['cells'] : [];
            foreach ($cells as $jns => $gradeMap) {
                foreach ($gradeMap as $g => $_v) {
                    if ($g === '__TOTAL__') {
                        continue;
                    }
                    $keys[$jns][$g] = true;
                }
            }
        }

        $n = count($rows);
        $targetAgg = 0.0;
        $grandAgg = 0.0;
        $tmAgg = 0.0;

        $outCells = [];
        foreach ($keys as $jns => $gradeSet) {
            $jnsTotal = 0.0;

            foreach (array_keys($gradeSet) as $g) {
                $vals = [];
                foreach ($rows as $r) {
                    $vals[] = (float) ($r['cells'][$jns][$g]['value'] ?? 0.0);
                }

                // Avg/Min/Max di aplikasi menghitung hanya dari hari yang memiliki output (nilai non-zero).
                if ($mode !== 'sum') {
                    $vals = array_values(array_filter(
                        $vals,
                        static fn(float $v): bool => abs($v) > self::EPS,
                    ));
                }

                $agg = match ($mode) {
                    'sum' => array_sum($vals),
                    'avg' => $vals === [] ? 0.0 : (array_sum($vals) / max(1, count($vals))),
                    'min' => $vals === [] ? 0.0 : min($vals),
                    'max' => $vals === [] ? 0.0 : max($vals),
                    default => array_sum($vals),
                };

                $outCells[$jns][$g] = ['value' => $agg, 'percent' => 0.0];
                $jnsTotal += $agg;
            }

            $outCells[$jns]['__TOTAL__'] = ['value' => $jnsTotal, 'percent' => 100.0];

            foreach (array_keys($gradeSet) as $g) {
                $v = (float) ($outCells[$jns][$g]['value'] ?? 0.0);
                $outCells[$jns][$g]['percent'] = $jnsTotal > 0.0 ? (($v / $jnsTotal) * 100.0) : 0.0;
            }

            $grandAgg += $jnsTotal;
        }

        $targetVals = array_map(static fn($r) => (float) ($r['target'] ?? 0.0), $rows);
        if ($mode !== 'sum') {
            $targetVals = array_values(array_filter(
                $targetVals,
                static fn(float $v): bool => abs($v) > self::EPS,
            ));
        }
        $targetAgg = match ($mode) {
            'sum' => array_sum($targetVals),
            'avg' => $targetVals === [] ? 0.0 : (array_sum($targetVals) / max(1, count($targetVals))),
            'min' => $targetVals === [] ? 0.0 : min($targetVals),
            'max' => $targetVals === [] ? 0.0 : max($targetVals),
            default => array_sum($targetVals),
        };

        $tmAgg = $grandAgg - $targetAgg;

        return [
            'date' => '',
            'target' => $targetAgg,
            'cells' => $outCells,
            'grand_total' => $grandAgg,
            'total_minus_target' => $tmAgg,
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
        $configKey = 'reports.output_produksi_s4s_per_grade';
        $connectionName = config("{$configKey}.database_connection");
        $procedure = (string) config("{$configKey}.stored_procedure", 'SPWps_LapProduksiOutputS4SPerGrade');
        $syntax = (string) config("{$configKey}.call_syntax", 'exec');
        $customQuery = config("{$configKey}.query");
        $parameterCount = (int) config("{$configKey}.parameter_count", 2);

        if ($parameterCount !== 2) {
            throw new RuntimeException('Jumlah parameter laporan Output Produksi S4S Per Grade harus 2 (Tanggal Awal dan Tanggal Akhir).');
        }

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan Output Produksi S4S Per Grade belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan Output Produksi S4S Per Grade dikonfigurasi untuk SQL Server. '
                . 'Set OUTPUT_PRODUKSI_S4S_PER_GRADE_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException('Query manual laporan Output Produksi S4S Per Grade belum diisi.');

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

    /**
     * @param array<string, array<string, bool>> $jnsGrades
     * @return array<int, array{jns:string, grades:array<int,string>}>
     */
    private function buildLayoutColumns(string $machineName, array $jnsGrades): array
    {
        $isMultiRipsaw = stripos($machineName, 'RIP') !== false;
        $isS4sLine1 = stripos($machineName, 'S4S LINE 1') !== false;

        // Fixed ordering per referensi (template).
        // Catatan: beberapa mesin (mis. Multi Ripsaw) memiliki komposisi grade berbeda.
        if ($isMultiRipsaw) {
            $layout = [
                'JABON' => ['BELAH', 'A/A', 'ISOBO', 'NISOBO'],
                'JABON TG' => ['A/A'],
                // PULAI pada referensi Multi Ripsaw: BELAH + ISOBO.
                'PULAI' => ['BELAH', 'ISOBO'],
                // Pada referensi Multi Ripsaw juga ada RAMBUNG: BELAH, A/B, A/C, C/C.
                'RAMBUNG' => ['BELAH', 'A/B', 'A/C', 'C/C'],
            ];
        } else {
            $layout = [
                'JABON' => ['BELAH', 'MISS TEBAL', 'A/A', 'ISOBO', 'NISOBO'],
                'JABON TG' => ['A/A'],
                'RAMBUNG' => ['BELAH', 'MISS TEBAL', 'A/A', 'A/B', 'A/C', 'C/C'],
                // PULAI pada referensi S4S LINE 1: ISOBO, NISOBO, TASOBO (tanpa BELAH).
                'PULAI' => ['ISOBO', 'NISOBO', 'TASOBO'],
            ];

            // Untuk Mesin S4S LINE 1, posisi section PULAI dan RAMBUNG ditukar.
            if ($isS4sLine1) {
                $layout = [
                    'JABON' => $layout['JABON'],
                    'JABON TG' => $layout['JABON TG'],
                    'PULAI' => $layout['PULAI'],
                    'RAMBUNG' => $layout['RAMBUNG'],
                ];
            }
        }

        $out = [];
        foreach ($layout as $jns => $desiredGrades) {
            $available = array_keys((array) ($jnsGrades[$jns] ?? []));

            // Ikuti urutan template, tapi tetap tampilkan kolom walau tidak ada output (nilai bisa blank di view).
            // Kalau ada grade tambahan dari SP, taruh di belakang (sorted) biar tidak "hilang" saat SP berubah.
            $grades = $desiredGrades;

            if ($available !== []) {
                $extras = array_values(array_diff($available, $desiredGrades));
                sort($extras, SORT_NATURAL | SORT_FLAG_CASE);
                foreach ($extras as $g) {
                    $grades[] = $g;
                }
            }

            // Deduplicate, keep order.
            $seen = [];
            $grades = array_values(array_filter($grades, static function (string $g) use (&$seen): bool {
                if (isset($seen[$g])) {
                    return false;
                }
                $seen[$g] = true;
                return true;
            }));

            $out[] = ['jns' => $jns, 'grades' => $grades];
        }

        return $out;
    }
}

<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class TargetMasukBBBulananReportService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetch(string $startDate, string $endDate): array
    {
        $rows = $this->runProcedureQuery($startDate, $endDate);

        return array_map(static fn ($row): array => (array) $row, $rows);
    }

    /**
     * @return array<string, mixed>
     */
    public function buildReportData(string $startDate, string $endDate): array
    {
        $rows = $this->fetch($startDate, $endDate);
        $monthColumns = $this->resolvePeriodMonths($startDate, $endDate);
        $monthIndexByKey = [];
        foreach ($monthColumns as $index => $month) {
            $monthIndexByKey[$month['key']] = $index;
        }

        $groups = [];
        foreach ($rows as $row) {
            $group = trim((string) ($row['NamaGroup'] ?? ''));
            if ($group === '') {
                $group = 'Tanpa Group';
            }

            $monthKey = $this->resolveMonthKey($row);
            if ($monthKey === null || !isset($monthIndexByKey[$monthKey])) {
                continue;
            }

            if (!isset($groups[$group])) {
                $groups[$group] = [
                    'jenis' => $group,
                    'target_bulanan' => 0.0,
                    'monthly_values' => array_fill(0, count($monthColumns), 0.0),
                    'raw_total' => 0.0,
                    'total' => 0,
                    'bulan_capai' => 0,
                    'total_bulan_target' => 0,
                    'persen_capai_group' => 0.0,
                ];
            }

            $target = $this->toFloat($row['TgtPerHari'] ?? null);
            if ($target > 0) {
                $groups[$group]['target_bulanan'] = $target;
            }

            $hasilRaw = $this->toFloat($row['hasil'] ?? null);
            $hasilRounded = (float) round($hasilRaw, 0, PHP_ROUND_HALF_UP);
            $monthIndex = $monthIndexByKey[$monthKey];
            $groups[$group]['monthly_values'][$monthIndex] = $hasilRounded;
            $groups[$group]['raw_total'] += $hasilRaw;

            $groups[$group]['bulan_capai'] = (int) ($row['BulanCapaiGroup'] ?? $groups[$group]['bulan_capai']);
            $groups[$group]['total_bulan_target'] = (int) ($row['TotalBulanGroup'] ?? $groups[$group]['total_bulan_target']);
            $groups[$group]['persen_capai_group'] = $this->toFloat($row['PersenCapaiGroup'] ?? $groups[$group]['persen_capai_group']);
        }

        ksort($groups);
        $tableRows = array_values($groups);
        $summaryRows = [];
        $chartSeries = [];

        foreach ($tableRows as $index => $row) {
            $totalRounded = (int) round($row['raw_total'], 0, PHP_ROUND_HALF_UP);
            $tableRows[$index]['total'] = $totalRounded;

            $summaryRows[] = [
                'jenis' => $row['jenis'],
                'avg' => $row['monthly_values'] === [] ? 0.0 : round($totalRounded / count($row['monthly_values']), 0, PHP_ROUND_HALF_UP),
                'min' => $row['monthly_values'] === [] ? 0.0 : min($row['monthly_values']),
                'max' => $row['monthly_values'] === [] ? 0.0 : max($row['monthly_values']),
                'bulan_capai' => $row['bulan_capai'],
                'total_bulan_target' => $row['total_bulan_target'],
                'persen_capai_group' => $row['persen_capai_group'],
            ];

            $chartSeries[$row['jenis']] = $row['monthly_values'];
        }

        return [
            'rows' => $rows,
            'month_columns' => $monthColumns,
            'table_rows' => $tableRows,
            'summary_rows' => $summaryRows,
            'chart_labels' => array_map(static fn (array $item): string => $item['label'], $monthColumns),
            'chart_series' => $chartSeries,
            'period_text' => sprintf('Dari %s Sampai %s', $this->formatDate($startDate), $this->formatDate($endDate)),
        ];
    }

    /**
     * @return array<int, array{key: string, label: string}>
     */
    private function resolvePeriodMonths(string $startDate, string $endDate): array
    {
        $startTs = strtotime(date('Y-m-01', strtotime($startDate)));
        $endTs = strtotime(date('Y-m-01', strtotime($endDate)));
        if ($startTs === false || $endTs === false) {
            return [];
        }

        $months = [];
        $current = $startTs;
        while ($current <= $endTs) {
            $key = date('Y-m', $current);
            $label = date('M-y', $current);
            $months[] = [
                'key' => $key,
                'label' => strtoupper($label),
            ];
            $next = strtotime('+1 month', $current);
            if ($next === false) {
                break;
            }
            $current = $next;
        }

        return $months;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function resolveMonthKey(array $row): ?string
    {
        $tahun = trim((string) ($row['Tahun'] ?? ''));
        $bulan = trim((string) ($row['Bulan'] ?? ''));
        if ($tahun !== '' && $bulan !== '' && ctype_digit($tahun) && ctype_digit($bulan)) {
            $month = (int) $bulan;
            if ($month >= 1 && $month <= 12) {
                return sprintf('%04d-%02d', (int) $tahun, $month);
            }
        }

        $bulanTahun = trim((string) ($row['BulanTahun'] ?? ''));
        if ($bulanTahun !== '') {
            $timestamp = strtotime('01-' . $bulanTahun);
            if ($timestamp !== false) {
                return date('Y-m', $timestamp);
            }
        }

        return null;
    }

    private function toFloat(mixed $value): float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        if (!is_string($value)) {
            return 0.0;
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            return 0.0;
        }

        if (preg_match('/^-?\d{1,3}(\.\d{3})*(,\d+)?$/', $trimmed) === 1) {
            $trimmed = str_replace('.', '', $trimmed);
            $trimmed = str_replace(',', '.', $trimmed);
        } elseif (preg_match('/^-?\d{1,3}(,\d{3})*(\.\d+)?$/', $trimmed) === 1) {
            $trimmed = str_replace(',', '', $trimmed);
        } else {
            $trimmed = str_replace(',', '.', $trimmed);
        }

        return (float) $trimmed;
    }

    private function formatDate(string $date): string
    {
        $timestamp = strtotime($date);

        return $timestamp === false ? $date : date('d/m/Y', $timestamp);
    }

    /**
     * @param array<int, mixed> $bindings
     * @return array<int, mixed>
     */
    private function resolveBindings(string $query, array $bindings): array
    {
        return str_contains($query, '?') ? $bindings : [];
    }

    /**
     * @return array<int, object>
     */
    private function runProcedureQuery(string $startDate, string $endDate): array
    {
        $connectionName = config('reports.target_masuk_bb_bulanan.database_connection');
        $procedure = (string) config('reports.target_masuk_bb_bulanan.stored_procedure');
        $syntax = (string) config('reports.target_masuk_bb_bulanan.call_syntax', 'exec');
        $customQuery = config('reports.target_masuk_bb_bulanan.query');
        $parameterCount = (int) config('reports.target_masuk_bb_bulanan.parameter_count', 2);
        $parameterCount = max(0, min(2, $parameterCount));

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan target masuk bahan baku bulanan belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan target masuk bahan baku bulanan dikonfigurasi untuk SQL Server. '
                . 'Set TARGET_MASUK_BB_BULANAN_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        $allBindings = [$startDate, $endDate];
        $bindings = array_slice($allBindings, 0, $parameterCount);

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException(
                    'TARGET_MASUK_BB_BULANAN_REPORT_QUERY belum diisi. '
                    . 'Isi query manual jika menggunakan TARGET_MASUK_BB_BULANAN_REPORT_CALL_SYNTAX=query.',
                );

            return $connection->select($query, $this->resolveBindings($query, $bindings));
        }

        if (!preg_match('/^[A-Za-z0-9_$.]+$/', $procedure)) {
            throw new RuntimeException('Nama stored procedure tidak valid.');
        }

        $placeholders = $parameterCount === 0
            ? ''
            : implode(', ', array_fill(0, $parameterCount, '?'));

        $sql = match ($syntax) {
            'exec' => $parameterCount === 0
                ? "SET NOCOUNT ON; EXEC {$procedure}"
                : "SET NOCOUNT ON; EXEC {$procedure} {$placeholders}",
            'call' => "CALL {$procedure}({$placeholders})",
            default => $driver === 'sqlsrv'
                ? ($parameterCount === 0
                    ? "SET NOCOUNT ON; EXEC {$procedure}"
                    : "SET NOCOUNT ON; EXEC {$procedure} {$placeholders}")
                : "CALL {$procedure}({$placeholders})",
        };

        return $connection->select($sql, $bindings);
    }
}

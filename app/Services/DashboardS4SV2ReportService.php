<?php

namespace App\Services;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DashboardS4SV2ReportService
{
    public function fetch(string $startDate, string $endDate): array
    {
        return $this->normalizeRows($this->runProcedureQuery($startDate, $endDate));
    }

    public function buildReportData(string $startDate, string $endDate): array
    {
        $rows = $this->fetch($startDate, $endDate);
        $columns = $this->resolveColumns($rows);

        if (
            $columns['date'] === null
            || $columns['jenis'] === null
            || $columns['barang_jadi'] === null
            || $columns['masuk'] === null
            || $columns['keluar'] === null
        ) {
            $detectedColumns = array_keys($rows[0] ?? []);

            throw new RuntimeException(
                'Kolom wajib tidak ditemukan di hasil SPWps_LapDashboardS4S2. '
                . 'Butuh kolom tanggal, jenis, grade, masuk, dan keluar. '
                . 'Kolom terdeteksi: ' . implode(', ', $detectedColumns),
            );
        }

        $orderedColumns = config('reports.dashboard_s4s_v2.column_order', []);
        $orderedColumns = is_array($orderedColumns) ? array_values($orderedColumns) : [];
        $ctrDivisor = (float) config('reports.dashboard_s4s_v2.ctr_divisor', 65);
        if ($ctrDivisor <= 0) {
            $ctrDivisor = 65.0;
        }

        $dateMap = [];
        $daily = [];
        $allKeys = [];
        $latestByKeyAndGrade = [];

        foreach ($rows as $row) {
            $date = $this->resolveDateValue($row[$columns['date']] ?? null);
            if ($date === null) {
                continue;
            }

            $key = $this->buildDisplayKey(
                (string) ($row[$columns['jenis']] ?? ''),
                (string) ($row[$columns['barang_jadi']] ?? ''),
            );

            if ($key === '') {
                continue;
            }

            $dateMap[$date] = true;
            $allKeys[$key] = true;

            $inflow = $this->toFloat($row[$columns['masuk']] ?? 0);
            $outflow = $this->toFloat($row[$columns['keluar']] ?? 0);

            $daily[$date][$key]['in'] = ($daily[$date][$key]['in'] ?? 0.0) + $inflow;
            $daily[$date][$key]['out'] = ($daily[$date][$key]['out'] ?? 0.0) + $outflow;

            $sAkhir = $columns['s_akhir'] !== null ? $this->toFloat($row[$columns['s_akhir']] ?? 0) : null;
            $gradeKey = (string) ($row['idGrade'] ?? ($row[$columns['barang_jadi']] ?? ''));
            $gradeKey = trim($gradeKey) === '' ? '__DEFAULT__' : (string) $gradeKey;

            $currentLatest = $latestByKeyAndGrade[$key][$gradeKey] ?? null;
            if ($currentLatest === null || strcmp($date, (string) $currentLatest['date']) >= 0) {
                $latestByKeyAndGrade[$key][$gradeKey] = ['date' => $date, 's_akhir' => $sAkhir];
            }
        }

        if ($dateMap === []) {
            $dateMap = $this->buildDateMapFromRange($startDate, $endDate);
        }

        $dates = array_keys($dateMap);
        sort($dates);

        $keys = array_keys($allKeys);
        $finalKeys = $this->applyColumnOrder($keys, $orderedColumns);

        $gridRows = [];
        foreach ($dates as $date) {
            $cells = [];
            foreach ($finalKeys as $key) {
                $cells[$key] = [
                    'in' => (float) ($daily[$date][$key]['in'] ?? 0.0),
                    'out' => (float) ($daily[$date][$key]['out'] ?? 0.0),
                ];
            }
            $gridRows[] = ['date' => $date, 'cells' => $cells];
        }

        $sAkhirByKey = [];
        $ctrByKey = [];
        $sAkhirTotal = 0.0;
        $ctrTotal = 0.0;

        foreach ($finalKeys as $key) {
            $sAkhir = 0.0;
            foreach (($latestByKeyAndGrade[$key] ?? []) as $latestByGrade) {
                $sAkhir += (float) (($latestByGrade['s_akhir'] ?? null) ?? 0.0);
            }
            $ctr = $sAkhir / $ctrDivisor;

            $sAkhirByKey[$key] = $sAkhir;
            $ctrByKey[$key] = $ctr;
            $sAkhirTotal += $sAkhir;
            $ctrTotal += $ctr;
        }

        $percentByKey = [];
        foreach ($finalKeys as $key) {
            $percentByKey[$key] = $sAkhirTotal > 0 ? (($sAkhirByKey[$key] ?? 0.0) / $sAkhirTotal) * 100 : 0.0;
        }

        return [
            'dates' => $dates,
            'columns' => $finalKeys,
            'rows' => $gridRows,
            's_akhir_by_column' => $sAkhirByKey,
            'percent_by_column' => $percentByKey,
            'ctr_by_column' => $ctrByKey,
            'totals' => ['s_akhir' => $sAkhirTotal, 'ctr' => $ctrTotal],
            'column_mapping' => $columns,
            'raw_rows' => $rows,
        ];
    }

    public function healthCheck(string $startDate, string $endDate): array
    {
        $rows = $this->fetch($startDate, $endDate);
        $columns = $this->resolveColumns($rows);

        $required = ['date', 'jenis', 'barang_jadi', 'masuk', 'keluar'];
        $missing = [];
        foreach ($required as $requiredKey) {
            if ($columns[$requiredKey] === null) {
                $missing[] = $requiredKey;
            }
        }

        return [
            'is_healthy' => $missing === [],
            'column_mapping' => $columns,
            'missing_mapped_columns' => $missing,
            'row_count' => count($rows),
        ];
    }

    private function applyColumnOrder(array $keys, array $orderedColumns): array
    {
        $keys = array_values(array_unique($keys));
        usort($keys, static fn(string $a, string $b): int => strcmp($a, $b));

        if ($orderedColumns === []) {
            return $keys;
        }

        $final = [];
        foreach ($orderedColumns as $column) {
            if (in_array($column, $keys, true)) {
                $final[] = $column;
            }
        }
        foreach ($keys as $key) {
            if (!in_array($key, $final, true)) {
                $final[] = $key;
            }
        }

        return $final;
    }

    private function buildDisplayKey(string $jenis, string $barangJadi): string
    {
        $jenisNormalized = $this->normalizeDisplayToken($jenis);
        $barangNormalized = $this->normalizeDisplayToken($barangJadi);

        if ($jenisNormalized === '' && $barangNormalized === '') {
            return '';
        }

        return trim($jenisNormalized . ' ' . $barangNormalized);
    }

    private function normalizeDisplayToken(string $value): string
    {
        $normalized = strtoupper(trim($value));
        $normalized = str_replace('FJLB', 'FILB', $normalized);
        $normalized = (string) preg_replace('/\s+/', ' ', $normalized);

        return trim($normalized);
    }

    private function buildDateMapFromRange(string $startDate, string $endDate): array
    {
        $dateMap = [];

        try {
            $period = CarbonPeriod::create(Carbon::parse($startDate)->startOfDay(), Carbon::parse($endDate)->startOfDay());
            foreach ($period as $date) {
                $dateMap[$date->format('Y-m-d')] = true;
            }
        } catch (\Throwable) {
        }

        return $dateMap;
    }

    private function normalizeRows(array $rows): array
    {
        return array_map(static fn($row): array => (array) $row, $rows);
    }

    private function resolveColumns(array $rows): array
    {
        $allKeys = [];
        foreach ($rows as $row) {
            foreach (array_keys($row) as $key) {
                $allKeys[$key] = true;
            }
        }

        $keys = array_keys($allKeys);

        return [
            'date' => $this->findMatchingKey($keys, ['date', 'tanggal', 'tgl', 'periode', 'hari']),
            'jenis' => $this->findMatchingKey($keys, ['jenis', 'jenis_kayu', 'type']),
            'barang_jadi' => $this->findMatchingKey($keys, ['namagrade', 'nama_grade', 'grade', 'namabarangjadi', 'nama_barang_jadi']),
            'masuk' => $this->findMatchingKey($keys, ['s4smasuk', 'masukall', 'masuk', 'inflow', 'debit']),
            'keluar' => $this->findMatchingKey($keys, ['keluarall', 's4skeluar', 's4sjual', 'jual', 'keluar', 'outflow', 'kredit']),
            's_akhir' => $this->findMatchingKey($keys, ['s4sakhir', 'akhir', 's_akhir', 'stokakhir', 'saldoakhir']),
            'ctr' => $this->findMatchingKey($keys, ['ctr', '#ctr']),
        ];
    }

    private function findMatchingKey(array $keys, array $candidates): ?string
    {
        $normalizedCandidates = array_map([$this, 'normalizeKey'], $candidates);
        $normalizedKeyMap = [];
        foreach ($keys as $key) {
            $normalizedKeyMap[$this->normalizeKey($key)][] = $key;
        }

        foreach ($normalizedCandidates as $candidate) {
            if (isset($normalizedKeyMap[$candidate][0])) {
                return $normalizedKeyMap[$candidate][0];
            }
        }

        foreach ($keys as $key) {
            $normalizedKey = $this->normalizeKey($key);
            foreach ($normalizedCandidates as $candidate) {
                if (str_contains($normalizedKey, $candidate)) {
                    return $key;
                }
            }
        }

        return null;
    }

    private function normalizeKey(string $key): string
    {
        return strtolower((string) preg_replace('/[^a-zA-Z0-9]+/', '', $key));
    }

    private function resolveDateValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        $timestamp = strtotime((string) $value);
        if ($timestamp === false) {
            return null;
        }

        return date('Y-m-d', $timestamp);
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

        $hasDot = str_contains($trimmed, '.');
        $hasComma = str_contains($trimmed, ',');

        if ($hasDot && $hasComma) {
            $lastDotPos = strrpos($trimmed, '.');
            $lastCommaPos = strrpos($trimmed, ',');
            if ($lastCommaPos !== false && $lastDotPos !== false && $lastCommaPos > $lastDotPos) {
                $trimmed = str_replace('.', '', $trimmed);
                $trimmed = str_replace(',', '.', $trimmed);
            } else {
                $trimmed = str_replace(',', '', $trimmed);
            }
        } elseif ($hasComma) {
            $trimmed = str_replace(',', '.', $trimmed);
        } else {
            $trimmed = str_replace(',', '', $trimmed);
        }

        return (float) $trimmed;
    }

    private function resolveBindings(string $query, array $bindings): array
    {
        return str_contains($query, '?') ? $bindings : [];
    }

    private function runProcedureQuery(string $startDate, string $endDate): array
    {
        $connectionName = config('reports.dashboard_s4s_v2.database_connection');
        $procedure = (string) config('reports.dashboard_s4s_v2.stored_procedure');
        $syntax = (string) config('reports.dashboard_s4s_v2.call_syntax', 'exec');
        $customQuery = config('reports.dashboard_s4s_v2.query');
        $parameterCount = (int) config('reports.dashboard_s4s_v2.parameter_count', 2);
        $parameterCount = max(0, min(2, $parameterCount));

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan dashboard s4s v2 belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan dashboard s4s v2 dikonfigurasi untuk SQL Server. '
                . 'Set DASHBOARD_S4S_V2_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        $allBindings = [$startDate, $endDate];
        $bindings = array_slice($allBindings, 0, $parameterCount);

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException(
                    'DASHBOARD_S4S_V2_REPORT_QUERY belum diisi. '
                    . 'Isi query manual jika menggunakan DASHBOARD_S4S_V2_REPORT_CALL_SYNTAX=query.',
                );

            return $connection->select($query, $this->resolveBindings($query, $bindings));
        }

        if (!preg_match('/^[A-Za-z0-9_$.]+$/', $procedure)) {
            throw new RuntimeException('Nama stored procedure tidak valid.');
        }

        $placeholders = $parameterCount === 0 ? '' : implode(', ', array_fill(0, $parameterCount, '?'));
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

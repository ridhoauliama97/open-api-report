<?php

namespace App\Services;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DashboardS4SReportService
{
    /**
     * @var array<string, string>
     */
    private const GROUP_LABELS = [
        'RAMBUNG A/B' => 'Rambung S4S A/B',
        'RAMBUNG A/C' => 'Rambung S4S A/C',
        'RAMBUNG C/C' => 'Rambung S4S C/C',
        'JABON NISOBO' => 'Jabon Nisobo S4S',
        'PULAI NISOBO' => 'Pulai Nisobo S4S',
    ];

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
            || $columns['awal'] === null
            || $columns['masuk'] === null
            || $columns['jual'] === null
            || $columns['keluar'] === null
        ) {
            $detectedColumns = array_keys($rows[0] ?? []);

            throw new RuntimeException(
                'Kolom wajib tidak ditemukan di hasil SPWps_LapDashboardS4S. '
                . 'Butuh kolom tanggal, jenis, grade, awal, masuk, jual, dan keluar. '
                . 'Kolom terdeteksi: ' . implode(', ', $detectedColumns),
            );
        }

        $ctrDivisor = (float) config('reports.dashboard_s4s.ctr_divisor', 65);
        if ($ctrDivisor <= 0) {
            $ctrDivisor = 65.0;
        }

        $dateMap = $this->buildDateMapFromRange($startDate, $endDate);
        $groupMetrics = [];
        $dailyFlow = [];

        foreach ($rows as $row) {
            $date = $this->resolveDateValue($row[$columns['date']] ?? null);
            if ($date === null) {
                continue;
            }

            $key = $this->buildGroupKey((string) ($row[$columns['jenis']] ?? ''), (string) ($row[$columns['barang_jadi']] ?? ''));
            if (!array_key_exists($key, self::GROUP_LABELS)) {
                continue;
            }

            $awal = $this->toFloat($row[$columns['awal']] ?? 0);
            $masuk = $this->toFloat($row[$columns['masuk']] ?? 0);
            $jual = $this->toFloat($row[$columns['jual']] ?? 0);
            $keluarTambahan = $this->toFloat($row[$columns['keluar']] ?? 0);
            $keluar = $jual + $keluarTambahan;

            if (!isset($groupMetrics[$key])) {
                $groupMetrics[$key] = [
                    'awal' => $awal,
                    'masuk_total' => 0.0,
                    'keluar_total' => 0.0,
                ];
            }

            // SP memberi S4SAwal yang sama berulang per hari; ambil nilai non-zero terakhir jika ada perubahan.
            if (abs($awal) > 0.000001 || abs((float) $groupMetrics[$key]['awal']) < 0.000001) {
                $groupMetrics[$key]['awal'] = $awal;
            }

            $groupMetrics[$key]['masuk_total'] += $masuk;
            $groupMetrics[$key]['keluar_total'] += $keluar;

            $dailyFlow[$date][$key]['masuk'] = ($dailyFlow[$date][$key]['masuk'] ?? 0.0) + $masuk;
            $dailyFlow[$date][$key]['keluar'] = ($dailyFlow[$date][$key]['keluar'] ?? 0.0) + $keluar;
        }

        $groups = [];
        foreach (self::GROUP_LABELS as $key => $label) {
            $awal = (float) ($groupMetrics[$key]['awal'] ?? 0.0);
            $masukTotal = (float) ($groupMetrics[$key]['masuk_total'] ?? 0.0);
            $keluarTotal = (float) ($groupMetrics[$key]['keluar_total'] ?? 0.0);
            $akhir = $awal + $masukTotal - $keluarTotal;
            $groups[$key] = [
                'key' => $key,
                'label' => $label,
                'akhir' => $akhir,
                'container' => $akhir / $ctrDivisor,
            ];
        }

        $rowsOut = [];
        $dates = array_keys($dateMap);
        sort($dates);

        foreach ($dates as $date) {
            $cells = [];
            foreach (array_keys(self::GROUP_LABELS) as $groupKey) {
                $cells[$groupKey] = [
                    'masuk' => (float) ($dailyFlow[$date][$groupKey]['masuk'] ?? 0.0),
                    'keluar' => (float) ($dailyFlow[$date][$groupKey]['keluar'] ?? 0.0),
                    'akhir' => (float) ($groups[$groupKey]['akhir'] ?? 0.0),
                ];
            }
            $rowsOut[] = ['date' => $date, 'cells' => $cells];
        }

        return [
            'groups' => $groups,
            'rows' => $rowsOut,
            'dates' => $dates,
            'column_mapping' => $columns,
            'raw_rows' => $rows,
        ];
    }

    public function healthCheck(string $startDate, string $endDate): array
    {
        $rows = $this->fetch($startDate, $endDate);
        $columns = $this->resolveColumns($rows);

        $required = ['date', 'jenis', 'barang_jadi', 'awal', 'masuk', 'jual', 'keluar'];
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

    private function buildGroupKey(string $jenis, string $barangJadi): string
    {
        return strtoupper(trim($jenis . ' ' . $barangJadi));
    }

    /**
     * @return array<string, bool>
     */
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

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<string, string|null>
     */
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
            'barang_jadi' => $this->findMatchingKey($keys, ['namagrade', 'nama_grade', 'grade']),
            'awal' => $this->findMatchingKey($keys, ['s4sawal', 'awal']),
            'masuk' => $this->findMatchingKey($keys, ['s4smasuk', 'masuk']),
            'jual' => $this->findMatchingKey($keys, ['s4sjual', 'jual']),
            'keluar' => $this->findMatchingKey($keys, ['s4skeluar', 'keluar']),
        ];
    }

    /**
     * @param array<int, string> $keys
     * @param array<int, string> $candidates
     */
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
        $connectionName = config('reports.dashboard_s4s.database_connection');
        $procedure = (string) config('reports.dashboard_s4s.stored_procedure');
        $syntax = (string) config('reports.dashboard_s4s.call_syntax', 'exec');
        $customQuery = config('reports.dashboard_s4s.query');
        $parameterCount = (int) config('reports.dashboard_s4s.parameter_count', 2);
        $parameterCount = max(0, min(2, $parameterCount));

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan dashboard s4s belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan dashboard s4s dikonfigurasi untuk SQL Server. '
                . 'Set DASHBOARD_S4S_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        $allBindings = [$startDate, $endDate];
        $bindings = array_slice($allBindings, 0, $parameterCount);

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException(
                    'DASHBOARD_S4S_REPORT_QUERY belum diisi. '
                    . 'Isi query manual jika menggunakan DASHBOARD_S4S_REPORT_CALL_SYNTAX=query.',
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

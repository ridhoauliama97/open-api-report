<?php

namespace App\Services;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DashboardBarangJadiReportService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetch(string $startDate, string $endDate): array
    {
        $rows = $this->runProcedureQuery($startDate, $endDate);

        return $this->normalizeRows($rows);
    }

    /**
     * @return array<string, mixed>
     */
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
                'Kolom wajib tidak ditemukan di hasil SPWps_LapDashboardBJ. '
                . 'Butuh kolom tanggal, jenis, nama barang jadi, masuk, dan keluar. '
                . 'Kolom terdeteksi: ' . implode(', ', $detectedColumns),
            );
        }

        $orderedColumns = config('reports.dashboard_barang_jadi.column_order', []);
        $orderedColumns = is_array($orderedColumns) ? array_values($orderedColumns) : [];
        $ctrDivisor = (float) config('reports.dashboard_barang_jadi.ctr_divisor', 65);
        if ($ctrDivisor <= 0) {
            $ctrDivisor = 65.0;
        }

        $dateMap = [];
        $daily = [];
        $allKeys = [];
        $latestByKey = [];
        $ctrSumByKey = [];

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

            if (!isset($daily[$date])) {
                $daily[$date] = [];
            }

            if (!isset($daily[$date][$key])) {
                $daily[$date][$key] = ['in' => 0.0, 'out' => 0.0];
            }

            $daily[$date][$key]['in'] += $inflow;
            $daily[$date][$key]['out'] += $outflow;

            $sAkhir = $columns['s_akhir'] !== null ? $this->toFloat($row[$columns['s_akhir']] ?? 0) : null;
            $ctr = $columns['ctr'] !== null ? $this->toFloat($row[$columns['ctr']] ?? 0) : null;

            $currentLatest = $latestByKey[$key] ?? null;
            if ($currentLatest === null || strcmp($date, (string) $currentLatest['date']) >= 0) {
                $latestByKey[$key] = [
                    'date' => $date,
                    's_akhir' => $sAkhir,
                ];
            }

            if ($ctr !== null) {
                $ctrSumByKey[$key] = ($ctrSumByKey[$key] ?? 0.0) + $ctr;
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

            $gridRows[] = [
                'date' => $date,
                'cells' => $cells,
            ];
        }

        $sAkhirByKey = [];
        $ctrByKey = [];
        $sAkhirTotal = 0.0;
        $ctrTotal = 0.0;

        foreach ($finalKeys as $key) {
            $sAkhir = (float) (($latestByKey[$key]['s_akhir'] ?? null) ?? 0.0);
            $ctr = array_key_exists($key, $ctrSumByKey)
                ? (float) $ctrSumByKey[$key]
                : ($sAkhir / $ctrDivisor);

            $sAkhirByKey[$key] = $sAkhir;
            $ctrByKey[$key] = $ctr;

            $sAkhirTotal += $sAkhir;
            $ctrTotal += $ctr;
        }

        $percentByKey = [];
        foreach ($finalKeys as $key) {
            $percentByKey[$key] = $sAkhirTotal > 0
                ? (($sAkhirByKey[$key] ?? 0.0) / $sAkhirTotal) * 100
                : 0.0;
        }

        return [
            'dates' => $dates,
            'columns' => $finalKeys,
            'rows' => $gridRows,
            's_akhir_by_column' => $sAkhirByKey,
            'percent_by_column' => $percentByKey,
            'ctr_by_column' => $ctrByKey,
            'totals' => [
                's_akhir' => $sAkhirTotal,
                'ctr' => $ctrTotal,
            ],
            'column_mapping' => $columns,
            'raw_rows' => $rows,
        ];
    }

    /**
     * @return array<string, mixed>
     */
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

    /**
     * @param array<int, string> $keys
     * @param array<int, string> $orderedColumns
     * @return array<int, string>
     */
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

    /**
     * @return array<string, bool>
     */
    private function buildDateMapFromRange(string $startDate, string $endDate): array
    {
        $dateMap = [];

        try {
            $period = CarbonPeriod::create(
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->startOfDay(),
            );

            foreach ($period as $date) {
                $dateMap[$date->format('Y-m-d')] = true;
            }
        } catch (\Throwable) {
            // Abaikan jika tanggal tidak valid, fallback akan tetap empty.
        }

        return $dateMap;
    }

    /**
     * @param array<int, object> $rows
     * @return array<int, array<string, mixed>>
     */
    private function normalizeRows(array $rows): array
    {
        return array_map(static fn($row): array => (array) $row, $rows);
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array{date: ?string, jenis: ?string, barang_jadi: ?string, masuk: ?string, keluar: ?string, s_akhir: ?string, ctr: ?string}
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
            'barang_jadi' => $this->findMatchingKey($keys, ['namabarangjadi', 'nama_barang_jadi', 'barangjadi', 'barang_jadi']),
            'masuk' => $this->findMatchingKey($keys, ['masukall', 'masuk', 'inflow', 'debit']),
            'keluar' => $this->findMatchingKey($keys, ['keluarall', 'jual', 'keluar', 'outflow', 'kredit']),
            's_akhir' => $this->findMatchingKey($keys, ['akhir', 's_akhir', 'stokakhir', 'saldoakhir']),
            'ctr' => $this->findMatchingKey($keys, ['ctr', '#ctr']),
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
        $connectionName = config('reports.dashboard_barang_jadi.database_connection');
        $procedure = (string) config('reports.dashboard_barang_jadi.stored_procedure');
        $syntax = (string) config('reports.dashboard_barang_jadi.call_syntax', 'exec');
        $customQuery = config('reports.dashboard_barang_jadi.query');
        $parameterCount = (int) config('reports.dashboard_barang_jadi.parameter_count', 2);
        $parameterCount = max(0, min(2, $parameterCount));

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan dashboard barang jadi belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan dashboard barang jadi dikonfigurasi untuk SQL Server. '
                . 'Set DASHBOARD_BARANG_JADI_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        $allBindings = [$startDate, $endDate];
        $bindings = array_slice($allBindings, 0, $parameterCount);

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException(
                    'DASHBOARD_BARANG_JADI_REPORT_QUERY belum diisi. '
                    . 'Isi query manual jika menggunakan DASHBOARD_BARANG_JADI_REPORT_CALL_SYNTAX=query.',
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

<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class TargetMasukBBReportService
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

        $columns = $this->resolveColumns($rows);
        $dayColumns = $this->resolvePeriodDays($startDate, $endDate);
        $dayIndexByLabel = [];
        foreach ($dayColumns as $index => $dayMeta) {
            $dayIndexByLabel[$dayMeta['label']] = $index;
        }

        $groupedRows = [];
        $chartLabels = array_map(static fn (array $day): string => $day['label'], $dayColumns);
        $summaryRows = [];
        $lbColumns = [];

        foreach ($rows as $row) {
            $groupName = $this->resolveGroupName($row, $columns);

            $targetHarian = $this->toFloat($row[$columns['target_harian']] ?? null);
            $targetBulanan = $this->toFloat($row[$columns['target_bulanan']] ?? null);
            $dateLabel = $this->resolveDateLabel($row[$columns['date']] ?? null);
            $hasilRaw = $this->toFloat($row[$columns['hasil']] ?? null);
            $hasilRounded = (float) round($hasilRaw, 0, PHP_ROUND_HALF_UP);
            $isLibur = $this->isLiburValue($row[$columns['keterangan']] ?? null);

            if (!isset($groupedRows[$groupName])) {
                $groupedRows[$groupName] = [
                    'jenis' => $groupName,
                    'target_harian' => $targetHarian,
                    'target_bulanan' => $targetBulanan,
                    'daily_values' => array_fill(0, count($dayColumns), 0.0),
                    'lb_values' => [],
                    'raw_total' => 0.0,
                    'total' => 0,
                ];
            }

            if ($targetHarian > 0) {
                $groupedRows[$groupName]['target_harian'] = $targetHarian;
            }
            if ($targetBulanan > 0) {
                $groupedRows[$groupName]['target_bulanan'] = $targetBulanan;
            }

            if ($dateLabel !== null && isset($dayIndexByLabel[$dateLabel])) {
                $dayIndex = $dayIndexByLabel[$dateLabel];
                $groupedRows[$groupName]['daily_values'][$dayIndex] = $hasilRounded;

                if ($isLibur) {
                    $dayColumns[$dayIndex]['is_lb_after'] = true;
                    $groupedRows[$groupName]['lb_values'][$dateLabel] = 0.0;
                    $lbColumns[$dateLabel] = true;
                }
            }

            $groupedRows[$groupName]['raw_total'] += $hasilRaw;
        }

        ksort($groupedRows);
        $tableRows = array_values($groupedRows);
        $chartSeries = [];

        foreach ($tableRows as $index => $row) {
            $totalRounded = (int) round($row['raw_total'], 0, PHP_ROUND_HALF_UP);
            $tableRows[$index]['total'] = $totalRounded;
            $summaryRows[] = [
                'jenis' => $row['jenis'],
                'avg' => $row['daily_values'] === [] ? 0.0 : round($totalRounded / count($row['daily_values']), 0, PHP_ROUND_HALF_UP),
                'min' => $row['daily_values'] === [] ? 0.0 : min($row['daily_values']),
                'max' => $row['daily_values'] === [] ? 0.0 : max($row['daily_values']),
            ];

            $chartSeries[$row['jenis']] = $row['daily_values'];
        }

        return [
            'rows' => $rows,
            'columns' => $columns,
            'day_columns' => $dayColumns,
            'lb_columns' => $lbColumns,
            'table_rows' => $tableRows,
            'summary_rows' => $summaryRows,
            'chart_labels' => $chartLabels,
            'chart_series' => $chartSeries,
            'period_text' => sprintf('Dari %s Sampai %s', $this->formatDate($startDate), $this->formatDate($endDate)),
            'group_column' => $columns['group'],
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array{group: ?string, jenis: ?string, target_harian: ?string, target_bulanan: ?string, total: ?string, hasil: ?string, date: ?string, keterangan: ?string}
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
            'group' => $this->findMatchingKey($keys, ['nama_group', 'nama group', 'group', 'kelompok']),
            'jenis' => $this->findMatchingKey($keys, ['jenis', 'type', 'nama_jenis']),
            'target_harian' => $this->findMatchingKey($keys, ['target_hari', 'tgthari', 'tgtperhari', 'targetharian', 'target_harian']),
            'target_bulanan' => $this->findMatchingKey($keys, ['target_bulan', 'tgtbulan', 'targetbulanan', 'target_bulanan']),
            'total' => $this->findMatchingKey($keys, ['total', 'jumlah_total', 'jml_total']),
            'hasil' => $this->findMatchingKey($keys, ['hasil', 'realisasi', 'nilai']),
            'date' => $this->findMatchingKey($keys, ['date', 'tanggal', 'tgl']),
            'keterangan' => $this->findMatchingKey($keys, ['keterangan', 'remark', 'status']),
        ];
    }

    /**
     * @return array<int, array{day: int, label: string, is_lb_after: bool}>
     */
    private function resolvePeriodDays(string $startDate, string $endDate): array
    {
        $dayColumns = [];
        $current = strtotime($startDate);
        $end = strtotime($endDate);
        if ($current === false || $end === false) {
            return $dayColumns;
        }

        while ($current <= $end) {
            $dayNumber = (int) date('d', $current);
            $dayColumns[] = [
                'day' => $dayNumber,
                'label' => str_pad((string) $dayNumber, 2, '0', STR_PAD_LEFT),
                'is_lb_after' => false,
            ];
            $current = strtotime('+1 day', $current);
            if ($current === false) {
                break;
            }
        }

        return $dayColumns;
    }

    /**
     * @param array<int, string> $keys
     * @param array<int, string> $candidates
     */
    private function findMatchingKey(array $keys, array $candidates): ?string
    {
        $normalizedCandidates = array_map([$this, 'normalizeKey'], $candidates);

        foreach ($keys as $key) {
            $normalizedKey = $this->normalizeKey($key);
            if (in_array($normalizedKey, $normalizedCandidates, true)) {
                return $key;
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
     * @param array<string, mixed> $row
     * @param array{group: ?string, jenis: ?string, target_harian: ?string, target_bulanan: ?string, total: ?string, hasil: ?string, date: ?string, keterangan: ?string} $columns
     */
    private function resolveGroupName(array $row, array $columns): string
    {
        $groupName = trim((string) ($columns['group'] !== null ? ($row[$columns['group']] ?? '') : ''));
        if ($groupName !== '') {
            return $groupName;
        }

        $jenis = trim((string) ($columns['jenis'] !== null ? ($row[$columns['jenis']] ?? '') : ''));

        return $jenis === '' ? 'Tanpa Group' : $jenis;
    }

    private function resolveDateLabel(mixed $value): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('d');
        }

        if ($value === null) {
            return null;
        }

        $timestamp = strtotime((string) $value);
        if ($timestamp === false) {
            return null;
        }

        return date('d', $timestamp);
    }

    private function isLiburValue(mixed $value): bool
    {
        $text = strtoupper(trim((string) $value));

        return $text === 'LIBUR';
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
        $connectionName = config('reports.target_masuk_bb.database_connection');
        $procedure = (string) config('reports.target_masuk_bb.stored_procedure');
        $syntax = (string) config('reports.target_masuk_bb.call_syntax', 'exec');
        $customQuery = config('reports.target_masuk_bb.query');
        $parameterCount = (int) config('reports.target_masuk_bb.parameter_count', 2);
        $parameterCount = max(0, min(2, $parameterCount));

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan target masuk bahan baku belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan target masuk bahan baku dikonfigurasi untuk SQL Server. '
                . 'Set TARGET_MASUK_BB_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        $allBindings = [$startDate, $endDate];
        $bindings = array_slice($allBindings, 0, $parameterCount);

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException(
                    'TARGET_MASUK_BB_REPORT_QUERY belum diisi. '
                    . 'Isi query manual jika menggunakan TARGET_MASUK_BB_REPORT_CALL_SYNTAX=query.',
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

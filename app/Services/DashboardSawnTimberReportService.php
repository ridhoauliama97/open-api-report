<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class DashboardSawnTimberReportService
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
    public function buildChartData(string $startDate, string $endDate): array
    {
        $rows = $this->fetch($startDate, $endDate);
        $columns = $this->resolveColumns($rows);
        $orderedTypes = config('reports.dashboard_sawn_timber.type_order', []);
        $orderedTypes = is_array($orderedTypes) ? array_values($orderedTypes) : [];
        $ctrDivisor = (float) config('reports.dashboard_sawn_timber.ctr_divisor', 75);
        if ($ctrDivisor <= 0) {
            $ctrDivisor = 75.0;
        }

        if ($rows === []) {
            return [
                'dates' => [],
                'types' => [],
                'series_by_type' => [],
                'totals_by_type' => [],
                'stock_by_type' => [],
                'stock_totals' => ['s_akhir' => 0.0, 'ctr' => 0.0],
                'column_mapping' => $columns,
                'raw_rows' => [],
            ];
        }

        if (
            $columns['date'] === null
            || $columns['type'] === null
            || $columns['in'] === null
            || $columns['out'] === null
        ) {
            $detectedColumns = array_keys($rows[0] ?? []);

            throw new RuntimeException(
                'Kolom wajib tidak ditemukan di hasil SPWps_LapDashboardSawnTimber. '
                . 'Butuh kolom tanggal, jenis, masuk, dan keluar. '
                . 'Kolom terdeteksi: ' . implode(', ', $detectedColumns),
            );
        }

        $aggregated = [];

        foreach ($rows as $row) {
            $date = $this->resolveDateValue($row[$columns['date']] ?? null);
            if ($date === null) {
                continue;
            }

            $type = trim((string) ($row[$columns['type']] ?? 'Tanpa Jenis'));
            if ($type === '') {
                $type = 'Tanpa Jenis';
            }

            $inflow = $this->toFloat($row[$columns['in']] ?? 0);
            $outflow = $this->toFloat($row[$columns['out']] ?? 0);

            if (!isset($aggregated[$date])) {
                $aggregated[$date] = [];
            }

            if (!isset($aggregated[$date][$type])) {
                $aggregated[$date][$type] = ['in' => 0.0, 'out' => 0.0];
            }

            $aggregated[$date][$type]['in'] += $inflow;
            $aggregated[$date][$type]['out'] += $outflow;
        }

        $dates = array_keys($aggregated);
        sort($dates);

        $types = [];
        foreach ($aggregated as $dateRows) {
            foreach (array_keys($dateRows) as $type) {
                $types[$type] = true;
            }
        }
        $types = array_keys($types);
        sort($types);

        $seriesByType = [];
        $totalsByType = [];
        $stockByType = [];
        $keys = array_keys($rows[0] ?? []);
        $stockSakhirColumn = $this->findMatchingKey($keys, [
            's_akhir',
            'sakhir',
            'saldo_akhir',
            'saldoakhir',
            'stok_akhir',
            'stokakhir',
            'akhir2',
            'akhir',
        ]);
        $stockCtrColumn = $this->findMatchingKey($keys, [
            'ctr',
            '#ctr',
        ]);

        $stockAccumulator = [];
        $stockCtrAccumulator = [];

        foreach ($types as $type) {
            $seriesByType[$type] = ['in' => [], 'out' => []];
            $totalsByType[$type] = ['in' => 0.0, 'out' => 0.0];

            foreach ($dates as $date) {
                $inflow = (float) ($aggregated[$date][$type]['in'] ?? 0.0);
                $outflow = (float) ($aggregated[$date][$type]['out'] ?? 0.0);

                $seriesByType[$type]['in'][] = $inflow;
                $seriesByType[$type]['out'][] = $outflow;

                $totalsByType[$type]['in'] += $inflow;
                $totalsByType[$type]['out'] += $outflow;
            }

            $stockAccumulator[$type] = $totalsByType[$type]['in'] - $totalsByType[$type]['out'];
        }

        if ($stockSakhirColumn !== null || $stockCtrColumn !== null) {
            $latestByType = [];
            foreach ($rows as $row) {
                $type = trim((string) ($row[$columns['type']] ?? 'Tanpa Jenis'));
                if ($type === '') {
                    $type = 'Tanpa Jenis';
                }

                $date = $this->resolveDateValue($row[$columns['date']] ?? null);
                if ($date === null) {
                    continue;
                }

                $current = $latestByType[$type] ?? null;
                if ($current === null || strcmp($date, (string) $current['date']) >= 0) {
                    $latestByType[$type] = [
                        'date' => $date,
                        's_akhir' => $stockSakhirColumn !== null ? $this->toFloat($row[$stockSakhirColumn] ?? 0) : null,
                        'ctr' => $stockCtrColumn !== null ? $this->toFloat($row[$stockCtrColumn] ?? 0) : null,
                    ];
                }
            }

            foreach ($latestByType as $type => $values) {
                if ($values['s_akhir'] !== null) {
                    $stockAccumulator[$type] = (float) $values['s_akhir'];
                }
                if ($values['ctr'] !== null) {
                    $stockCtrAccumulator[$type] = (float) $values['ctr'];
                }
            }
        }

        $finalTypes = $types;
        foreach ($orderedTypes as $orderedType) {
            if (!in_array($orderedType, $finalTypes, true)) {
                $finalTypes[] = $orderedType;
            }
        }

        usort($finalTypes, function (string $a, string $b) use ($orderedTypes): int {
            $aIndex = array_search($a, $orderedTypes, true);
            $bIndex = array_search($b, $orderedTypes, true);

            $aRank = $aIndex === false ? PHP_INT_MAX : $aIndex;
            $bRank = $bIndex === false ? PHP_INT_MAX : $bIndex;

            if ($aRank === $bRank) {
                return strcmp($a, $b);
            }

            return $aRank <=> $bRank;
        });

        $stockTotalSakhir = 0.0;
        $stockTotalCtr = 0.0;
        foreach ($finalTypes as $type) {
            $sAkhir = (float) ($stockAccumulator[$type] ?? 0.0);
            $ctr = array_key_exists($type, $stockCtrAccumulator)
                ? (float) $stockCtrAccumulator[$type]
                : $sAkhir / $ctrDivisor;

            $stockByType[$type] = [
                's_akhir' => $sAkhir,
                'ctr' => $ctr,
            ];

            $stockTotalSakhir += $sAkhir;
            $stockTotalCtr += $ctr;
        }

        return [
            'dates' => $dates,
            'types' => $finalTypes,
            'series_by_type' => $seriesByType,
            'totals_by_type' => $totalsByType,
            'daily_in_totals' => $this->buildDailyTotals($finalTypes, $dates, $seriesByType, 'in'),
            'daily_out_totals' => $this->buildDailyTotals($finalTypes, $dates, $seriesByType, 'out'),
            'stock_by_type' => $stockByType,
            'stock_totals' => [
                's_akhir' => $stockTotalSakhir,
                'ctr' => $stockTotalCtr,
            ],
            'column_mapping' => $columns,
            'raw_rows' => $rows,
        ];
    }

    /**
     * @param array<int, string> $types
     * @param array<int, string> $dates
     * @param array<string, array{in: array<int, float>, out: array<int, float>}> $seriesByType
     * @return array<int, float>
     */
    private function buildDailyTotals(array $types, array $dates, array $seriesByType, string $direction): array
    {
        $totals = array_fill(0, count($dates), 0.0);

        foreach ($types as $type) {
            $series = $seriesByType[$type][$direction] ?? [];
            foreach ($series as $idx => $value) {
                $totals[$idx] += (float) $value;
            }
        }

        return $totals;
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
     * @return array{date: ?string, type: ?string, in: ?string, out: ?string}
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
            'date' => $this->findMatchingKey($keys, [
                'tanggal',
                'tgl',
                'date',
                'tanggal_transaksi',
                'tgl_transaksi',
                'periode',
                'hari',
            ]),
            'type' => $this->findMatchingKey($keys, [
                'jenis',
                'jenis_kayu',
                'nama_jenis',
                'kategori',
                'type',
            ]),
            'in' => $this->findMatchingKey($keys, [
                'masuk',
                'qty_masuk',
                'jumlah_masuk',
                'inflow',
                'debit',
                'penerimaan',
                'ton_masuk',
                'm3_masuk',
                'volume_masuk',
            ]),
            'out' => $this->findMatchingKey($keys, [
                'keluar',
                'qty_keluar',
                'jumlah_keluar',
                'outflow',
                'kredit',
                'pengeluaran',
                'ton_keluar',
                'm3_keluar',
                'volume_keluar',
            ]),
        ];
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
        $connectionName = config('reports.dashboard_sawn_timber.database_connection');
        $procedure = (string) config('reports.dashboard_sawn_timber.stored_procedure');
        $syntax = (string) config('reports.dashboard_sawn_timber.call_syntax', 'exec');
        $customQuery = config('reports.dashboard_sawn_timber.query');
        $parameterCount = (int) config('reports.dashboard_sawn_timber.parameter_count', 2);
        $parameterCount = max(0, min(2, $parameterCount));

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan dashboard sawn timber belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan dashboard sawn timber dikonfigurasi untuk SQL Server. '
                . 'Set DASHBOARD_SAWN_TIMBER_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        $allBindings = [$startDate, $endDate];
        $bindings = array_slice($allBindings, 0, $parameterCount);

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException(
                    'DASHBOARD_SAWN_TIMBER_REPORT_QUERY belum diisi. '
                    . 'Isi query manual jika menggunakan DASHBOARD_SAWN_TIMBER_REPORT_CALL_SYNTAX=query.',
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

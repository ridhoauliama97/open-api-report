<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PembelianStTimelineTonReportService
{
    /**
     * Output shape:
     * - month_columns: [{ key: 'YYYY-MM', year: 2024, month: 2, label: '02' }]
     * - year_groups: [{ year: 2024, months: ['YYYY-MM', ...] }]
     * - rows: [{ supplier, values: {monthKey => ton}, total_ton }]
     * - totals: { by_month: {monthKey => ton}, grand_total }
     *
     * @return array<string, mixed>
     */
    public function buildReportData(string $startDate, string $endDate): array
    {
        $rows = $this->fetch($startDate, $endDate);

        /** @var array<string, array<string, float>> $bySupplier */
        $bySupplier = [];
        /** @var array<string, float> $totalsByMonth */
        $totalsByMonth = [];
        /** @var array<string, array{key: string, year: int, month: int, label: string}> $monthMeta */
        $monthMeta = [];

        foreach ($rows as $row) {
            $supplier = trim((string) ($row['supplier'] ?? ''));
            $supplier = $supplier !== '' ? $supplier : '-';

            $monthKey = (string) ($row['month_key'] ?? '');
            $monthLabel = (string) ($row['month_label'] ?? $monthKey);
            $year = (int) ($row['year'] ?? 0);
            $month = (int) ($row['month'] ?? 0);

            $ton = (float) ($row['ton'] ?? 0.0);

            if ($monthKey === '') {
                $monthKey = 'Tanpa Periode';
            }

            if (!isset($bySupplier[$supplier])) {
                $bySupplier[$supplier] = [];
            }

            $bySupplier[$supplier][$monthKey] = (float) ($bySupplier[$supplier][$monthKey] ?? 0.0) + $ton;
            $totalsByMonth[$monthKey] = (float) ($totalsByMonth[$monthKey] ?? 0.0) + $ton;

            if (!isset($monthMeta[$monthKey])) {
                $monthMeta[$monthKey] = [
                    'key' => $monthKey,
                    'year' => $year,
                    'month' => $month,
                    'label' => $monthLabel !== '' ? $monthLabel : $monthKey,
                ];
            }
        }

        $monthKeys = array_keys($totalsByMonth);
        usort($monthKeys, function (string $a, string $b): int {
            $da = $this->tryParseMonthKey($a);
            $db = $this->tryParseMonthKey($b);
            if ($da !== null && $db !== null) {
                return $da <=> $db;
            }
            if ($da !== null && $db === null) {
                return -1;
            }
            if ($da === null && $db !== null) {
                return 1;
            }
            return strnatcasecmp($a, $b);
        });

        $monthColumns = [];
        $yearGroups = [];
        foreach ($monthKeys as $key) {
            $meta = $monthMeta[$key] ?? ['key' => $key, 'year' => 0, 'month' => 0, 'label' => $key];
            $monthColumns[] = $meta;

            $y = (int) ($meta['year'] ?? 0);
            if ($y > 0) {
                if (!isset($yearGroups[$y])) {
                    $yearGroups[$y] = ['year' => $y, 'months' => []];
                }
                $yearGroups[$y]['months'][] = $key;
            }
        }

        ksort($yearGroups);

        $supplierKeys = array_keys($bySupplier);
        sort($supplierKeys, SORT_NATURAL | SORT_FLAG_CASE);

        $outRows = [];
        foreach ($supplierKeys as $supplier) {
            $values = [];
            $rowTotal = 0.0;

            foreach ($monthKeys as $monthKey) {
                $v = (float) ($bySupplier[$supplier][$monthKey] ?? 0.0);
                $values[$monthKey] = $v;
                $rowTotal += (float) $v;
            }

            $outRows[] = [
                'supplier' => $supplier,
                'values' => $values,
                'total_ton' => $rowTotal,
            ];
        }

        $grandTotal = array_sum(array_values($totalsByMonth));

        return [
            'month_columns' => array_values($monthColumns),
            'year_groups' => array_values($yearGroups),
            'month_keys' => array_values($monthKeys),
            'rows' => $outRows,
            'totals' => [
                'by_month' => $totalsByMonth,
                'grand_total' => $grandTotal,
            ],
            'summary' => [
                'total_rows' => count($rows),
                'total_suppliers' => count($outRows),
                'total_months' => count($monthKeys),
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
        $expectedColumns = config('reports.pembelian_st_timeline_ton.expected_columns', []);
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
     * Normalize stored procedure output into a stable shape:
     * - date_key: "Y-m-d" if parseable
     * - month_key: "Y-m" if parseable
     * - month_label: "m" (02, 03, ...)
     * - supplier: string
     * - ton: float
     *
     * @return array<int, array{month_key: string, month_label: string, year: int, month: int, supplier: string, ton: float}>
     */
    private function fetch(string $startDate, string $endDate): array
    {
        $raw = $this->runProcedureQuery($startDate, $endDate);

        $out = [];
        foreach ($raw as $row) {
            $item = (array) $row;

            $dateRaw = $item['TglLaporan']
                ?? $item['Tanggal']
                ?? $item['Tgl']
                ?? $item['DateCreate']
                ?? $item['DateUsage']
                ?? null;

            [$monthKey, $monthLabel, $year, $month] = $this->normalizeMonth($dateRaw);

            $supplier = (string) ($item['NmSupplier'] ?? $item['Supplier'] ?? '');
            $supplier = trim($supplier);

            $tonRaw = $item['STTon'] ?? $item['Ton'] ?? $item['TonST'] ?? $item['TonBerat'] ?? null;
            $ton = (float) ($this->toFloat($tonRaw) ?? 0.0);

            $out[] = [
                'month_key' => $monthKey,
                'month_label' => $monthLabel,
                'year' => $year,
                'month' => $month,
                'supplier' => $supplier,
                'ton' => $ton,
            ];
        }

        return $out;
    }

    /**
     * @return array{0: string, 1: string, 2: int, 3: int}
     */
    private function normalizeMonth(mixed $value): array
    {
        if ($value instanceof \DateTimeInterface) {
            $c = Carbon::instance($value)->locale('id');
            return [$c->format('Y-m'), $c->translatedFormat('M'), (int) $c->format('Y'), (int) $c->format('n')];
        }

        if (is_string($value)) {
            $t = trim($value);
            if ($t === '') {
                return ['Tanpa Periode', 'Tanpa Periode', 0, 0];
            }

            try {
                $c = Carbon::parse($t)->locale('id');
                return [$c->format('Y-m'), $c->translatedFormat('M'), (int) $c->format('Y'), (int) $c->format('n')];
            } catch (\Throwable) {
                return [$t, $t, 0, 0];
            }
        }

        if (is_numeric($value)) {
            // Rare, but keep stable.
            $t = (string) $value;
            return [$t, $t, 0, 0];
        }

        return ['Tanpa Periode', 'Tanpa Periode', 0, 0];
    }

    private function tryParseMonthKey(string $key): ?int
    {
        if (!preg_match('/^\\d{4}-\\d{2}$/', $key)) {
            return null;
        }

        try {
            return Carbon::parse($key . '-01')->getTimestamp();
        } catch (\Throwable) {
            return null;
        }
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
        $configKey = 'reports.pembelian_st_timeline_ton';
        $connectionName = config("{$configKey}.database_connection");
        $procedure = (string) config("{$configKey}.stored_procedure", 'SP_LapPembelianSTTimeline');
        $syntax = (string) config("{$configKey}.call_syntax", 'exec');
        $customQuery = config("{$configKey}.query");
        $parameterCount = (int) config("{$configKey}.parameter_count", 2);

        if ($parameterCount !== 2) {
            throw new RuntimeException('Jumlah parameter laporan Pembelian ST Time Line (Ton) harus 2 (Tanggal Awal dan Tanggal Akhir).');
        }

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan Pembelian ST Time Line (Ton) belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan Pembelian ST Time Line (Ton) dikonfigurasi untuk SQL Server. '
                . 'Set PEMBELIAN_ST_TIMELINE_TON_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException('Query manual laporan Pembelian ST Time Line (Ton) belum diisi.');

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

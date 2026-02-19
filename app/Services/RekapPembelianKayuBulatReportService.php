<?php

namespace App\Services;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RekapPembelianKayuBulatReportService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetch(string $startDate, string $endDate): array
    {
        $rows = $this->runProcedureQuery($startDate, $endDate);

        return array_map(static fn($row): array => (array) $row, $rows);
    }

    /**
     * @return array<string, mixed>
     */
    public function buildReportData(string $startDate, string $endDate): array
    {
        $rows = $this->fetch($startDate, $endDate);
        $columns = $this->resolveColumns($rows);
        $startYear = (int) date('Y', strtotime($startDate));
        $endYear = (int) date('Y', strtotime($endDate));
        if ($endYear < $startYear) {
            [$startYear, $endYear] = [$endYear, $startYear];
        }
        $years = range($startYear, $endYear);
        $monthLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

        if ($rows === []) {
            return [
                'rows' => [],
                'columns' => $columns,
                'dates' => [],
                'types' => [],
                'series_by_type' => [],
                'totals_by_type' => [],
                'daily_totals' => [],
                'grand_total' => 0.0,
                'chart_years' => $years,
                'chart_month_labels' => $monthLabels,
                'chart_series_by_year' => [],
                'yearly_totals' => [],
            ];
        }

        if ($columns['amount'] === null) {
            $detectedColumns = array_keys($rows[0] ?? []);
            throw new RuntimeException(
                'Kolom nilai pembelian tidak ditemukan pada hasil SPWps_LapRekapPembelianKayuBulat. '
                . 'Kolom terdeteksi: ' . implode(', ', $detectedColumns),
            );
        }

        if ($columns['date'] === null && ($columns['year'] === null || $columns['month'] === null)) {
            $detectedColumns = array_keys($rows[0] ?? []);
            throw new RuntimeException(
                'Kolom tanggal atau pasangan Tahun+Bulan tidak ditemukan pada hasil SPWps_LapRekapPembelianKayuBulat. '
                . 'Kolom terdeteksi: ' . implode(', ', $detectedColumns),
            );
        }

        $aggregated = [];
        $totalsByType = [];
        $dailyTotals = [];
        $monthlyByYear = [];
        $yearlyTotals = [];
        foreach ($years as $year) {
            $monthlyByYear[$year] = array_fill(0, 12, 0.0);
            $yearlyTotals[$year] = 0.0;
        }
        $filteredRows = [];

        foreach ($rows as $row) {
            $amount = $this->toFloat($row[$columns['amount']] ?? 0);
            $type = $this->resolveType($row[$columns['type']] ?? null);
            $year = null;
            $monthIndex = null;
            $date = null;

            if ($columns['date'] !== null) {
                $date = $this->resolveDateValue($row[$columns['date']] ?? null);
                if ($date !== null) {
                    $year = (int) substr($date, 0, 4);
                    $monthIndex = (int) substr($date, 5, 2) - 1;
                }
            }

            if (($year === null || $monthIndex === null) && $columns['year'] !== null && $columns['month'] !== null) {
                $yearValue = $row[$columns['year']] ?? null;
                $monthValue = $row[$columns['month']] ?? null;
                $year = $this->resolveYearValue($yearValue);
                $monthIndex = $this->resolveMonthIndex($monthValue);
                if ($year !== null && $monthIndex !== null) {
                    $date = sprintf('%04d-%02d-01', $year, $monthIndex + 1);
                }
            }

            if ($year === null || $monthIndex === null || $date === null) {
                continue;
            }

            if ($year < $startYear || $year > $endYear) {
                continue;
            }

            if ($monthIndex < 0 || $monthIndex > 11) {
                continue;
            }

            $filteredRows[] = $row;

            if (!isset($aggregated[$date])) {
                $aggregated[$date] = [];
            }
            if (!isset($aggregated[$date][$type])) {
                $aggregated[$date][$type] = 0.0;
            }

            $aggregated[$date][$type] += $amount;
            $totalsByType[$type] = (float) ($totalsByType[$type] ?? 0.0) + $amount;
            $dailyTotals[$date] = (float) ($dailyTotals[$date] ?? 0.0) + $amount;
            $monthlyByYear[$year][$monthIndex] += $amount;
            $yearlyTotals[$year] += $amount;
        }

        $dates = array_keys($aggregated);
        sort($dates);

        $types = array_keys($totalsByType);
        sort($types);

        $seriesByType = [];
        foreach ($types as $type) {
            $seriesByType[$type] = [];
            foreach ($dates as $date) {
                $seriesByType[$type][] = (float) ($aggregated[$date][$type] ?? 0.0);
            }
        }

        $tableRows = [];
        foreach ($types as $type) {
            $tableRows[] = [
                'type' => $type,
                'total' => (float) ($totalsByType[$type] ?? 0.0),
            ];
        }

        usort($tableRows, static fn(array $a, array $b): int => $b['total'] <=> $a['total']);

        $grandTotal = array_sum($totalsByType);

        return [
            'rows' => $filteredRows,
            'columns' => $columns,
            'dates' => $dates,
            'types' => $types,
            'series_by_type' => $seriesByType,
            'totals_by_type' => $totalsByType,
            'daily_totals' => $dailyTotals,
            'table_rows' => $tableRows,
            'grand_total' => (float) $grandTotal,
            'chart_years' => $years,
            'chart_month_labels' => $monthLabels,
            'chart_series_by_year' => $monthlyByYear,
            'yearly_totals' => $yearlyTotals,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array{date: ?string, year: ?string, month: ?string, type: ?string, amount: ?string}
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
                'tanggal_beli',
                'tgl_beli',
                'tanggal_transaksi',
                'date',
            ]),
            'year' => $this->findMatchingKey($keys, [
                'tahun',
                'year',
                'thn',
            ]),
            'month' => $this->findMatchingKey($keys, [
                'bulan',
                'month',
                'bln',
            ]),
            'type' => $this->findMatchingKey($keys, [
                'jenis',
                'jenis_kayu',
                'nama_jenis',
                'kategori',
                'kelompok',
                'item',
                'produk',
                'supplier',
            ]),
            'amount' => $this->findMatchingKey($keys, [
                'total',
                'jumlah',
                'nilai',
                'volume',
                'qty',
                'kuantitas',
                'm3',
                'ton',
                'pembelian',
                'beli',
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

    private function resolveType(mixed $value): string
    {
        $type = trim((string) $value);

        return $type === '' ? 'Tanpa Jenis' : $type;
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

    private function resolveYearValue(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && preg_match('/^\d{4}$/', trim($value)) === 1) {
            return (int) trim($value);
        }

        return null;
    }

    private function resolveMonthIndex(mixed $value): ?int
    {
        if (is_int($value)) {
            $month = $value;
            return ($month >= 1 && $month <= 12) ? $month - 1 : null;
        }

        $text = strtolower(trim((string) $value));
        if ($text === '') {
            return null;
        }

        if (preg_match('/^\d{1,2}$/', $text) === 1) {
            $month = (int) $text;
            return ($month >= 1 && $month <= 12) ? $month - 1 : null;
        }

        $map = [
            'jan' => 0,
            'januari' => 0,
            'feb' => 1,
            'februari' => 1,
            'mar' => 2,
            'maret' => 2,
            'apr' => 3,
            'april' => 3,
            'mei' => 4,
            'may' => 4,
            'jun' => 5,
            'juni' => 5,
            'jul' => 6,
            'juli' => 6,
            'agu' => 7,
            'agt' => 7,
            'agustus' => 7,
            'aug' => 7,
            'sep' => 8,
            'sept' => 8,
            'september' => 8,
            'okt' => 9,
            'oct' => 9,
            'oktober' => 9,
            'nov' => 10,
            'november' => 10,
            'des' => 11,
            'dec' => 11,
            'desember' => 11,
        ];

        return $map[$text] ?? null;
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
        $connectionName = config('reports.rekap_pembelian_kayu_bulat.database_connection');
        $procedure = (string) config('reports.rekap_pembelian_kayu_bulat.stored_procedure');
        $syntax = (string) config('reports.rekap_pembelian_kayu_bulat.call_syntax', 'exec');
        $customQuery = config('reports.rekap_pembelian_kayu_bulat.query');
        $parameterCount = (int) config('reports.rekap_pembelian_kayu_bulat.parameter_count', 2);
        $parameterCount = max(0, min(2, $parameterCount));

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan rekap pembelian kayu bulat belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan rekap pembelian kayu bulat dikonfigurasi untuk SQL Server. '
                . 'Set REKAP_PEMBELIAN_KAYU_BULAT_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        $allBindings = [$startDate, $endDate];
        $bindings = array_slice($allBindings, 0, $parameterCount);

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException(
                    'REKAP_PEMBELIAN_KAYU_BULAT_REPORT_QUERY belum diisi. '
                    . 'Isi query manual jika menggunakan REKAP_PEMBELIAN_KAYU_BULAT_REPORT_CALL_SYNTAX=query.',
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

        try {
            return $connection->select($sql, $bindings);
        } catch (QueryException $exception) {
            $errorMessage = strtolower((string) $exception->getMessage());
            $isNoParameterProcedureError = str_contains($errorMessage, 'has no parameters')
                || str_contains($errorMessage, 'too many arguments specified');

            if ($parameterCount > 0 && $isNoParameterProcedureError) {
                $fallbackSql = "SET NOCOUNT ON; EXEC {$procedure}";

                return $connection->select($fallbackSql);
            }

            throw $exception;
        }
    }
}

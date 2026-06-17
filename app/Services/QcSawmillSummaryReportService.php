<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class QcSawmillSummaryReportService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetch(string $startDate, string $endDate): array
    {
        $rows = $this->runProcedureQuery($startDate, $endDate);

        return array_map(fn (object $row): array => $this->normalizeRow((array) $row), $rows);
    }

    /**
     * @return array<string, mixed>
     */
    public function buildReportData(string $startDate, string $endDate): array
    {
        $rows = $this->fetch($startDate, $endDate);

        if ($rows === []) {
            throw new RuntimeException('Data QC Sawmill - Summary tidak ditemukan untuk rentang tanggal yang dipilih.');
        }

        $dateKeys = [];
        $mejaRows = [];

        foreach ($rows as $row) {
            $tanggal = (string) ($row['Tgl'] ?? '');
            $namaMeja = trim((string) ($row['NamaMeja'] ?? ''));
            $meja = trim((string) ($row['Meja'] ?? ''));

            if ($tanggal !== '') {
                $dateKeys[$tanggal] = true;
            }

            $rowKey = $meja.'|'.$namaMeja;

            if (! isset($mejaRows[$rowKey])) {
                $mejaRows[$rowKey] = [
                    'meja' => $meja,
                    'nama_meja' => $namaMeja !== '' ? $namaMeja : 'Meja '.$meja,
                    'cells' => [],
                    'avg_accurate' => 0.0,
                    '_sort_meja' => is_numeric($meja) ? (float) $meja : PHP_FLOAT_MAX,
                ];
            }

            $mejaRows[$rowKey]['cells'][$tanggal] = [
                'accurate' => (float) ($row['Accurate'] ?? 0),
                'data' => (int) ($row['Data'] ?? 0),
                'acc' => (int) ($row['Acc'] ?? 0),
                'dev_tebal' => (float) ($row['DevTebal'] ?? 0),
                'dev_lebar' => (float) ($row['DevLebar'] ?? 0),
                'sum_dev_tebal' => (float) ($row['SumDevTebal'] ?? 0),
                'sum_dev_lebar' => (float) ($row['SumDevLebar'] ?? 0),
            ];
        }

        $dateKeys = array_keys($dateKeys);
        usort($dateKeys, static fn (string $a, string $b): int => strcmp($a, $b));

        $mejaRows = array_values($mejaRows);
        foreach ($mejaRows as &$mejaRow) {
            $values = array_map(
                static fn (array $cell): float => (float) ($cell['accurate'] ?? 0),
                array_values($mejaRow['cells'] ?? []),
            );
            $mejaRow['avg_accurate'] = $values !== [] ? array_sum($values) / count($values) : 0.0;
        }
        unset($mejaRow);

        usort($mejaRows, static function (array $a, array $b): int {
            $sortA = (float) ($a['_sort_meja'] ?? PHP_FLOAT_MAX);
            $sortB = (float) ($b['_sort_meja'] ?? PHP_FLOAT_MAX);

            if ($sortA === $sortB) {
                return strcmp((string) ($a['nama_meja'] ?? ''), (string) ($b['nama_meja'] ?? ''));
            }

            return $sortA <=> $sortB;
        });

        foreach ($mejaRows as &$mejaRow) {
            unset($mejaRow['_sort_meja']);
        }
        unset($mejaRow);

        return [
            'rows' => $rows,
            'date_keys' => $dateKeys,
            'meja_rows' => $mejaRows,
            'summary' => [
                'total_rows' => count($rows),
                'total_dates' => count($dateKeys),
                'total_meja' => count($mejaRows),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function healthCheck(string $startDate, string $endDate): array
    {
        $rows = $this->fetch($startDate, $endDate);
        $detectedColumns = array_keys($rows[0] ?? []);
        $expectedColumns = config('reports.qc_sawmill_summary.expected_columns', []);
        $expectedColumns = is_array($expectedColumns) ? array_values($expectedColumns) : [];

        return [
            'is_healthy' => empty(array_diff($expectedColumns, $detectedColumns)),
            'expected_columns' => $expectedColumns,
            'detected_columns' => $detectedColumns,
            'missing_columns' => array_values(array_diff($expectedColumns, $detectedColumns)),
            'extra_columns' => array_values(array_diff($detectedColumns, $expectedColumns)),
            'row_count' => count($rows),
        ];
    }

    /**
     * @return array<int, object>
     */
    private function runProcedureQuery(string $startDate, string $endDate): array
    {
        $configKey = 'reports.qc_sawmill_summary';
        $connectionName = config("{$configKey}.database_connection");
        $procedure = (string) config("{$configKey}.stored_procedure", 'SP_LapQCSawmillSummary');
        $syntax = (string) config("{$configKey}.call_syntax", 'exec');
        $customQuery = config("{$configKey}.query");

        if ($procedure === '' && ! is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan QC Sawmill - Summary belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan QC Sawmill - Summary dikonfigurasi untuk SQL Server. '
                .'Set QC_SAWMILL_SUMMARY_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException('Query manual laporan QC Sawmill - Summary belum diisi.');

            return $connection->select($query, str_contains($query, '?') ? [$startDate, $endDate] : []);
        }

        if (! preg_match('/^[A-Za-z0-9_$.]+$/', $procedure)) {
            throw new RuntimeException('Nama stored procedure laporan QC Sawmill - Summary tidak valid.');
        }

        $sql = match ($syntax) {
            'exec' => "SET NOCOUNT ON; EXEC {$procedure} @StartDate = ?, @EndDate = ?",
            'call' => "CALL {$procedure}(?, ?)",
            default => $driver === 'sqlsrv'
                ? "SET NOCOUNT ON; EXEC {$procedure} @StartDate = ?, @EndDate = ?"
                : "CALL {$procedure}(?, ?)",
        };

        try {
            return $connection->select($sql, [$startDate, $endDate]);
        } catch (\Throwable $exception) {
            throw new RuntimeException('Gagal mengambil data laporan QC Sawmill - Summary: '.$exception->getMessage(), 0, $exception);
        }
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function normalizeRow(array $row): array
    {
        return [
            'Meja' => trim((string) ($row['Meja'] ?? '')),
            'NamaMeja' => trim((string) ($row['NamaMeja'] ?? '')),
            'Tgl' => $this->normalizeDate($row['Tgl'] ?? null),
            'DevTebal' => $this->toFloat($row['DevTebal'] ?? null) ?? 0.0,
            'DevLebar' => $this->toFloat($row['DevLebar'] ?? null) ?? 0.0,
            'Data' => (int) round($this->toFloat($row['Data'] ?? null) ?? 0),
            'Acc' => (int) round($this->toFloat($row['Acc'] ?? null) ?? 0),
            'SumDevTebal' => $this->toFloat($row['SumDevTebal'] ?? null) ?? 0.0,
            'SumDevLebar' => $this->toFloat($row['SumDevLebar'] ?? null) ?? 0.0,
            'Accurate' => $this->toFloat($row['Accurate'] ?? null) ?? 0.0,
        ];
    }

    private function normalizeDate(mixed $value): string
    {
        if ($value === null || trim((string) $value) === '') {
            return '';
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return trim((string) $value);
        }
    }

    private function toFloat(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        if (! is_string($value)) {
            return null;
        }

        $normalized = trim(str_replace(' ', '', $value));
        if ($normalized === '') {
            return null;
        }

        if (str_contains($normalized, ',') && str_contains($normalized, '.')) {
            if (strrpos($normalized, ',') > strrpos($normalized, '.')) {
                $normalized = str_replace('.', '', $normalized);
                $normalized = str_replace(',', '.', $normalized);
            } else {
                $normalized = str_replace(',', '', $normalized);
            }
        } elseif (str_contains($normalized, ',')) {
            $normalized = str_replace(',', '.', $normalized);
        }

        return is_numeric($normalized) ? (float) $normalized : null;
    }
}

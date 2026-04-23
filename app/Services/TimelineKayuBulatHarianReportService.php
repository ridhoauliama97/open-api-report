<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TimelineKayuBulatHarianReportService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetch(string $startDate, string $endDate): array
    {
        $rows = $this->runProcedureQuery($startDate, $endDate);

        return array_map(function (object $row): array {
            $item = (array) $row;

            $dateColumn = $this->findColumn(array_keys($item), ['Tanggal', 'DateCreate', 'Tgl', 'Date']);
            $supplierColumn = $this->findColumn(array_keys($item), ['NmSupplier', 'NamaSupplier', 'Supplier']);
            $tonColumn = $this->findColumn(
                array_keys($item),
                ['TonBerat', 'KBTon', 'Tonase', 'Ton', 'Berat', 'TotalTon', 'Jumlah', 'Qty'],
            );
            $rankingColumn = $this->findColumn(array_keys($item), ['Ranking', 'Rank', 'Urutan', 'NoUrut']);

            if ($dateColumn !== null) {
                $item['Tanggal'] = (string) ($item[$dateColumn] ?? '');
            }

            if ($supplierColumn !== null) {
                $item['NmSupplier'] = trim((string) ($item[$supplierColumn] ?? ''));
            }

            if ($tonColumn !== null) {
                $item['TonBerat'] = $this->toFloat($item[$tonColumn] ?? null) ?? 0.0;
            } else {
                $item['TonBerat'] = 0.0;
            }

            $item['Ranking'] = $rankingColumn !== null ? (int) ($item[$rankingColumn] ?? 0) : 0;

            return $item;
        }, $rows);
    }

    /**
     * @return array<string, mixed>
     */
    public function buildReportData(string $startDate, string $endDate): array
    {
        $rows = $this->fetch($startDate, $endDate);
        $periodMap = [];
        $supplierTotals = [];

        foreach ($rows as $row) {
            $date = (string) ($row['Tanggal'] ?? '');
            $label = $date !== '' ? $date : 'Tanpa Tanggal';

            if (!isset($periodMap[$label])) {
                $periodMap[$label] = [
                    'key' => $label,
                    'label' => $label,
                    'rows' => [],
                    'total_ton' => 0.0,
                ];
            }

            $periodMap[$label]['rows'][] = $row;
            $periodMap[$label]['total_ton'] += (float) ($row['TonBerat'] ?? 0.0);

            $supplier = trim((string) ($row['NmSupplier'] ?? 'Tanpa Supplier'));
            $supplier = $supplier !== '' ? $supplier : 'Tanpa Supplier';
            $supplierTotals[$supplier] = ($supplierTotals[$supplier] ?? 0.0) + (float) ($row['TonBerat'] ?? 0.0);
        }

        ksort($periodMap);
        arsort($supplierTotals);

        return [
            'rows' => $rows,
            'periods' => array_values($periodMap),
            'summary' => [
                'total_rows' => count($rows),
                'total_periods' => count($periodMap),
                'total_ton' => array_sum(array_map(static fn(array $period): float => (float) $period['total_ton'], $periodMap)),
                'top_suppliers' => array_slice($supplierTotals, 0, 10, true),
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
        $expectedColumns = config('reports.timeline_kayu_bulat_harian.expected_columns', []);
        $expectedColumns = is_array($expectedColumns) ? array_values($expectedColumns) : [];
        $missingColumns = array_values(array_diff($expectedColumns, $detectedColumns));
        $extraColumns = array_values(array_diff($detectedColumns, $expectedColumns));

        return [
            'is_healthy' => empty($missingColumns),
            'expected_columns' => $expectedColumns,
            'detected_columns' => $detectedColumns,
            'missing_columns' => $missingColumns,
            'extra_columns' => $extraColumns,
            'row_count' => count($rows),
        ];
    }

    /**
     * @return array<int, object>
     */
    private function runProcedureQuery(string $startDate, string $endDate): array
    {
        $configKey = 'reports.timeline_kayu_bulat_harian';
        $connectionName = config("{$configKey}.database_connection");
        $procedure = (string) config("{$configKey}.stored_procedure", 'SP_LapTimelineKBHarian');
        $syntax = (string) config("{$configKey}.call_syntax", 'exec');
        $customQuery = config("{$configKey}.query");

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan timeline kayu bulat harian belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();
        $inclusiveStartDate = Carbon::parse($startDate)->subDay()->toDateString();
        $bindings = [$inclusiveStartDate, $endDate];

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan timeline kayu bulat harian dikonfigurasi untuk SQL Server. '
                . 'Set TIMELINE_KAYU_BULAT_HARIAN_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException(
                    'TIMELINE_KAYU_BULAT_HARIAN_REPORT_QUERY belum diisi. '
                    . 'Isi query manual jika menggunakan TIMELINE_KAYU_BULAT_HARIAN_REPORT_CALL_SYNTAX=query.',
                );

            $resolvedBindings = str_contains($query, '?') ? $bindings : [];

            return $connection->select($query, $resolvedBindings);
        }

        if (!preg_match('/^[A-Za-z0-9_$.]+$/', $procedure)) {
            throw new RuntimeException('Nama stored procedure tidak valid.');
        }

        $sql = match ($syntax) {
            'exec' => "SET NOCOUNT ON; EXEC {$procedure} @StartDate = ?, @EndDate = ?",
            'call' => "CALL {$procedure}(?, ?)",
            default => $driver === 'sqlsrv'
            ? "SET NOCOUNT ON; EXEC {$procedure} @StartDate = ?, @EndDate = ?"
            : "CALL {$procedure}(?, ?)",
        };

        return $connection->select($sql, $bindings);
    }

    private function findColumn(array $columns, array $candidates): ?string
    {
        $candidateSet = [];
        foreach ($candidates as $candidate) {
            $candidateSet[$this->normalizeName((string) $candidate)] = true;
        }

        foreach ($columns as $column) {
            if (isset($candidateSet[$this->normalizeName((string) $column)])) {
                return (string) $column;
            }
        }

        return null;
    }

    private function normalizeName(string $value): string
    {
        return preg_replace('/[^a-z0-9]/', '', strtolower($value)) ?? '';
    }

    private function toFloat(mixed $value): ?float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        if (!is_string($value)) {
            return null;
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }

        $trimmed = str_replace(' ', '', $trimmed);
        if (str_contains($trimmed, ',') && str_contains($trimmed, '.')) {
            if (strrpos($trimmed, ',') > strrpos($trimmed, '.')) {
                $trimmed = str_replace('.', '', $trimmed);
                $trimmed = str_replace(',', '.', $trimmed);
            } else {
                $trimmed = str_replace(',', '', $trimmed);
            }
        } elseif (str_contains($trimmed, ',')) {
            $trimmed = str_replace(',', '.', $trimmed);
        }

        return is_numeric($trimmed) ? (float) $trimmed : null;
    }
}

<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class TimelineKayuBulatHarianKgReportService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetch(string $startDate, string $endDate): array
    {
        $rows = $this->runProcedureQuery($startDate, $endDate);

        return array_map(function (object $row): array {
            $item = (array) $row;
            $item['TonBerat'] = $this->toFloat($item['TonBerat'] ?? null) ?? 0.0;
            $item['Ranking'] = (int) ($item['Ranking'] ?? 0);

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
                'total_ton' => array_sum(array_map(static fn (array $period): float => (float) $period['total_ton'], $periodMap)),
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
        $expectedColumns = config('reports.timeline_kayu_bulat_harian_kg.expected_columns', []);
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
        $configKey = 'reports.timeline_kayu_bulat_harian_kg';
        $connectionName = config("{$configKey}.database_connection");
        $procedure = (string) config("{$configKey}.stored_procedure");
        $syntax = (string) config("{$configKey}.call_syntax", 'exec');
        $customQuery = config("{$configKey}.query");

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan time line KB harian rambung timbang KG belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();
        $bindings = [$startDate, $endDate];

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan time line KB harian rambung timbang KG dikonfigurasi untuk SQL Server. '
                . 'Set TIMELINE_KAYU_BULAT_HARIAN_KG_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException('Query manual laporan time line KB harian rambung timbang KG belum diisi.');

            return $connection->select($query, str_contains($query, '?') ? $bindings : []);
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

        $trimmed = str_replace(',', '.', $trimmed);

        return is_numeric($trimmed) ? (float) $trimmed : null;
    }
}

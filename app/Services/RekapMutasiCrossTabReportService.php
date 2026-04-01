<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class RekapMutasiCrossTabReportService
{
    /**
     * @var array<string, string>
     */
    private const DISPLAY_COLUMNS = [
        'KB' => 'Zero Kayu Bulat',
        'KBKG' => 'Zero Kayu Bulat KG',
        'ST' => 'Stock Sawn Timber',
        'S4S' => 'Zero S4S',
        'FJ' => 'Zero Finger Joint',
        'MLD' => 'Zero Moulding',
        'LMT' => 'Zero Laminating',
        'CCAkhir' => 'Zero CCAkhir',
        'SAND' => 'Zero Sanding',
        'BJadi' => 'Zero Barang Jadi',
    ];

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
        $rawRows = $this->fetch($startDate, $endDate);

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'rows' => $this->transformTimelineRows($rawRows),
            'stats_rows' => $this->buildStatRows($rawRows),
            'summary' => [
                'row_count' => count($rawRows),
                'display_columns' => self::DISPLAY_COLUMNS,
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
        $expectedColumns = config('reports.rekap_mutasi_cross_tab.expected_columns', []);
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
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    private function transformTimelineRows(array $rows): array
    {
        $result = [];

        foreach ($rows as $row) {
            $displayRow = [
                'day' => isset($row['Tanggal']) ? date('d', strtotime((string) $row['Tanggal'])) : '',
                'metrics' => [],
            ];

            foreach (array_keys(self::DISPLAY_COLUMNS) as $source) {
                $displayRow['metrics'][$source] = $this->toFloat($row[$source] ?? null);
            }

            $result[] = $displayRow;
        }

        return $result;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    private function buildStatRows(array $rows): array
    {
        if ($rows === []) {
            return [];
        }

        $result = [];

        foreach (['Avg', 'Min', 'Max'] as $label) {
            $metrics = [];

            foreach (array_keys(self::DISPLAY_COLUMNS) as $source) {
                $values = array_values(array_filter(
                    array_map(fn(array $row): ?float => $this->toFloat($row[$source] ?? null), $rows),
                    static fn(?float $value): bool => $value !== null
                ));

                if ($values === []) {
                    $metrics[$source] = null;
                    continue;
                }

                $metrics[$source] = match ($label) {
                    'Avg' => array_sum($values) / count($values),
                    'Min' => min($values),
                    'Max' => max($values),
                };
            }

            $result[] = [
                'label' => $label,
                'metrics' => $metrics,
            ];
        }

        return $result;
    }

    /**
     * @return array<int, object>
     */
    private function runProcedureQuery(string $startDate, string $endDate): array
    {
        $connectionName = config('reports.rekap_mutasi_cross_tab.database_connection');
        $procedure = (string) config('reports.rekap_mutasi_cross_tab.stored_procedure', 'SP_LapRekapMutasi');
        $syntax = (string) config('reports.rekap_mutasi_cross_tab.call_syntax', 'exec');
        $customQuery = config('reports.rekap_mutasi_cross_tab.query');

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan rekap mutasi (cross tab) belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan rekap mutasi (cross tab) dikonfigurasi untuk SQL Server. '
                . 'Set REKAP_MUTASI_CROSS_TAB_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        $bindings = [$startDate, $endDate];

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException(
                    'REKAP_MUTASI_CROSS_TAB_REPORT_QUERY belum diisi. '
                    . 'Isi query manual jika menggunakan REKAP_MUTASI_CROSS_TAB_REPORT_CALL_SYNTAX=query.',
                );

            return $connection->select($query, str_contains($query, '?') ? $bindings : []);
        }

        if (!preg_match('/^[A-Za-z0-9_$.]+$/', $procedure)) {
            throw new RuntimeException('Nama stored procedure tidak valid.');
        }

        $sql = match ($syntax) {
            'exec' => "SET NOCOUNT ON; EXEC {$procedure} ?, ?",
            'call' => "CALL {$procedure}(?, ?)",
            default => $driver === 'sqlsrv' ? "SET NOCOUNT ON; EXEC {$procedure} ?, ?" : "CALL {$procedure}(?, ?)",
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

        $normalized = trim($value);
        if ($normalized === '') {
            return null;
        }

        $normalized = str_replace(' ', '', $normalized);

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

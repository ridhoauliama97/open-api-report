<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class DiscrepancyRekapMutasiReportService
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
    public function fetch(string $startDate, string $endDate, int $usingMode): array
    {
        $rows = $this->runProcedureQuery($startDate, $endDate, $usingMode);

        return array_map(static fn($row): array => (array) $row, $rows);
    }

    /**
     * @return array<string, mixed>
     */
    public function buildReportData(string $startDate, string $endDate): array
    {
        $rawSections = [
            'stock_non_spk' => $this->fetch($startDate, $endDate, 1),
            'stock_ber_spk' => $this->fetch($endDate, $endDate, 2),
            'stats_source' => $this->fetch($startDate, $endDate, 3),
        ];

        $stockNonSpkRows = $this->transformTimelineRows($rawSections['stock_non_spk']);
        $stockBerSpkRow = $this->transformSingleRow($rawSections['stock_ber_spk'][0] ?? null);
        [$stockTotalRows, $stockStatRows] = $this->buildStockTotalData($stockNonSpkRows, $stockBerSpkRow, $rawSections['stats_source']);

        $sections = [
            [
                'key' => 'stock_non_spk',
                'title' => 'Stock (Non SPK)',
                'rows' => $stockNonSpkRows,
                'single_row' => null,
                'raw_rows' => $rawSections['stock_non_spk'],
            ],
            [
                'key' => 'stock_ber_spk',
                'title' => 'Stock Ber-SPK',
                'rows' => [],
                'single_row' => $stockBerSpkRow,
                'raw_rows' => $rawSections['stock_ber_spk'],
            ],
            [
                'key' => 'stock_total',
                'title' => 'Stock Total',
                'rows' => $stockTotalRows,
                'stats_rows' => $stockStatRows,
                'single_row' => null,
                'raw_rows' => [],
            ],
        ];

        $summary = [
            'section_count' => count($sections),
            'total_rows' => array_sum(array_map(static fn(array $rows): int => count($rows), $rawSections)),
            'display_columns' => self::DISPLAY_COLUMNS,
        ];

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'sections' => $sections,
            'summary' => $summary,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function healthCheck(string $startDate, string $endDate): array
    {
        $rows = $this->fetch($startDate, $endDate, 1);
        $detectedColumns = array_keys($rows[0] ?? []);
        $expectedColumns = config('reports.discrepancy_rekap_mutasi.expected_columns', []);
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
     * @param array<int, array<string, mixed>> $rows
     * @return array<string, bool>
     */
    private function detectNumericColumns(array $rows): array
    {
        $columns = array_keys($rows[0] ?? []);
        $result = [];

        foreach ($columns as $column) {
            $result[$column] = false;
            foreach ($rows as $row) {
                if (!array_key_exists($column, $row)) {
                    continue;
                }

                if ($this->toFloat($row[$column]) !== null) {
                    $result[$column] = true;
                    break;
                }
            }
        }

        return $result;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @param array<string, bool> $numericColumns
     * @return array<string, float>
     */
    private function computeTotals(array $rows, array $numericColumns): array
    {
        $totals = [];

        foreach ($numericColumns as $column => $isNumeric) {
            if ($isNumeric) {
                $totals[$column] = 0.0;
            }
        }

        foreach ($rows as $row) {
            foreach ($totals as $column => $total) {
                $value = $this->toFloat($row[$column] ?? null);
                if ($value !== null) {
                    $totals[$column] += $value;
                }
            }
        }

        return $totals;
    }

    /**
     * @param array<int, array<string, mixed>> $stockNonSpkRows
     * @param array<string, mixed>|null $stockBerSpkRow
     * @return array<int, array<string, mixed>>
     */
    private function buildStockTotalData(array $stockNonSpkRows, ?array $stockBerSpkRow, array $statsSourceRows): array
    {
        if ($stockBerSpkRow === null) {
            return [[], []];
        }

        $combinedRows = [];
        foreach ($stockNonSpkRows as $row) {
            $metrics = [];
            foreach (self::DISPLAY_COLUMNS as $source => $label) {
                $metrics[$source] = (float) ($row['metrics'][$source] ?? 0) + (float) ($stockBerSpkRow['metrics'][$source] ?? 0);
            }

            $combinedRows[] = $metrics;
        }

        if ($combinedRows === []) {
            return [[], []];
        }

        $lastMetrics = $combinedRows[array_key_last($combinedRows)];
        $statsRows = $this->buildStatRows($statsSourceRows);

        return [[
            ['label' => 'Total', 'metrics' => $lastMetrics],
        ], $statsRows];
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

            foreach (self::DISPLAY_COLUMNS as $source => $label) {
                $displayRow['metrics'][$source] = $this->toFloat($row[$source] ?? null);
            }

            $result[] = $displayRow;
        }

        return $result;
    }

    /**
     * @param array<string, mixed>|null $row
     * @return array<string, mixed>|null
     */
    private function transformSingleRow(?array $row): ?array
    {
        if ($row === null) {
            return null;
        }

        $metrics = [];
        foreach (self::DISPLAY_COLUMNS as $source => $label) {
            $metrics[$source] = $this->toFloat($row[$source] ?? null);
        }

        return [
            'metrics' => $metrics,
        ];
    }

    /**
     * @return array<int, object>
     */
    private function runProcedureQuery(string $startDate, string $endDate, int $usingMode): array
    {
        $connectionName = config('reports.discrepancy_rekap_mutasi.database_connection');
        $procedure = (string) config('reports.discrepancy_rekap_mutasi.stored_procedure', 'SP_LapRekapMutasiV2');
        $syntax = (string) config('reports.discrepancy_rekap_mutasi.call_syntax', 'exec');
        $customQuery = config('reports.discrepancy_rekap_mutasi.query');

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan discrepancy rekap mutasi belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan discrepancy rekap mutasi dikonfigurasi untuk SQL Server. '
                . 'Set DISCREPANCY_REKAP_MUTASI_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        $bindings = [$startDate, $endDate, $usingMode];

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException(
                    'DISCREPANCY_REKAP_MUTASI_REPORT_QUERY belum diisi. '
                    . 'Isi query manual jika menggunakan DISCREPANCY_REKAP_MUTASI_REPORT_CALL_SYNTAX=query.',
                );

            return $connection->select($query, str_contains($query, '?') ? $bindings : []);
        }

        if (!preg_match('/^[A-Za-z0-9_$.]+$/', $procedure)) {
            throw new RuntimeException('Nama stored procedure tidak valid.');
        }

        $sql = match ($syntax) {
            'exec' => "SET NOCOUNT ON; EXEC {$procedure} ?, ?, ?",
            'call' => "CALL {$procedure}(?, ?, ?)",
            default => $driver === 'sqlsrv' ? "SET NOCOUNT ON; EXEC {$procedure} ?, ?, ?" : "CALL {$procedure}(?, ?, ?)",
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

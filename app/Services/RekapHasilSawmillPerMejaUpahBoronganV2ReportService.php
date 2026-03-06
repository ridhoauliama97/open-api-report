<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class RekapHasilSawmillPerMejaUpahBoronganV2ReportService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetch(string $startDate, string $endDate): array
    {
        return $this->normalizeRows($this->runProcedureQuery($startDate, $endDate, false));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetchSubReport(string $startDate, string $endDate): array
    {
        return $this->normalizeRows($this->runProcedureQuery($startDate, $endDate, true));
    }

    /**
     * @return array<string, mixed>
     */
    public function buildReportData(string $startDate, string $endDate): array
    {
        $rows = $this->fetch($startDate, $endDate);
        $subRows = $this->fetchSubReport($startDate, $endDate);
        $groupedRows = $this->groupRowsByMeja($rows);
        $groupedSubRows = $this->groupRowsByMeja($subRows);

        return [
            'rows' => $rows,
            'sub_rows' => $subRows,
            'grouped_rows' => $groupedRows,
            'grouped_sub_rows' => $groupedSubRows,
            'summary' => [
                'main' => $this->buildSummary($groupedRows, 'TonRacip'),
                'sub' => $this->buildSummary($groupedSubRows, 'TonRacip', 'SM'),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function healthCheck(string $startDate, string $endDate): array
    {
        $rows = $this->fetch($startDate, $endDate);
        $subRows = $this->fetchSubReport($startDate, $endDate);
        $detectedColumns = array_keys($rows[0] ?? []);
        $detectedSubColumns = array_keys($subRows[0] ?? []);
        $expectedColumns = config('reports.rekap_hasil_sawmill_per_meja_upah_borongan_v2.expected_columns', []);
        $expectedSubColumns = config('reports.rekap_hasil_sawmill_per_meja_upah_borongan_v2.expected_sub_columns', []);
        $expectedColumns = is_array($expectedColumns) ? array_values($expectedColumns) : [];
        $expectedSubColumns = is_array($expectedSubColumns) ? array_values($expectedSubColumns) : [];

        return [
            'is_healthy' => empty(array_diff($expectedColumns, $detectedColumns))
                && empty(array_diff($expectedSubColumns, $detectedSubColumns)),
            'expected_columns' => $expectedColumns,
            'detected_columns' => $detectedColumns,
            'missing_columns' => array_values(array_diff($expectedColumns, $detectedColumns)),
            'extra_columns' => array_values(array_diff($detectedColumns, $expectedColumns)),
            'row_count' => count($rows),
            'expected_sub_columns' => $expectedSubColumns,
            'detected_sub_columns' => $detectedSubColumns,
            'missing_sub_columns' => array_values(array_diff($expectedSubColumns, $detectedSubColumns)),
            'extra_sub_columns' => array_values(array_diff($detectedSubColumns, $expectedSubColumns)),
            'sub_row_count' => count($subRows),
        ];
    }

    /**
     * @param array<int, object> $rows
     * @return array<int, array<string, mixed>>
     */
    private function normalizeRows(array $rows): array
    {
        $normalized = array_map(function (object $row): array {
            $item = (array) $row;
            if (array_key_exists('TonRacip', $item)) {
                $item['TonRacip'] = $this->toFloat($item['TonRacip']);
            }
            if (array_key_exists('SM', $item)) {
                $item['SM'] = $this->toFloat($item['SM']);
            }

            return $item;
        }, $rows);

        usort($normalized, function (array $left, array $right): int {
            $leftMeja = (int) ($left['NoMeja'] ?? 0);
            $rightMeja = (int) ($right['NoMeja'] ?? 0);
            if ($leftMeja !== $rightMeja) {
                return $leftMeja <=> $rightMeja;
            }

            $leftDate = (string) ($left['TglSawmill'] ?? '');
            $rightDate = (string) ($right['TglSawmill'] ?? '');
            $dateCompare = strcmp($leftDate, $rightDate);
            if ($dateCompare !== 0) {
                return $dateCompare;
            }

            return strcmp((string) ($left['Jenis'] ?? ''), (string) ($right['Jenis'] ?? ''));
        });

        return array_values($normalized);
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array{no_meja: int, nama_meja: string, rows: array<int, array<string, mixed>>}>
     */
    private function groupRowsByMeja(array $rows): array
    {
        $grouped = [];

        foreach ($rows as $row) {
            $noMeja = (int) ($row['NoMeja'] ?? 0);
            $namaMeja = trim((string) ($row['NamaMeja'] ?? ''));
            $key = (string) $noMeja;

            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'no_meja' => $noMeja,
                    'nama_meja' => $namaMeja !== '' ? $namaMeja : ('Meja ' . $noMeja),
                    'rows' => [],
                ];
            }

            $grouped[$key]['rows'][] = $row;
        }

        ksort($grouped, SORT_NUMERIC);

        return array_values($grouped);
    }

    /**
     * @param array<int, array{no_meja: int, nama_meja: string, rows: array<int, array<string, mixed>>}> $groups
     * @return array<string, mixed>
     */
    private function buildSummary(array $groups, string $tonColumn, ?string $smColumn = null): array
    {
        $grandTon = 0.0;
        $grandSm = 0.0;
        $items = [];

        foreach ($groups as $group) {
            $totalTon = 0.0;
            $totalSm = 0.0;

            foreach ($group['rows'] as $row) {
                $totalTon += (float) ($row[$tonColumn] ?? 0.0);
                if ($smColumn !== null) {
                    $totalSm += (float) ($row[$smColumn] ?? 0.0);
                }
            }

            $grandTon += $totalTon;
            $grandSm += $totalSm;
            $items[] = [
                'no_meja' => $group['no_meja'],
                'nama_meja' => $group['nama_meja'],
                'total_rows' => count($group['rows']),
                'total_ton' => $totalTon,
                'total_sm' => $totalSm,
            ];
        }

        return [
            'total_meja' => count($groups),
            'total_rows' => array_sum(array_map(static fn(array $group): int => count($group['rows']), $groups)),
            'grand_total_ton' => $grandTon,
            'grand_total_sm' => $grandSm,
            'items' => $items,
        ];
    }

    /**
     * @return array<int, object>
     */
    private function runProcedureQuery(string $startDate, string $endDate, bool $isSub): array
    {
        $configKey = 'reports.rekap_hasil_sawmill_per_meja_upah_borongan_v2';
        $connectionName = config("{$configKey}.database_connection");
        $procedureKey = $isSub ? 'sub_stored_procedure' : 'stored_procedure';
        $queryKey = $isSub ? 'sub_query' : 'query';
        $procedure = (string) config("{$configKey}.{$procedureKey}", '');
        $syntax = (string) config("{$configKey}.call_syntax", 'exec');
        $customQuery = config("{$configKey}.{$queryKey}");
        $parameterCount = (int) config("{$configKey}.parameter_count", 2);

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan rekap hasil sawmill per meja belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();
        $bindings = array_slice([$startDate, $endDate], 0, max(0, min($parameterCount, 2)));

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan rekap hasil sawmill per meja dikonfigurasi untuk SQL Server. '
                . 'Set REKAP_HASIL_SAWMILL_PER_MEJA_UPAH_BORONGAN_V2_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException('Query manual laporan rekap hasil sawmill per meja belum diisi.');

            return $connection->select($query, str_contains($query, '?') ? $bindings : []);
        }

        if (!preg_match('/^[A-Za-z0-9_$.]+$/', $procedure)) {
            throw new RuntimeException('Nama stored procedure tidak valid.');
        }

        $sql = match ($syntax) {
            'exec' => "EXEC {$procedure} ?, ?",
            'call' => "CALL {$procedure}(?, ?)",
            default => $driver === 'sqlsrv' ? "EXEC {$procedure} ?, ?" : "CALL {$procedure}(?, ?)",
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

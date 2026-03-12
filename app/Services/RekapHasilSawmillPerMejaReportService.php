<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RekapHasilSawmillPerMejaReportService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetch(string $startDate, string $endDate): array
    {
        return $this->normalizeRows($this->runProcedureQuery($startDate, $endDate));
    }

    /**
     * @return array<string, mixed>
     */
    public function buildReportData(string $startDate, string $endDate): array
    {
        $rows = $this->fetch($startDate, $endDate);
        $dateKeys = $this->extractDateKeys($rows);
        $mejaGroups = $this->pivotRows($rows, $dateKeys);

        $totalsByDate = array_fill_keys($dateKeys, 0.0);
        $grandTotal = 0.0;

        foreach ($mejaGroups as $group) {
            foreach ($group['rows'] as $row) {
                foreach ($dateKeys as $dateKey) {
                    $value = (float) ($row['values'][$dateKey] ?? 0.0);
                    $totalsByDate[$dateKey] = (float) ($totalsByDate[$dateKey] ?? 0.0) + $value;
                }
                $grandTotal += (float) ($row['row_total'] ?? 0.0);
            }
        }

        return [
            'rows' => $rows,
            'date_keys' => $dateKeys,
            'meja_groups' => $mejaGroups,
            'totals_by_date' => $totalsByDate,
            'grand_total' => $grandTotal,
            'summary' => [
                'total_rows' => count($rows),
                'total_meja' => count($mejaGroups),
                'total_dates' => count($dateKeys),
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
        $expectedColumns = config('reports.rekap_hasil_sawmill_per_meja.expected_columns', []);
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
            if (array_key_exists('Tebal', $item)) {
                $item['Tebal'] = $this->toFloat($item['Tebal']);
            }

            return $item;
        }, $rows);

        usort($normalized, function (array $left, array $right): int {
            $leftMeja = (int) ($left['NoMeja'] ?? 0);
            $rightMeja = (int) ($right['NoMeja'] ?? 0);
            if ($leftMeja !== $rightMeja) {
                return $leftMeja <=> $rightMeja;
            }

            $leftTebal = (float) ($left['Tebal'] ?? 0.0);
            $rightTebal = (float) ($right['Tebal'] ?? 0.0);
            if (abs($leftTebal - $rightTebal) > 0.0000001) {
                return $leftTebal <=> $rightTebal;
            }

            return strcmp((string) ($left['TglSawmill'] ?? ''), (string) ($right['TglSawmill'] ?? ''));
        });

        return array_values($normalized);
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, string>
     */
    private function extractDateKeys(array $rows): array
    {
        $keys = [];
        foreach ($rows as $row) {
            $raw = trim((string) ($row['TglSawmill'] ?? ''));
            if ($raw === '') {
                continue;
            }
            try {
                $keys[Carbon::parse($raw)->format('Y-m-d')] = true;
            } catch (\Throwable $exception) {
                $keys[$raw] = true;
            }
        }

        $dateKeys = array_keys($keys);
        sort($dateKeys);

        return $dateKeys;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @param array<int, string> $dateKeys
     * @return array<int, array{no_meja: int, rows: array<int, array{tebal: float, uom: string, values: array<string, float>, row_total: float}>}>
     */
    private function pivotRows(array $rows, array $dateKeys): array
    {
        /** @var array<string, array{no_meja: int, rows: array<string, array{tebal: float, uom: string, values: array<string, float>, row_total: float}>}> $byMeja */
        $byMeja = [];

        foreach ($rows as $row) {
            $noMeja = (int) ($row['NoMeja'] ?? 0);
            $tebal = (float) ($row['Tebal'] ?? 0.0);
            $uom = trim((string) ($row['UOM'] ?? ''));
            $value = (float) ($row['TonRacip'] ?? 0.0);

            $dateKey = '';
            $rawDate = trim((string) ($row['TglSawmill'] ?? ''));
            if ($rawDate !== '') {
                try {
                    $dateKey = Carbon::parse($rawDate)->format('Y-m-d');
                } catch (\Throwable $exception) {
                    $dateKey = $rawDate;
                }
            }

            $mejaKey = (string) $noMeja;
            if (!isset($byMeja[$mejaKey])) {
                $byMeja[$mejaKey] = [
                    'no_meja' => $noMeja,
                    'rows' => [],
                ];
            }

            $rowKey = $tebal . '|' . $uom;
            if (!isset($byMeja[$mejaKey]['rows'][$rowKey])) {
                $byMeja[$mejaKey]['rows'][$rowKey] = [
                    'tebal' => $tebal,
                    'uom' => $uom,
                    'values' => array_fill_keys($dateKeys, 0.0),
                    'row_total' => 0.0,
                ];
            }

            if ($dateKey !== '' && in_array($dateKey, $dateKeys, true)) {
                $byMeja[$mejaKey]['rows'][$rowKey]['values'][$dateKey] =
                    (float) ($byMeja[$mejaKey]['rows'][$rowKey]['values'][$dateKey] ?? 0.0) + $value;
                $byMeja[$mejaKey]['rows'][$rowKey]['row_total'] += $value;
            }
        }

        ksort($byMeja, SORT_NUMERIC);

        $out = [];
        foreach ($byMeja as $group) {
            $rowsList = array_values($group['rows']);
            usort($rowsList, static function (array $a, array $b): int {
                $ta = (float) ($a['tebal'] ?? 0.0);
                $tb = (float) ($b['tebal'] ?? 0.0);
                if (abs($ta - $tb) > 0.0000001) {
                    return $ta <=> $tb;
                }
                return strcmp((string) ($a['uom'] ?? ''), (string) ($b['uom'] ?? ''));
            });

            $out[] = [
                'no_meja' => (int) $group['no_meja'],
                'rows' => $rowsList,
            ];
        }

        return $out;
    }

    /**
     * @return array<int, object>
     */
    private function runProcedureQuery(string $startDate, string $endDate): array
    {
        $configKey = 'reports.rekap_hasil_sawmill_per_meja';
        $connectionName = config("{$configKey}.database_connection");
        $procedure = (string) config("{$configKey}.stored_procedure", '');
        $syntax = (string) config("{$configKey}.call_syntax", 'exec');
        $customQuery = config("{$configKey}.query");
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
                . 'Set REKAP_HASIL_SAWMILL_PER_MEJA_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
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


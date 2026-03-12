<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class StSawmillHariTebalLebarReportService
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

        // Prevent overly wide tables by chunking date columns (10 dates/table).
        $maxDatesPerTable = (int) config('reports.st_sawmill_hari_tebal_lebar.max_dates_per_table', 10);
        $maxDatesPerTable = $maxDatesPerTable > 0 ? $maxDatesPerTable : 10;
        $dateChunks = array_chunk($dateKeys, $maxDatesPerTable);

        $isGroupBlocks = $this->buildBlocks($rows, $dateKeys);

        return [
            'rows' => $rows,
            'date_keys' => $dateKeys,
            'date_chunks' => $dateChunks,
            'is_group_blocks' => $isGroupBlocks,
            'summary' => [
                'total_rows' => count($rows),
                'total_dates' => count($dateKeys),
                'total_is_groups' => count($isGroupBlocks),
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
        $expectedColumns = config('reports.st_sawmill_hari_tebal_lebar.expected_columns', []);
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

            if (array_key_exists('STton', $item)) {
                $item['STton'] = $this->toFloat($item['STton']) ?? 0.0;
            }
            if (array_key_exists('Tebal', $item)) {
                $item['Tebal'] = $this->toFloat($item['Tebal']) ?? 0.0;
            }
            if (array_key_exists('Lebar', $item)) {
                $item['Lebar'] = $this->toFloat($item['Lebar']) ?? 0.0;
            }
            if (array_key_exists('IsGroup', $item)) {
                $item['IsGroup'] = (int) $item['IsGroup'];
            }
            if (array_key_exists('Group', $item)) {
                $item['Group'] = trim((string) $item['Group']);
            }

            return $item;
        }, $rows);

        usort($normalized, static function (array $a, array $b): int {
            $ga = (int) ($a['IsGroup'] ?? 0);
            $gb = (int) ($b['IsGroup'] ?? 0);
            if ($ga !== $gb) {
                return $ga <=> $gb;
            }

            $na = (string) ($a['Group'] ?? '');
            $nb = (string) ($b['Group'] ?? '');
            if ($na !== $nb) {
                return strcmp($na, $nb);
            }

            $ta = (float) ($a['Tebal'] ?? 0.0);
            $tb = (float) ($b['Tebal'] ?? 0.0);
            if (abs($ta - $tb) > 0.0000001) {
                return $ta <=> $tb;
            }

            $la = (float) ($a['Lebar'] ?? 0.0);
            $lb = (float) ($b['Lebar'] ?? 0.0);
            if (abs($la - $lb) > 0.0000001) {
                return $la <=> $lb;
            }

            return strcmp((string) ($a['TglSawmill'] ?? ''), (string) ($b['TglSawmill'] ?? ''));
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
     * @return array<int, array{is_group: int, groups: array<int, array{name: string, tebal_blocks: array<int, array{tebal: float, lebar_rows: array<int, array{lebar: float, values: array<string, float>}> , totals_by_date: array<string, float>}>, totals_by_date: array<string, float>}> , totals_by_date: array<string, float>}>
     */
    private function buildBlocks(array $rows, array $dateKeys): array
    {
        /** @var array<int, array{is_group: int, groups: array<string, mixed>}> $byIsGroup */
        $byIsGroup = [];

        foreach ($rows as $row) {
            $isGroup = (int) ($row['IsGroup'] ?? 0);
            $groupName = trim((string) ($row['Group'] ?? ''));
            $tebal = (float) ($row['Tebal'] ?? 0.0);
            $lebar = (float) ($row['Lebar'] ?? 0.0);
            $value = (float) ($row['STton'] ?? 0.0);

            $rawDate = trim((string) ($row['TglSawmill'] ?? ''));
            if ($rawDate === '') {
                continue;
            }

            try {
                $dateKey = Carbon::parse($rawDate)->format('Y-m-d');
            } catch (\Throwable $exception) {
                $dateKey = $rawDate;
            }

            if (!in_array($dateKey, $dateKeys, true)) {
                continue;
            }

            if (!isset($byIsGroup[$isGroup])) {
                $byIsGroup[$isGroup] = [
                    'is_group' => $isGroup,
                    'groups' => [],
                ];
            }

            if (!isset($byIsGroup[$isGroup]['groups'][$groupName])) {
                $byIsGroup[$isGroup]['groups'][$groupName] = [
                    'name' => $groupName,
                    'tebal_blocks' => [],
                    'totals_by_date' => array_fill_keys($dateKeys, 0.0),
                ];
            }

            $tebalKey = (string) $tebal;
            if (!isset($byIsGroup[$isGroup]['groups'][$groupName]['tebal_blocks'][$tebalKey])) {
                $byIsGroup[$isGroup]['groups'][$groupName]['tebal_blocks'][$tebalKey] = [
                    'tebal' => $tebal,
                    'lebar_rows' => [],
                    'totals_by_date' => array_fill_keys($dateKeys, 0.0),
                ];
            }

            $lebarKey = (string) $lebar;
            if (!isset($byIsGroup[$isGroup]['groups'][$groupName]['tebal_blocks'][$tebalKey]['lebar_rows'][$lebarKey])) {
                $byIsGroup[$isGroup]['groups'][$groupName]['tebal_blocks'][$tebalKey]['lebar_rows'][$lebarKey] = [
                    'lebar' => $lebar,
                    'values' => array_fill_keys($dateKeys, 0.0),
                ];
            }

            $leaf = &$byIsGroup[$isGroup]['groups'][$groupName]['tebal_blocks'][$tebalKey]['lebar_rows'][$lebarKey];
            $leaf['values'][$dateKey] = (float) ($leaf['values'][$dateKey] ?? 0.0) + $value;
            unset($leaf);

            $byIsGroup[$isGroup]['groups'][$groupName]['tebal_blocks'][$tebalKey]['totals_by_date'][$dateKey] =
                (float) ($byIsGroup[$isGroup]['groups'][$groupName]['tebal_blocks'][$tebalKey]['totals_by_date'][$dateKey] ?? 0.0) + $value;

            $byIsGroup[$isGroup]['groups'][$groupName]['totals_by_date'][$dateKey] =
                (float) ($byIsGroup[$isGroup]['groups'][$groupName]['totals_by_date'][$dateKey] ?? 0.0) + $value;
        }

        ksort($byIsGroup);

        $out = [];
        foreach ($byIsGroup as $isGroup => $block) {
            $groups = array_values($block['groups']);
            usort($groups, static fn(array $a, array $b): int => strcmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? '')));

            foreach ($groups as &$g) {
                $tebalBlocks = array_values($g['tebal_blocks']);
                usort($tebalBlocks, static function (array $a, array $b): int {
                    $ta = (float) ($a['tebal'] ?? 0.0);
                    $tb = (float) ($b['tebal'] ?? 0.0);
                    if (abs($ta - $tb) > 0.0000001) {
                        return $ta <=> $tb;
                    }
                    return 0;
                });

                foreach ($tebalBlocks as &$t) {
                    $lebarRows = array_values($t['lebar_rows']);
                    usort($lebarRows, static function (array $a, array $b): int {
                        $la = (float) ($a['lebar'] ?? 0.0);
                        $lb = (float) ($b['lebar'] ?? 0.0);
                        if (abs($la - $lb) > 0.0000001) {
                            return $la <=> $lb;
                        }
                        return 0;
                    });
                    $t['lebar_rows'] = $lebarRows;
                }
                unset($t);

                $g['tebal_blocks'] = $tebalBlocks;
            }
            unset($g);

            $totalsByDate = array_fill_keys($dateKeys, 0.0);
            foreach ($groups as $g) {
                foreach ($dateKeys as $dk) {
                    $totalsByDate[$dk] = (float) ($totalsByDate[$dk] ?? 0.0) + (float) ($g['totals_by_date'][$dk] ?? 0.0);
                }
            }

            $out[] = [
                'is_group' => (int) $isGroup,
                'groups' => $groups,
                'totals_by_date' => $totalsByDate,
            ];
        }

        return $out;
    }

    /**
     * @return array<int, object>
     */
    private function runProcedureQuery(string $startDate, string $endDate): array
    {
        $configKey = 'reports.st_sawmill_hari_tebal_lebar';
        $connectionName = config("{$configKey}.database_connection");
        $procedure = (string) config("{$configKey}.stored_procedure", '');
        $syntax = (string) config("{$configKey}.call_syntax", 'exec');
        $customQuery = config("{$configKey}.query");
        $parameterCount = (int) config("{$configKey}.parameter_count", 2);

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan ST sawmill per hari/tebal/lebar belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();
        $bindings = array_slice([$startDate, $endDate], 0, max(0, min($parameterCount, 2)));

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan ST sawmill per hari/tebal/lebar dikonfigurasi untuk SQL Server. '
                . 'Set ST_SAWMILL_HARI_TEBAL_LEBAR_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException('Query manual laporan ST sawmill per hari/tebal/lebar belum diisi.');

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
        if ($value === null) {
            return null;
        }

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


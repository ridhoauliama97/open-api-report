<?php

namespace App\Services\Concerns;

/**
 * Group rows by a single column key with sorting and fallback labels.
 *
 * Used by Ascends HRM report services that share the same grouping pattern:
 * accumulate rows by a column, sort naturally, then build
 * [{label, rows, summary}] output.
 */
trait GroupsRows
{
    /**
     * Group rows by a single column, with natural sorting and fallback label for empty values.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @param  string  $key  Column name to group by (e.g. 'Agama', 'Department').
     * @param  string  $labelPrefix  Prefix for each group label (e.g. 'Agama' → 'Agama : Islam').
     * @param  string  $fallbackLabel  Label used when the column value is empty.
     * @param  callable|null  $sortCallback  Optional custom sort: fn(array $keys): array.
     * @return list<array{label: string, rows: list<array<string, mixed>>}>
     */
    protected static function groupRowsByKey(
        array $rows,
        string $key,
        string $labelPrefix,
        string $fallbackLabel = '',
        ?callable $sortCallback = null,
    ): array {
        $grouped = [];

        foreach ($rows as $row) {
            $groupValue = trim((string) ($row[$key] ?? ''));
            $groupKey = $groupValue !== '' ? $groupValue : $fallbackLabel;
            $grouped[$groupKey][] = $row;
        }

        if ($sortCallback !== null) {
            uksort($grouped, $sortCallback);
        } else {
            ksort($grouped, SORT_NATURAL | SORT_FLAG_CASE);
        }

        $result = [];
        foreach ($grouped as $groupValue => $rowsInGroup) {
            $result[] = [
                'label' => $labelPrefix.' : '.$groupValue,
                'rows' => $rowsInGroup,
            ];
        }

        return $result;
    }
}

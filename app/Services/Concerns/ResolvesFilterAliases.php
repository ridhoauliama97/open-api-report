<?php

namespace App\Services\Concerns;

/**
 * Resolve filter values from Ascend XML payloads by trying multiple alias names.
 *
 * Ascend client sends field names with inconsistent encoding:
 *   - Dot notation:  "AttendanceDate.StartDate"
 *   - Underscore:    "AttendanceDate_StartDate"
 *   - XML-encoded:   "AttendanceDate_x0020_StartDate" (space), "AttendanceDate_x002e_StartDate" (dot)
 *   - Plain:         "start_date", "StartDate", "TglAwal"
 *
 * This trait normalizes all variants so a single alias list matches any encoding.
 */
trait ResolvesFilterAliases
{
    /**
     * Find a value in $filters by trying each alias, with normalized-key fallback.
     *
     * Pass 1: exact alias match against array keys.
     * Pass 2: normalize all aliases + all keys, then match.
     *
     * @param  array<string, mixed>  $filters
     * @param  list<string>  $aliases  Ordered list of alias names to try.
     * @return string First non-empty match, or '' if not found.
     */
    protected static function resolveFilterValue(array $filters, array $aliases): string
    {
        foreach ($aliases as $alias) {
            if (array_key_exists($alias, $filters)) {
                $value = trim((string) $filters[$alias]);
                if ($value !== '') {
                    return $value;
                }
            }
        }

        $normalizedAliases = array_map(
            static fn (string $alias): string => self::normalizeFilterKey($alias),
            $aliases
        );
        foreach ($filters as $key => $value) {
            if (in_array(self::normalizeFilterKey((string) $key), $normalizedAliases, true)) {
                $value = trim((string) $value);
                if ($value !== '') {
                    return $value;
                }
            }
        }

        return '';
    }

    /**
     * Normalize a filter key for fuzzy matching.
     *
     * Strips: spaces, _x0020_ (XML space), _x002e_ (XML dot), underscores, hyphens, dots.
     * Result is lowercased for case-insensitive comparison.
     */
    protected static function normalizeFilterKey(string $key): string
    {
        return strtolower(str_replace(
            [' ', '_x0020_', '_x002e_', '_', '-', '.'],
            '',
            $key
        ));
    }
}

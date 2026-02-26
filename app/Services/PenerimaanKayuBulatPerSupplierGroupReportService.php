<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class PenerimaanKayuBulatPerSupplierGroupReportService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetch(string $startDate, string $endDate): array
    {
        $rows = $this->runProcedureQuery($startDate, $endDate);

        return array_map(static fn ($row): array => (array) $row, $rows);
    }

    /**
     * @return array<string, mixed>
     */
    public function buildReportData(string $startDate, string $endDate): array
    {
        $rows = $this->fetch($startDate, $endDate);
        $columns = $this->resolveColumns($rows);
        $tonFallbackColumns = $this->resolveTonFallbackColumns($rows, $columns);
        $pivotGroupColumns = $this->resolvePivotGroupColumns($rows, $columns);
        $usePivotGroups = $columns['group'] === null && $pivotGroupColumns !== [];

        $supplierMap = [];
        $groupTotals = [];
        $supplierOrder = [];

        foreach ($rows as $row) {
            $supplier = $this->resolveSupplierName($row, $columns);
            $truckValue = $this->toFloat($columns['truck'] !== null ? ($row[$columns['truck']] ?? null) : null) ?? 0.0;

            if (!isset($supplierMap[$supplier])) {
                $supplierMap[$supplier] = [
                    'supplier' => $supplier,
                    'trucks' => 0.0,
                    'groups' => [],
                    'total_ton' => 0.0,
                ];
                $supplierOrder[] = $supplier;
            }

            // Nilai truk umumnya level supplier; ambil nilai maksimum untuk menghindari double-count.
            $supplierMap[$supplier]['trucks'] = max($supplierMap[$supplier]['trucks'], $truckValue);

            if ($usePivotGroups) {
                $groupsInRow = $this->resolvePivotGroupsFromRow($row, $pivotGroupColumns);
                $rowTotalFromGroups = array_sum($groupsInRow);
                $rowTotalFromColumn = $this->toFloat(
                    $columns['total_ton'] !== null ? ($row[$columns['total_ton']] ?? null) : null
                );
                $rowTotalTon = $rowTotalFromColumn ?? $rowTotalFromGroups;

                foreach ($groupsInRow as $groupName => $tonValue) {
                    $supplierMap[$supplier]['groups'][$groupName] = ($supplierMap[$supplier]['groups'][$groupName] ?? 0.0) + $tonValue;
                    $groupTotals[$groupName] = ($groupTotals[$groupName] ?? 0.0) + $tonValue;
                }

                $supplierMap[$supplier]['total_ton'] += $rowTotalTon;
                continue;
            }

            $groupName = $this->resolveGroupName($row, $columns);
            $tonValue = $this->resolveTonValue($row, $columns, $tonFallbackColumns);

            if ($groupName !== '') {
                $supplierMap[$supplier]['groups'][$groupName] = ($supplierMap[$supplier]['groups'][$groupName] ?? 0.0) + $tonValue;
                $groupTotals[$groupName] = ($groupTotals[$groupName] ?? 0.0) + $tonValue;
                $supplierMap[$supplier]['total_ton'] += $tonValue;
                continue;
            }

            $supplierMap[$supplier]['total_ton'] += $this->toFloat(
                $columns['total_ton'] !== null ? ($row[$columns['total_ton']] ?? null) : null
            ) ?? 0.0;
        }

        $groupNames = array_keys($groupTotals);
        natcasesort($groupNames);
        $groupNames = array_values($groupNames);

        $grandTotalTon = array_sum(array_map(static fn (array $item): float => (float) $item['total_ton'], $supplierMap));
        $grandTotalTrucks = array_sum(array_map(static fn (array $item): float => (float) $item['trucks'], $supplierMap));

        $sortedSupplierOrder = array_values($supplierOrder);
        natcasesort($sortedSupplierOrder);

        $suppliers = [];
        foreach ($sortedSupplierOrder as $supplierName) {
            $item = $supplierMap[$supplierName];
            $groupCells = [];

            foreach ($groupNames as $groupName) {
                $groupTon = (float) ($item['groups'][$groupName] ?? 0.0);
                $groupRatioInSupplier = $item['total_ton'] > 0
                    ? ($groupTon / $item['total_ton']) * 100
                    : 0.0;

                $groupCells[$groupName] = [
                    'ton' => $groupTon,
                    'ratio' => $groupRatioInSupplier,
                ];
            }

            $supplierRatio = $grandTotalTon > 0
                ? ((float) $item['total_ton'] / $grandTotalTon) * 100
                : 0.0;

            $suppliers[] = [
                'supplier' => $supplierName,
                'trucks' => (int) round((float) $item['trucks']),
                'groups' => $groupCells,
                'total_ton' => (float) $item['total_ton'],
                'ratio' => $supplierRatio,
            ];
        }

        $workingDays = $this->countWorkingDays($startDate, $endDate);

        return [
            'rows' => $rows,
            'columns' => $columns,
            'group_names' => $groupNames,
            'suppliers' => $suppliers,
            'summary' => [
                'total_rows' => count($rows),
                'total_suppliers' => count($suppliers),
                'total_trucks' => (int) round($grandTotalTrucks),
                'total_ton' => $grandTotalTon,
                'working_days' => $workingDays,
                'daily_ton' => $workingDays > 0 ? $grandTotalTon / $workingDays : 0.0,
                'estimated_25_days_ton' => ($workingDays > 0 ? $grandTotalTon / $workingDays : 0.0) * 25,
                'group_totals' => $groupTotals,
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
        $expectedColumns = config('reports.penerimaan_kayu_bulat_per_supplier_group.expected_columns', []);
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
     * @return array{supplier: ?string, truck: ?string, group: ?string, ton: ?string, total_ton: ?string, ratio: ?string}
     */
    private function resolveColumns(array $rows): array
    {
        $allKeys = [];
        foreach ($rows as $row) {
            foreach (array_keys($row) as $key) {
                $allKeys[$key] = true;
            }
        }

        $keys = array_keys($allKeys);

        return [
            'supplier' => $this->findMatchingKey($keys, ['nama supplier', 'supplier', 'nm supplier', 'nmsupplier']),
            'truck' => $this->findMatchingKey($keys, ['jml truk', 'jmlh truk', 'jumlah truk', 'truk', 'jml_truk']),
            'group' => $this->findMatchingKey($keys, ['group kayu', 'group', 'nama group', 'jenis kayu', 'jenis']),
            'ton' => $this->findMatchingKey($keys, [
                'ton',
                'berat',
                'tonase',
                'jmlh ton',
                'jumlah ton',
                'ton group',
                'tonase group',
                'kb masuk',
                'kbmasuk',
                'jml kb masuk',
                'jumlah kb masuk',
                'masuk',
            ]),
            'total_ton' => $this->findMatchingKey($keys, ['total ton', 'total', 'jmlh ton', 'jumlah ton']),
            'ratio' => $this->findMatchingKey($keys, ['rasio', 'ratio', 'persen', 'prosentase']),
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @param array{supplier: ?string, truck: ?string, group: ?string, ton: ?string, total_ton: ?string, ratio: ?string} $columns
     * @return array<int, string>
     */
    private function resolveTonFallbackColumns(array $rows, array $columns): array
    {
        $allKeys = [];
        foreach ($rows as $row) {
            foreach (array_keys($row) as $key) {
                $allKeys[$key] = true;
            }
        }

        $excluded = array_filter([
            $columns['supplier'],
            $columns['truck'],
            $columns['group'],
            $columns['ratio'],
        ]);

        $prioritized = [];
        $others = [];
        foreach (array_keys($allKeys) as $key) {
            if (in_array($key, $excluded, true) || in_array($key, [$columns['ton'], $columns['total_ton']], true)) {
                continue;
            }

            $normalized = $this->normalizeKey($key);
            if (
                str_contains($normalized, 'rank')
                || str_contains($normalized, 'urut')
                || str_contains($normalized, 'nomor')
                || str_contains($normalized, 'notlp')
                || str_contains($normalized, 'telepon')
                || str_contains($normalized, 'phone')
                || str_contains($normalized, 'id')
                || str_contains($normalized, 'tanggal')
                || str_contains($normalized, 'date')
            ) {
                continue;
            }

            if (str_contains($normalized, 'ton') || str_contains($normalized, 'berat') || str_contains($normalized, 'total')) {
                $prioritized[] = $key;
                continue;
            }

            $others[] = $key;
        }

        return array_values(array_merge($prioritized, $others));
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @param array{supplier: ?string, truck: ?string, group: ?string, ton: ?string, total_ton: ?string, ratio: ?string} $columns
     * @return array<int, string>
     */
    private function resolvePivotGroupColumns(array $rows, array $columns): array
    {
        $allKeys = [];
        foreach ($rows as $row) {
            foreach (array_keys($row) as $key) {
                $allKeys[$key] = true;
            }
        }

        $excluded = array_filter([
            $columns['supplier'],
            $columns['truck'],
            $columns['group'],
            $columns['ton'],
            $columns['total_ton'],
            $columns['ratio'],
        ]);

        $candidates = [];
        foreach (array_keys($allKeys) as $key) {
            if (in_array($key, $excluded, true)) {
                continue;
            }

            $normalized = $this->normalizeKey($key);
            if (
                str_contains($normalized, 'supplier')
                || str_contains($normalized, 'truk')
                || str_contains($normalized, 'ratio')
                || str_contains($normalized, 'rasio')
                || str_contains($normalized, 'persen')
                || str_contains($normalized, 'total')
                || str_contains($normalized, 'nomor')
                || str_contains($normalized, 'no')
                || str_contains($normalized, 'urut')
                || str_contains($normalized, 'tanggal')
                || str_contains($normalized, 'date')
                || str_contains($normalized, 'id')
            ) {
                continue;
            }

            if (preg_match('/[a-zA-Z]/', $key) !== 1) {
                continue;
            }

            foreach ($rows as $row) {
                if ($this->toFloat($row[$key] ?? null) !== null) {
                    $candidates[] = $key;
                    break;
                }
            }
        }

        return array_values(array_unique($candidates));
    }

    /**
     * @param array<string, mixed> $row
     * @param array<int, string> $groupColumns
     * @return array<string, float>
     */
    private function resolvePivotGroupsFromRow(array $row, array $groupColumns): array
    {
        $result = [];

        foreach ($groupColumns as $column) {
            $ton = $this->toFloat($row[$column] ?? null) ?? 0.0;
            $groupName = strtoupper(trim($column));
            if ($groupName === '') {
                continue;
            }

            $result[$groupName] = $ton;
        }

        return $result;
    }

    /**
     * @param array<int, string> $keys
     * @param array<int, string> $candidates
     */
    private function findMatchingKey(array $keys, array $candidates): ?string
    {
        $normalizedCandidates = array_map([$this, 'normalizeKey'], $candidates);

        foreach ($keys as $key) {
            $normalizedKey = $this->normalizeKey($key);
            if (in_array($normalizedKey, $normalizedCandidates, true)) {
                return $key;
            }
        }

        foreach ($keys as $key) {
            $normalizedKey = $this->normalizeKey($key);
            foreach ($normalizedCandidates as $candidate) {
                if (str_contains($normalizedKey, $candidate)) {
                    return $key;
                }
            }
        }

        return null;
    }

    private function normalizeKey(string $key): string
    {
        return strtolower((string) preg_replace('/[^a-zA-Z0-9]+/', '', $key));
    }

    /**
     * @param array<string, mixed> $row
     * @param array{supplier: ?string, truck: ?string, group: ?string, ton: ?string, total_ton: ?string, ratio: ?string} $columns
     */
    private function resolveSupplierName(array $row, array $columns): string
    {
        $supplier = trim((string) ($columns['supplier'] !== null ? ($row[$columns['supplier']] ?? '') : ''));

        return $supplier !== '' ? $supplier : 'Tanpa Supplier';
    }

    /**
     * @param array<string, mixed> $row
     * @param array{supplier: ?string, truck: ?string, group: ?string, ton: ?string, total_ton: ?string, ratio: ?string} $columns
     */
    private function resolveGroupName(array $row, array $columns): string
    {
        $group = trim((string) ($columns['group'] !== null ? ($row[$columns['group']] ?? '') : ''));

        return $group !== '' ? strtoupper($group) : 'GROUP';
    }

    /**
     * @param array<string, mixed> $row
     * @param array{supplier: ?string, truck: ?string, group: ?string, ton: ?string, total_ton: ?string, ratio: ?string} $columns
     */
    private function resolveTonValue(array $row, array $columns, array $fallbackTonColumns): float
    {
        $ton = $this->toFloat($columns['ton'] !== null ? ($row[$columns['ton']] ?? null) : null);
        if ($ton !== null) {
            return $ton;
        }

        $totalTon = $this->toFloat($columns['total_ton'] !== null ? ($row[$columns['total_ton']] ?? null) : null);
        if ($totalTon !== null) {
            return $totalTon;
        }

        foreach ($fallbackTonColumns as $column) {
            $value = $this->toFloat($row[$column] ?? null);
            if ($value !== null) {
                return $value;
            }
        }

        return 0.0;
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

        $trimmed = preg_replace('/[^0-9,.\-]/', '', $trimmed) ?? '';
        if ($trimmed === '' || $trimmed === '-' || $trimmed === '.' || $trimmed === ',') {
            return null;
        }

        if (preg_match('/^-?\d{1,3}(\.\d{3})*(,\d+)?$/', $trimmed) === 1) {
            $trimmed = str_replace('.', '', $trimmed);
            $trimmed = str_replace(',', '.', $trimmed);
        } elseif (preg_match('/^-?\d{1,3}(,\d{3})*(\.\d+)?$/', $trimmed) === 1) {
            $trimmed = str_replace(',', '', $trimmed);
        } else {
            $trimmed = str_replace(',', '.', $trimmed);
        }

        return is_numeric($trimmed) ? (float) $trimmed : null;
    }

    private function countWorkingDays(string $startDate, string $endDate): int
    {
        $startTs = strtotime($startDate);
        $endTs = strtotime($endDate);

        if ($startTs === false || $endTs === false) {
            return 1;
        }

        if ($endTs < $startTs) {
            [$startTs, $endTs] = [$endTs, $startTs];
        }

        return max(1, (int) floor(($endTs - $startTs) / 86400) + 1);
    }

    /**
     * @param array<int, string> $bindings
     * @return array<int, string>
     */
    private function resolveBindings(string $query, array $bindings): array
    {
        return str_contains($query, '?') ? $bindings : [];
    }

    /**
     * @return array<int, object>
     */
    private function runProcedureQuery(string $startDate, string $endDate): array
    {
        $connectionName = config('reports.penerimaan_kayu_bulat_per_supplier_group.database_connection');
        $procedure = (string) config('reports.penerimaan_kayu_bulat_per_supplier_group.stored_procedure');
        $syntax = (string) config('reports.penerimaan_kayu_bulat_per_supplier_group.call_syntax', 'exec');
        $customQuery = config('reports.penerimaan_kayu_bulat_per_supplier_group.query');

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan penerimaan kayu bulat per supplier group belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();
        $bindings = [$startDate, $endDate];

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan penerimaan kayu bulat per supplier group dikonfigurasi untuk SQL Server. '
                . 'Set PENERIMAAN_KAYU_BULAT_PER_SUPPLIER_GROUP_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException(
                    'PENERIMAAN_KAYU_BULAT_PER_SUPPLIER_GROUP_REPORT_QUERY belum diisi. '
                    . 'Isi query manual jika menggunakan PENERIMAAN_KAYU_BULAT_PER_SUPPLIER_GROUP_REPORT_CALL_SYNTAX=query.',
                );

            return $connection->select($query, $this->resolveBindings($query, $bindings));
        }

        if (!preg_match('/^[A-Za-z0-9_$.]+$/', $procedure)) {
            throw new RuntimeException('Nama stored procedure tidak valid.');
        }

        $sql = match ($syntax) {
            'exec' => "EXEC {$procedure} ?, ?",
            'call' => "CALL {$procedure}(?, ?)",
            default => $driver === 'sqlsrv'
                ? "EXEC {$procedure} ?, ?"
                : "CALL {$procedure}(?, ?)",
        };

        return $connection->select($sql, $bindings);
    }
}

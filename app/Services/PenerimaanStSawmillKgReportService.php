<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class PenerimaanStSawmillKgReportService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetch(string $startDate, string $endDate): array
    {
        $rows = $this->runProcedureQuery($startDate, $endDate);
        $rows = array_map(static fn(object $row): array => (array) $row, $rows);
        $supplierColumn = $this->resolveSupplierColumn($rows);
        $noPenerimaanColumn = $this->resolveNoPenerimaanColumn($rows);
        $inOutColumn = $this->resolveInOutColumn($rows);

        if ($supplierColumn !== null && $noPenerimaanColumn !== null) {
            $noPenToSupplier = [];
            foreach ($rows as $row) {
                $noPen = trim((string) ($row[$noPenerimaanColumn] ?? ''));
                $supplier = trim((string) ($row[$supplierColumn] ?? ''));
                $inOut = $inOutColumn !== null ? trim((string) ($row[$inOutColumn] ?? '')) : '';

                if ($noPen === '' || $supplier === '') {
                    continue;
                }

                // Prefer INPUT row as canonical supplier source.
                if ($inOut === '1' || !isset($noPenToSupplier[$noPen])) {
                    $noPenToSupplier[$noPen] = $supplier;
                }
            }

            foreach ($rows as $index => $row) {
                $noPen = trim((string) ($row[$noPenerimaanColumn] ?? ''));
                $supplier = trim((string) ($row[$supplierColumn] ?? ''));
                if ($supplier !== '' || $noPen === '') {
                    continue;
                }

                if (isset($noPenToSupplier[$noPen])) {
                    $rows[$index][$supplierColumn] = $noPenToSupplier[$noPen];
                }
            }
        }

        usort(
            $rows,
            function (array $left, array $right) use ($supplierColumn, $noPenerimaanColumn): int {
                $leftSupplier = $supplierColumn !== null ? strtolower(trim((string) ($left[$supplierColumn] ?? ''))) : '';
                $rightSupplier = $supplierColumn !== null ? strtolower(trim((string) ($right[$supplierColumn] ?? ''))) : '';
                $supplierCompare = $leftSupplier <=> $rightSupplier;

                if ($supplierCompare !== 0) {
                    return $supplierCompare;
                }

                $leftNo = $noPenerimaanColumn !== null ? strtolower(trim((string) ($left[$noPenerimaanColumn] ?? ''))) : '';
                $rightNo = $noPenerimaanColumn !== null ? strtolower(trim((string) ($right[$noPenerimaanColumn] ?? ''))) : '';
                $noCompare = $leftNo <=> $rightNo;
                if ($noCompare !== 0) {
                    return $noCompare;
                }

                return strtolower(json_encode($left) ?: '') <=> strtolower(json_encode($right) ?: '');
            },
        );

        return array_values($rows);
    }

    /**
     * @return array<string, mixed>
     */
    public function buildReportData(string $startDate, string $endDate): array
    {
        $rows = $this->fetch($startDate, $endDate);
        $noPenerimaanColumn = $this->resolveNoPenerimaanColumn($rows);
        $supplierColumn = $this->resolveSupplierColumn($rows);
        $groupedRows = $this->groupRowsBySupplier($rows, $supplierColumn, $noPenerimaanColumn);

        return [
            'rows' => $rows,
            'grouped_rows' => $groupedRows,
            'no_penerimaan_column' => $noPenerimaanColumn,
            'supplier_column' => $supplierColumn,
            'summary' => $this->buildSummary($groupedRows),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function healthCheck(string $startDate, string $endDate): array
    {
        $rows = $this->fetch($startDate, $endDate);
        $detectedColumns = array_keys($rows[0] ?? []);
        $expectedColumns = config('reports.penerimaan_st_dari_sawmill_kg.expected_columns', []);
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
     */
    private function resolveSupplierColumn(array $rows): ?string
    {
        $keys = array_keys($rows[0] ?? []);
        if ($keys === []) {
            return null;
        }

        $candidates = ['Supplier', 'NmSupplier', 'Nama Supplier', 'NamaSupplier', 'supplier'];
        foreach ($candidates as $candidate) {
            foreach ($keys as $key) {
                if (strcasecmp(trim((string) $key), $candidate) === 0) {
                    return (string) $key;
                }
            }
        }

        foreach ($keys as $key) {
            if (str_contains(strtolower((string) $key), 'supplier')) {
                return (string) $key;
            }
        }

        return null;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    private function resolveNoPenerimaanColumn(array $rows): ?string
    {
        $keys = array_keys($rows[0] ?? []);
        if ($keys === []) {
            return null;
        }

        $candidates = ['NoPenST', 'No Pen ST', 'NoPenerimaanST', 'NoPenerimaan', 'NoBukti'];
        foreach ($candidates as $candidate) {
            foreach ($keys as $key) {
                if (strcasecmp(trim((string) $key), $candidate) === 0) {
                    return (string) $key;
                }
            }
        }

        foreach ($keys as $key) {
            $normalized = strtolower(str_replace([' ', '_', '-'], '', (string) $key));
            if (str_contains($normalized, 'nopen') || str_contains($normalized, 'penerimaan')) {
                return (string) $key;
            }
        }

        return null;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    private function resolveInOutColumn(array $rows): ?string
    {
        $keys = array_keys($rows[0] ?? []);
        if ($keys === []) {
            return null;
        }

        foreach ($keys as $key) {
            if (strcasecmp(trim((string) $key), 'InOut') === 0) {
                return (string) $key;
            }
        }

        return null;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array{no_penerimaan_st: string, supplier: string, rows: array<int, array<string, mixed>>}>
     */
    private function groupRowsBySupplier(
        array $rows,
        ?string $supplierColumn,
        ?string $noPenerimaanColumn,
    ): array
    {
        if ($rows === []) {
            return [];
        }

        $grouped = [];
        foreach ($rows as $row) {
            $supplier = $supplierColumn !== null
                ? trim((string) ($row[$supplierColumn] ?? ''))
                : '';
            $supplier = $supplier !== '' ? $supplier : 'Tanpa Supplier';
            $grouped[$supplier][] = $row;
        }

        ksort($grouped, SORT_NATURAL | SORT_FLAG_CASE);

        $result = [];
        foreach ($grouped as $supplier => $supplierRows) {
            $head = $supplierRows[0] ?? [];
            $noPenerimaan = $noPenerimaanColumn !== null ? trim((string) ($head[$noPenerimaanColumn] ?? '')) : '';
            $noPenerimaan = $noPenerimaan !== '' ? $noPenerimaan : 'Tanpa No Penerimaan ST';
            $result[] = [
                'no_penerimaan_st' => $noPenerimaan,
                'supplier' => $supplier,
                'rows' => array_values($supplierRows),
            ];
        }

        return $result;
    }

    /**
     * @param array<int, array{no_penerimaan_st: string, supplier: string, rows: array<int, array<string, mixed>>}> $groups
     * @return array<string, mixed>
     */
    private function buildSummary(array $groups): array
    {
        $totalRows = 0;
        $numericTotals = [];
        $suppliers = [];

        foreach ($groups as $group) {
            $rows = $group['rows'];
            $totalRows += count($rows);

            $supplierTotals = [];
            foreach ($rows as $row) {
                foreach ($row as $column => $value) {
                    $number = $this->toFloat($value);
                    if ($number === null) {
                        continue;
                    }

                    $numericTotals[$column] = ($numericTotals[$column] ?? 0.0) + $number;
                    $supplierTotals[$column] = ($supplierTotals[$column] ?? 0.0) + $number;
                }
            }

            $suppliers[] = [
                'no_penerimaan_st' => $group['no_penerimaan_st'],
                'supplier' => $group['supplier'],
                'total_rows' => count($rows),
                'numeric_totals' => $supplierTotals,
            ];
        }

        return [
            'total_groups' => count($groups),
            'total_suppliers' => count($groups),
            'total_rows' => $totalRows,
            'numeric_totals' => $numericTotals,
            'groups' => $suppliers,
            'suppliers' => $suppliers,
        ];
    }

    private function toFloat(mixed $value): ?float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        if (!is_string($value)) {
            return null;
        }

        $normalized = str_replace(' ', '', trim($value));
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

    /**
     * @return array<int, object>
     */
    private function runProcedureQuery(string $startDate, string $endDate): array
    {
        $configKey = 'reports.penerimaan_st_dari_sawmill_kg';
        $connectionName = config("{$configKey}.database_connection");
        $procedure = (string) config("{$configKey}.stored_procedure", 'SPWps_LapRekapPenerimaanSawmilRp');
        $syntax = (string) config("{$configKey}.call_syntax", 'exec');
        $customQuery = config("{$configKey}.query");
        $parameterCount = (int) config("{$configKey}.parameter_count", 2);

        if ($parameterCount < 1 || $parameterCount > 2) {
            throw new RuntimeException('Jumlah parameter laporan penerimaan ST dari sawmill harus antara 1 sampai 2.');
        }

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan penerimaan ST dari sawmill belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan penerimaan ST dari sawmill dikonfigurasi untuk SQL Server. '
                . 'Set PENERIMAAN_ST_DARI_SAWMILL_KG_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        $bindings = [$startDate, $endDate];
        $bindings = array_slice($bindings, 0, $parameterCount);

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException(
                    'PENERIMAAN_ST_DARI_SAWMILL_KG_REPORT_QUERY belum diisi. '
                    . 'Isi query manual jika menggunakan PENERIMAAN_ST_DARI_SAWMILL_KG_REPORT_CALL_SYNTAX=query.',
                );

            return $connection->select($query, str_contains($query, '?') ? $bindings : []);
        }

        if (!preg_match('/^[A-Za-z0-9_$.]+$/', $procedure)) {
            throw new RuntimeException('Nama stored procedure tidak valid.');
        }

        $placeholders = implode(', ', array_fill(0, $parameterCount, '?'));
        $sql = match ($syntax) {
            'exec' => "EXEC {$procedure} {$placeholders}",
            'call' => "CALL {$procedure}({$placeholders})",
            default => $driver === 'sqlsrv'
                ? "EXEC {$procedure} {$placeholders}"
                : "CALL {$procedure}({$placeholders})",
        };

        return $connection->select($sql, $bindings);
    }
}

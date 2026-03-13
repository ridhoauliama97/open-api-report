<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class PembelianStPerSupplierTonReportService
{
    private const EPS = 0.0000001;

    /**
     * Output shape:
     * - jenis_columns: list of pivot columns (jenis kayu)
     * - rows: [{ supplier, values: {jenis => ton} }]
     * - totals: { by_jenis: {jenis => ton}, grand_total }
     *
     * @return array<string, mixed>
     */
    public function buildReportData(string $startDate, string $endDate): array
    {
        $rows = $this->fetch($startDate, $endDate);

        /** @var array<string, array<string, float>> $bySupplier */
        $bySupplier = [];
        /** @var array<string, float> $totalsByJenis */
        $totalsByJenis = [];

        foreach ($rows as $row) {
            $supplier = trim((string) ($row['NmSupplier'] ?? ''));
            $supplier = $supplier !== '' ? $supplier : '-';

            $jenis = trim((string) ($row['Jenis'] ?? ''));
            $jenis = $jenis !== '' ? $jenis : '-';

            $ton = (float) ($row['STTon'] ?? 0.0);

            if (!isset($bySupplier[$supplier])) {
                $bySupplier[$supplier] = [];
            }
            $bySupplier[$supplier][$jenis] = (float) ($bySupplier[$supplier][$jenis] ?? 0.0) + $ton;
            $totalsByJenis[$jenis] = (float) ($totalsByJenis[$jenis] ?? 0.0) + $ton;
        }

        $jenisColumns = array_keys($totalsByJenis);
        sort($jenisColumns, SORT_NATURAL | SORT_FLAG_CASE);
        $jenisColumns = $this->preferJenisOrder($jenisColumns);

        $supplierKeys = array_keys($bySupplier);
        sort($supplierKeys, SORT_NATURAL | SORT_FLAG_CASE);

        $outRows = [];
        foreach ($supplierKeys as $supplier) {
            $values = [];
            foreach ($jenisColumns as $jenis) {
                $values[$jenis] = (float) ($bySupplier[$supplier][$jenis] ?? 0.0);
            }
            $outRows[] = [
                'supplier' => $supplier,
                'values' => $values,
            ];
        }

        $grandTotal = array_sum(array_values($totalsByJenis));

        return [
            'jenis_columns' => $jenisColumns,
            'rows' => $outRows,
            'totals' => [
                'by_jenis' => $totalsByJenis,
                'grand_total' => $grandTotal,
            ],
            'summary' => [
                'total_rows' => count($rows),
                'total_suppliers' => count($outRows),
                'total_jenis' => count($jenisColumns),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function healthCheck(string $startDate, string $endDate): array
    {
        $raw = $this->runProcedureQuery($startDate, $endDate);
        $first = (array) ($raw[0] ?? []);
        $detectedColumns = array_keys($first);
        $expectedColumns = config('reports.pembelian_st_per_supplier_ton.expected_columns', []);
        $expectedColumns = is_array($expectedColumns) ? array_values($expectedColumns) : [];
        $missingColumns = array_values(array_diff($expectedColumns, $detectedColumns));

        return [
            'is_healthy' => empty($missingColumns),
            'expected_columns' => $expectedColumns,
            'detected_columns' => $detectedColumns,
            'missing_columns' => $missingColumns,
            'row_count' => count($raw),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetch(string $startDate, string $endDate): array
    {
        $raw = $this->runProcedureQuery($startDate, $endDate);

        $out = [];
        foreach ($raw as $row) {
            $item = (array) $row;
            foreach ($item as $k => $v) {
                if (is_string($v)) {
                    $item[$k] = trim($v);
                }
            }

            $item['STTon'] = (float) ($this->toFloat($item['STTon'] ?? null) ?? 0.0);

            // Normalize the expected keys so the Blade stays stable even if SP changes casing.
            $out[] = [
                'NmSupplier' => (string) ($item['NmSupplier'] ?? $item['Supplier'] ?? ''),
                'Jenis' => (string) ($item['Jenis'] ?? $item['GroupKayu'] ?? $item['JenisKayu'] ?? ''),
                'STTon' => (float) ($item['STTon'] ?? 0.0),
            ];
        }

        return array_values($out);
    }

    private function toFloat(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }
        if (!is_string($value)) {
            return null;
        }
        $t = trim($value);
        if ($t === '') {
            return null;
        }
        $t = str_replace(',', '', $t);
        if (!is_numeric($t)) {
            return null;
        }

        return (float) $t;
    }

    /**
     * Prefer the order used in the reference, then fall back to the remaining types.
     *
     * @param array<int, string> $jenisColumns
     * @return array<int, string>
     */
    private function preferJenisOrder(array $jenisColumns): array
    {
        $preferred = [
            'BIRA - BIRA',
            'BIRA-BIRA',
            'JABON',
            'JABON TD',
            'JABON TG',
            'PULAI',
            'RAMBUNG',
            'RAMBUNG MC1',
            'RAMBUNG MC 1',
            'RAMBUNG MC2',
            'RAMBUNG MC 2',
            'RAMBUNG STD',
            'KAYU LAT JABON',
        ];

        $existing = array_fill_keys($jenisColumns, true);
        $out = [];

        foreach ($preferred as $p) {
            if (isset($existing[$p])) {
                $out[] = $p;
                unset($existing[$p]);
            }
        }

        $rest = array_keys($existing);
        sort($rest, SORT_NATURAL | SORT_FLAG_CASE);

        return array_values(array_merge($out, $rest));
    }

    /**
     * @return array<int, object>
     */
    private function runProcedureQuery(string $startDate, string $endDate): array
    {
        $configKey = 'reports.pembelian_st_per_supplier_ton';
        $connectionName = config("{$configKey}.database_connection");
        $procedure = (string) config("{$configKey}.stored_procedure", 'SP_LapPembelianSTPerSupplier');
        $syntax = (string) config("{$configKey}.call_syntax", 'exec');
        $customQuery = config("{$configKey}.query");
        $parameterCount = (int) config("{$configKey}.parameter_count", 2);

        if ($parameterCount !== 2) {
            throw new RuntimeException('Jumlah parameter laporan Pembelian ST Per Supplier (Ton) harus 2 (Tanggal Awal dan Tanggal Akhir).');
        }

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan Pembelian ST Per Supplier (Ton) belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan Pembelian ST Per Supplier (Ton) dikonfigurasi untuk SQL Server. '
                . 'Set PEMBELIAN_ST_PER_SUPPLIER_TON_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException('Query manual laporan Pembelian ST Per Supplier (Ton) belum diisi.');

            return $connection->select($query, str_contains($query, '?') ? [$startDate, $endDate] : []);
        }

        if (!preg_match('/^[A-Za-z0-9_$.]+$/', $procedure)) {
            throw new RuntimeException('Nama stored procedure tidak valid.');
        }

        $sql = match ($syntax) {
            'exec' => "SET NOCOUNT ON; EXEC {$procedure} ?, ?",
            'call' => "CALL {$procedure}(?, ?)",
            default => $driver === 'sqlsrv'
                ? "SET NOCOUNT ON; EXEC {$procedure} ?, ?"
                : "CALL {$procedure}(?, ?)",
        };

        return $connection->select($sql, [$startDate, $endDate]);
    }
}


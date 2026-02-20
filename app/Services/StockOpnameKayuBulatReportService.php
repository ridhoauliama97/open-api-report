<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class StockOpnameKayuBulatReportService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetch(): array
    {
        $rows = $this->runProcedureQuery();

        return array_map(function ($row): array {
            $item = (array) $row;

            return [
                'NoKayuBulat' => $item['NoKayuBulat'] ?? null,
                'Tanggal' => $item['DateCreate'] ?? null,
                'JenisKayu' => $item['Jenis'] ?? null,
                'Supplier' => $item['NmSupplier'] ?? null,
                'NoSuket' => $item['Suket'] ?? null,
                'NoPlat' => $item['NoPlat'] ?? null,
                'NoTruk' => $item['NoTruk'] ?? null,
                'Tebal' => $item['Tebal'] ?? null,
                'Lebar' => $item['Lebar'] ?? null,
                'Panjang' => $item['Panjang'] ?? null,
                'Pcs' => $item['Pcs'] ?? null,
                'JmlhTon' => $item['Ton'] ?? null,
            ];
        }, $rows);
    }

    /**
     * @return array<string, mixed>
     */
    public function buildReportData(): array
    {
        $rows = $this->fetch();
        $grouped = $this->groupByNoKayuBulat($rows);

        return [
            'rows' => $rows,
            'grouped_rows' => $grouped,
            'summary' => $this->buildSummary($rows, $grouped),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function healthCheck(): array
    {
        $rows = $this->fetch();
        $detectedColumns = array_keys($rows[0] ?? []);
        $expectedColumns = config('reports.stock_opname_kayu_bulat.expected_columns', []);
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
     * @return array<int, array{no_kayu_bulat: string, rows: array<int, array<string, mixed>>}>
     */
    private function groupByNoKayuBulat(array $rows): array
    {
        $groups = [];
        foreach ($rows as $row) {
            $noKayu = trim((string) ($row['NoKayuBulat'] ?? ''));
            $noKayu = $noKayu !== '' ? $noKayu : 'Tanpa NoKayuBulat';
            $groups[$noKayu][] = $row;
        }

        krsort($groups, SORT_NATURAL);

        $result = [];
        foreach ($groups as $noKayu => $groupRows) {
            $result[] = [
                'no_kayu_bulat' => $noKayu,
                'rows' => array_values($groupRows),
            ];
        }

        return $result;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @param array<int, array{no_kayu_bulat: string, rows: array<int, array<string, mixed>>}> $grouped
     * @return array<string, mixed>
     */
    private function buildSummary(array $rows, array $grouped): array
    {
        $totalPcs = 0.0;
        $totalTon = 0.0;
        $perNoKayu = [];

        foreach ($rows as $row) {
            $totalPcs += is_numeric($row['Pcs'] ?? null) ? (float) $row['Pcs'] : 0.0;
            $totalTon += is_numeric($row['JmlhTon'] ?? null) ? (float) $row['JmlhTon'] : 0.0;
        }

        foreach ($grouped as $group) {
            $groupPcs = 0.0;
            $groupTon = 0.0;
            foreach ($group['rows'] as $row) {
                $groupPcs += is_numeric($row['Pcs'] ?? null) ? (float) $row['Pcs'] : 0.0;
                $groupTon += is_numeric($row['JmlhTon'] ?? null) ? (float) $row['JmlhTon'] : 0.0;
            }
            $perNoKayu[] = [
                'no_kayu_bulat' => $group['no_kayu_bulat'],
                'total_rows' => count($group['rows']),
                'total_pcs' => $groupPcs,
                'total_ton' => $groupTon,
            ];
        }

        return [
            'total_rows' => count($rows),
            'total_no_kayu_bulat' => count($grouped),
            'total_pcs' => $totalPcs,
            'total_ton' => $totalTon,
            'per_no_kayu_bulat' => $perNoKayu,
        ];
    }

    /**
     * @return array<int, object>
     */
    private function runProcedureQuery(): array
    {
        $connectionName = config('reports.stock_opname_kayu_bulat.database_connection');
        $procedure = (string) config('reports.stock_opname_kayu_bulat.stored_procedure', 'sp_LapStockOpnameKB');
        $syntax = (string) config('reports.stock_opname_kayu_bulat.call_syntax', 'exec');
        $customQuery = config('reports.stock_opname_kayu_bulat.query');

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan stock opname kayu bulat belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan stock opname kayu bulat dikonfigurasi untuk SQL Server. '
                . 'Set STOCK_OPNAME_KAYU_BULAT_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException(
                    'STOCK_OPNAME_KAYU_BULAT_REPORT_QUERY belum diisi. '
                    . 'Isi query manual jika menggunakan STOCK_OPNAME_KAYU_BULAT_REPORT_CALL_SYNTAX=query.',
                );

            return $connection->select($query);
        }

        if (!preg_match('/^[A-Za-z0-9_$.]+$/', $procedure)) {
            throw new RuntimeException('Nama stored procedure tidak valid.');
        }

        $sql = match ($syntax) {
            'exec' => "EXEC {$procedure}",
            'call' => "CALL {$procedure}()",
            default => $driver === 'sqlsrv' ? "EXEC {$procedure}" : "CALL {$procedure}()",
        };

        return $connection->select($sql);
    }
}

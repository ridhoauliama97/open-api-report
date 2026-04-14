<?php

namespace App\Services\PPS;

use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class HasilProduksiHarianPackingProduksiReportService
{
    public function fetch(string $noPacking): array
    {
        try {
            $rows = $this->runProcedureQuery($noPacking);
            $rows = array_map(static fn ($row): array => (array) $row, $rows);

            if ($rows !== []) {
                return $this->buildReportDataFromProcedure($noPacking, $rows);
            }
        } catch (QueryException | RuntimeException $exception) {
        }

        return $this->buildReportDataFromTables($noPacking);
    }

    public function healthCheck(string $noPacking): array
    {
        try {
            $rows = $this->runProcedureQuery($noPacking);
            $rows = array_map(static fn ($row): array => (array) $row, $rows);
            $detectedColumns = array_keys($rows[0] ?? []);
            $expectedColumns = config('reports.pps_packing_produksi_harian.expected_columns', []);
            $expectedColumns = is_array($expectedColumns) ? array_values($expectedColumns) : [];

            return [
                'is_healthy' => empty(array_diff($expectedColumns, $detectedColumns)),
                'expected_columns' => $expectedColumns,
                'detected_columns' => $detectedColumns,
                'missing_columns' => array_values(array_diff($expectedColumns, $detectedColumns)),
                'extra_columns' => array_values(array_diff($detectedColumns, $expectedColumns)),
                'row_count' => count($rows),
                'mode' => 'stored_procedure',
            ];
        } catch (QueryException | RuntimeException $exception) {
            $report = $this->buildReportDataFromTables($noPacking);

            return [
                'is_healthy' => true,
                'expected_columns' => [],
                'detected_columns' => array_keys($report['detail_rows'][0] ?? []),
                'missing_columns' => [],
                'extra_columns' => [],
                'row_count' => count($report['detail_rows'] ?? []),
                'mode' => 'fallback_tables',
                'warning' => $exception->getMessage(),
            ];
        }
    }

    public function recentNoPacking(int $limit = 10): array
    {
        $connectionName = config('reports.pps_packing_produksi_harian.database_connection');

        $rows = DB::connection($connectionName ?: null)
            ->table('PackingProduksi_h')
            ->select(['NoPacking', 'Tanggal', 'Shift'])
            ->orderByDesc('Tanggal')
            ->orderByDesc('NoPacking')
            ->limit($limit)
            ->get();

        return $rows->map(static function ($row): array {
            return [
                'no_packing' => (string) ($row->NoPacking ?? ''),
                'tanggal' => isset($row->Tanggal)
                    ? Carbon::parse((string) $row->Tanggal)->format('d-M-y')
                    : '',
                'shift' => (string) ($row->Shift ?? ''),
            ];
        })->all();
    }

    private function buildReportDataFromProcedure(string $noPacking, array $rows): array
    {
        $firstRow = $rows[0];
        $inputs = [];
        $outputs = [];

        foreach ($rows as $row) {
            $type = strtolower(trim((string) ($row['Tipe'] ?? '')));
            $group = strtoupper(trim((string) ($row['Group'] ?? '')));
            $item = [
                'group' => $group,
                'reference' => (string) ($row['NoLabel'] ?? ''),
                'jenis' => (string) ($row['Jenis'] ?? ''),
                'qty' => $this->toFloat($row['Total'] ?? null),
                'berat' => $this->toFloat($row['Total2'] ?? null),
            ];

            if ($type === 'input') {
                $inputs[] = $item;
                continue;
            }

            if ($type === 'output') {
                $outputs[] = $item;
            }
        }

        return $this->assembleReport([
            'source' => 'stored_procedure',
            'no_packing' => (string) ($firstRow['NoProduksi'] ?? $noPacking),
            'tanggal' => isset($firstRow['Tanggal']) && $firstRow['Tanggal'] !== null ? Carbon::parse((string) $firstRow['Tanggal']) : null,
            'nama_mesin' => (string) ($firstRow['NamaMesin'] ?? ''),
            'shift' => (string) ($firstRow['Shift'] ?? ''),
            'inputs' => $inputs,
            'outputs' => $outputs,
            'approvals' => [
                'operator' => (string) ($firstRow['CreateBy'] ?? ''),
                'ka_regu_packing' => (string) ($firstRow['CheckBy1'] ?? ''),
                'ka_div_packing' => (string) ($firstRow['CheckBy2'] ?? ''),
                'ka_dept_produksi' => (string) ($firstRow['ApproveBy'] ?? ''),
            ],
        ]);
    }

    private function buildReportDataFromTables(string $noPacking): array
    {
        $connectionName = config('reports.pps_packing_produksi_harian.database_connection');
        $connection = DB::connection($connectionName ?: null);

        $header = $connection->table('PackingProduksi_h as h')
            ->leftJoin('MstMesin as m', 'm.IdMesin', '=', 'h.IdMesin')
            ->select([
                'h.NoPacking',
                'h.Tanggal',
                'h.Shift',
                'h.CreateBy',
                'h.CheckBy1',
                'h.CheckBy2',
                'h.ApproveBy',
                'm.NamaMesin',
            ])
            ->where('h.NoPacking', $noPacking)
            ->first();

        if ($header === null) {
            throw new RuntimeException('Data packing produksi tidak ditemukan untuk nomor packing tersebut.');
        }

        $inputs = collect()
            ->concat(
                $connection->table('PackingProduksiInputLabelFWIP as i')
                    ->join('FurnitureWIP as f', 'f.NoFurnitureWIP', '=', 'i.NoFurnitureWIP')
                    ->leftJoin('MstCabinetWIP as m', 'm.IdCabinetWIP', '=', 'f.IDFurnitureWIP')
                    ->selectRaw("i.NoFurnitureWIP as reference, 'FWIP' as input_group, MAX(COALESCE(m.Nama, i.NoFurnitureWIP)) as jenis, SUM(COALESCE(f.Pcs, 0)) as qty, SUM(COALESCE(f.Berat, 0)) as berat")
                    ->where('i.NoPacking', $noPacking)
                    ->groupBy('i.NoFurnitureWIP')
                    ->orderBy('i.NoFurnitureWIP')
                    ->get()
                    ->map(static fn ($row): array => [
                        'reference' => (string) ($row->reference ?? ''),
                        'group' => (string) ($row->input_group ?? 'FWIP'),
                        'jenis' => (string) ($row->jenis ?? $row->reference ?? ''),
                        'qty' => (float) ($row->qty ?? 0),
                        'berat' => (float) ($row->berat ?? 0),
                    ])
            )
            ->concat(
                $connection->table('PackingProduksiInputWIP as i')
                    ->leftJoin('MstCabinetWIP as m', 'm.IdCabinetWIP', '=', 'i.IdCabinetWIP')
                    ->selectRaw("CAST(i.IdCabinetWIP as varchar(50)) as reference, 'FWIPPART' as input_group, MAX(COALESCE(m.Nama, CAST(i.IdCabinetWIP as varchar(50)))) as jenis, SUM(COALESCE(i.Jumlah, 0)) as qty, CAST(0 as float) as berat")
                    ->where('i.NoPacking', $noPacking)
                    ->groupBy('i.IdCabinetWIP')
                    ->orderBy('i.IdCabinetWIP')
                    ->get()
                    ->map(static fn ($row): array => [
                        'reference' => (string) ($row->reference ?? ''),
                        'group' => (string) ($row->input_group ?? 'FWIPPART'),
                        'jenis' => (string) ($row->jenis ?? $row->reference ?? ''),
                        'qty' => (float) ($row->qty ?? 0),
                        'berat' => (float) ($row->berat ?? 0),
                    ])
            )
            ->concat(
                $connection->table('PackingProduksiInputMaterial as i')
                    ->leftJoin('MstCabinetMaterial as m', 'm.IdCabinetMaterial', '=', 'i.IdCabinetMaterial')
                    ->selectRaw("CAST(i.IdCabinetMaterial as varchar(50)) as reference, 'MAT' as input_group, MAX(COALESCE(m.Nama, CAST(i.IdCabinetMaterial as varchar(50)))) as jenis, SUM(COALESCE(i.Jumlah, 0)) as qty, CAST(0 as float) as berat")
                    ->where('i.NoPacking', $noPacking)
                    ->groupBy('i.IdCabinetMaterial')
                    ->orderBy('i.IdCabinetMaterial')
                    ->get()
                    ->map(static fn ($row): array => [
                        'reference' => (string) ($row->reference ?? ''),
                        'group' => (string) ($row->input_group ?? 'MAT'),
                        'jenis' => (string) ($row->jenis ?? $row->reference ?? ''),
                        'qty' => (float) ($row->qty ?? 0),
                        'berat' => (float) ($row->berat ?? 0),
                    ])
            )
            ->sortBy([
                ['group', 'asc'],
                ['reference', 'asc'],
            ])
            ->values()
            ->all();

        $outputs = $connection->table('PackingProduksiOutputLabelBJ as o')
            ->join('BarangJadi as b', 'b.NoBJ', '=', 'o.NoBJ')
            ->leftJoin('MstBarangJadi as m', 'm.IdBJ', '=', 'b.IdBJ')
            ->selectRaw("o.NoBJ as reference, 'BJ' as output_group, MAX(COALESCE(m.NamaBJ, o.NoBJ)) as jenis, SUM(COALESCE(b.Pcs, 0)) as qty, SUM(COALESCE(b.Berat, 0)) as berat")
            ->where('o.NoPacking', $noPacking)
            ->groupBy('o.NoBJ')
            ->orderBy('o.NoBJ')
            ->get()
            ->map(static fn ($row): array => [
                'reference' => (string) ($row->reference ?? ''),
                'group' => (string) ($row->output_group ?? 'BJ'),
                'jenis' => (string) ($row->jenis ?? $row->reference ?? ''),
                'qty' => (float) ($row->qty ?? 0),
                'berat' => (float) ($row->berat ?? 0),
            ])
            ->all();

        return $this->assembleReport([
            'source' => 'fallback_tables',
            'no_packing' => (string) ($header->NoPacking ?? $noPacking),
            'tanggal' => isset($header->Tanggal) ? Carbon::parse((string) $header->Tanggal) : null,
            'nama_mesin' => (string) ($header->NamaMesin ?? ''),
            'shift' => (string) ($header->Shift ?? ''),
            'inputs' => $inputs,
            'outputs' => $outputs,
            'approvals' => [
                'operator' => (string) ($header->CreateBy ?? ''),
                'ka_regu_packing' => (string) ($header->CheckBy1 ?? ''),
                'ka_div_packing' => (string) ($header->CheckBy2 ?? ''),
                'ka_dept_produksi' => (string) ($header->ApproveBy ?? ''),
            ],
        ]);
    }

    private function assembleReport(array $payload): array
    {
        $inputs = $payload['inputs'];
        $outputs = $payload['outputs'];
        $detailCount = max(count($inputs), count($outputs), 1);
        $detailRows = [];

        for ($index = 0; $index < $detailCount; $index++) {
            $detailRows[] = [
                'input_nama_barang' => $inputs[$index]['jenis'] ?? '',
                'input_qty' => $inputs[$index]['qty'] ?? null,
                'input_berat' => $inputs[$index]['berat'] ?? null,
                'output_nama_barang' => $outputs[$index]['jenis'] ?? '',
                'output_nomor_label' => $outputs[$index]['reference'] ?? '',
                'output_qty' => $outputs[$index]['qty'] ?? null,
                'output_berat' => $outputs[$index]['berat'] ?? null,
            ];
        }

        return [
            'meta' => [
                'source' => $payload['source'],
                'no_packing' => $payload['no_packing'],
                'tanggal' => $payload['tanggal'],
                'nama_mesin' => $payload['nama_mesin'],
                'shift' => $payload['shift'],
            ],
            'detail_rows' => $detailRows,
            'blank_row_count' => max(18 - count($detailRows), 0),
            'totals' => [
                'input_qty' => $this->sumField($inputs, 'qty'),
                'input_berat' => $this->sumField($inputs, 'berat'),
                'output_qty' => $this->sumField($outputs, 'qty'),
                'output_berat' => $this->sumField($outputs, 'berat'),
            ],
            'approvals' => $payload['approvals'],
        ];
    }

    private function runProcedureQuery(string $noPacking): array
    {
        $configPath = 'reports.pps_packing_produksi_harian';
        $connectionName = config("{$configPath}.database_connection");
        $procedure = (string) config("{$configPath}.stored_procedure");
        $syntax = (string) config("{$configPath}.call_syntax", 'exec');

        if ($procedure === '') {
            throw new RuntimeException('Stored procedure laporan PPS Packing Produksi Harian belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException('Laporan PPS Packing Produksi Harian dikonfigurasi untuk SQL Server.');
        }

        if (!preg_match('/^[A-Za-z0-9_$.]+$/', $procedure)) {
            throw new RuntimeException('Nama stored procedure tidak valid.');
        }

        return $connection->select("SET NOCOUNT ON; EXEC {$procedure} ?", [$noPacking]);
    }

    private function toFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            return (float) $value;
        }
        if (!is_string($value)) {
            return null;
        }

        $normalized = str_replace([',', ' '], ['', ''], trim($value));

        return is_numeric($normalized) ? (float) $normalized : null;
    }

    private function sumField(array $rows, string $field): float
    {
        $total = 0.0;
        foreach ($rows as $row) {
            $total += (float) ($row[$field] ?? 0);
        }

        return $total;
    }
}

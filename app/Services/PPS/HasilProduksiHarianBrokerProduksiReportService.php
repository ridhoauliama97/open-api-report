<?php

namespace App\Services\PPS;

use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class HasilProduksiHarianBrokerProduksiReportService
{
    public function fetch(string $noProduksi): array
    {
        try {
            $rows = $this->runProcedureQuery($noProduksi);
            $rows = array_map(static fn($row): array => (array) $row, $rows);

            if ($rows !== []) {
                return $this->buildReportDataFromProcedure($noProduksi, $rows);
            }
        } catch (QueryException | RuntimeException $exception) {
        }

        return $this->buildReportDataFromTables($noProduksi);
    }

    public function healthCheck(string $noProduksi): array
    {
        try {
            $rows = $this->runProcedureQuery($noProduksi);
            $rows = array_map(static fn($row): array => (array) $row, $rows);
            $detectedColumns = array_keys($rows[0] ?? []);
            $expectedColumns = config('reports.pps_broker_produksi_harian.expected_columns', []);
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
            $report = $this->buildReportDataFromTables($noProduksi);

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

    public function recentNoProduksi(int $limit = 10): array
    {
        $connectionName = config('reports.pps_broker_produksi_harian.database_connection');

        $rows = DB::connection($connectionName ?: null)
            ->table('BrokerProduksi_h')
            ->select(['NoProduksi', 'TglProduksi', 'Shift'])
            ->orderByDesc('TglProduksi')
            ->orderByDesc('NoProduksi')
            ->limit($limit)
            ->get();

        return $rows->map(static function ($row): array {
            return [
                'no_produksi' => (string) ($row->NoProduksi ?? ''),
                'tanggal' => isset($row->TglProduksi)
                    ? Carbon::parse((string) $row->TglProduksi)->format('d-M-y')
                    : '',
                'shift' => (string) ($row->Shift ?? ''),
            ];
        })->all();
    }

    private function buildReportDataFromProcedure(string $noProduksi, array $rows): array
    {
        $firstRow = $rows[0];
        $inputs = [];
        $outputs = [];

        foreach ($rows as $row) {
            $type = strtolower(trim((string) ($row['Tipe'] ?? '')));
            $group = strtoupper(trim((string) ($row['Group'] ?? '')));
            $qty = $this->toFloat($row['Berat'] ?? $row['Brt'] ?? null);
            $item = [
                'group' => $group,
                'reference' => (string) ($row['NoLabel'] ?? ''),
                'jenis' => (string) ($row['Jenis'] ?? ''),
                'qty' => $qty,
                'hasil_cek_qc' => (string) ($row['Stat'] ?? ''),
                'reject_qty' => $group === 'BONG' ? $qty : null,
            ];

            if ($type === 'input') {
                $inputs[] = $item;
                continue;
            }

            if ($type === 'output') {
                $outputs[] = $item;
                continue;
            }
        }

        usort($outputs, static function (array $left, array $right): int {
            $weight = static fn(string $group): int => match ($group) {
                'BRK' => 0,
                'BONG' => 1,
                default => 2,
            };

            return [$weight($left['group'] ?? ''), $left['reference'] ?? '']
                <=> [$weight($right['group'] ?? ''), $right['reference'] ?? ''];
        });

        return $this->assembleReport([
            'source' => 'stored_procedure',
            'no_produksi' => (string) ($firstRow['NoProduksi'] ?? $noProduksi),
            'tanggal' => isset($firstRow['TglProduksi']) && $firstRow['TglProduksi'] !== null ? Carbon::parse((string) $firstRow['TglProduksi']) : null,
            'nama_mesin' => (string) ($firstRow['NamaMesin'] ?? ''),
            'shift' => (string) ($firstRow['Shift'] ?? ''),
            'inputs' => $inputs,
            'outputs' => $outputs,
            'downtime_rows' => $this->fetchDowntimeRows($noProduksi),
            'approvals' => [
                'operator' => (string) ($firstRow['CreateBy'] ?? ''),
                'ka_regu_broker' => (string) ($firstRow['CheckBy1'] ?? ''),
                'ka_div_broker' => (string) ($firstRow['CheckBy2'] ?? ''),
                'ka_dept_produksi' => (string) ($firstRow['ApproveBy'] ?? ''),
            ],
            'attendance_total' => (int) round($this->toFloat($firstRow['JmlhAnggota'] ?? 0) ?? 0),
            'attendance_hadir' => (int) round($this->toFloat($firstRow['Hadir'] ?? 0) ?? 0),
        ]);
    }

    private function buildReportDataFromTables(string $noProduksi): array
    {
        $connectionName = config('reports.pps_broker_produksi_harian.database_connection');
        $connection = DB::connection($connectionName ?: null);

        $header = $connection->table('BrokerProduksi_h as h')
            ->leftJoin('MstMesin as m', 'm.IdMesin', '=', 'h.IdMesin')
            ->select([
                'h.NoProduksi',
                'h.TglProduksi',
                'h.Shift',
                'h.CreateBy',
                'h.CheckBy1',
                'h.CheckBy2',
                'h.ApproveBy',
                'h.JmlhAnggota',
                'h.Hadir',
                'm.NamaMesin',
            ])
            ->where('h.NoProduksi', $noProduksi)
            ->first();

        if ($header === null) {
            throw new RuntimeException('Data broker produksi tidak ditemukan untuk nomor produksi tersebut.');
        }

        $inputs = collect();

        $inputBahanBaku = $connection->table('BrokerProduksiInputBB as i')
            ->join('BahanBaku_d as d', function ($join): void {
                $join->on('d.NoBahanBaku', '=', 'i.NoBahanBaku')
                    ->on('d.NoSak', '=', 'i.NoSak');
            })
            ->selectRaw("i.NoBahanBaku as reference, 'BB' as input_group, SUM(COALESCE(d.Berat, 0)) as qty")
            ->where('i.NoProduksi', $noProduksi)
            ->groupBy('i.NoBahanBaku')
            ->orderBy('i.NoBahanBaku')
            ->get()
            ->map(static fn($row): array => [
                'reference' => (string) ($row->reference ?? ''),
                'group' => (string) ($row->input_group ?? 'BB'),
                'jenis' => (string) ($row->reference ?? ''),
                'qty' => (float) ($row->qty ?? 0),
                'hasil_cek_qc' => '',
                'reject_qty' => null,
            ]);

        $inputWashing = $connection->table('BrokerProduksiInputWashing as i')
            ->join('Washing_d as d', function ($join): void {
                $join->on('d.NoWashing', '=', 'i.NoWashing')
                    ->on('d.NoSak', '=', 'i.NoSak');
            })
            ->leftJoin('Washing_h as h', 'h.NoWashing', '=', 'i.NoWashing')
            ->leftJoin('MstJenisPlastik as j', 'j.IdJenisPlastik', '=', 'h.IdJenisPlastik')
            ->selectRaw("i.NoWashing as reference, 'WASH' as input_group, MAX(COALESCE(j.Jenis, i.NoWashing)) as jenis, SUM(COALESCE(d.Berat, 0)) as qty")
            ->where('i.NoProduksi', $noProduksi)
            ->groupBy('i.NoWashing')
            ->orderBy('i.NoWashing')
            ->get()
            ->map(static fn($row): array => [
                'reference' => (string) ($row->reference ?? ''),
                'group' => (string) ($row->input_group ?? 'WASH'),
                'jenis' => (string) ($row->jenis ?? $row->reference ?? ''),
                'qty' => (float) ($row->qty ?? 0),
                'hasil_cek_qc' => '',
                'reject_qty' => null,
            ]);

        $inputs = $inputs
            ->concat($inputBahanBaku)
            ->concat($inputWashing)
            ->sortBy([
                ['group', 'asc'],
                ['reference', 'asc'],
            ])
            ->values()
            ->all();

        $outputs = $connection->table('BrokerProduksiOutput as o')
            ->join('Broker_d as d', function ($join): void {
                $join->on('d.NoBroker', '=', 'o.NoBroker')
                    ->on('d.NoSak', '=', 'o.NoSak');
            })
            ->leftJoin('Broker_h as h', 'h.NoBroker', '=', 'o.NoBroker')
            ->leftJoin('MstJenisPlastik as j', 'j.IdJenisPlastik', '=', 'h.IdJenisPlastik')
            ->selectRaw("o.NoBroker as reference, 'BRK' as output_group, MAX(COALESCE(j.Jenis, o.NoBroker)) as jenis, SUM(COALESCE(d.Berat, 0)) as qty")
            ->where('o.NoProduksi', $noProduksi)
            ->groupBy('o.NoBroker')
            ->orderBy('o.NoBroker')
            ->get()
            ->map(static fn($row): array => [
                'reference' => (string) ($row->reference ?? ''),
                'group' => (string) ($row->output_group ?? 'BRK'),
                'jenis' => (string) ($row->jenis ?? $row->reference ?? ''),
                'qty' => (float) ($row->qty ?? 0),
                'hasil_cek_qc' => '',
                'reject_qty' => null,
            ]);

        $bonggolanOutputs = $connection->table('BrokerProduksiOutputBonggolan as o')
            ->join('Bonggolan_d as d', function ($join): void {
                $join->on('d.NoBonggolan', '=', 'o.NoBonggolan')
                    ->on('d.NoSak', '=', 'o.NoSak');
            })
            ->leftJoin('Bonggolan_h as h', 'h.NoBonggolan', '=', 'o.NoBonggolan')
            ->leftJoin('MstJenisPlastik as j', 'j.IdJenisPlastik', '=', 'h.IdJenisPlastik')
            ->selectRaw("o.NoBonggolan as reference, 'BONG' as output_group, MAX(COALESCE(j.Jenis, o.NoBonggolan)) as jenis, SUM(COALESCE(d.Berat, 0)) as qty")
            ->where('o.NoProduksi', $noProduksi)
            ->groupBy('o.NoBonggolan')
            ->orderBy('o.NoBonggolan')
            ->get()
            ->map(static fn($row): array => [
                'reference' => (string) ($row->reference ?? ''),
                'group' => (string) ($row->output_group ?? 'BONG'),
                'jenis' => (string) ($row->jenis ?? $row->reference ?? ''),
                'qty' => (float) ($row->qty ?? 0),
                'hasil_cek_qc' => '',
                'reject_qty' => (float) ($row->qty ?? 0),
            ]);

        $outputs = $outputs
            ->concat($bonggolanOutputs)
            ->sortBy([
                ['group', 'asc'],
                ['reference', 'asc'],
            ])
            ->values()
            ->all();

        return $this->assembleReport([
            'source' => 'fallback_tables',
            'no_produksi' => (string) ($header->NoProduksi ?? $noProduksi),
            'tanggal' => isset($header->TglProduksi) ? Carbon::parse((string) $header->TglProduksi) : null,
            'nama_mesin' => (string) ($header->NamaMesin ?? ''),
            'shift' => (string) ($header->Shift ?? ''),
            'inputs' => $inputs,
            'outputs' => $outputs,
            'downtime_rows' => $this->fetchDowntimeRows($noProduksi),
            'approvals' => [
                'operator' => (string) ($header->CreateBy ?? ''),
                'ka_regu_broker' => (string) ($header->CheckBy1 ?? ''),
                'ka_div_broker' => (string) ($header->CheckBy2 ?? ''),
                'ka_dept_produksi' => (string) ($header->ApproveBy ?? ''),
            ],
            'attendance_total' => (int) round($this->toFloat($header->JmlhAnggota ?? 0) ?? 0),
            'attendance_hadir' => (int) round($this->toFloat($header->Hadir ?? 0) ?? 0),
        ]);
    }

    private function assembleReport(array $payload): array
    {
        $inputs = $payload['inputs'];
        $outputs = $payload['outputs'];
        $downtimeRows = $payload['downtime_rows'];
        $totalInputQty = $this->sumQty($inputs);
        $detailCount = max(count($inputs), count($outputs), count($downtimeRows), 1);
        $detailRows = [];

        for ($index = 0; $index < $detailCount; $index++) {
            $inputQty = $inputs[$index]['qty'] ?? null;
            $inputPercent = null;
            if ($inputQty !== null && $totalInputQty > 0) {
                $inputPercent = ((float) $inputQty / $totalInputQty) * 100;
            }

            $detailRows[] = [
                'input_nama_bahan' => $inputs[$index]['jenis'] ?? '',
                'input_qty' => $inputQty,
                'input_percent' => $inputPercent,
                'output_nama_barang' => $outputs[$index]['jenis'] ?? '',
                'output_nomor_label' => $outputs[$index]['reference'] ?? '',
                'output_qty' => ($outputs[$index]['reject_qty'] ?? null) !== null ? null : ($outputs[$index]['qty'] ?? null),
                'output_hasil_cek_qc' => $outputs[$index]['hasil_cek_qc'] ?? '',
                'reject_qty' => $outputs[$index]['reject_qty'] ?? null,
                'downtime_jam_berhenti' => $downtimeRows[$index]['jam_berhenti'] ?? '',
                'downtime_durasi' => $downtimeRows[$index]['durasi'] ?? '',
                'downtime_keterangan' => $downtimeRows[$index]['keterangan'] ?? '',
            ];
        }

        $hadir = $payload['attendance_hadir'];
        $total = $payload['attendance_total'];

        return [
            'meta' => [
                'source' => $payload['source'],
                'no_produksi' => $payload['no_produksi'],
                'tanggal' => $payload['tanggal'],
                'nama_mesin' => $payload['nama_mesin'],
                'shift' => $payload['shift'],
            ],
            'detail_rows' => $detailRows,
            'blank_row_count' => max(20 - count($detailRows), 0),
            'totals' => [
                'input_qty' => $totalInputQty,
                'output_qty' => array_reduce(
                    $outputs,
                    static fn(float $carry, array $row): float => $carry + (($row['reject_qty'] ?? null) === null ? (float) ($row['qty'] ?? 0) : 0.0),
                    0.0,
                ),
                'reject_qty' => array_reduce(
                    $outputs,
                    static fn(float $carry, array $row): float => $carry + (float) ($row['reject_qty'] ?? 0),
                    0.0,
                ),
            ],
            'approvals' => $payload['approvals'],
            'attendance' => [
                'hadir' => $hadir,
                'absen' => max($total - $hadir, 0),
                'total' => $total,
            ],
        ];
    }

    private function fetchDowntimeRows(string $noProduksi): array
    {
        $connectionName = config('reports.pps_broker_produksi_harian.database_connection');
        $connection = DB::connection($connectionName ?: null);

        $rows = $connection->select(
            "
            SELECT NoUrut, TimeStart, TimeEnd, Remarks FROM BrokerProduksi_dDowntime WHERE NoProduksi = ?
            UNION ALL
            SELECT NoUrut, TimeStart, TimeEnd, Remarks FROM BrokerProduksi_dDowntime1 WHERE NoProduksi = ?
            ORDER BY NoUrut
            ",
            [$noProduksi, $noProduksi]
        );

        return array_map(function ($row): array {
            $timeStart = isset($row->TimeStart) ? Carbon::parse((string) $row->TimeStart) : null;
            $timeEnd = isset($row->TimeEnd) ? Carbon::parse((string) $row->TimeEnd) : null;
            $duration = '';

            if ($timeStart !== null && $timeEnd !== null) {
                $minutes = $timeEnd->diffInMinutes($timeStart);
                $duration = sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
            }

            return [
                'jam_berhenti' => $timeStart?->format('H:i') ?? '',
                'durasi' => $duration,
                'keterangan' => (string) ($row->Remarks ?? ''),
            ];
        }, $rows);
    }

    private function runProcedureQuery(string $noProduksi): array
    {
        $configPath = 'reports.pps_broker_produksi_harian';
        $connectionName = config("{$configPath}.database_connection");
        $procedure = (string) config("{$configPath}.stored_procedure");
        $syntax = (string) config("{$configPath}.call_syntax", 'exec');

        if ($procedure === '') {
            throw new RuntimeException('Stored procedure laporan PPS Broker Produksi Harian belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException('Laporan PPS Broker Produksi Harian dikonfigurasi untuk SQL Server.');
        }

        if (!preg_match('/^[A-Za-z0-9_$.]+$/', $procedure)) {
            throw new RuntimeException('Nama stored procedure tidak valid.');
        }

        return $connection->select("SET NOCOUNT ON; EXEC {$procedure} ?", [$noProduksi]);
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

    private function sumQty(array $rows): float
    {
        $total = 0.0;
        foreach ($rows as $row) {
            $total += (float) ($row['qty'] ?? 0);
        }

        return $total;
    }
}

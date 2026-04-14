<?php

namespace App\Services\PPS;

use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class HasilProduksiHarianMixerProduksiReportService
{
    public function fetch(string $noProduksi): array
    {
        try {
            $rows = $this->runProcedureQuery($noProduksi);
            $rows = array_map(static fn ($row): array => (array) $row, $rows);

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
            $rows = array_map(static fn ($row): array => (array) $row, $rows);
            $detectedColumns = array_keys($rows[0] ?? []);
            $expectedColumns = config('reports.pps_mixer_produksi_harian.expected_columns', []);
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
        $connectionName = config('reports.pps_mixer_produksi_harian.database_connection');

        $rows = DB::connection($connectionName ?: null)
            ->table('MixerProduksi_h')
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
            $item = [
                'group' => $group,
                'reference' => (string) ($row['NoLabel'] ?? ''),
                'jenis' => (string) ($row['Jenis'] ?? ''),
                'qty' => $this->toFloat($row['Berat'] ?? $row['Brt'] ?? null),
                'hasil_cek_qc' => (string) ($row['Stat'] ?? ''),
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
            'no_produksi' => (string) ($firstRow['NoProduksi'] ?? $noProduksi),
            'tanggal' => isset($firstRow['Tanggal']) && $firstRow['Tanggal'] !== null ? Carbon::parse((string) $firstRow['Tanggal']) : null,
            'nama_mesin' => (string) ($firstRow['NamaMesin'] ?? ''),
            'shift' => (string) ($firstRow['Shift'] ?? ''),
            'inputs' => $inputs,
            'outputs' => $outputs,
            'downtime_rows' => $this->fetchDowntimeRows($noProduksi),
            'approvals' => [
                'operator' => (string) ($firstRow['CreateBy'] ?? ''),
                'ka_regu_mixer' => (string) ($firstRow['CheckBy1'] ?? ''),
                'ka_div_mixer' => (string) ($firstRow['CheckBy2'] ?? ''),
                'ka_dept_produksi' => (string) ($firstRow['ApproveBy'] ?? ''),
            ],
            'attendance_total' => (int) round($this->toFloat($firstRow['JmlhAnggota'] ?? 0) ?? 0),
            'attendance_hadir' => (int) round($this->toFloat($firstRow['Hadir'] ?? 0) ?? 0),
        ]);
    }

    private function buildReportDataFromTables(string $noProduksi): array
    {
        $connectionName = config('reports.pps_mixer_produksi_harian.database_connection');
        $connection = DB::connection($connectionName ?: null);

        $header = $connection->table('MixerProduksi_h as h')
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
            throw new RuntimeException('Data mixer produksi tidak ditemukan untuk nomor produksi tersebut.');
        }

        $inputs = collect()
            ->concat($this->fetchInputBrokerRows($connection, $noProduksi))
            ->concat($this->fetchInputMixerRows($connection, $noProduksi))
            ->concat($this->fetchInputGilinganRows($connection, $noProduksi))
            ->concat($this->fetchInputBahanBakuRows($connection, $noProduksi))
            ->sortBy([
                ['group', 'asc'],
                ['reference', 'asc'],
            ])
            ->values()
            ->all();

        $outputs = $connection->table('MixerProduksiOutput as o')
            ->join('Mixer_d as d', function ($join): void {
                $join->on('d.NoMixer', '=', 'o.NoMixer')
                    ->on('d.NoSak', '=', 'o.NoSak');
            })
            ->leftJoin('Mixer_h as h', 'h.NoMixer', '=', 'o.NoMixer')
            ->leftJoin('MstMixer as m', 'm.IdMixer', '=', 'h.IdMixer')
            ->selectRaw("o.NoMixer as reference, 'MIXR' as output_group, MAX(COALESCE(m.NamaMixer, o.NoMixer)) as jenis, SUM(COALESCE(d.Berat, 0)) as qty")
            ->where('o.NoProduksi', $noProduksi)
            ->groupBy('o.NoMixer')
            ->orderBy('o.NoMixer')
            ->get()
            ->map(static fn ($row): array => [
                'reference' => (string) ($row->reference ?? ''),
                'group' => (string) ($row->output_group ?? 'MIXR'),
                'jenis' => (string) ($row->jenis ?? $row->reference ?? ''),
                'qty' => (float) ($row->qty ?? 0),
                'hasil_cek_qc' => '',
            ])
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
                'ka_regu_mixer' => (string) ($header->CheckBy1 ?? ''),
                'ka_div_mixer' => (string) ($header->CheckBy2 ?? ''),
                'ka_dept_produksi' => (string) ($header->ApproveBy ?? ''),
            ],
            'attendance_total' => (int) round($this->toFloat($header->JmlhAnggota ?? 0) ?? 0),
            'attendance_hadir' => (int) round($this->toFloat($header->Hadir ?? 0) ?? 0),
        ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function fetchInputBrokerRows($connection, string $noProduksi): Collection
    {
        return $connection->table('MixerProduksiInputBroker as i')
            ->join('Broker_d as d', function ($join): void {
                $join->on('d.NoBroker', '=', 'i.NoBroker')
                    ->on('d.NoSak', '=', 'i.NoSak');
            })
            ->leftJoin('Broker_h as h', 'h.NoBroker', '=', 'i.NoBroker')
            ->leftJoin('MstJenisPlastik as j', 'j.IdJenisPlastik', '=', 'h.IdJenisPlastik')
            ->selectRaw("i.NoBroker as reference, 'BROK' as input_group, MAX(COALESCE(j.Jenis, i.NoBroker)) as jenis, SUM(COALESCE(d.Berat, 0)) as qty")
            ->where('i.NoProduksi', $noProduksi)
            ->groupBy('i.NoBroker')
            ->orderBy('i.NoBroker')
            ->get()
            ->map(static fn ($row): array => [
                'reference' => (string) ($row->reference ?? ''),
                'group' => (string) ($row->input_group ?? 'BROK'),
                'jenis' => (string) ($row->jenis ?? $row->reference ?? ''),
                'qty' => (float) ($row->qty ?? 0),
                'hasil_cek_qc' => '',
            ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function fetchInputMixerRows($connection, string $noProduksi): Collection
    {
        return $connection->table('MixerProduksiInputMixer as i')
            ->join('Mixer_d as d', function ($join): void {
                $join->on('d.NoMixer', '=', 'i.NoMixer')
                    ->on('d.NoSak', '=', 'i.NoSak');
            })
            ->leftJoin('Mixer_h as h', 'h.NoMixer', '=', 'i.NoMixer')
            ->leftJoin('MstMixer as m', 'm.IdMixer', '=', 'h.IdMixer')
            ->selectRaw("i.NoMixer as reference, 'MIXR' as input_group, MAX(COALESCE(m.NamaMixer, i.NoMixer)) as jenis, SUM(COALESCE(d.Berat, 0)) as qty")
            ->where('i.NoProduksi', $noProduksi)
            ->groupBy('i.NoMixer')
            ->orderBy('i.NoMixer')
            ->get()
            ->map(static fn ($row): array => [
                'reference' => (string) ($row->reference ?? ''),
                'group' => (string) ($row->input_group ?? 'MIXR'),
                'jenis' => (string) ($row->jenis ?? $row->reference ?? ''),
                'qty' => (float) ($row->qty ?? 0),
                'hasil_cek_qc' => '',
            ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function fetchInputGilinganRows($connection, string $noProduksi): Collection
    {
        return $connection->table('MixerProduksiInputGilingan as i')
            ->leftJoin('Gilingan as g', 'g.NoGilingan', '=', 'i.NoGilingan')
            ->leftJoin('MstGilingan as m', 'm.IdGilingan', '=', 'g.IdGilingan')
            ->selectRaw("i.NoGilingan as reference, 'GIL' as input_group, MAX(COALESCE(m.NamaGilingan, i.NoGilingan)) as jenis, SUM(COALESCE(g.Berat, 0)) as qty")
            ->where('i.NoProduksi', $noProduksi)
            ->groupBy('i.NoGilingan')
            ->orderBy('i.NoGilingan')
            ->get()
            ->map(static fn ($row): array => [
                'reference' => (string) ($row->reference ?? ''),
                'group' => (string) ($row->input_group ?? 'GIL'),
                'jenis' => (string) ($row->jenis ?? $row->reference ?? ''),
                'qty' => (float) ($row->qty ?? 0),
                'hasil_cek_qc' => '',
            ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function fetchInputBahanBakuRows($connection, string $noProduksi): Collection
    {
        return $connection->table('MixerProduksiInputBB as i')
            ->join('BahanBaku_d as d', function ($join): void {
                $join->on('d.NoBahanBaku', '=', 'i.NoBahanBaku')
                    ->on('d.NoSak', '=', 'i.NoSak');
            })
            ->selectRaw("i.NoBahanBaku as reference, 'BB' as input_group, i.NoBahanBaku as jenis, SUM(COALESCE(d.Berat, 0)) as qty")
            ->where('i.NoProduksi', $noProduksi)
            ->groupBy('i.NoBahanBaku')
            ->orderBy('i.NoBahanBaku')
            ->get()
            ->map(static fn ($row): array => [
                'reference' => (string) ($row->reference ?? ''),
                'group' => (string) ($row->input_group ?? 'BB'),
                'jenis' => (string) ($row->jenis ?? $row->reference ?? ''),
                'qty' => (float) ($row->qty ?? 0),
                'hasil_cek_qc' => '',
            ]);
    }

    private function assembleReport(array $payload): array
    {
        $inputs = $payload['inputs'];
        $outputs = $payload['outputs'];
        $downtimeRows = $payload['downtime_rows'];
        $detailCount = max(count($inputs), count($outputs), count($downtimeRows), 1);
        $detailRows = [];

        for ($index = 0; $index < $detailCount; $index++) {
            $detailRows[] = [
                'input_nama_bahan' => $inputs[$index]['jenis'] ?? '',
                'input_qty' => $inputs[$index]['qty'] ?? null,
                'output_nama_barang' => $outputs[$index]['jenis'] ?? '',
                'output_nomor_label' => $outputs[$index]['reference'] ?? '',
                'output_qty' => $outputs[$index]['qty'] ?? null,
                'output_hasil_cek_qc' => $outputs[$index]['hasil_cek_qc'] ?? '',
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
                'input_qty' => $this->sumQty($inputs),
                'output_qty' => $this->sumQty($outputs),
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
        $connectionName = config('reports.pps_mixer_produksi_harian.database_connection');
        $connection = DB::connection($connectionName ?: null);

        $rows = $connection->select(
            'SELECT NoUrut, TimeStart, TimeEnd, Remarks FROM MixerProduksi_dDowntime WHERE NoProduksi = ? ORDER BY NoUrut',
            [$noProduksi]
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
        $configPath = 'reports.pps_mixer_produksi_harian';
        $connectionName = config("{$configPath}.database_connection");
        $procedure = (string) config("{$configPath}.stored_procedure");
        $syntax = (string) config("{$configPath}.call_syntax", 'exec');

        if ($procedure === '') {
            throw new RuntimeException('Stored procedure laporan PPS Mixer Produksi Harian belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException('Laporan PPS Mixer Produksi Harian dikonfigurasi untuk SQL Server.');
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

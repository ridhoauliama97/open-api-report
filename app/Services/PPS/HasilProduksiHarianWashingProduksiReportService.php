<?php

namespace App\Services\PPS;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class HasilProduksiHarianWashingProduksiReportService
{
    public function fetch(string $noProduksi): array
    {
        $rows = $this->runProcedureQuery($noProduksi);
        $rows = array_map(static fn ($row): array => (array) $row, $rows);

        if ($rows === []) {
            throw new RuntimeException('Data washing produksi tidak ditemukan untuk nomor produksi tersebut.');
        }

        return $this->buildReportData($noProduksi, $rows);
    }

    public function healthCheck(string $noProduksi): array
    {
        $rows = $this->runProcedureQuery($noProduksi);
        $rows = array_map(static fn ($row): array => (array) $row, $rows);
        $detectedColumns = array_keys($rows[0] ?? []);
        $expectedColumns = config('reports.pps_washing_produksi_harian.expected_columns', []);
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

    public function recentNoProduksi(int $limit = 10): array
    {
        $connectionName = config('reports.pps_washing_produksi_harian.database_connection');

        $rows = DB::connection($connectionName ?: null)
            ->table('WashingProduksi_h')
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

    private function buildReportData(string $noProduksi, array $rows): array
    {
        $firstRow = $rows[0];
        $inputs = [];
        $outputs = [];
        $rejects = [];

        foreach ($rows as $row) {
            $type = strtolower(trim((string) ($row['Tipe'] ?? '')));
            $item = [
                'no_label' => (string) ($row['NoLabel'] ?? ''),
                'jenis' => (string) ($row['Jenis'] ?? ''),
                'qty' => $this->toFloat($row['Brt'] ?? null),
                'hasil_cek_qc' => (string) ($row['Stat'] ?? ''),
            ];

            if ($type === 'input') {
                $inputs[] = $item;
                continue;
            }

            if ($type === 'output') {
                $outputs[] = $item;
                continue;
            }

            if ($type === 'reject') {
                $rejects[] = $item;
            }
        }

        $downtimeRows = $this->fetchDowntimeRows($noProduksi);
        $detailCount = max(count($inputs), count($outputs), count($rejects), count($downtimeRows), 1);
        $detailRows = [];

        for ($index = 0; $index < $detailCount; $index++) {
            $detailRows[] = [
                'input_nama_bahan' => $inputs[$index]['jenis'] ?? '',
                'input_qty' => $inputs[$index]['qty'] ?? null,
                'output_nama_barang' => $outputs[$index]['jenis'] ?? '',
                'output_nomor_label' => $outputs[$index]['no_label'] ?? '',
                'output_qty' => $outputs[$index]['qty'] ?? null,
                'output_hasil_cek_qc' => $outputs[$index]['hasil_cek_qc'] ?? '',
                'reject_qty' => $rejects[$index]['qty'] ?? null,
                'downtime_jam_berhenti' => $downtimeRows[$index]['jam_berhenti'] ?? '',
                'downtime_durasi' => $downtimeRows[$index]['durasi'] ?? '',
                'downtime_keterangan' => $downtimeRows[$index]['keterangan'] ?? '',
            ];
        }

        $anggota = (int) round($this->toFloat($firstRow['JmlhAnggota'] ?? 0) ?? 0);
        $hadir = (int) round($this->toFloat($firstRow['Hadir'] ?? 0) ?? 0);

        return [
            'meta' => [
                'no_produksi' => (string) ($firstRow['NoProduksi'] ?? $noProduksi),
                'tanggal' => isset($firstRow['TglProduksi']) && $firstRow['TglProduksi'] !== null
                    ? Carbon::parse((string) $firstRow['TglProduksi'])
                    : null,
                'nama_mesin' => (string) ($firstRow['NamaMesin'] ?? ''),
                'shift' => (string) ($firstRow['Shift'] ?? ''),
            ],
            'detail_rows' => $detailRows,
            'blank_row_count' => max(20 - count($detailRows), 0),
            'totals' => [
                'input_qty' => $this->sumQty($inputs),
                'output_qty' => $this->sumQty($outputs),
                'reject_qty' => $this->sumQty($rejects),
            ],
            'approvals' => [
                'operator' => (string) ($firstRow['CreateBy'] ?? ''),
                'ka_regu_cuci' => (string) ($firstRow['CheckBy1'] ?? ''),
                'ka_div_cuci_broker' => (string) ($firstRow['CheckBy2'] ?? ''),
                'ka_dept_produksi' => (string) ($firstRow['ApproveBy'] ?? ''),
            ],
            'attendance' => [
                'hadir' => $hadir,
                'absen' => max($anggota - $hadir, 0),
                'total' => $anggota,
            ],
        ];
    }

    private function fetchDowntimeRows(string $noProduksi): array
    {
        $connectionName = config('reports.pps_washing_produksi_harian.database_connection');
        $connection = DB::connection($connectionName ?: null);

        $rows = $connection->select(
            "
            SELECT NoUrut, TimeStart, TimeEnd, Remarks FROM WashingProduksi_dDownTime WHERE NoProduksi = ?
            UNION ALL
            SELECT NoUrut, TimeStart, TimeEnd, Remarks FROM WashingProduksi_dDownTime1 WHERE NoProduksi = ?
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
        $configPath = 'reports.pps_washing_produksi_harian';
        $connectionName = config("{$configPath}.database_connection");
        $procedure = (string) config("{$configPath}.stored_procedure");
        $syntax = (string) config("{$configPath}.call_syntax", 'exec');

        if ($procedure === '') {
            throw new RuntimeException('Stored procedure laporan PPS Washing Produksi Harian belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException('Laporan PPS Washing Produksi Harian dikonfigurasi untuk SQL Server.');
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

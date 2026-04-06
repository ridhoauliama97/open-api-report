<?php

namespace App\Services\PPS;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class HasilProduksiHarianCrusherProduksiReportService
{
    public function fetch(string $noProduksi): array
    {
        $rows = $this->runProcedureQuery($noProduksi);
        $rows = array_map(static fn ($row): array => (array) $row, $rows);

        if ($rows === []) {
            throw new RuntimeException('Data crusher produksi tidak ditemukan untuk nomor produksi tersebut.');
        }

        return $this->buildReportData($noProduksi, $rows);
    }

    public function healthCheck(string $noProduksi): array
    {
        $rows = $this->runProcedureQuery($noProduksi);
        $rows = array_map(static fn ($row): array => (array) $row, $rows);
        $detectedColumns = array_keys($rows[0] ?? []);
        $expectedColumns = config('reports.pps_crusher_produksi_harian.expected_columns', []);
        $expectedColumns = is_array($expectedColumns) ? array_values($expectedColumns) : [];

        return [
            'is_healthy' => empty(array_diff($expectedColumns, $detectedColumns)),
            'expected_columns' => $expectedColumns,
            'detected_columns' => $detectedColumns,
            'missing_columns' => array_values(array_diff($expectedColumns, $detectedColumns)),
            'extra_columns' => array_values(array_diff($detectedColumns, $expectedColumns)),
            'row_count' => count($rows),
            'field_aliases' => $this->aliasUsageSummary($rows),
        ];
    }

    public function recentNoProduksi(int $limit = 10): array
    {
        $connectionName = config('reports.pps_crusher_produksi_harian.database_connection');

        $rows = DB::connection($connectionName ?: null)
            ->table('CrusherProduksi_h')
            ->select(['NoCrusherProduksi', 'Tanggal', 'Shift'])
            ->orderByDesc('Tanggal')
            ->orderByDesc('NoCrusherProduksi')
            ->limit($limit)
            ->get();

        return $rows->map(static function ($row): array {
            return [
                'no_produksi' => (string) ($row->NoCrusherProduksi ?? ''),
                'tanggal' => isset($row->Tanggal)
                    ? Carbon::parse((string) $row->Tanggal)->format('d-M-y')
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

        foreach ($rows as $row) {
            $type = strtolower(trim((string) ($row['Tipe'] ?? '')));
            $item = [
                'no_label' => $this->stringValue($row, ['NoLabel', 'NomorLabel', 'NoCrusher', 'NoBarang']),
                'jenis' => $this->stringValue($row, ['NamaBarang', 'Jenis', 'NamaJenis']),
                'qty' => $this->floatValue($row, ['Berat', 'Brt', 'Qty', 'QtyKg']),
                'hasil_cek_qc' => $this->stringValue($row, ['HasilCekQC', 'HasilQC', 'QC', 'Stat']),
            ];

            if ($type === 'input') {
                $inputs[] = $item;
                continue;
            }

            if ($type === 'output') {
                $outputs[] = $item;
            }
        }

        $downtimeRows = $this->extractDowntimeRowsFromProcedure($rows);
        if ($downtimeRows === []) {
            $downtimeRows = $this->fetchDowntimeRows($noProduksi);
        }
        $detailCount = max(count($inputs), count($outputs), count($downtimeRows), 1);
        $detailRows = [];

        for ($index = 0; $index < $detailCount; $index++) {
            $detailRows[] = [
                'input_nama_bahan' => $inputs[$index]['jenis'] ?? '',
                'input_qty' => $inputs[$index]['qty'] ?? null,
                'output_nama_barang' => $outputs[$index]['jenis'] ?? '',
                'output_nomor_label' => $outputs[$index]['no_label'] ?? '',
                'output_qty' => $outputs[$index]['qty'] ?? null,
                'output_hasil_cek_qc' => $outputs[$index]['hasil_cek_qc'] ?? '',
                'downtime_jam_berhenti' => $downtimeRows[$index]['jam_berhenti'] ?? '',
                'downtime_durasi' => $downtimeRows[$index]['durasi'] ?? '',
                'downtime_keterangan' => $downtimeRows[$index]['keterangan'] ?? '',
            ];
        }

        $anggota = (int) round($this->toFloat($firstRow['JmlhAnggota'] ?? 0) ?? 0);
        $hadir = (int) round($this->toFloat($firstRow['Hadir'] ?? 0) ?? 0);

        return [
            'meta' => [
                'no_produksi' => (string) ($firstRow['NoCrusherProduksi'] ?? $noProduksi),
                'tanggal' => isset($firstRow['Tanggal']) && $firstRow['Tanggal'] !== null
                    ? Carbon::parse((string) $firstRow['Tanggal'])
                    : null,
                'nama_mesin' => (string) ($firstRow['NamaMesin'] ?? ''),
                'shift' => (string) ($firstRow['Shift'] ?? ''),
            ],
            'detail_rows' => $detailRows,
            'blank_row_count' => max(20 - count($detailRows), 0),
            'totals' => [
                'input_qty' => $this->sumQty($inputs),
                'output_qty' => $this->sumQty($outputs),
            ],
            'approvals' => [
                'operator' => (string) ($firstRow['CreateBy'] ?? ''),
                'ka_regu_crusher' => (string) ($firstRow['CheckBy1'] ?? ''),
                'ka_div_crusher' => (string) ($firstRow['CheckBy2'] ?? ''),
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
        $connectionName = config('reports.pps_crusher_produksi_harian.database_connection');
        $connection = DB::connection($connectionName ?: null);

        $rows = $connection->select(
            "
            SELECT NoUrut, TimeStart, TimeEnd, Remarks FROM CrusherProduksi_dDowntime WHERE NoCrusherProduksi = ?
            UNION ALL
            SELECT NoUrut, TimeStart, TimeEnd, Remarks FROM CrusherProduksi_dDowntime1 WHERE NoCrusherProduksi = ?
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

    private function extractDowntimeRowsFromProcedure(array $rows): array
    {
        $items = [];

        foreach ($rows as $row) {
            $jamBerhenti = $this->stringValue($row, ['JamBerhenti', 'TimeStart', 'JamStop']);
            $durasi = $this->stringValue($row, ['Durasi', 'DurasiMenit', 'Duration']);
            $keterangan = $this->stringValue($row, ['Keterangan', 'Remarks', 'Remark']);

            if ($jamBerhenti === '' && $durasi === '' && $keterangan === '') {
                continue;
            }

            $items[] = [
                'jam_berhenti' => $jamBerhenti,
                'durasi' => $durasi,
                'keterangan' => $keterangan,
            ];
        }

        return $items;
    }

    private function runProcedureQuery(string $noProduksi): array
    {
        $configPath = 'reports.pps_crusher_produksi_harian';
        $connectionName = config("{$configPath}.database_connection");
        $procedure = (string) config("{$configPath}.stored_procedure");
        $syntax = (string) config("{$configPath}.call_syntax", 'exec');

        if ($procedure === '') {
            throw new RuntimeException('Stored procedure laporan PPS Crusher Produksi Harian belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException('Laporan PPS Crusher Produksi Harian dikonfigurasi untuk SQL Server.');
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

    private function stringValue(array $row, array $keys): string
    {
        foreach ($keys as $key) {
            if (!array_key_exists($key, $row)) {
                continue;
            }

            $value = trim((string) ($row[$key] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    private function floatValue(array $row, array $keys): ?float
    {
        foreach ($keys as $key) {
            if (!array_key_exists($key, $row)) {
                continue;
            }

            $value = $this->toFloat($row[$key] ?? null);
            if ($value !== null) {
                return $value;
            }
        }

        return null;
    }

    private function sumQty(array $rows): float
    {
        $total = 0.0;

        foreach ($rows as $row) {
            $total += (float) ($row['qty'] ?? 0);
        }

        return $total;
    }

    private function aliasUsageSummary(array $rows): array
    {
        $aliasMap = [
            'nomor_label' => ['NoLabel', 'NomorLabel', 'NoCrusher', 'NoBarang'],
            'nama_barang' => ['NamaBarang', 'Jenis', 'NamaJenis'],
            'qty' => ['Berat', 'Brt', 'Qty', 'QtyKg'],
            'hasil_cek_qc' => ['HasilCekQC', 'HasilQC', 'QC', 'Stat'],
            'downtime_jam_berhenti' => ['JamBerhenti', 'TimeStart', 'JamStop'],
            'downtime_durasi' => ['Durasi', 'DurasiMenit', 'Duration'],
            'downtime_keterangan' => ['Keterangan', 'Remarks', 'Remark'],
        ];

        $summary = [];

        foreach ($aliasMap as $field => $aliases) {
            $matched = [];

            foreach ($aliases as $alias) {
                foreach ($rows as $row) {
                    if (!array_key_exists($alias, $row)) {
                        continue;
                    }

                    $value = $row[$alias] ?? null;
                    if ($value !== null && trim((string) $value) !== '') {
                        $matched[] = $alias;
                        break;
                    }
                }
            }

            $summary[$field] = [
                'aliases' => $aliases,
                'matched' => array_values(array_unique($matched)),
            ];
        }

        return $summary;
    }
}

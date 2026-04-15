<?php

namespace App\Services\PPS;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class HasilProduksiHarianSpannerProduksiReportService
{
    public function fetch(string $noProduksi): array
    {
        $rows = $this->runProcedureQuery($noProduksi);
        $rows = array_map(static fn ($row): array => (array) $row, $rows);

        if ($rows === []) {
            throw new RuntimeException('Data spanner produksi tidak ditemukan untuk nomor produksi tersebut.');
        }

        return $this->buildReportData($noProduksi, $rows);
    }

    public function healthCheck(string $noProduksi): array
    {
        $rows = $this->runProcedureQuery($noProduksi);
        $rows = array_map(static fn ($row): array => (array) $row, $rows);
        $detectedColumns = array_keys($rows[0] ?? []);
        $expectedColumns = config('reports.pps_spanner_produksi_harian.expected_columns', []);
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
        $connectionName = config('reports.pps_spanner_produksi_harian.database_connection');

        $rows = DB::connection($connectionName ?: null)
            ->table('Spanner_h')
            ->select(['NoProduksi', 'Tanggal', 'Shift'])
            ->orderByDesc('Tanggal')
            ->orderByDesc('NoProduksi')
            ->limit($limit)
            ->get();

        return $rows->map(static function ($row): array {
            return [
                'no_produksi' => (string) ($row->NoProduksi ?? ''),
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
                'reference' => (string) ($row['NoLabel'] ?? ''),
                'jenis' => (string) ($row['Jenis'] ?? ''),
                'qty' => $this->toFloat($row['Total'] ?? null),
            ];

            if ($type === 'input') {
                $inputs[] = $item;
                continue;
            }

            if ($type === 'output') {
                $outputs[] = $item;
            }
        }

        $totalInputQty = $this->sumField($inputs, 'qty');
        $detailCount = max(count($inputs), count($outputs), 1);
        $detailRows = [];

        for ($index = 0; $index < $detailCount; $index++) {
            $inputQty = $inputs[$index]['qty'] ?? null;
            $detailRows[] = [
                'input_nama_barang' => $inputs[$index]['jenis'] ?? '',
                'input_qty' => $inputQty,
                'input_percentage' => $inputQty !== null && $totalInputQty > 0
                    ? (((float) $inputQty) / $totalInputQty) * 100
                    : null,
                'output_nama_barang' => $outputs[$index]['jenis'] ?? '',
                'output_nomor_label' => $outputs[$index]['reference'] ?? '',
                'output_qty' => $outputs[$index]['qty'] ?? null,
                'output_berat' => null,
                'downtime_jam_berhenti' => '',
                'downtime_durasi' => '',
                'downtime_keterangan' => '',
            ];
        }

        return [
            'meta' => [
                'source' => 'stored_procedure',
                'no_produksi' => (string) ($firstRow['NoProduksi'] ?? $noProduksi),
                'tanggal' => isset($firstRow['Tanggal']) && $firstRow['Tanggal'] !== null
                    ? Carbon::parse((string) $firstRow['Tanggal'])
                    : null,
                'nama_mesin' => (string) ($firstRow['NamaMesin'] ?? ''),
                'shift' => (string) ($firstRow['Shift'] ?? ''),
            ],
            'detail_rows' => $detailRows,
            'blank_row_count' => max(18 - count($detailRows), 0),
            'totals' => [
                'input_qty' => $totalInputQty,
                'output_qty' => $this->sumField($outputs, 'qty'),
                'output_berat' => 0.0,
            ],
            'approvals' => [
                'operator' => (string) ($firstRow['CreateBy'] ?? ''),
                'ka_regu_spanner' => (string) ($firstRow['CheckBy1'] ?? ''),
                'ka_div_spanner' => (string) ($firstRow['CheckBy2'] ?? ''),
                'ka_dept_produksi' => (string) ($firstRow['ApproveBy'] ?? ''),
            ],
            'attendance' => [
                'hadir' => (int) round($this->toFloat($firstRow['Hadir'] ?? 0) ?? 0),
                'absen' => max(
                    (int) round($this->toFloat($firstRow['JmlhAnggota'] ?? 0) ?? 0) -
                    (int) round($this->toFloat($firstRow['Hadir'] ?? 0) ?? 0),
                    0,
                ),
                'total' => (int) round($this->toFloat($firstRow['JmlhAnggota'] ?? 0) ?? 0),
            ],
        ];
    }

    private function runProcedureQuery(string $noProduksi): array
    {
        $configPath = 'reports.pps_spanner_produksi_harian';
        $connectionName = config("{$configPath}.database_connection");
        $procedure = (string) config("{$configPath}.stored_procedure");
        $syntax = (string) config("{$configPath}.call_syntax", 'exec');

        if ($procedure === '') {
            throw new RuntimeException('Stored procedure laporan PPS Spanner Produksi Harian belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException('Laporan PPS Spanner Produksi Harian dikonfigurasi untuk SQL Server.');
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

    private function sumField(array $rows, string $field): float
    {
        $total = 0.0;
        foreach ($rows as $row) {
            $total += (float) ($row[$field] ?? 0);
        }

        return $total;
    }
}

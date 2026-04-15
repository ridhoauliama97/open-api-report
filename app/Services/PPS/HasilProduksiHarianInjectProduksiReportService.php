<?php

namespace App\Services\PPS;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class HasilProduksiHarianInjectProduksiReportService
{
    public function fetch(string $noProduksi): array
    {
        $rows = $this->runProcedureQuery($noProduksi);
        $rows = array_map(static fn ($row): array => (array) $row, $rows);

        if ($rows === []) {
            throw new RuntimeException('Data inject produksi tidak ditemukan untuk nomor produksi tersebut.');
        }

        return $this->buildReportData($noProduksi, $rows);
    }

    public function healthCheck(string $noProduksi): array
    {
        $rows = $this->runProcedureQuery($noProduksi);
        $rows = array_map(static fn ($row): array => (array) $row, $rows);
        $detectedColumns = array_keys($rows[0] ?? []);
        $expectedColumns = config('reports.pps_inject_produksi_harian.expected_columns', []);
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
        $connectionName = config('reports.pps_inject_produksi_harian.database_connection');

        $rows = DB::connection($connectionName ?: null)
            ->table('InjectProduksi_h')
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
        $goods = [];
        $rejects = [];

        foreach ($rows as $row) {
            $type = strtolower(trim((string) ($row['Tipe'] ?? '')));
            $group = strtolower(trim((string) ($row['Group'] ?? '')));

            if ($type === 'input') {
                $inputs[] = [
                    'jenis' => (string) ($row['Jenis'] ?? ''),
                    'qty' => $this->toFloat($row['Total'] ?? null),
                ];

                continue;
            }

            if ($type !== 'output') {
                continue;
            }

            if ($group === 'reject') {
                $rejects[] = [
                    'nama_barang' => (string) ($row['Jenis'] ?? ''),
                    'jumlah_label' => (string) ($row['NoLabel'] ?? ''),
                    'reject_qty' => $this->toFloat($row['Total'] ?? null),
                ];

                continue;
            }

            if (!in_array($group, ['fwip', 'bj'], true)) {
                continue;
            }

            $goods[] = [
                'nama_barang' => (string) ($row['Jenis'] ?? ''),
                'jumlah_label' => (string) ($row['NoLabel'] ?? ''),
                'qty' => $this->toFloat($row['Total2'] ?? null),
                'berat' => $this->toFloat($row['Total'] ?? null),
            ];
        }

        $inputs = $this->aggregateInputsByName($inputs);

        $totalInputQty = $this->sumField($inputs, 'qty');
        $totalGoodBerat = $this->sumField($goods, 'berat');
        $totalRejectQty = $this->sumField($rejects, 'reject_qty');
        $displayRows = $this->buildDisplayRows($goods, $rejects);
        $detailCount = max(count($inputs), count($displayRows), 1);
        $detailRows = [];

        for ($index = 0; $index < $detailCount; $index++) {
            $inputQty = $inputs[$index]['qty'] ?? null;
            $display = $displayRows[$index] ?? [];

            $detailRows[] = [
                'input_nama_barang' => $inputs[$index]['jenis'] ?? '',
                'input_qty' => $inputQty,
                'input_percentage' => $inputQty !== null && $totalInputQty > 0
                    ? (((float) $inputQty) / $totalInputQty) * 100
                    : null,
                'display_items' => $display['items'] ?? [],
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
                'output_qty' => $this->sumField($goods, 'qty'),
                'output_berat' => $totalGoodBerat,
                'reject_qty' => $totalRejectQty,
            ],
            'rates' => [
                'achievement' => $totalInputQty > 0 ? ($totalGoodBerat / $totalInputQty) * 100 : null,
                'rejection' => $totalInputQty > 0 ? ($totalRejectQty / $totalInputQty) * 100 : null,
            ],
            'approvals' => [
                'operator' => (string) ($firstRow['CreateBy'] ?? ''),
                'ka_regu_inject' => (string) ($firstRow['CheckBy1'] ?? ''),
                'ka_div_inject' => (string) ($firstRow['CheckBy2'] ?? ''),
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
        $configPath = 'reports.pps_inject_produksi_harian';
        $connectionName = config("{$configPath}.database_connection");
        $procedure = (string) config("{$configPath}.stored_procedure");
        $syntax = (string) config("{$configPath}.call_syntax", 'exec');

        if ($procedure === '') {
            throw new RuntimeException('Stored procedure laporan PPS Inject Produksi Harian belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException('Laporan PPS Inject Produksi Harian dikonfigurasi untuk SQL Server.');
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

    /**
     * @param array<int, array{nama_barang: string, jumlah_label: string, qty: ?float, berat: ?float}> $goods
     * @param array<int, array{nama_barang: string, jumlah_label: string, reject_qty: ?float}> $rejects
     * @return array<int, array{items: array<int, array{nama_barang: string, jumlah_label: ?string, qty: ?float, berat: ?float, reject: ?float}>}>
     */
    private function buildDisplayRows(array $goods, array $rejects): array
    {
        $items = [];

        foreach ($goods as $good) {
            $items[] = [
                'nama_barang' => (string) ($good['nama_barang'] ?? ''),
                'jumlah_label' => (string) ($good['jumlah_label'] ?? ''),
                'qty' => $good['qty'] ?? null,
                'berat' => $good['berat'] ?? null,
                'reject' => null,
            ];
        }

        foreach ($rejects as $reject) {
            $items[] = [
                'nama_barang' => (string) ($reject['nama_barang'] ?? ''),
                'jumlah_label' => (string) ($reject['jumlah_label'] ?? ''),
                'qty' => null,
                'berat' => null,
                'reject' => $reject['reject_qty'] ?? null,
            ];
        }

        if ($items === []) {
            $items[] = [
                'nama_barang' => '',
                'jumlah_label' => null,
                'qty' => null,
                'berat' => null,
                'reject' => null,
            ];
        }

        return [['items' => $items]];
    }

    /**
     * @param array<int, array{jenis: string, qty: ?float}> $inputs
     * @return array<int, array{jenis: string, qty: float}>
     */
    private function aggregateInputsByName(array $inputs): array
    {
        $grouped = [];
        $order = [];

        foreach ($inputs as $input) {
            $jenis = trim((string) ($input['jenis'] ?? ''));
            if ($jenis === '') {
                continue;
            }

            if (!array_key_exists($jenis, $grouped)) {
                $grouped[$jenis] = [
                    'jenis' => $jenis,
                    'qty' => 0.0,
                ];
                $order[] = $jenis;
            }

            $grouped[$jenis]['qty'] += (float) ($input['qty'] ?? 0);
        }

        return array_map(
            static fn (string $jenis): array => $grouped[$jenis],
            $order,
        );
    }
}

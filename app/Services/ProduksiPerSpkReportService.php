<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class ProduksiPerSpkReportService
{
    private const GROUP_ORDER = ['S4S', 'FJ', 'MLD', 'LMT', 'CCA', 'SAND', 'PACK'];

    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetchRendemen(string $noSpk): array
    {
        $rows = $this->runProcedureQuery($noSpk);

        return array_values(array_map(static fn(object $row): array => (array) $row, $rows));
    }

    /**
     * @return array<string, mixed>
     */
    public function buildReportData(string $noSpk): array
    {
        $noSpk = trim($noSpk);
        if ($noSpk === '') {
            throw new RuntimeException('No SPK wajib diisi.');
        }

        $header = $this->fetchHeader($noSpk);
        $dimensions = $this->fetchDimensions($noSpk);
        $rendemenRows = $this->normalizeRendemenRows($this->fetchRendemen($noSpk));
        $aliveLabels = $this->groupLabelRows($this->fetchLabelRows($noSpk, false));
        $missLabels = $this->groupLabelRows($this->fetchLabelRows($noSpk, true));

        return [
            'no_spk' => $noSpk,
            'header' => $header,
            'dimensions' => $dimensions,
            'rendemen_rows' => $rendemenRows,
            'rendemen_global' => $this->extractRendemenGlobal($rendemenRows),
            'alive_labels' => $aliveLabels,
            'miss_labels' => $missLabels,
            'summary' => [
                'dimension_rows' => count($dimensions),
                'alive_categories' => count($aliveLabels),
                'alive_rows' => array_sum(array_map(static fn(array $item): int => count($item['rows'] ?? []), $aliveLabels)),
                'miss_categories' => count($missLabels),
                'miss_rows' => array_sum(array_map(static fn(array $item): int => count($item['rows'] ?? []), $missLabels)),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function healthCheck(string $noSpk): array
    {
        $rows = $this->fetchRendemen($noSpk);
        $detectedColumns = array_keys($rows[0] ?? []);
        $expectedColumns = config('reports.produksi_per_spk.expected_columns', []);
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
     * @return array<string, mixed>
     */
    private function fetchHeader(string $noSpk): array
    {
        $row = DB::connection(config('reports.produksi_per_spk.database_connection'))
            ->table('dbo.MstSPK_h as h')
            ->leftJoin('dbo.MstBuyer as b', 'b.IdBuyer', '=', 'h.IdBuyer')
            ->select([
                'h.NoSPK',
                'h.Tanggal',
                'h.NoContract',
                'h.Tujuan',
                'h.Enable',
                'b.Buyer',
            ])
            ->where('h.NoSPK', $noSpk)
            ->first();

        if ($row === null) {
            return [
                'NoSPK' => $noSpk,
                'Tanggal' => null,
                'Buyer' => '',
                'NoContract' => '',
                'Tujuan' => '',
                'Status' => '',
            ];
        }

        return [
            'NoSPK' => (string) ($row->NoSPK ?? $noSpk),
            'Tanggal' => $row->Tanggal ?? null,
            'Buyer' => trim((string) ($row->Buyer ?? '')),
            'NoContract' => trim((string) ($row->NoContract ?? '')),
            'Tujuan' => trim((string) ($row->Tujuan ?? '')),
            'Status' => ((int) ($row->Enable ?? 0)) === 1 ? 'Aktif' : 'Nonaktif',
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchDimensions(string $noSpk): array
    {
        $rows = DB::connection(config('reports.produksi_per_spk.database_connection'))
            ->table('dbo.MstSPK_d as d')
            ->leftJoin('dbo.MstJenisKayu as j', 'j.IdJenisKayu', '=', 'd.IdJenisKayu')
            ->selectRaw('COALESCE(NULLIF(LTRIM(RTRIM(j.Jenis)), \'\'), \'-\') as Jenis')
            ->selectRaw('CAST(d.Tebal as float) as Tebal')
            ->selectRaw('CAST(d.Lebar as float) as Lebar')
            ->where('d.NoSPK', $noSpk)
            ->distinct()
            ->orderBy('Jenis')
            ->orderBy('Tebal')
            ->orderBy('Lebar')
            ->get()
            ->map(static fn(object $row): array => [
                'Jenis' => (string) ($row->Jenis ?? '-'),
                'Tebal' => isset($row->Tebal) ? (float) $row->Tebal : null,
                'Lebar' => isset($row->Lebar) ? (float) $row->Lebar : null,
            ])
            ->values()
            ->all();

        return is_array($rows) ? $rows : [];
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    private function normalizeRendemenRows(array $rows): array
    {
        $indexed = [];
        foreach ($rows as $row) {
            $group = strtoupper(trim((string) ($row['Group'] ?? '')));
            if ($group === '') {
                continue;
            }

            $indexed[$group] = [
                'Group' => $group,
                'Output' => $this->toFloat($row['Output'] ?? null),
                'Input' => $this->toFloat($row['Input'] ?? null),
                'Rend' => $this->toFloat($row['Rend'] ?? null),
                'RendGlobal' => $this->toFloat($row['RendGlobal'] ?? null),
            ];
        }

        $normalized = [];
        foreach (self::GROUP_ORDER as $group) {
            $normalized[] = $indexed[$group] ?? [
                'Group' => $group,
                'Output' => null,
                'Input' => null,
                'Rend' => null,
                'RendGlobal' => null,
            ];
        }

        return $normalized;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    private function groupLabelRows(array $rows): array
    {
        $grouped = [];

        foreach ($rows as $row) {
            $category = (string) ($row['Kategori'] ?? 'LAINNYA');

            if (!isset($grouped[$category])) {
                $grouped[$category] = [
                    'name' => $category,
                    'rows' => [],
                    'total' => 0.0,
                ];
            }

            $grouped[$category]['rows'][] = $row;
            $grouped[$category]['total'] += (float) ($row['Total'] ?? 0.0);
        }

        $result = [];
        foreach (self::GROUP_ORDER as $category) {
            if (!isset($grouped[$category])) {
                continue;
            }

            $result[] = $grouped[$category];
        }

        foreach ($grouped as $category => $item) {
            if (in_array($category, self::GROUP_ORDER, true)) {
                continue;
            }
            $result[] = $item;
        }

        return $result;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchLabelRows(string $noSpk, bool $missPrediction): array
    {
        $sql = $this->buildLabelUnionSql($missPrediction);
        $params = $missPrediction ? array_fill(0, 14, $noSpk) : array_fill(0, 7, $noSpk);

        $rows = DB::connection(config('reports.produksi_per_spk.database_connection'))->select($sql, $params);

        return array_values(array_map(function (object $row): array {
            return [
                'Kategori' => trim((string) ($row->Kategori ?? 'LAINNYA')),
                'Jenis' => trim((string) ($row->Jenis ?? '-')),
                'NoLabel' => trim((string) ($row->NoLabel ?? '-')),
                'Lokasi' => trim((string) ($row->Lokasi ?? '-')),
                'Tebal' => $this->toFloat($row->Tebal ?? null),
                'Lebar' => $this->toFloat($row->Lebar ?? null),
                'Panjang' => $this->toFloat($row->Panjang ?? null),
                'Total' => (float) ($this->toFloat($row->Total ?? null) ?? 0.0),
            ];
        }, $rows));
    }

    private function extractRendemenGlobal(array $rendemenRows): ?float
    {
        foreach ($rendemenRows as $row) {
            $value = $this->toFloat($row['RendGlobal'] ?? null);
            if ($value !== null) {
                return $value;
            }
        }

        return null;
    }

    private function buildLabelUnionSql(bool $missPrediction): string
    {
        $condition = $missPrediction
            ? 'h.NoSPKTujuan = ? AND ISNULL(h.NoSPK, \'\') <> ? AND h.DateUsage IS NULL'
            : 'h.NoSPK = ? AND h.DateUsage IS NULL';

        $build = static function (
            string $kategori,
            string $headerTable,
            string $headerNo,
            string $detailTable,
            string $detailNo,
            bool $usesUnits = true
        ) use ($condition): string {
            $formula = $usesUnits
                ? "ROUND(d.Tebal * d.Lebar * d.Panjang * d.JmlhBatang / 1000000000 * CASE WHEN h.IdUOMTblLebar = 3 THEN 645.16 ELSE 1 END * CASE WHEN h.IdUOMPanjang = 4 THEN 304.8 ELSE 1 END, 4, 1)"
                : "ROUND(d.Tebal * d.Lebar * d.Panjang * d.JmlhBatang / 1000000000, 4, 1)";

            return "
                SELECT
                    '{$kategori}' AS Kategori,
                    COALESCE(NULLIF(LTRIM(RTRIM(j.Jenis)), ''), '-') AS Jenis,
                    CAST(h.{$headerNo} AS varchar(50)) AS NoLabel,
                    COALESCE(NULLIF(LTRIM(RTRIM(CAST(h.IdLokasi AS varchar(50)))), ''), '-') AS Lokasi,
                    CAST(d.Tebal AS float) AS Tebal,
                    CAST(d.Lebar AS float) AS Lebar,
                    CAST(d.Panjang AS float) AS Panjang,
                    {$formula} AS Total
                FROM dbo.{$headerTable} h
                INNER JOIN dbo.{$detailTable} d ON d.{$detailNo} = h.{$headerNo}
                LEFT JOIN dbo.MstJenisKayu j ON j.IdJenisKayu = h.IdJenisKayu
                WHERE {$condition}
            ";
        };

        return implode("\nUNION ALL\n", [
            $build('S4S', 'S4S_h', 'NoS4S', 'S4S_d', 'NoS4S'),
            $build('FJ', 'FJ_h', 'NoFJ', 'FJ_d', 'NoFJ'),
            $build('MLD', 'Moulding_h', 'NoMoulding', 'Moulding_d', 'NoMoulding'),
            $build('LMT', 'Laminating_h', 'NoLaminating', 'Laminating_d', 'NoLaminating'),
            $build('CCA', 'CCAkhir_h', 'NoCCAkhir', 'CCAkhir_d', 'NoCCAkhir'),
            $build('SAND', 'Sanding_h', 'NoSanding', 'Sanding_d', 'NoSanding'),
            $build('PACK', 'BarangJadi_h', 'NoBJ', 'BarangJadi_d', 'NoBJ', false),
        ]) . "\nORDER BY Kategori, Jenis, NoLabel, Tebal, Lebar, Panjang";
    }

    /**
     * @return array<int, object>
     */
    private function runProcedureQuery(string $noSpk): array
    {
        $connectionName = config('reports.produksi_per_spk.database_connection');
        $procedure = (string) config('reports.produksi_per_spk.stored_procedure', 'SP_LapProduksiPerSPK');
        $syntax = (string) config('reports.produksi_per_spk.call_syntax', 'exec');
        $customQuery = config('reports.produksi_per_spk.query');

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan produksi per SPK belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan produksi per SPK dikonfigurasi untuk SQL Server. '
                . 'Set PRODUKSI_PER_SPK_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException(
                    'PRODUKSI_PER_SPK_REPORT_QUERY belum diisi. '
                    . 'Isi query manual jika menggunakan PRODUKSI_PER_SPK_REPORT_CALL_SYNTAX=query.',
                );

            return $connection->select($query, [$noSpk]);
        }

        if (!preg_match('/^[A-Za-z0-9_$.]+$/', $procedure)) {
            throw new RuntimeException('Nama stored procedure tidak valid.');
        }

        $sql = match ($syntax) {
            'exec' => "SET NOCOUNT ON; EXEC {$procedure} ?",
            'call' => "CALL {$procedure}(?)",
            default => $driver === 'sqlsrv' ? "SET NOCOUNT ON; EXEC {$procedure} ?" : "CALL {$procedure}(?)",
        };

        return $connection->select($sql, [$noSpk]);
    }

    private function toFloat(mixed $value): ?float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        if (!is_string($value)) {
            return null;
        }

        $normalized = trim($value);
        if ($normalized === '') {
            return null;
        }

        $normalized = str_replace(' ', '', $normalized);

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
}

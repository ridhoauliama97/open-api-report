<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class PenerimaanStHasilSawmillReportService
{
    private const QUANTITY_DIVISOR = 3;

    /**
     * @return array<string, mixed>
     */
    public function buildReportData(string $noPenSt): array
    {
        $mainResult = $this->fetchMainRowsWithMeta($noPenSt);
        $rows = $mainResult['rows'];
        $subRows = $this->fetchSubRows($noPenSt);
        $layout = $mainResult['is_fallback'] ? 'flat' : 'grade';

        if ($rows === []) {
            throw new RuntimeException('Data penerimaan ST hasil sawmill tidak ditemukan untuk No Pen ST yang dipilih.');
        }

        $lengthColumns = $this->buildLengthColumns($rows);
        $detailRows = $this->buildDetailRows($rows, $layout === 'flat' ? null : 'NamaGrade');
        $gradeGroups = $this->buildGradeGroups($detailRows, $lengthColumns);
        $summary = $this->buildSummary($gradeGroups, $lengthColumns);

        if ($layout === 'flat') {
            $summary = array_merge($summary, $this->buildFlatSummary($rows));
        }

        return [
            'no_pen_st' => $noPenSt,
            'layout' => $layout,
            'rows' => $rows,
            'sub_rows' => $subRows,
            'header' => $this->buildHeader($rows[0]),
            'length_columns' => $lengthColumns,
            'flat_tebal_groups' => $layout === 'flat' ? $this->buildFlatTebalGroups($detailRows) : [],
            'grade_groups' => $gradeGroups,
            'sub_summary' => $this->buildSubSummary($subRows),
            'summary' => $summary,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function healthCheck(string $noPenSt): array
    {
        $rows = $this->fetchMainRows($noPenSt);
        $subRows = $this->fetchSubRows($noPenSt);
        $detectedColumns = array_keys($rows[0] ?? []);
        $detectedSubColumns = array_keys($subRows[0] ?? []);
        $expectedColumns = $this->configuredColumns('expected_columns');
        $expectedSubColumns = $this->configuredColumns('expected_sub_columns');

        return [
            'is_healthy' => empty(array_diff($expectedColumns, $detectedColumns))
                && empty(array_diff($expectedSubColumns, $detectedSubColumns)),
            'expected_columns' => $expectedColumns,
            'detected_columns' => $detectedColumns,
            'missing_columns' => array_values(array_diff($expectedColumns, $detectedColumns)),
            'extra_columns' => array_values(array_diff($detectedColumns, $expectedColumns)),
            'expected_sub_columns' => $expectedSubColumns,
            'detected_sub_columns' => $detectedSubColumns,
            'missing_sub_columns' => array_values(array_diff($expectedSubColumns, $detectedSubColumns)),
            'extra_sub_columns' => array_values(array_diff($detectedSubColumns, $expectedSubColumns)),
            'row_count' => count($rows),
            'sub_row_count' => count($subRows),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetchMainRows(string $noPenSt): array
    {
        return $this->fetchMainRowsWithMeta($noPenSt)['rows'];
    }

    /**
     * @return array{rows: array<int, array<string, mixed>>, is_fallback: bool}
     */
    private function fetchMainRowsWithMeta(string $noPenSt): array
    {
        $rows = $this->runProcedureQuery('stored_procedure', $noPenSt);
        $isFallback = false;
        $quantityDivisor = self::QUANTITY_DIVISOR;

        if ($rows === []) {
            $rows = $this->runFallbackMainQuery($noPenSt);
            $isFallback = true;
            $quantityDivisor = 1;
        }

        return [
            'rows' => $this->normalizeMainRows($rows, $quantityDivisor),
            'is_fallback' => $isFallback,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetchSubRows(string $noPenSt): array
    {
        return $this->normalizeSubRows($this->runProcedureQuery('sub_stored_procedure', $noPenSt));
    }

    /**
     * @param  array<int, object>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function normalizeMainRows(array $rows, int $quantityDivisor = self::QUANTITY_DIVISOR): array
    {
        $normalized = array_map(function (object $row) use ($quantityDivisor): array {
            $item = (array) $row;
            $panjang = $this->toFloat($item['Panjang'] ?? null) ?? 0.0;
            $jmlhBatang = (int) round($this->toFloat($item['JmlhBatang'] ?? null) ?? 0);
            $hasil = $this->toFloat($item['Hasil'] ?? null) ?? 0.0;

            return [
                'NamaGrade' => trim((string) ($item['NamaGrade'] ?? '')),
                'Tebal' => $this->toFloat($item['Tebal'] ?? null) ?? 0.0,
                'Lebar' => $this->toFloat($item['Lebar'] ?? null) ?? 0.0,
                'IdTblLebar' => trim((string) ($item['IdTblLebar'] ?? '')),
                'Panjang' => $panjang,
                'DisplayPanjang' => $this->formatLengthLabel($panjang),
                'IdPanjang' => trim((string) ($item['IdPanjang'] ?? '')),
                'JmlhBatang' => $jmlhBatang,
                'DisplayJmlhBatang' => (int) round($jmlhBatang / $quantityDivisor),
                'IsLocal' => (int) round($this->toFloat($item['IsLocal'] ?? null) ?? 0),
                'Hasil' => $hasil,
                'DisplayHasil' => $hasil / $quantityDivisor,
                'NoKayuBulat' => trim((string) ($item['NoKayuBulat'] ?? '')),
                'NoPenerimaanST' => trim((string) ($item['NoPenerimaanST'] ?? '')),
                'TglLaporan' => trim((string) ($item['TglLaporan'] ?? '')),
                'NmSupplier' => trim((string) ($item['NmSupplier'] ?? '')),
                'NoTruk' => trim((string) ($item['NoTruk'] ?? '')),
                'NoPlat' => trim((string) ($item['NoPlat'] ?? '')),
                'Jenis' => trim((string) ($item['Jenis'] ?? '')),
                'Suket' => trim((string) ($item['Suket'] ?? '')),
                'TglMasuk' => trim((string) ($item['TglMasuk'] ?? '')),
            ];
        }, $rows);

        usort($normalized, function (array $a, array $b): int {
            return $this->gradeSortWeight((string) $a['NamaGrade']) <=> $this->gradeSortWeight((string) $b['NamaGrade'])
                ?: ((float) $a['Tebal'] <=> (float) $b['Tebal'])
                ?: ((float) $a['Lebar'] <=> (float) $b['Lebar'])
                ?: ((float) $a['Panjang'] <=> (float) $b['Panjang']);
        });

        return array_values($normalized);
    }

    /**
     * @param  array<int, object>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function normalizeSubRows(array $rows): array
    {
        return array_map(function (object $row): array {
            $item = (array) $row;

            return [
                'IdGradeKB' => (int) round($this->toFloat($item['IdGradeKB'] ?? null) ?? 0),
                'NamaGrade' => trim((string) ($item['NamaGrade'] ?? '')),
                'Berat' => $this->toFloat($item['Berat'] ?? null) ?? 0.0,
            ];
        }, $rows);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array{key: string, label: string, raw_panjang: float, display_panjang: string}>
     */
    private function buildLengthColumns(array $rows): array
    {
        $columns = [];

        foreach ($rows as $row) {
            $key = $this->lengthKey((float) $row['Panjang']);
            $columns[$key] = [
                'key' => $key,
                'label' => (string) $row['DisplayPanjang'],
                'raw_panjang' => (float) $row['Panjang'],
                'display_panjang' => (string) $row['DisplayPanjang'],
            ];
        }

        uasort($columns, static fn (array $a, array $b): int => $a['raw_panjang'] <=> $b['raw_panjang']);

        return array_values($columns);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function buildDetailRows(array $rows, ?string $gradeColumn): array
    {
        $details = [];

        foreach ($rows as $row) {
            $grade = $gradeColumn !== null
                ? (string) ($row[$gradeColumn] !== '' ? $row[$gradeColumn] : 'Tanpa Grade')
                : '';
            $tebal = (float) $row['Tebal'];
            $lebar = (float) $row['Lebar'];
            $uom = (string) ($row['IdTblLebar'] !== '' ? $row['IdTblLebar'] : '-');
            $lengthKey = $this->lengthKey((float) $row['Panjang']);
            $rowKey = implode('|', [$grade, $tebal, $lebar, $uom]);

            if (! isset($details[$rowKey])) {
                $details[$rowKey] = [
                    'grade' => $grade,
                    'tebal' => $tebal,
                    'lebar' => $lebar,
                    'uom' => $uom,
                    'cells' => [],
                    'total_pcs' => 0,
                    'total_ton' => 0.0,
                ];
            }

            $quantity = (int) ($row['DisplayJmlhBatang'] ?? 0);
            $ton = (float) ($row['DisplayHasil'] ?? 0.0);
            $details[$rowKey]['cells'][$lengthKey] = (int) ($details[$rowKey]['cells'][$lengthKey] ?? 0) + $quantity;
            $details[$rowKey]['total_pcs'] += $quantity;
            $details[$rowKey]['total_ton'] += $ton;
        }

        $result = array_values($details);
        usort($result, function (array $a, array $b): int {
            return $this->gradeSortWeight((string) $a['grade']) <=> $this->gradeSortWeight((string) $b['grade'])
                ?: ((float) $a['tebal'] <=> (float) $b['tebal'])
                ?: ((float) $a['lebar'] <=> (float) $b['lebar']);
        });

        return $result;
    }

    /**
     * @param  array<int, array<string, mixed>>  $detailRows
     * @return array<int, array<string, mixed>>
     */
    private function buildFlatTebalGroups(array $detailRows): array
    {
        $groups = [];

        foreach ($detailRows as $row) {
            $tebalKey = (string) (float) $row['tebal'];

            if (! isset($groups[$tebalKey])) {
                $groups[$tebalKey] = [
                    'tebal' => (float) $row['tebal'],
                    'rows' => [],
                ];
            }

            $groups[$tebalKey]['rows'][] = $row;
        }

        return array_values($groups);
    }

    /**
     * @param  array<int, array<string, mixed>>  $detailRows
     * @param  array<int, array{key: string, label: string, raw_panjang: float, display_panjang: string}>  $lengthColumns
     * @return array<int, array<string, mixed>>
     */
    private function buildGradeGroups(array $detailRows, array $lengthColumns): array
    {
        $groups = [];

        foreach ($detailRows as $row) {
            $grade = (string) $row['grade'];
            $tebalKey = (string) (float) $row['tebal'];

            if (! isset($groups[$grade])) {
                $groups[$grade] = [
                    'grade' => $grade,
                    'tebal_groups' => [],
                    'totals' => $this->emptyTotals($lengthColumns),
                    'total_pcs' => 0,
                    'total_ton' => 0.0,
                ];
            }

            if (! isset($groups[$grade]['tebal_groups'][$tebalKey])) {
                $groups[$grade]['tebal_groups'][$tebalKey] = [
                    'tebal' => (float) $row['tebal'],
                    'rows' => [],
                ];
            }

            $groups[$grade]['tebal_groups'][$tebalKey]['rows'][] = $row;

            foreach ($lengthColumns as $column) {
                $key = $column['key'];
                $groups[$grade]['totals'][$key] += (int) ($row['cells'][$key] ?? 0);
            }

            $groups[$grade]['total_pcs'] += (int) ($row['total_pcs'] ?? 0);
            $groups[$grade]['total_ton'] += (float) ($row['total_ton'] ?? 0.0);
        }

        $result = array_values($groups);
        foreach ($result as &$group) {
            $group['tebal_groups'] = array_values($group['tebal_groups']);
        }
        unset($group);

        return $result;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, string>
     */
    private function buildHeader(array $row): array
    {
        $kayuBulatHeader = $this->lookupKayuBulatHeader((string) ($row['NoKayuBulat'] ?? ''));
        $noTruk = trim((string) ($row['NoTruk'] ?? ''));
        $noPlat = trim((string) ($row['NoPlat'] ?? ''));

        return [
            'no_penerimaan_st' => (string) ($row['NoPenerimaanST'] ?? '-'),
            'supplier' => (string) ($row['NmSupplier'] ?? '-'),
            'jenis_kayu' => (string) ($row['Jenis'] ?? '-'),
            'no_kayu_bulat' => (string) ($row['NoKayuBulat'] ?? '-'),
            'no_truk' => $noTruk !== '' ? $noTruk : (string) ($kayuBulatHeader['NoTruk'] ?? '-'),
            'no_suket' => (string) ($row['Suket'] ?? '-'),
            'no_plat' => $noPlat !== '' ? $noPlat : (string) ($kayuBulatHeader['NoPlat'] ?? '-'),
            'tgl_laporan' => (string) ($row['TglLaporan'] ?? ''),
            'tgl_masuk' => (string) ($row['TglMasuk'] ?? ''),
        ];
    }

    /**
     * @return array{NoTruk?: string, NoPlat?: string}
     */
    private function lookupKayuBulatHeader(string $noKayuBulat): array
    {
        if (trim($noKayuBulat) === '') {
            return [];
        }

        $connectionName = config('reports.penerimaan_st_hasil_sawmill.database_connection');
        $connection = DB::connection($connectionName ?: null);

        if ($connection->getDriverName() !== 'sqlsrv') {
            return [];
        }

        $rows = $connection->select(
            'SELECT TOP 1 NoTruk, NoPlat FROM KayuBulat_h WHERE NoKayuBulat = ?',
            [$noKayuBulat],
        );

        if ($rows === []) {
            return [];
        }

        $row = (array) $rows[0];

        return [
            'NoTruk' => trim((string) ($row['NoTruk'] ?? '')),
            'NoPlat' => trim((string) ($row['NoPlat'] ?? '')),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $subRows
     * @return array<string, mixed>
     */
    private function buildSubSummary(array $subRows): array
    {
        return [
            'rows' => $subRows,
            'total_berat' => array_sum(array_map(static fn (array $row): float => (float) ($row['Berat'] ?? 0), $subRows)),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $gradeGroups
     * @param  array<int, array{key: string, label: string, raw_panjang: float, display_panjang: string}>  $lengthColumns
     * @return array<string, mixed>
     */
    private function buildSummary(array $gradeGroups, array $lengthColumns): array
    {
        $totals = $this->emptyTotals($lengthColumns);

        foreach ($gradeGroups as $group) {
            foreach ($lengthColumns as $column) {
                $key = $column['key'];
                $totals[$key] += (int) ($group['totals'][$key] ?? 0);
            }
        }

        return [
            'total_rows' => array_sum(array_map(static fn (array $group): int => array_sum(array_map(
                static fn (array $tebalGroup): int => count($tebalGroup['rows'] ?? []),
                $group['tebal_groups'] ?? [],
            )), $gradeGroups)),
            'total_pcs' => array_sum($totals),
            'total_ton' => array_sum(array_map(static fn (array $group): float => (float) ($group['total_ton'] ?? 0.0), $gradeGroups)),
            'totals' => $totals,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<string, float>
     */
    private function buildFlatSummary(array $rows): array
    {
        $noKayuBulat = trim((string) ($rows[0]['NoKayuBulat'] ?? ''));
        $exportTon = 0.0;
        $localTon = 0.0;

        foreach ($rows as $row) {
            if ((int) ($row['IsLocal'] ?? 0) === 1) {
                $localTon += (float) ($row['DisplayHasil'] ?? 0.0);
            } else {
                $exportTon += (float) ($row['DisplayHasil'] ?? 0.0);
            }
        }

        return [
            'kb_ton' => $this->calculateKayuBulatTon($noKayuBulat),
            'export_ton' => $exportTon,
            'local_ton' => $localTon,
            'total_ton' => $exportTon + $localTon,
        ];
    }

    private function calculateKayuBulatTon(string $noKayuBulat): float
    {
        if ($noKayuBulat === '') {
            return 0.0;
        }

        $connectionName = config('reports.penerimaan_st_hasil_sawmill.database_connection');
        $connection = DB::connection($connectionName ?: null);

        if ($connection->getDriverName() !== 'sqlsrv') {
            return 0.0;
        }

        $rows = $connection->select(
            'SELECT SUM(FLOOR((Tebal * Lebar * Panjang / 7200.8) * 10000) / 10000.0) AS TonKB FROM KayuBulat_d WHERE NoKayuBulat = ?',
            [$noKayuBulat],
        );

        return (float) ($rows[0]->TonKB ?? 0.0);
    }

    /**
     * @param  array<int, array{key: string}>  $lengthColumns
     * @return array<string, int>
     */
    private function emptyTotals(array $lengthColumns): array
    {
        $totals = [];
        foreach ($lengthColumns as $column) {
            $totals[$column['key']] = 0;
        }

        return $totals;
    }

    /**
     * @return array<int, object>
     */
    private function runProcedureQuery(string $procedureConfigKey, string $noPenSt): array
    {
        $configKey = 'reports.penerimaan_st_hasil_sawmill';
        $connectionName = config("{$configKey}.database_connection");
        $procedure = (string) config("{$configKey}.{$procedureConfigKey}");
        $syntax = (string) config("{$configKey}.call_syntax", 'exec');
        $customQuery = config("{$configKey}.query");

        if ($procedure === '' && ! is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan penerimaan ST hasil sawmill belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan penerimaan ST hasil sawmill dikonfigurasi untuk SQL Server. '
                .'Set PENERIMAAN_ST_HASIL_SAWMILL_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException('Query manual laporan penerimaan ST hasil sawmill belum diisi.');

            return $connection->select($query, str_contains($query, '?') ? [$noPenSt] : []);
        }

        if (! preg_match('/^[A-Za-z0-9_$.]+$/', $procedure)) {
            throw new RuntimeException('Nama stored procedure tidak valid.');
        }

        $sql = match ($syntax) {
            'exec' => "SET NOCOUNT ON; EXEC {$procedure} ?",
            'call' => "CALL {$procedure}(?)",
            default => $driver === 'sqlsrv' ? "SET NOCOUNT ON; EXEC {$procedure} ?" : "CALL {$procedure}(?)",
        };

        try {
            return $connection->select($sql, [$noPenSt]);
        } catch (\Throwable $exception) {
            throw new RuntimeException('Gagal mengambil data laporan penerimaan ST hasil sawmill: '.$exception->getMessage(), 0, $exception);
        }
    }

    /**
     * @return array<int, object>
     */
    private function runFallbackMainQuery(string $noPenSt): array
    {
        $connectionName = config('reports.penerimaan_st_hasil_sawmill.database_connection');
        $connection = DB::connection($connectionName ?: null);

        if ($connection->getDriverName() !== 'sqlsrv') {
            return [];
        }

        $sql = <<<'SQL'
SET NOCOUNT ON;
SELECT
    COALESCE(F.NamaGrade, P.NamaProduk, 'Tanpa Grade') AS NamaGrade,
    D.Tebal,
    D.Lebar,
    CASE
        WHEN D.IdUOMTblLebar = 1 THEN 'mm'
        WHEN D.IdUOMTblLebar = 3 THEN 'inch'
    END AS IdTblLebar,
    D.Panjang,
    CASE
        WHEN D.IdUOMPanjang = 4 THEN 'feet'
    END AS IdPanjang,
    SUM(D.JmlhBatang) AS JmlhBatang,
    D.IsLocal,
    CASE
        WHEN D.IdUOMTblLebar = 3 THEN
            SUM(FLOOR((D.Tebal * D.Lebar * D.Panjang * D.JmlhBatang / 7200.8) * 10000) / 10000.0)
        WHEN D.IdUOMTblLebar = 1 THEN
            SUM(FLOOR(((D.Tebal * D.Lebar * D.Panjang) * 304.8 * D.JmlhBatang / 1000000000.0 / 1.416) * 10000) / 10000.0)
    END AS Hasil,
    A.NoKayuBulat,
    A.NoPenerimaanST,
    A.TglLaporan,
    H.NmSupplier,
    G.NoTruk,
    G.NoPlat,
    I.Jenis,
    G.Suket,
    G.DateCreate AS TglMasuk
FROM PenerimaanSTSawmill_h A
INNER JOIN PenerimaanSTSawmill_d B ON B.NoPenerimaanST = A.NoPenerimaanST
INNER JOIN STSawmill_h C ON C.NoSTSawmill = B.NoSTSawmill
INNER JOIN STSawmill_d D ON D.NoSTSawmill = C.NoSTSawmill
LEFT JOIN STSawmillKG_d E ON E.NoSTSawmill = D.NoSTSawmill AND E.NoUrut = D.NoUrut
LEFT JOIN MstGradeKB F ON F.IdGradeKB = E.IdGradeKB
LEFT JOIN MstProdukSPK P ON P.IdProdukSPK = D.IdProdukSPK
LEFT JOIN KayuBulat_h G ON G.NoKayuBulat = A.NoKayuBulat
LEFT JOIN MstSupplier H ON H.IdSupplier = G.IdSupplier
LEFT JOIN MstJenisKayu I ON I.IdJenisKayu = G.IdJenisKayu
WHERE A.NoPenerimaanST = ?
GROUP BY
    COALESCE(F.NamaGrade, P.NamaProduk, 'Tanpa Grade'),
    D.Tebal,
    D.Lebar,
    D.IdUOMTblLebar,
    D.Panjang,
    D.IdUOMPanjang,
    D.JmlhBatang,
    D.IsLocal,
    A.NoKayuBulat,
    A.NoPenerimaanST,
    A.TglLaporan,
    H.NmSupplier,
    G.NoTruk,
    G.NoPlat,
    I.Jenis,
    G.Suket,
    G.DateCreate
ORDER BY COALESCE(F.NamaGrade, P.NamaProduk, 'Tanpa Grade') DESC, D.Tebal ASC
SQL;

        try {
            return $connection->select($sql, [$noPenSt]);
        } catch (\Throwable $exception) {
            throw new RuntimeException('Gagal mengambil data fallback laporan penerimaan ST hasil sawmill: '.$exception->getMessage(), 0, $exception);
        }
    }

    /**
     * @return array<int, string>
     */
    private function configuredColumns(string $key): array
    {
        $columns = config("reports.penerimaan_st_hasil_sawmill.{$key}", []);

        return is_array($columns) ? array_values($columns) : [];
    }

    private function lengthKey(float $value): string
    {
        return rtrim(rtrim(number_format($value, 4, '.', ''), '0'), '.');
    }

    private function formatLengthLabel(float $value): string
    {
        return $this->lengthKey($value);
    }

    private function gradeSortWeight(string $gradeName): int
    {
        return match (strtoupper(trim($gradeName))) {
            'STD' => 10,
            'MC 2', 'MC2' => 20,
            'MC 1', 'MC1' => 30,
            'KAYU LAT' => 40,
            default => 90,
        };
    }

    private function toFloat(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        if (! is_string($value)) {
            return null;
        }

        $normalized = trim(str_replace(' ', '', $value));
        if ($normalized === '') {
            return null;
        }

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

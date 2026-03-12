<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RekapProduktivitasSawmillReportService
{
    private const EPS = 0.0000001;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetch(string $startDate, string $endDate): array
    {
        return $this->normalizeRows($this->runProcedureQuery($startDate, $endDate));
    }

    /**
     * Output shape:
     * - rows: list per tanggal, with fixed keys for rendering table
     * - summary: {total_rows, total_dates}
     *
     * @return array<string, mixed>
     */
    public function buildReportData(string $startDate, string $endDate): array
    {
        $rows = $this->fetch($startDate, $endDate);

        if ($rows === []) {
            return [
                'rows' => [],
                'summary' => [
                    'total_rows' => 0,
                    'total_dates' => 0,
                ],
            ];
        }

        $columns = array_keys($rows[0] ?? []);

        $dateColumn = $this->resolveDateColumn($columns) ?? $this->guessDateColumn($columns);
        $jumlahMejaColumn = $this->resolveJumlahMejaColumn($columns);
        $totalColumn = $this->resolveTotalColumn($columns);

        // Prefer explicit mapping; if not found, try infer from remaining pivot columns.
        $categoryColumns = $this->resolveCategoryColumns($columns);
        if ($categoryColumns === [] && $dateColumn !== null) {
            $categoryColumns = $this->inferCategoryColumnsFromPivot($rows, $columns, $dateColumn, $jumlahMejaColumn, $totalColumn);
        }

        // If SP already returns pivot columns, use them as-is.
        $hasPivotColumns =
            $dateColumn !== null
            && ($jumlahMejaColumn !== null || $this->hasAnyColumn($columns, ['NoMeja']))
            && (count($categoryColumns) > 0);

        $out = $hasPivotColumns
            ? $this->buildFromPivotRows($rows, $dateColumn, $jumlahMejaColumn, $categoryColumns, $totalColumn)
            : $this->buildFromLongRows($rows, $dateColumn, $jumlahMejaColumn);

        usort($out, static function (array $a, array $b): int {
            return strcmp((string) ($a['Tanggal'] ?? ''), (string) ($b['Tanggal'] ?? ''));
        });

        return [
            'rows' => $out,
            'summary' => [
                'total_rows' => count($out),
                'total_dates' => count(array_unique(array_map(static fn($r): string => (string) ($r['Tanggal'] ?? ''), $out))),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function healthCheck(string $startDate, string $endDate): array
    {
        $rows = $this->fetch($startDate, $endDate);
        $detectedColumns = array_keys($rows[0] ?? []);
        $expectedColumns = config('reports.rekap_produktivitas_sawmill.expected_columns', []);
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

    /**
     * @param array<int, object> $rows
     * @return array<int, array<string, mixed>>
     */
    private function normalizeRows(array $rows): array
    {
        $normalized = array_values(array_map(function (object $row): array {
            $item = (array) $row;

            foreach ($item as $k => $v) {
                if (is_string($v)) {
                    $item[$k] = trim($v);
                }
            }

            return $item;
        }, $rows));

        // Best-effort sort by date asc if we can detect a date column.
        $columns = array_keys($normalized[0] ?? []);
        $dateColumn = $this->resolveDateColumn($columns);
        if ($dateColumn !== null) {
            usort($normalized, function (array $a, array $b) use ($dateColumn): int {
                return strcmp(
                    $this->normalizeDateKey($a[$dateColumn] ?? null),
                    $this->normalizeDateKey($b[$dateColumn] ?? null),
                );
            });
        }

        return $normalized;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @param array<string, string> $categoryColumns mapping output label => source column name
     * @return array<int, array<string, mixed>>
     */
    private function buildFromPivotRows(
        array $rows,
        string $dateColumn,
        ?string $jumlahMejaColumn,
        array $categoryColumns,
        ?string $totalColumn,
    ): array {
        $byDate = [];
        $mejaSetByDate = [];

        foreach ($rows as $row) {
            $dateKey = $this->normalizeDateKey($row[$dateColumn] ?? null);
            if ($dateKey === '') {
                continue;
            }

            if (!isset($byDate[$dateKey])) {
                $byDate[$dateKey] = [
                    'Tanggal' => $dateKey,
                    'JumlahMeja' => 0,
                    'JABON' => 0.0,
                    'RAMBUNG KAYU L' => 0.0,
                    'RAMBUNG MC 1' => 0.0,
                    'RAMBUNG MC 2' => 0.0,
                    'RAMBUNG STD' => 0.0,
                    'Total' => 0.0,
                ];
                $mejaSetByDate[$dateKey] = [];
            }

            if ($jumlahMejaColumn !== null) {
                $byDate[$dateKey]['JumlahMeja'] = max(
                    (int) ($byDate[$dateKey]['JumlahMeja'] ?? 0),
                    (int) ($row[$jumlahMejaColumn] ?? 0),
                );
            } else {
                // Optional fallback: count distinct NoMeja if present.
                if (array_key_exists('NoMeja', $row)) {
                    $m = trim((string) ($row['NoMeja'] ?? ''));
                    if ($m !== '') {
                        $mejaSetByDate[$dateKey][$m] = true;
                        $byDate[$dateKey]['JumlahMeja'] = count($mejaSetByDate[$dateKey]);
                    }
                }
            }

            foreach ($categoryColumns as $label => $sourceCol) {
                $val = $this->toFloat($row[$sourceCol] ?? null) ?? 0.0;
                $byDate[$dateKey][$label] = (float) ($byDate[$dateKey][$label] ?? 0.0) + $val;
            }

            // Prefer computed total from the displayed columns to keep consistency with the PDF.
            $computedTotal = 0.0;
            foreach (['JABON', 'RAMBUNG KAYU L', 'RAMBUNG MC 1', 'RAMBUNG MC 2', 'RAMBUNG STD'] as $k) {
                $computedTotal += (float) ($byDate[$dateKey][$k] ?? 0.0);
            }

            $byDate[$dateKey]['Total'] = $computedTotal;

            // If SP provides a total column and we didn't map all categories, keep it as fallback when ours is zero.
            if ($totalColumn !== null && abs($computedTotal) < self::EPS) {
                $byDate[$dateKey]['Total'] = $this->toFloat($row[$totalColumn] ?? null) ?? $computedTotal;
            }
        }

        return array_values($byDate);
    }

    /**
     * Fallback when SP returns "long" rows (Tanggal + Jenis + Nilai).
     *
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    private function buildFromLongRows(array $rows, ?string $dateColumn, ?string $jumlahMejaColumn): array
    {
        $dateColumn = $dateColumn ?? $this->firstExistingColumn(array_keys($rows[0] ?? []), ['TglSawmill', 'Tanggal', 'Tgl', 'TglLaporan']);
        if ($dateColumn === null) {
            throw new RuntimeException('Kolom tanggal tidak ditemukan pada output SPWps_LapRekapProduktivitasSawmill.');
        }

        $jenisColumn = $this->firstExistingColumn(array_keys($rows[0] ?? []), [
            'Jenis',
            'JenisKayu',
            'NamaGrade',
            'Grade',
            'Produk',
            'Group',
            'GroupKayu',
        ]);
        $valueColumn = $this->firstExistingColumn(array_keys($rows[0] ?? []), [
            'TonST',
            'STTon',
            'STton',
            'Ton',
            'TonRacip',
            'Nilai',
            'Value',
            'Jumlah',
            'Qty',
        ]);

        if ($jenisColumn === null || $valueColumn === null) {
            $detectedColumns = array_keys($rows[0] ?? []);
            throw new RuntimeException(
                'Kolom pivot tidak ditemukan. Pastikan SPWps_LapRekapProduktivitasSawmill mengembalikan kolom tanggal + jenis + nilai. '
                . 'Kolom terdeteksi: ' . implode(', ', $detectedColumns),
            );
        }

        $byDate = [];
        $mejaSetByDate = [];

        foreach ($rows as $row) {
            $dateKey = $this->normalizeDateKey($row[$dateColumn] ?? null);
            if ($dateKey === '') {
                continue;
            }

            if (!isset($byDate[$dateKey])) {
                $byDate[$dateKey] = [
                    'Tanggal' => $dateKey,
                    'JumlahMeja' => 0,
                    'JABON' => 0.0,
                    'RAMBUNG KAYU L' => 0.0,
                    'RAMBUNG MC 1' => 0.0,
                    'RAMBUNG MC 2' => 0.0,
                    'RAMBUNG STD' => 0.0,
                    'Total' => 0.0,
                ];
                $mejaSetByDate[$dateKey] = [];
            }

            if ($jumlahMejaColumn !== null) {
                $byDate[$dateKey]['JumlahMeja'] = (int) ($row[$jumlahMejaColumn] ?? 0);
            } elseif (array_key_exists('NoMeja', $row)) {
                $m = trim((string) ($row['NoMeja'] ?? ''));
                if ($m !== '') {
                    $mejaSetByDate[$dateKey][$m] = true;
                    $byDate[$dateKey]['JumlahMeja'] = count($mejaSetByDate[$dateKey]);
                }
            }

            $jenisRaw = trim((string) ($row[$jenisColumn] ?? ''));
            $mapped = $this->mapJenisToColumnLabel($jenisRaw);
            if ($mapped === null) {
                continue;
            }

            $val = $this->toFloat($row[$valueColumn] ?? null) ?? 0.0;
            $byDate[$dateKey][$mapped] = (float) ($byDate[$dateKey][$mapped] ?? 0.0) + $val;
        }

        foreach ($byDate as $dateKey => $item) {
            $sum = 0.0;
            foreach (['JABON', 'RAMBUNG KAYU L', 'RAMBUNG MC 1', 'RAMBUNG MC 2', 'RAMBUNG STD'] as $k) {
                $sum += (float) ($item[$k] ?? 0.0);
            }
            $byDate[$dateKey]['Total'] = $sum;
        }

        return array_values($byDate);
    }

    /**
     * @param array<int, string> $columns
     */
    private function resolveDateColumn(array $columns): ?string
    {
        return $this->firstExistingColumn($columns, ['Tanggal', 'TglSawmill', 'Tgl', 'TglLaporan', 'TglProduksi']);
    }

    /**
     * @param array<int, string> $columns
     */
    private function resolveJumlahMejaColumn(array $columns): ?string
    {
        return $this->firstExistingColumn($columns, ['JumlahMeja', 'JlhMeja', 'JmlhMeja', 'JMLHMEJA', 'Jumlah_Meja']);
    }

    /**
     * @param array<int, string> $columns
     */
    private function resolveTotalColumn(array $columns): ?string
    {
        return $this->firstExistingColumn($columns, ['Total', 'TOTAL', 'GrandTotal', 'JumlahTotal']);
    }

    /**
     * @param array<int, string> $columns
     * @return array<string, string> mapping output label => source column name
     */
    private function resolveCategoryColumns(array $columns): array
    {
        $normToCol = [];
        foreach ($columns as $col) {
            $normToCol[$this->normKey($col)] = $col;
        }

        $pick = function (array $variants) use ($normToCol): ?string {
            foreach ($variants as $v) {
                $k = $this->normKey($v);
                if (isset($normToCol[$k])) {
                    return $normToCol[$k];
                }
            }
            return null;
        };

        $out = [];
        $jabon = $pick(['JABON', 'Jabon']);
        if ($jabon !== null) {
            $out['JABON'] = $jabon;
        }

        $kayuL = $pick(['RAMBUNG KAYU L', 'RAMBUNG KAYU LAT', 'RambungKayuL', 'RambungKayuLat']);
        if ($kayuL !== null) {
            $out['RAMBUNG KAYU L'] = $kayuL;
        }

        $mc1 = $pick(['RAMBUNG MC 1', 'RambungMC1', 'RAMBUNG_MC_1']);
        if ($mc1 !== null) {
            $out['RAMBUNG MC 1'] = $mc1;
        }

        $mc2 = $pick(['RAMBUNG MC 2', 'RambungMC2', 'RAMBUNG_MC_2']);
        if ($mc2 !== null) {
            $out['RAMBUNG MC 2'] = $mc2;
        }

        $std = $pick(['RAMBUNG STD', 'RambungSTD', 'RAMBUNG_STD']);
        if ($std !== null) {
            $out['RAMBUNG STD'] = $std;
        }

        return $out;
    }

    private function mapJenisToColumnLabel(string $jenis): ?string
    {
        $norm = $this->normKey($jenis);

        if ($norm === 'jabon' || str_contains($norm, 'jabon')) {
            return 'JABON';
        }
        if (str_contains($norm, 'rambung') && (str_contains($norm, 'kayulat') || str_contains($norm, 'kayul'))) {
            return 'RAMBUNG KAYU L';
        }
        if (str_contains($norm, 'rambung') && str_contains($norm, 'mc1')) {
            return 'RAMBUNG MC 1';
        }
        if (str_contains($norm, 'rambung') && str_contains($norm, 'mc2')) {
            return 'RAMBUNG MC 2';
        }
        if (str_contains($norm, 'rambung') && str_contains($norm, 'std')) {
            return 'RAMBUNG STD';
        }

        return null;
    }

    /**
     * @param array<int, string> $columns
     * @param array<int, string> $needles
     */
    private function firstExistingColumn(array $columns, array $needles): ?string
    {
        $normToCol = [];
        foreach ($columns as $col) {
            $normToCol[$this->normKey($col)] = $col;
        }

        foreach ($needles as $n) {
            $k = $this->normKey($n);
            if (isset($normToCol[$k])) {
                return $normToCol[$k];
            }
        }

        return null;
    }

    /**
     * @param array<int, string> $columns
     * @param array<int, string> $needles
     */
    private function hasAnyColumn(array $columns, array $needles): bool
    {
        return $this->firstExistingColumn($columns, $needles) !== null;
    }

    private function normKey(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/i', '', $value) ?? $value;

        return $value;
    }

    private function normalizeDateKey(mixed $raw): string
    {
        $value = trim((string) ($raw ?? ''));
        if ($value === '') {
            return '';
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $exception) {
            return $value;
        }
    }

    /**
     * @return array<int, object>
     */
    private function runProcedureQuery(string $startDate, string $endDate): array
    {
        $configKey = 'reports.rekap_produktivitas_sawmill';
        $connectionName = config("{$configKey}.database_connection");
        $procedure = (string) config("{$configKey}.stored_procedure", '');
        $syntax = (string) config("{$configKey}.call_syntax", 'exec');
        $customQuery = config("{$configKey}.query");
        $parameterCount = (int) config("{$configKey}.parameter_count", 2);

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan rekap produktivitas sawmill belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();
        $bindings = array_slice([$startDate, $endDate], 0, max(0, min($parameterCount, 2)));

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan rekap produktivitas sawmill dikonfigurasi untuk SQL Server. '
                . 'Set REKAP_PRODUKTIVITAS_SAWMILL_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException('Query manual laporan rekap produktivitas sawmill belum diisi.');

            return $connection->select($query, str_contains($query, '?') ? $bindings : []);
        }

        if (!preg_match('/^[A-Za-z0-9_$.]+$/', $procedure)) {
            throw new RuntimeException('Nama stored procedure tidak valid.');
        }

        $sql = match ($syntax) {
            'exec' => "EXEC {$procedure} ?, ?",
            'call' => "CALL {$procedure}(?, ?)",
            default => $driver === 'sqlsrv' ? "EXEC {$procedure} ?, ?" : "CALL {$procedure}(?, ?)",
        };

        return $connection->select($sql, $bindings);
    }

    private function toFloat(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }
        if (is_string($value)) {
            $trimmed = trim($value);
            if ($trimmed === '') {
                return null;
            }
            $trimmed = str_replace(',', '', $trimmed);
            if (!is_numeric($trimmed)) {
                return null;
            }
            return (float) $trimmed;
        }

        return null;
    }

    /**
     * Fallback date column detection: any column containing "tgl" or "tanggal".
     *
     * @param array<int, string> $columns
     */
    private function guessDateColumn(array $columns): ?string
    {
        foreach ($columns as $col) {
            $k = $this->normKey($col);
            if (str_contains($k, 'tanggal') || str_contains($k, 'tgl')) {
                return $col;
            }
        }

        return null;
    }

    /**
     * Infer pivot category columns when the SP already returns pivoted numeric columns, but naming differs.
     *
     * Strategy:
     * - Exclude known columns (date, jumlah meja, total)
     * - Take remaining columns that look numeric in the first row
     * - Map their column names to one of the required output labels
     *
     * @param array<int, array<string, mixed>> $rows
     * @param array<int, string> $columns
     * @return array<string, string> mapping output label => source column name
     */
    private function inferCategoryColumnsFromPivot(
        array $rows,
        array $columns,
        string $dateColumn,
        ?string $jumlahMejaColumn,
        ?string $totalColumn,
    ): array {
        $first = $rows[0] ?? [];
        $exclude = [
            $this->normKey($dateColumn) => true,
        ];
        if ($jumlahMejaColumn !== null) {
            $exclude[$this->normKey($jumlahMejaColumn)] = true;
        }
        if ($totalColumn !== null) {
            $exclude[$this->normKey($totalColumn)] = true;
        }

        $mapping = [];
        foreach ($columns as $col) {
            $nk = $this->normKey($col);
            if (isset($exclude[$nk])) {
                continue;
            }

            // Skip typical non-numeric columns.
            if (str_contains($nk, 'nama') || str_contains($nk, 'operator') || str_contains($nk, 'shift')) {
                continue;
            }

            $rawVal = $first[$col] ?? null;
            $num = $this->toFloat($rawVal);
            if ($num === null) {
                continue;
            }

            $label = $this->mapJenisToColumnLabel($col);
            if ($label === null) {
                continue;
            }

            // Keep first match per label to avoid ambiguity.
            if (!isset($mapping[$label])) {
                $mapping[$label] = $col;
            }
        }

        return $mapping;
    }
}

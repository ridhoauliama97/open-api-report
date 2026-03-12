<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RekapPenerimaanSTDariSawmillNonRambungReportService
{
    private const OUTPUT_SCHEMA = [
        ['key' => 'No ST', 'label' => 'No ST', 'type' => 'text'],
        ['key' => 'NoTruk', 'label' => 'NoTruk', 'type' => 'text'],
        ['key' => 'Meja', 'label' => 'Meja', 'type' => 'text'],
        ['key' => 'Tanggal', 'label' => 'Tanggal', 'type' => 'date'],
        ['key' => 'No.KB', 'label' => 'No.KB', 'type' => 'text'],
        ['key' => 'Jenis Kayu Bulat', 'label' => 'Jenis Kayu Bulat', 'type' => 'text'],
        ['key' => 'Ton (KB)', 'label' => 'Ton (KB)', 'type' => 'number', 'decimals' => 4],
        ['key' => 'Ton (ST)', 'label' => 'Ton (ST)', 'type' => 'number', 'decimals' => 4],
        ['key' => 'Ave Dia', 'label' => 'Ave Dia', 'type' => 'number', 'decimals' => 1],
        ['key' => 'Ave Tbl', 'label' => 'Ave Tbl', 'type' => 'number', 'decimals' => 1],
        ['key' => 'Potong', 'label' => 'Potong', 'type' => 'text'],
        ['key' => 'Rend ST-KB', 'label' => 'Rend ST-KB', 'type' => 'percent', 'decimals' => 2],
    ];

    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetch(string $startDate, string $endDate): array
    {
        $rows = $this->runProcedureQuery($startDate, $endDate);

        return array_values(array_map(static fn(object $row): array => (array) $row, $rows));
    }

    /**
     * @return array<string, mixed>
     */
    public function buildReportData(string $startDate, string $endDate): array
    {
        $rawRows = $this->fetch($startDate, $endDate);
        $expectedColumns = config('reports.rekap_penerimaan_st_dari_sawmill_non_rambung.expected_columns', []);
        $expectedColumns = is_array($expectedColumns) ? array_values(array_filter(array_map('strval', $expectedColumns))) : [];

        $columns = $this->extractColumns($rawRows, $expectedColumns);

        $supplierSource = $this->resolveSupplierColumn($columns);
        $dateSource = $this->resolveDateColumn($columns);
        $noStSource = $this->resolveNoStColumn($columns);
        $noTrukSource = $this->resolveNoTrukColumn($columns);
        $mejaSource = $this->resolveMejaColumn($columns);
        $noKbSource = $this->resolveNoKbColumn($columns);
        $jenisKbSource = $this->resolveJenisKayuBulatColumn($columns);
        $tonKbSource = $this->resolveTonKbColumn($columns);
        $tonStSource = $this->resolveTonStColumn($columns);
        $aveDiaSource = $this->resolveAveDiaColumn($columns);
        $aveTblSource = $this->resolveAveTblColumn($columns);
        $potongSource = $this->resolvePotongColumn($columns);
        $rendSource = $this->resolveRendStKbColumn($columns);

        $schema = self::OUTPUT_SCHEMA;
        $schemaKeys = array_values(array_map(static fn(array $spec): string => (string) ($spec['key'] ?? ''), $schema));

        /** @var array<string, array<int, array{sort_date: string, row: array<string, mixed}}>> $rowsBySupplier */
        $rowsBySupplier = [];
        $mappedRows = [];

        foreach ($rawRows as $rawRow) {
            $supplierName = $this->normalizeSupplierName($supplierSource !== null ? ($rawRow[$supplierSource] ?? null) : null);
            $sortDate = $dateSource !== null ? $this->normalizeDateKey($rawRow[$dateSource] ?? null) : '';

            $kb = $tonKbSource !== null ? $this->toFloat($rawRow[$tonKbSource] ?? null) : null;
            $st = $tonStSource !== null ? $this->toFloat($rawRow[$tonStSource] ?? null) : null;
            $rend = $rendSource !== null ? ($rawRow[$rendSource] ?? null) : null;
            if ($rend === null && $kb !== null && $kb > 0.0000001 && $st !== null) {
                $rend = $st / $kb; // ratio 0.xx, view formats as percent
            }

            $mapped = [
                'No ST' => $noStSource !== null ? ($rawRow[$noStSource] ?? null) : null,
                'NoTruk' => $noTrukSource !== null ? $this->formatNoTruk($rawRow[$noTrukSource] ?? null) : null,
                'Meja' => $mejaSource !== null ? ($rawRow[$mejaSource] ?? null) : null,
                'Tanggal' => $dateSource !== null ? ($rawRow[$dateSource] ?? null) : null,
                'No.KB' => $noKbSource !== null ? ($rawRow[$noKbSource] ?? null) : null,
                'Jenis Kayu Bulat' => $jenisKbSource !== null ? ($rawRow[$jenisKbSource] ?? null) : null,
                'Ton (KB)' => $tonKbSource !== null ? ($rawRow[$tonKbSource] ?? null) : null,
                'Ton (ST)' => $tonStSource !== null ? ($rawRow[$tonStSource] ?? null) : null,
                'Ave Dia' => $aveDiaSource !== null ? ($rawRow[$aveDiaSource] ?? null) : null,
                'Ave Tbl' => $aveTblSource !== null ? ($rawRow[$aveTblSource] ?? null) : null,
                'Potong' => $potongSource !== null ? ($rawRow[$potongSource] ?? null) : null,
                'Rend ST-KB' => $rend,
            ];

            $ordered = [];
            foreach ($schemaKeys as $key) {
                $ordered[$key] = $mapped[$key] ?? null;
            }

            $rowsBySupplier[$supplierName][] = [
                'sort_date' => $sortDate,
                'row' => $ordered,
            ];
            $mappedRows[] = $ordered;
        }

        ksort($rowsBySupplier, SORT_NATURAL | SORT_FLAG_CASE);

        $supplierGroups = [];
        $supplierSummaries = [];

        $grandKb = 0.0;
        $grandSt = 0.0;
        $grandDiaSum = 0.0;
        $grandDiaCount = 0;
        $grandTblSum = 0.0;
        $grandTblCount = 0;

        foreach ($rowsBySupplier as $supplierName => $items) {
            usort($items, static function (array $a, array $b): int {
                $da = (string) ($a['sort_date'] ?? '');
                $db = (string) ($b['sort_date'] ?? '');

                if ($da === $db) {
                    return 0;
                }

                if ($da === '') {
                    return 1;
                }

                if ($db === '') {
                    return -1;
                }

                return strcmp($da, $db);
            });

            $rowsForSupplier = array_values(array_map(static fn(array $item): array => (array) ($item['row'] ?? []), $items));

            $kbSum = 0.0;
            $stSum = 0.0;
            $diaSum = 0.0;
            $diaCount = 0;
            $tblSum = 0.0;
            $tblCount = 0;

            foreach ($rowsForSupplier as $row) {
                $kb = $this->toFloat($row['Ton (KB)'] ?? null);
                $st = $this->toFloat($row['Ton (ST)'] ?? null);
                $dia = $this->toFloat($row['Ave Dia'] ?? null);
                $tbl = $this->toFloat($row['Ave Tbl'] ?? null);

                $kbSum += (float) ($kb ?? 0.0);
                $stSum += (float) ($st ?? 0.0);

                if ($dia !== null) {
                    $diaSum += $dia;
                    $diaCount++;
                }
                if ($tbl !== null) {
                    $tblSum += $tbl;
                    $tblCount++;
                }
            }

            $diaAvg = $diaCount > 0 ? $diaSum / $diaCount : null;
            $tblAvg = $tblCount > 0 ? $tblSum / $tblCount : null;
            $rendPercent = $kbSum > 0.0000001 ? ($stSum / $kbSum) * 100.0 : null;

            $supplierGroups[] = [
                'supplier' => $supplierName,
                'rows' => $rowsForSupplier,
            ];
            $supplierSummaries[] = [
                'supplier' => $supplierName,
                'kb_total' => $kbSum,
                'st_total' => $stSum,
                'ave_dia' => $diaAvg,
                'ave_tbl' => $tblAvg,
                'rend_percent' => $rendPercent,
                'row_count' => count($rowsForSupplier),
            ];

            $grandKb += $kbSum;
            $grandSt += $stSum;
            $grandDiaSum += $diaSum;
            $grandDiaCount += $diaCount;
            $grandTblSum += $tblSum;
            $grandTblCount += $tblCount;
        }

        $grandDiaAvg = $grandDiaCount > 0 ? $grandDiaSum / $grandDiaCount : null;
        $grandTblAvg = $grandTblCount > 0 ? $grandTblSum / $grandTblCount : null;
        $grandRendPercent = $grandKb > 0.0000001 ? ($grandSt / $grandKb) * 100.0 : null;

        foreach ($supplierSummaries as $index => $summary) {
            $kbTotal = (float) ($summary['kb_total'] ?? 0.0);
            $stTotal = (float) ($summary['st_total'] ?? 0.0);
            $supplierSummaries[$index]['kb_percent'] = $grandKb > 0.0000001 ? ($kbTotal / $grandKb) * 100.0 : null;
            $supplierSummaries[$index]['st_percent'] = $grandSt > 0.0000001 ? ($stTotal / $grandSt) * 100.0 : null;
        }

        return [
            'rows' => $mappedRows,
            'supplier_groups' => $supplierGroups,
            'supplier_summaries' => $supplierSummaries,
            'grand_totals' => [
                'kb_total' => $grandKb,
                'st_total' => $grandSt,
                'ave_dia' => $grandDiaAvg,
                'ave_tbl' => $grandTblAvg,
                'rend_percent' => $grandRendPercent,
            ],
            'columns' => $schemaKeys,
            'column_schema' => $schema,
            'expected_columns' => $expectedColumns,
            'detected_columns' => $columns,
            'supplier_column' => $supplierSource,
            'date_column' => $dateSource,
            'summary' => [
                'total_rows' => count($mappedRows),
                'total_suppliers' => count($supplierGroups),
            ],
            'source_columns' => [
                'supplier' => $supplierSource,
                'tanggal' => $dateSource,
                'no_st' => $noStSource,
                'no_truk' => $noTrukSource,
                'meja' => $mejaSource,
                'no_kb' => $noKbSource,
                'jenis_kayu_bulat' => $jenisKbSource,
                'ton_kb' => $tonKbSource,
                'ton_st' => $tonStSource,
                'ave_dia' => $aveDiaSource,
                'ave_tbl' => $aveTblSource,
                'potong' => $potongSource,
                'rend' => $rendSource,
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
        $expectedColumns = config('reports.rekap_penerimaan_st_dari_sawmill_non_rambung.expected_columns', []);
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
     * @param array<int, string> $columns
     */
    private function resolveSupplierColumn(array $columns): ?string
    {
        $candidates = [
            'Nama Supplier',
            'NamaSupplier',
            'Supplier',
            'NamaSuplier',
            'Suplier',
            'Vendor',
            'NamaVendor',
        ];

        foreach ($candidates as $candidate) {
            foreach ($columns as $column) {
                if (strcasecmp(trim($column), $candidate) === 0) {
                    return $column;
                }
            }
        }

        foreach ($columns as $column) {
            $normalized = strtolower(str_replace([' ', '_', '-'], '', $column));
            if (str_contains($normalized, 'supplier') || str_contains($normalized, 'suplier') || str_contains($normalized, 'vendor')) {
                return $column;
            }
        }

        return null;
    }

    /**
     * @param array<int, string> $columns
     */
    private function resolveDateColumn(array $columns): ?string
    {
        $candidates = [
            'Tanggal',
            'Tgl',
            'TglPenerimaan',
            'TglPenerimaanST',
            'Tgl Penerimaan',
            'Tgl Penerimaan ST',
            'TglPenerimaanSTSawmill',
            'TglPenerimaanST_Sawmill',
            'TglTerima',
            'TanggalTerima',
            'Date',
        ];

        foreach ($candidates as $candidate) {
            foreach ($columns as $column) {
                if (strcasecmp(trim($column), $candidate) === 0) {
                    return $column;
                }
            }
        }

        foreach ($columns as $column) {
            $normalized = strtolower(str_replace([' ', '_', '-'], '', $column));
            if (str_contains($normalized, 'tgl') || str_contains($normalized, 'tanggal') || str_contains($normalized, 'date')) {
                return $column;
            }
        }

        return null;
    }

    private function normalizeSupplierName(mixed $value): string
    {
        $name = trim((string) ($value ?? ''));

        return $name !== '' ? $name : 'Tanpa Supplier';
    }

    private function normalizeDateKey(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->format('Y-m-d');
        }

        $raw = trim((string) $value);
        if ($raw === '') {
            return '';
        }

        try {
            return Carbon::parse($raw)->format('Y-m-d');
        } catch (\Throwable $exception) {
            return $raw;
        }
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @param array<int, string> $expectedColumns
     * @return array<int, string>
     */
    private function extractColumns(array $rows, array $expectedColumns): array
    {
        $seen = [];
        $ordered = [];

        foreach ($expectedColumns as $col) {
            $col = trim((string) $col);
            if ($col === '' || isset($seen[$col])) {
                continue;
            }
            $seen[$col] = true;
            $ordered[] = $col;
        }

        foreach ($rows as $row) {
            foreach (array_keys($row) as $col) {
                $col = (string) $col;
                if ($col === '' || isset($seen[$col])) {
                    continue;
                }
                $seen[$col] = true;
                $ordered[] = $col;
            }
        }

        return $ordered;
    }

    /**
     * @param array<int, string> $columns
     */
    private function resolveNoStColumn(array $columns): ?string
    {
        $candidates = ['No ST', 'NoST', 'NoPenST', 'NoPenerimaanST', 'No Penerimaan ST', 'No Pen ST'];

        foreach ($candidates as $candidate) {
            foreach ($columns as $column) {
                if (strcasecmp(trim($column), $candidate) === 0) {
                    return $column;
                }
            }
        }

        foreach ($columns as $column) {
            $normalized = strtolower(str_replace([' ', '_', '-'], '', $column));
            if (str_contains($normalized, 'nost') || str_contains($normalized, 'nopenst') || str_contains($normalized, 'nopenerimaanst')) {
                return $column;
            }
        }

        return null;
    }

    /**
     * @param array<int, string> $columns
     */
    private function resolveNoTrukColumn(array $columns): ?string
    {
        $candidates = ['NoTruk', 'No Truk', 'NoTruck', 'No Truck', 'NoKendaraan', 'No Kendaraan', 'Plat', 'NoPlat'];

        foreach ($candidates as $candidate) {
            foreach ($columns as $column) {
                if (strcasecmp(trim($column), $candidate) === 0) {
                    return $column;
                }
            }
        }

        foreach ($columns as $column) {
            $normalized = strtolower(str_replace([' ', '_', '-'], '', $column));
            if (str_contains($normalized, 'notruk') || str_contains($normalized, 'truck') || str_contains($normalized, 'kendaraan') || str_contains($normalized, 'plat')) {
                return $column;
            }
        }

        return null;
    }

    /**
     * @param array<int, string> $columns
     */
    private function resolveMejaColumn(array $columns): ?string
    {
        $candidates = ['Meja', 'MejaSawmill', 'NoMeja', 'No Meja'];

        foreach ($candidates as $candidate) {
            foreach ($columns as $column) {
                if (strcasecmp(trim($column), $candidate) === 0) {
                    return $column;
                }
            }
        }

        foreach ($columns as $column) {
            $normalized = strtolower(str_replace([' ', '_', '-'], '', $column));
            if (str_contains($normalized, 'meja')) {
                return $column;
            }
        }

        return null;
    }

    /**
     * @param array<int, string> $columns
     */
    private function resolveNoKbColumn(array $columns): ?string
    {
        $candidates = ['No.KB', 'NoKB', 'No Kayu Bulat', 'NoKayuBulat', 'NoKBulat', 'NoKB.'];

        foreach ($candidates as $candidate) {
            foreach ($columns as $column) {
                if (strcasecmp(trim($column), $candidate) === 0) {
                    return $column;
                }
            }
        }

        foreach ($columns as $column) {
            $normalized = strtolower(str_replace([' ', '_', '-'], '', $column));
            if (str_contains($normalized, 'nokb') || (str_contains($normalized, 'no') && str_contains($normalized, 'kayubulat'))) {
                return $column;
            }
        }

        return null;
    }

    /**
     * @param array<int, string> $columns
     */
    private function resolveJenisKayuBulatColumn(array $columns): ?string
    {
        $candidates = ['Jenis Kayu Bulat', 'JenisKayuBulat', 'Jenis', 'JenisKayu', 'NamaJenis', 'Kayu', 'NamaKayu'];

        foreach ($candidates as $candidate) {
            foreach ($columns as $column) {
                if (strcasecmp(trim($column), $candidate) === 0) {
                    return $column;
                }
            }
        }

        foreach ($columns as $column) {
            $normalized = strtolower(str_replace([' ', '_', '-'], '', $column));
            if (str_contains($normalized, 'jeniskayu') || str_contains($normalized, 'kayubulat') || str_contains($normalized, 'namakayu')) {
                return $column;
            }
        }

        return null;
    }

    /**
     * @param array<int, string> $columns
     */
    private function resolveTonKbColumn(array $columns): ?string
    {
        $candidates = [
            'Ton (KB)',
            'Ton(KB)',
            'Ton KB',
            'TonKB',
            'Ton_KB',
            'KB (Ton)',
            'KB(Ton)',
            'KB_Ton',
            'TonKBulat',
            'TonKayuBulat',
            'TonKB.',
        ];

        foreach ($candidates as $candidate) {
            foreach ($columns as $column) {
                if (strcasecmp(trim($column), $candidate) === 0) {
                    return $column;
                }
            }
        }

        foreach ($columns as $column) {
            $normalized = strtolower(str_replace([' ', '_', '-'], '', $column));

            // Avoid false positives with "NoKB" / identifiers.
            if (str_starts_with($normalized, 'no') || str_contains($normalized, 'nokb')) {
                continue;
            }

            if (str_contains($normalized, 'tonkb') || str_contains($normalized, 'kbton') || (str_contains($normalized, 'ton') && str_contains($normalized, 'kb'))) {
                return $column;
            }
        }

        return null;
    }

    /**
     * @param array<int, string> $columns
     */
    private function resolveTonStColumn(array $columns): ?string
    {
        $candidates = [
            'Ton (ST)',
            'Ton(ST)',
            'Ton ST',
            'TonST',
            'Ton_ST',
            'ST (Ton)',
            'ST(Ton)',
            'ST_Ton',
            'TonSawnTimber',
            'TonSt.',
        ];

        foreach ($candidates as $candidate) {
            foreach ($columns as $column) {
                if (strcasecmp(trim($column), $candidate) === 0) {
                    return $column;
                }
            }
        }

        foreach ($columns as $column) {
            $normalized = strtolower(str_replace([' ', '_', '-'], '', $column));

            // Avoid "NoST" fields.
            if (str_contains($normalized, 'nost') || str_starts_with($normalized, 'no')) {
                continue;
            }

            if (str_contains($normalized, 'tonst') || str_contains($normalized, 'stton') || (str_contains($normalized, 'ton') && str_contains($normalized, 'st'))) {
                return $column;
            }
        }

        return null;
    }

    /**
     * @param array<int, string> $columns
     */
    private function resolveAveDiaColumn(array $columns): ?string
    {
        $candidates = ['Ave Dia', 'AveDia', 'AvgDia', 'RataDia', 'Rata2Dia', 'Diameter', 'Dia'];

        foreach ($candidates as $candidate) {
            foreach ($columns as $column) {
                if (strcasecmp(trim($column), $candidate) === 0) {
                    return $column;
                }
            }
        }

        foreach ($columns as $column) {
            $normalized = strtolower(str_replace([' ', '_', '-'], '', $column));
            if (str_starts_with($normalized, 'no')) {
                continue;
            }

            if (str_contains($normalized, 'avedia') || str_contains($normalized, 'avgdia') || $normalized === 'dia' || str_contains($normalized, 'diameter')) {
                return $column;
            }
        }

        return null;
    }

    /**
     * @param array<int, string> $columns
     */
    private function resolveAveTblColumn(array $columns): ?string
    {
        $candidates = ['Ave Tbl', 'AveTbl', 'AvgTbl', 'RataTbl', 'Rata2Tbl', 'Tebal', 'Tbl'];

        foreach ($candidates as $candidate) {
            foreach ($columns as $column) {
                if (strcasecmp(trim($column), $candidate) === 0) {
                    return $column;
                }
            }
        }

        foreach ($columns as $column) {
            $normalized = strtolower(str_replace([' ', '_', '-'], '', $column));
            if (str_starts_with($normalized, 'no')) {
                continue;
            }

            if (str_contains($normalized, 'avetbl') || str_contains($normalized, 'avgtbl') || $normalized === 'tbl' || str_contains($normalized, 'tebal')) {
                return $column;
            }
        }

        return null;
    }

    /**
     * @param array<int, string> $columns
     */
    private function resolvePotongColumn(array $columns): ?string
    {
        $candidates = ['Potong', 'Potongan', 'Cut', 'PjgPotong', 'PanjangPotong', 'Panjang', 'Length'];

        foreach ($candidates as $candidate) {
            foreach ($columns as $column) {
                if (strcasecmp(trim($column), $candidate) === 0) {
                    return $column;
                }
            }
        }

        foreach ($columns as $column) {
            $normalized = strtolower(str_replace([' ', '_', '-'], '', $column));
            if (str_contains($normalized, 'potong') || str_contains($normalized, 'cut')) {
                return $column;
            }
        }

        return null;
    }

    /**
     * @param array<int, string> $columns
     */
    private function resolveRendStKbColumn(array $columns): ?string
    {
        $candidates = ['Rend ST-KB', 'RendSTKB', 'Rendemen', 'Rend', 'Rend_ST_KB', 'RendST-KB', 'RendST'];

        foreach ($candidates as $candidate) {
            foreach ($columns as $column) {
                if (strcasecmp(trim($column), $candidate) === 0) {
                    return $column;
                }
            }
        }

        foreach ($columns as $column) {
            $normalized = strtolower(str_replace([' ', '_', '-'], '', $column));
            if (str_contains($normalized, 'rend') || str_contains($normalized, 'rendemen')) {
                return $column;
            }
        }

        return null;
    }

    private function toFloat(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        if (!is_string($value)) {
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

    private function formatNoTruk(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_numeric($value)) {
            return number_format((float) $value, 0, '.', ',');
        }

        $raw = trim((string) $value);

        return $raw;
    }

    /**
     * @return array<int, object>
     */
    private function runProcedureQuery(string $startDate, string $endDate): array
    {
        $configKey = 'reports.rekap_penerimaan_st_dari_sawmill_non_rambung';
        $connectionName = config("{$configKey}.database_connection");
        $procedure = (string) config("{$configKey}.stored_procedure", '');
        $syntax = (string) config("{$configKey}.call_syntax", 'exec');
        $customQuery = config("{$configKey}.query");
        $parameterCount = (int) config("{$configKey}.parameter_count", 2);

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan rekap penerimaan ST dari sawmill (Non Rambung) belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();
        $bindings = array_slice([$startDate, $endDate], 0, max(0, min($parameterCount, 2)));

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan rekap penerimaan ST dari sawmill (Non Rambung) dikonfigurasi untuk SQL Server. '
                . 'Set REKAP_PENERIMAAN_ST_DARI_SAWMILL_NON_RAMBUNG_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException('Query manual laporan rekap penerimaan ST dari sawmill (Non Rambung) belum diisi.');

            return $connection->select($query, str_contains($query, '?') ? $bindings : []);
        }

        if (!preg_match('/^[A-Za-z0-9_$.]+$/', $procedure)) {
            throw new RuntimeException('Nama stored procedure tidak valid.');
        }

        $sql = match ($syntax) {
            'exec' => $parameterCount >= 2 ? "EXEC {$procedure} ?, ?" : "EXEC {$procedure}",
            'call' => $parameterCount >= 2 ? "CALL {$procedure}(?, ?)" : "CALL {$procedure}()",
            default => $driver === 'sqlsrv'
                ? ($parameterCount >= 2 ? "EXEC {$procedure} ?, ?" : "EXEC {$procedure}")
                : ($parameterCount >= 2 ? "CALL {$procedure}(?, ?)" : "CALL {$procedure}()"),
        };

        return $connection->select($sql, $bindings);
    }
}

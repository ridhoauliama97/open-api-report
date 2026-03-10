<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RekapPenerimaanSTDariSawmillKgReportService
{
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
        $rows = $this->fetch($startDate, $endDate);

        $columns = array_keys($rows[0] ?? []);
        $dateColumn = $this->resolvePenerimaanDateColumn($columns) ?? $this->resolveDateColumn($columns);
        $kategoriColumn = $this->resolveKategoriColumn($columns);
        $inOutColumn = $this->resolveInOutColumn($columns);
        $gradeColumn = $this->resolveGradeColumn($columns);
        $gradeAltColumn = $this->resolveGradeAltColumn($columns);
        $kbColumn = $this->resolveKbColumn($columns);
        $stColumn = $this->resolveStColumn($columns);
        $percentColumn = $this->resolvePercentColumn($columns);

        // Optional header/meta columns (follow reference PDF).
        $noPenColumn = $this->resolveNoPenerimaanColumn($columns);
        $noKbColumn = $this->resolveNoKbColumn($columns);
        $dateCreateColumn = $this->resolveDateCreateColumn($columns);
        $mejaColumn = $this->resolveMejaColumn($columns);
        $supplierColumn = $this->resolveSupplierColumn($columns);
        $noTrukColumn = $this->resolveNoTrukColumn($columns);
        $jenisKayuColumn = $this->resolveJenisKayuColumn($columns);
        $jmlhTrukColumn = $this->resolveJmlhTrukColumn($columns);

        $byDate = [];
        $grandKb = 0.0;
        $grandSt = 0.0;
        $grandByGrade = [
            'input' => [],
            'output' => [],
        ];

        $lastDateKey = '';
        $lastKategoriByReceipt = [];
        $lineIndexByReceipt = [];

        foreach ($rows as $row) {
            $dateKey = $this->normalizeDateKey($dateColumn !== null ? ($row[$dateColumn] ?? null) : null);
            if ($dateKey === '' && $lastDateKey !== '') {
                // Handle merged-cell style SP output (date only present on first row).
                $dateKey = $lastDateKey;
            }
            $lastDateKey = $dateKey;

            $dateKey = $dateKey !== '' ? $dateKey : 'Tanpa Tanggal';

            if (!isset($byDate[$dateKey])) {
                $byDate[$dateKey] = [
                    'date_key' => $dateKey,
                    'date_label' => $this->formatDateLabel($dateKey),
                    'receipts' => [],
                ];
            }

            $receiptKey = $this->resolveReceiptKey($row, $noPenColumn, $noKbColumn, $supplierColumn, $noTrukColumn);
            if (!isset($byDate[$dateKey]['receipts'][$receiptKey])) {
                $byDate[$dateKey]['receipts'][$receiptKey] = [
                    'receipt_key' => $receiptKey,
                    'meta' => $this->buildReceiptMeta(
                        $row,
                        $noPenColumn,
                        $noKbColumn,
                        $dateCreateColumn,
                        $dateColumn,
                        $mejaColumn,
                        $supplierColumn,
                        $noTrukColumn,
                        $jenisKayuColumn,
                    ),
                    'rows' => [
                        'input' => [],
                        'output' => [],
                    ],
                    'totals' => [
                        'kb_total' => 0.0,
                        'st_total' => 0.0,
                        'rendemen' => 0.0,
                    ],
                ];
            }

            $rawInOut = $inOutColumn !== null ? trim((string) ($row[$inOutColumn] ?? '')) : '';
            $grade = $gradeColumn !== null ? trim((string) ($row[$gradeColumn] ?? '')) : '';
            if ($grade === '' && $gradeAltColumn !== null) {
                $grade = trim((string) ($row[$gradeAltColumn] ?? ''));
            }
            $rawGradeEmpty = $grade === '';
            $grade = $grade !== '' ? $grade : 'Tanpa Grade';

            // Determine kategori in a safe order:
            // 1) explicit kategori column (if any)
            // 2) in/out direction (if SP provides it for the row)
            // 3) force based on known OUTPUT-only grade labels
            // 4) carry-forward within the same receipt (merged-cell output)
            // 5) fallback to input
            $kategori = $this->normalizeKategori($kategoriColumn !== null ? ($row[$kategoriColumn] ?? null) : null);
            if ($kategori === null && $inOutColumn !== null && ($row[$inOutColumn] ?? null) !== null && trim((string) ($row[$inOutColumn] ?? '')) !== '') {
                $kategori = $this->normalizeDirection($row[$inOutColumn] ?? null);
            }
            if ($kategori === null) {
                $kategori = $this->forceKategoriByGrade($grade);
            }
            if ($kategori === null) {
                $kategori = $lastKategoriByReceipt[$dateKey][$receiptKey] ?? null;
            }
            if ($kategori === null) {
                $kategori = 'input';
            }
            $lastKategoriByReceipt[$dateKey][$receiptKey] = $kategori;

            if ($this->isSummaryGradeLabel($grade)) {
                // Skip summary rows from SP (JUMLAH/RENDEMEN) and compute totals ourselves.
                continue;
            }

            $kb = $kbColumn !== null ? ($this->toFloat($row[$kbColumn] ?? null) ?? 0.0) : 0.0;
            $st = $stColumn !== null ? ($this->toFloat($row[$stColumn] ?? null) ?? 0.0) : 0.0;
            $percent = $percentColumn !== null ? ($this->toFloat($row[$percentColumn] ?? null) ?? 0.0) : 0.0;
            $jmlhTruk = $jmlhTrukColumn !== null
                ? trim((string) ($row[$jmlhTrukColumn] ?? ''))
                // When SP doesn't provide truck count, the reference report prints "1" on INPUT grade rows.
                : ($kategori === 'input' ? '1' : '0');

            // Skip separator/blank lines that can appear due to merged cells.
            if ($rawGradeEmpty && abs($kb) < 0.0000001 && abs($st) < 0.0000001 && abs($percent) < 0.0000001) {
                continue;
            }

            $line = [
                'kategori' => $kategori,
                'grade' => $grade,
                'jmlh_truk' => $jmlhTruk,
                'kb' => $kb,
                'st' => $st,
                'percent' => $percent,
            ];

            // Group duplicate grades within the same receipt + kategori (SP often outputs multiple lines per grade).
            if (!isset($lineIndexByReceipt[$dateKey])) {
                $lineIndexByReceipt[$dateKey] = [];
            }
            if (!isset($lineIndexByReceipt[$dateKey][$receiptKey])) {
                $lineIndexByReceipt[$dateKey][$receiptKey] = ['input' => [], 'output' => []];
            }
            if (!isset($lineIndexByReceipt[$dateKey][$receiptKey][$kategori])) {
                $lineIndexByReceipt[$dateKey][$receiptKey][$kategori] = [];
            }

            $gradeKey = $grade;
            $existingIndex = $lineIndexByReceipt[$dateKey][$receiptKey][$kategori][$gradeKey] ?? null;
            if ($existingIndex !== null) {
                $existing = &$byDate[$dateKey]['receipts'][$receiptKey]['rows'][$kategori][$existingIndex];
                $existing['kb'] = (float) ($existing['kb'] ?? 0.0) + $kb;
                $existing['st'] = (float) ($existing['st'] ?? 0.0) + $st;
                if (trim((string) ($existing['jmlh_truk'] ?? '')) === '' && trim((string) $jmlhTruk) !== '') {
                    $existing['jmlh_truk'] = $jmlhTruk;
                }
                unset($existing);
            } else {
                $byDate[$dateKey]['receipts'][$receiptKey]['rows'][$kategori][] = $line;
                $lineIndexByReceipt[$dateKey][$receiptKey][$kategori][$gradeKey] =
                    count($byDate[$dateKey]['receipts'][$receiptKey]['rows'][$kategori]) - 1;
            }
            $byDate[$dateKey]['receipts'][$receiptKey]['totals']['kb_total'] += $kb;
            $byDate[$dateKey]['receipts'][$receiptKey]['totals']['st_total'] += $st;

            $grandKb += $kb;
            $grandSt += $st;

            if (!isset($grandByGrade[$kategori])) {
                $grandByGrade[$kategori] = [];
            }
            if (!isset($grandByGrade[$kategori][$gradeKey])) {
                $grandByGrade[$kategori][$gradeKey] = [
                    'kategori' => $kategori,
                    'grade' => $grade,
                    // Keep consistent with per-table output: INPUT shows 1, OUTPUT is treated as 0 (hidden in PDF).
                    'jmlh_truk' => $kategori === 'input' ? '1' : '0',
                    'kb' => 0.0,
                    'st' => 0.0,
                    'percent' => 0.0,
                ];
            }
            $grandByGrade[$kategori][$gradeKey]['kb'] += $kb;
            $grandByGrade[$kategori][$gradeKey]['st'] += $st;
        }

        // Finalize totals + sort receipts.
        foreach ($byDate as $dateKey => $dateGroup) {
            foreach ($dateGroup['receipts'] as $receiptKey => $receipt) {
                $kbTotal = (float) ($receipt['totals']['kb_total'] ?? 0.0);
                $stTotal = (float) ($receipt['totals']['st_total'] ?? 0.0);
                $receipt['totals']['rendemen'] = $kbTotal > 0.0 ? (($stTotal / $kbTotal) * 100.0) : 0.0;

                // Calculate percent columns to match the reference PDF:
                // - INPUT: percent is distribution of KB per grade.
                // - OUTPUT: percent is distribution of ST per product/grade.
                if (isset($receipt['rows']['input']) && is_array($receipt['rows']['input'])) {
                    foreach ($receipt['rows']['input'] as $idx => $line) {
                        $kb = (float) ($line['kb'] ?? 0.0);
                        $receipt['rows']['input'][$idx]['percent'] = $kbTotal > 0.0 ? (($kb / $kbTotal) * 100.0) : 0.0;
                    }
                }
                if (isset($receipt['rows']['output']) && is_array($receipt['rows']['output'])) {
                    foreach ($receipt['rows']['output'] as $idx => $line) {
                        $st = (float) ($line['st'] ?? 0.0);
                        $receipt['rows']['output'][$idx]['percent'] = $stTotal > 0.0 ? (($st / $stTotal) * 100.0) : 0.0;
                    }
                }

                $byDate[$dateKey]['receipts'][$receiptKey] = $receipt;
            }

            ksort($byDate[$dateKey]['receipts'], SORT_NATURAL | SORT_FLAG_CASE);
            $byDate[$dateKey]['receipts'] = array_values($byDate[$dateKey]['receipts']);
        }

        $dateGroups = array_values($byDate);
        usort($dateGroups, static fn(array $a, array $b): int => strcmp((string) $a['date_key'], (string) $b['date_key']));

        $grandInputRows = array_values($grandByGrade['input'] ?? []);
        $grandOutputRows = array_values($grandByGrade['output'] ?? []);
        $grandKbTotal = array_sum(array_map(static fn(array $l): float => (float) ($l['kb'] ?? 0.0), $grandInputRows));
        $grandStTotal = array_sum(array_map(static fn(array $l): float => (float) ($l['st'] ?? 0.0), $grandOutputRows));
        $grandRendemen = $grandKbTotal > 0.0 ? (($grandStTotal / $grandKbTotal) * 100.0) : 0.0;

        foreach ($grandInputRows as $idx => $line) {
            $kb = (float) ($line['kb'] ?? 0.0);
            $grandInputRows[$idx]['percent'] = $grandKbTotal > 0.0 ? (($kb / $grandKbTotal) * 100.0) : 0.0;
        }
        foreach ($grandOutputRows as $idx => $line) {
            $st = (float) ($line['st'] ?? 0.0);
            $grandOutputRows[$idx]['percent'] = $grandStTotal > 0.0 ? (($st / $grandStTotal) * 100.0) : 0.0;
        }

        return [
            'rows' => $rows,
            'columns' => $columns,
            'date_column' => $dateColumn,
            'kategori_column' => $kategoriColumn,
            'grade_column' => $gradeColumn,
            'grade_alt_column' => $gradeAltColumn,
            'kb_column' => $kbColumn,
            'st_column' => $stColumn,
            'percent_column' => $percentColumn,
            'no_penerimaan_column' => $noPenColumn,
            'no_kayu_bulat_column' => $noKbColumn,
            'supplier_column' => $supplierColumn,
            'no_truk_column' => $noTrukColumn,
            'jenis_kayu_column' => $jenisKayuColumn,
            'meja_column' => $mejaColumn,
            'date_create_column' => $dateCreateColumn,
            'jmlh_truk_column' => $jmlhTrukColumn,
            'date_groups' => $dateGroups,
            'grand_totals' => [
                'rows' => [
                    'input' => $grandInputRows,
                    'output' => $grandOutputRows,
                ],
                'totals' => [
                    'kb_total' => $grandKbTotal,
                    'st_total' => $grandStTotal,
                    'rendemen' => $grandRendemen,
                ],
            ],
            'summary' => [
                'total_rows' => count($rows),
                'total_dates' => count($dateGroups),
                'total_receipts' => array_sum(array_map(static fn(array $g): int => count($g['receipts'] ?? []), $dateGroups)),
                'grand_kb' => $grandKb,
                'grand_st' => $grandSt,
                'grand_rendemen' => $grandKb > 0.0 ? (($grandSt / $grandKb) * 100.0) : 0.0,
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
        $expectedColumns = config('reports.rekap_penerimaan_st_dari_sawmill_kg.expected_columns', []);
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
     * @return array<int, object>
     */
    private function runProcedureQuery(string $startDate, string $endDate): array
    {
        $configKey = 'reports.rekap_penerimaan_st_dari_sawmill_kg';
        $connectionName = config("{$configKey}.database_connection");
        $procedure = (string) config("{$configKey}.stored_procedure");
        $syntax = (string) config("{$configKey}.call_syntax", 'exec');
        $customQuery = config("{$configKey}.query");
        $parameterCount = (int) config("{$configKey}.parameter_count", 2);

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException('Stored procedure laporan rekap penerimaan ST dari sawmill timbang KG belum dikonfigurasi.');
        }

        $connection = DB::connection($connectionName ?: null);
        $driver = $connection->getDriverName();
        $bindings = [];

        if ($parameterCount >= 2) {
            $bindings = [$startDate, $endDate];
        } elseif ($parameterCount === 1) {
            $bindings = [$startDate];
        }

        if ($driver !== 'sqlsrv' && $syntax !== 'query') {
            throw new RuntimeException(
                'Laporan rekap penerimaan ST dari sawmill timbang KG dikonfigurasi untuk SQL Server. '
                . 'Set REKAP_PENERIMAAN_ST_DARI_SAWMILL_KG_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
            );
        }

        if ($syntax === 'query') {
            $query = is_string($customQuery) && trim($customQuery) !== ''
                ? $customQuery
                : throw new RuntimeException('Query manual laporan rekap penerimaan ST dari sawmill timbang KG belum diisi.');

            return $connection->select($query, str_contains($query, '?') ? $bindings : []);
        }

        if (!preg_match('/^[A-Za-z0-9_$.]+$/', $procedure)) {
            throw new RuntimeException('Nama stored procedure tidak valid.');
        }

        $placeholders = $parameterCount >= 1 ? implode(', ', array_fill(0, $parameterCount, '?')) : '';

        $sql = match ($syntax) {
            'exec' => $parameterCount >= 1
                ? "SET NOCOUNT ON; EXEC {$procedure} {$placeholders}"
                : "SET NOCOUNT ON; EXEC {$procedure}",
            'call' => $parameterCount >= 1
                ? "CALL {$procedure}({$placeholders})"
                : "CALL {$procedure}()",
            default => $driver === 'sqlsrv'
                ? ($parameterCount >= 1
                    ? "SET NOCOUNT ON; EXEC {$procedure} {$placeholders}"
                    : "SET NOCOUNT ON; EXEC {$procedure}")
                : ($parameterCount >= 1
                    ? "CALL {$procedure}({$placeholders})"
                    : "CALL {$procedure}()"),
        };

        return $connection->select($sql, $bindings);
    }

    /**
     * @param array<int, string> $columns
     */
    private function resolveDateColumn(array $columns): ?string
    {
        $candidates = ['Tanggal', 'Tgl', 'TglST', 'TglPenerimaan', 'DateCreate', 'Date', 'TglInput'];

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

    /**
     * @param array<int, string> $columns
     */
    private function resolveGradeColumn(array $columns): ?string
    {
        $candidates = [
            'NamaGrade',
            'NmGrade',
            'Grade',
            'Grd',
            'Nama Grade',
            'GRADE / PRODUK',
            'Grade / Produk',
            'Produk',
            'Produk ST',
        ];

        foreach ($candidates as $candidate) {
            foreach ($columns as $column) {
                if (strcasecmp(trim($column), $candidate) === 0) {
                    return $column;
                }
            }
        }

        foreach ($columns as $column) {
            if (str_contains(strtolower($column), 'grade')) {
                return $column;
            }
        }

        return null;
    }

    /**
     * Some SP exports include two grade-like columns (e.g. NamaGrade and NamaGrade1).
     * We use this as a fallback when the primary grade column is empty.
     *
     * @param array<int, string> $columns
     */
    private function resolveGradeAltColumn(array $columns): ?string
    {
        $candidates = ['NamaGrade1', 'NmGrade1', 'Grade1', 'GRADE1', 'Nama Grade 1', 'Nama_Grade_1'];

        foreach ($candidates as $candidate) {
            foreach ($columns as $column) {
                if (strcasecmp(trim($column), $candidate) === 0) {
                    return $column;
                }
            }
        }

        foreach ($columns as $column) {
            $normalized = strtolower(str_replace([' ', '_', '-'], '', $column));
            if ($normalized === 'namagrade1' || $normalized === 'grade1' || str_contains($normalized, 'namagrade1')) {
                return $column;
            }
        }

        return null;
    }

    /**
     * @param array<int, string> $columns
     */
    private function resolveInOutColumn(array $columns): ?string
    {
        $candidates = ['InOut', 'InputOutput', 'In Out', 'INOUT', 'Status'];

        foreach ($candidates as $candidate) {
            foreach ($columns as $column) {
                if (strcasecmp(trim($column), $candidate) === 0) {
                    return $column;
                }
            }
        }

        foreach ($columns as $column) {
            $normalized = strtolower(str_replace([' ', '_', '-'], '', $column));
            if (str_contains($normalized, 'inout') || str_contains($normalized, 'inputoutput')) {
                return $column;
            }
        }

        return null;
    }

    /**
     * @param array<int, string> $columns
     */
    private function resolvePenerimaanDateColumn(array $columns): ?string
    {
        $candidates = ['Tgl Penerimaan ST', 'TglPenerimaanST', 'Tanggal Penerimaan', 'TglPenerimaan'];

        foreach ($candidates as $candidate) {
            foreach ($columns as $column) {
                if (strcasecmp(trim($column), $candidate) === 0) {
                    return $column;
                }
            }
        }

        foreach ($columns as $column) {
            $normalized = strtolower(str_replace([' ', '_', '-'], '', $column));
            if (str_contains($normalized, 'penerimaan') && (str_contains($normalized, 'tgl') || str_contains($normalized, 'tanggal'))) {
                return $column;
            }
        }

        return null;
    }

    /**
     * @param array<int, string> $columns
     */
    private function resolveKategoriColumn(array $columns): ?string
    {
        $candidates = ['Kategori', 'KATEGORI', 'Category', 'KategoriGrade', 'Kategori Grade'];

        foreach ($candidates as $candidate) {
            foreach ($columns as $column) {
                if (strcasecmp(trim($column), $candidate) === 0) {
                    return $column;
                }
            }
        }

        foreach ($columns as $column) {
            $normalized = strtolower(str_replace([' ', '_', '-'], '', $column));
            if (str_contains($normalized, 'kategori') || str_contains($normalized, 'category')) {
                return $column;
            }
        }

        return null;
    }

    /**
     * @param array<int, string> $columns
     */
    private function resolveKbColumn(array $columns): ?string
    {
        $candidates = ['KB (Ton)', 'KB(Ton)', 'KB Ton', 'KB', 'KBTon', 'KBTonase', 'KBTimbang', 'KBKg', 'KB (Kg)'];

        foreach ($candidates as $candidate) {
            foreach ($columns as $column) {
                if (strcasecmp(trim($column), $candidate) === 0) {
                    return $column;
                }
            }
        }

        foreach ($columns as $column) {
            $normalized = strtolower(str_replace([' ', '_', '-'], '', $column));
            if (str_contains($normalized, 'kb') && (str_contains($normalized, 'ton') || str_contains($normalized, 'kg') || str_contains($normalized, 'berat'))) {
                return $column;
            }
        }

        return null;
    }

    /**
     * @param array<int, string> $columns
     */
    private function resolveStColumn(array $columns): ?string
    {
        $candidates = ['ST (Ton)', 'ST(Ton)', 'ST Ton', 'ST', 'STTon', 'STTonase', 'STKg', 'ST (Kg)'];

        foreach ($candidates as $candidate) {
            foreach ($columns as $column) {
                if (strcasecmp(trim($column), $candidate) === 0) {
                    return $column;
                }
            }
        }

        foreach ($columns as $column) {
            $normalized = strtolower(str_replace([' ', '_', '-'], '', $column));
            if (str_starts_with($normalized, 'st') && (str_contains($normalized, 'ton') || str_contains($normalized, 'kg') || str_contains($normalized, 'berat'))) {
                return $column;
            }
        }

        return null;
    }

    /**
     * @param array<int, string> $columns
     */
    private function resolvePercentColumn(array $columns): ?string
    {
        $candidates = ['%', 'Persen', 'Percent', 'Rendemen', 'Rendemen(%)', 'Persentase'];

        foreach ($candidates as $candidate) {
            foreach ($columns as $column) {
                if (strcasecmp(trim($column), $candidate) === 0) {
                    return $column;
                }
            }
        }

        foreach ($columns as $column) {
            $normalized = strtolower(str_replace([' ', '_', '-'], '', $column));
            if (str_contains($normalized, 'percent') || str_contains($normalized, 'persen') || str_contains($normalized, 'rendemen')) {
                return $column;
            }
        }

        return null;
    }

    /**
     * @param array<int, string> $columns
     */
    private function resolveNoPenerimaanColumn(array $columns): ?string
    {
        $candidates = ['No Pen ST', 'NoPenST', 'NoPen', 'NoPenerimaanST', 'NoPenerimaan', 'NoBukti'];

        foreach ($candidates as $candidate) {
            foreach ($columns as $column) {
                if (strcasecmp(trim($column), $candidate) === 0) {
                    return $column;
                }
            }
        }

        foreach ($columns as $column) {
            $normalized = strtolower(str_replace([' ', '_', '-'], '', $column));
            if (str_contains($normalized, 'nopen') || str_contains($normalized, 'nopenerimaan') || str_contains($normalized, 'nobukti')) {
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
        $candidates = ['No KB', 'NoKB', 'NoKayuBulat', 'No Kayu Bulat'];

        foreach ($candidates as $candidate) {
            foreach ($columns as $column) {
                if (strcasecmp(trim($column), $candidate) === 0) {
                    return $column;
                }
            }
        }

        foreach ($columns as $column) {
            $normalized = strtolower(str_replace([' ', '_', '-'], '', $column));
            if (str_contains($normalized, 'nokb') || str_contains($normalized, 'nokayubulat')) {
                return $column;
            }
        }

        return null;
    }

    /**
     * @param array<int, string> $columns
     */
    private function resolveDateCreateColumn(array $columns): ?string
    {
        foreach ($columns as $column) {
            if (strcasecmp(trim($column), 'DateCreate') === 0) {
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
        $candidates = ['Meja', 'NoMeja', 'No Meja', 'NO MEJA'];

        foreach ($candidates as $candidate) {
            foreach ($columns as $column) {
                if (strcasecmp(trim($column), $candidate) === 0) {
                    return $column;
                }
            }
        }

        return null;
    }

    /**
     * @param array<int, string> $columns
     */
    private function resolveSupplierColumn(array $columns): ?string
    {
        $candidates = ['Supplier', 'NmSupplier', 'Nama Supplier', 'NamaSupplier'];
        foreach ($candidates as $candidate) {
            foreach ($columns as $column) {
                if (strcasecmp(trim($column), $candidate) === 0) {
                    return $column;
                }
            }
        }

        foreach ($columns as $column) {
            if (str_contains(strtolower($column), 'supplier')) {
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
        $candidates = ['NoTruk', 'No Truk', 'Truk', 'NoPlat'];
        foreach ($candidates as $candidate) {
            foreach ($columns as $column) {
                if (strcasecmp(trim($column), $candidate) === 0) {
                    return $column;
                }
            }
        }

        foreach ($columns as $column) {
            $normalized = strtolower(str_replace([' ', '_', '-'], '', $column));
            if (str_contains($normalized, 'notruk') || $normalized == 'truk') {
                return $column;
            }
        }

        return null;
    }

    /**
     * @param array<int, string> $columns
     */
    private function resolveJenisKayuColumn(array $columns): ?string
    {
        $candidates = ['Jenis Kayu', 'JenisKayu', 'Jenis'];
        foreach ($candidates as $candidate) {
            foreach ($columns as $column) {
                if (strcasecmp(trim($column), $candidate) === 0) {
                    return $column;
                }
            }
        }

        return null;
    }

    /**
     * @param array<int, string> $columns
     */
    private function resolveJmlhTrukColumn(array $columns): ?string
    {
        $candidates = ['JMLH TRUK', 'JMLH_TRUK', 'JmlhTruk', 'Jmlh Truk', 'JumlahTruk', 'Jumlah Truk'];
        foreach ($candidates as $candidate) {
            foreach ($columns as $column) {
                if (strcasecmp(trim($column), $candidate) === 0) {
                    return $column;
                }
            }
        }

        foreach ($columns as $column) {
            $normalized = strtolower(str_replace([' ', '_', '-'], '', $column));
            if (str_contains($normalized, 'jmlhtruk') || str_contains($normalized, 'jumlahtruk')) {
                return $column;
            }
        }

        return null;
    }

    private function resolveReceiptKey(array $row, ?string $noPenColumn, ?string $noKbColumn, ?string $supplierColumn, ?string $noTrukColumn): string
    {
        $noPen = $noPenColumn !== null ? trim((string) ($row[$noPenColumn] ?? '')) : '';
        if ($noPen !== '') {
            return $noPen;
        }

        $noKb = $noKbColumn !== null ? trim((string) ($row[$noKbColumn] ?? '')) : '';
        $supplier = $supplierColumn !== null ? trim((string) ($row[$supplierColumn] ?? '')) : '';
        $truk = $noTrukColumn !== null ? trim((string) ($row[$noTrukColumn] ?? '')) : '';

        $key = trim(implode('|', array_filter([$supplier, $truk, $noKb], static fn(string $v): bool => $v !== '')));

        return $key !== '' ? $key : 'receipt';
    }

    private function buildReceiptMeta(
        array $row,
        ?string $noPenColumn,
        ?string $noKbColumn,
        ?string $dateCreateColumn,
        ?string $penerimaanDateColumn,
        ?string $mejaColumn,
        ?string $supplierColumn,
        ?string $noTrukColumn,
        ?string $jenisKayuColumn,
    ): array {
        $noPen = $noPenColumn !== null ? trim((string) ($row[$noPenColumn] ?? '')) : '';
        $noKb = $noKbColumn !== null ? trim((string) ($row[$noKbColumn] ?? '')) : '';
        $dateCreate = $dateCreateColumn !== null ? trim((string) ($row[$dateCreateColumn] ?? '')) : '';
        $tglPen = $penerimaanDateColumn !== null ? trim((string) ($row[$penerimaanDateColumn] ?? '')) : '';
        $meja = $mejaColumn !== null ? trim((string) ($row[$mejaColumn] ?? '')) : '';
        $supplier = $supplierColumn !== null ? trim((string) ($row[$supplierColumn] ?? '')) : '';
        $truk = $noTrukColumn !== null ? trim((string) ($row[$noTrukColumn] ?? '')) : '';
        $jenis = $jenisKayuColumn !== null ? trim((string) ($row[$jenisKayuColumn] ?? '')) : '';

        return [
            'no_pen_st' => $noPen,
            'no_kayu_bulat' => $noKb,
            'date_create' => $dateCreate,
            'tgl_penerimaan_st' => $tglPen,
            'meja' => $meja,
            'supplier' => $supplier,
            'no_truk' => $truk,
            'jenis_kayu' => $jenis,
        ];
    }

    private function normalizeKategori(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $raw = strtolower(trim((string) $value));
        if ($raw === '') {
            return null;
        }

        $raw = str_replace([' ', '_', '-'], '', $raw);

        if (in_array($raw, ['input', 'in', 'masuk'], true)) {
            return 'input';
        }

        if (in_array($raw, ['output', 'out', 'keluar'], true)) {
            return 'output';
        }

        return null;
    }

    /**
     * Some grades are always part of OUTPUT block in the reference report.
     * This acts as a last-resort categorization when SP output is merged/ambiguous.
     */
    private function forceKategoriByGrade(string $grade): ?string
    {
        $normalized = strtolower(trim($grade));
        $normalized = str_replace([' ', '_', '-'], '', $normalized);

        // Output product grades.
        if (in_array($normalized, ['kayulat', 'mc1', 'mc2', 'std'], true)) {
            return 'output';
        }

        return null;
    }

    private function isSummaryGradeLabel(string $grade): bool
    {
        $normalized = strtolower(trim($grade));
        $normalized = str_replace([' ', '_', '-', ':'], '', $normalized);

        return in_array($normalized, ['jumlah', 'total', 'rendemen', 'rendemennya'], true);
    }

    /**
     * @param array<int, string> $columns
     * @return array{0: string|null, 1: string|null}
     */
    private function resolveInputOutputColumns(array $columns): array
    {
        $inputCandidates = ['InputKg', 'KgInput', 'Input', 'Masuk', 'In', 'QtyIn', 'BeratInput', 'BeratMasuk'];
        $outputCandidates = ['OutputKg', 'KgOutput', 'Output', 'Keluar', 'Out', 'QtyOut', 'BeratOutput', 'BeratKeluar'];

        $input = null;
        $output = null;

        foreach ($inputCandidates as $candidate) {
            foreach ($columns as $column) {
                if (strcasecmp(trim($column), $candidate) === 0) {
                    $input = $column;
                    break 2;
                }
            }
        }

        foreach ($outputCandidates as $candidate) {
            foreach ($columns as $column) {
                if (strcasecmp(trim($column), $candidate) === 0) {
                    $output = $column;
                    break 2;
                }
            }
        }

        return [$input, $output];
    }

    /**
     * @param array<int, string> $columns
     */
    private function resolveValueColumn(array $columns, ?string $inputColumn, ?string $outputColumn): ?string
    {
        // If we already have explicit input/output columns, value column is optional.
        if ($inputColumn !== null || $outputColumn !== null) {
            return null;
        }

        $candidates = ['Kg', 'BeratKg', 'Berat', 'TonBerat', 'JmlhKg', 'JumlahKg', 'Qty', 'Jumlah'];

        foreach ($candidates as $candidate) {
            foreach ($columns as $column) {
                if (strcasecmp(trim($column), $candidate) === 0) {
                    return $column;
                }
            }
        }

        // Fallback: pick the first numeric-like column that is not obviously an id/name/date.
        foreach ($columns as $column) {
            $normalized = strtolower(str_replace([' ', '_', '-'], '', $column));
            if (str_contains($normalized, 'tgl') || str_contains($normalized, 'tanggal') || str_contains($normalized, 'date')) {
                continue;
            }
            if (str_contains($normalized, 'grade') || str_contains($normalized, 'supplier') || str_contains($normalized, 'nama')) {
                continue;
            }
            if (str_contains($normalized, 'inout') || str_contains($normalized, 'inputoutput')) {
                continue;
            }

            return $column;
        }

        return null;
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

    private function formatDateLabel(string $dateKey): string
    {
        try {
            return Carbon::parse($dateKey)->locale('id')->translatedFormat('d-M-y');
        } catch (\Throwable $exception) {
            return $dateKey;
        }
    }

    private function normalizeDirection(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        // Common SP pattern: numeric 1=input, 0=output (sometimes 2=output).
        if (is_int($value) || is_float($value) || (is_string($value) && is_numeric(trim($value)))) {
            $n = (int) round((float) (is_string($value) ? trim($value) : $value));
            if ($n === 1) {
                return 'input';
            }
            if ($n === 0 || $n === 2) {
                return 'output';
            }
        }

        $raw = strtolower(trim((string) $value));
        if ($raw === '') {
            return null;
        }

        $raw = str_replace([' ', '_', '-'], '', $raw);

        if (in_array($raw, ['1', 'i', 'in', 'input', 'masuk'], true)) {
            return 'input';
        }

        // Some SPs use 0 for OUTPUT (based on reference PDF data export).
        if (in_array($raw, ['0', '0.0', '2', '2.0', 'o', 'out', 'output', 'keluar'], true)) {
            return 'output';
        }

        return null;
    }

    private function toFloat(mixed $value): ?float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        if (!is_string($value)) {
            return null;
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }

        $trimmed = str_replace(',', '.', $trimmed);

        return is_numeric($trimmed) ? (float) $trimmed : null;
    }
}

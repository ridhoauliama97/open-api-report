<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RekapProduktivitasSawmillRpReportService
{
    private const EPS = 0.0000001;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetchMain(string $startDate, string $endDate): array
    {
        $rows = $this->runProcedureQuery($startDate, $endDate, 'main');

        return array_values(array_map(static fn(object $row): array => (array) $row, $rows));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetchSub(string $startDate, string $endDate): array
    {
        $rows = $this->runProcedureQuery($startDate, $endDate, 'sub');

        return array_values(array_map(static fn(object $row): array => (array) $row, $rows));
    }

    /**
     * @return array<string, mixed>
     */
    public function buildReportData(string $startDate, string $endDate): array
    {
        $mainRows = $this->fetchMain($startDate, $endDate);
        $subRows = $this->fetchSub($startDate, $endDate);

        $rows = $mainRows;
        if (!$this->hasNonEmptyGrade($rows) && $this->hasNonEmptyGrade($subRows)) {
            $rows = $subRows;
        }

        $columns = array_keys($rows[0] ?? []);
        $dateColumn = $this->resolvePenerimaanDateColumn($columns) ?? $this->resolveDateColumn($columns);
        $kategoriColumn = $this->resolveKategoriColumn($columns);
        $inOutColumn = $this->resolveInOutColumn($columns);
        $gradeColumn = $this->resolveGradeColumn($columns);
        $gradeAltColumn = $this->resolveGradeAltColumn($columns);
        $kbColumn = $this->resolveKbColumn($columns);
        $stColumn = $this->resolveStColumn($columns);
        $valueColumn = $this->resolveValueColumn($columns);
        $percentColumn = $this->resolvePercentColumn($columns);

        $noPenColumn = $this->resolveNoPenerimaanColumn($columns);
        $noKbColumn = $this->resolveNoKbColumn($columns);
        $dateCreateColumn = $this->resolveDateCreateColumn($columns);
        $mejaColumn = $this->resolveMejaColumn($columns);
        $supplierColumn = $this->resolveSupplierColumn($columns);
        $noTrukColumn = $this->resolveNoTrukColumn($columns);
        $jenisKayuColumn = $this->resolveJenisKayuColumn($columns);
        $jmlhTrukColumn = $this->resolveJmlhTrukColumn($columns);

        $moneyColumns = $this->resolveMoneyColumns($columns);
        $hargaColumn = $this->resolveHargaColumn($columns);
        $upahPerKg = (float) config('reports.rekap_produktivitas_sawmill_rp.upah_per_kg', 0.0);
        $shouldCalcMoneyFromHarga =
            $moneyColumns['st'] === null
            && $moneyColumns['kb'] === null
            && $moneyColumns['upah'] === null
            && $moneyColumns['hasil'] === null
            && $hargaColumn !== null;

        // Sub-report: attach "balok timbang ulang" rows per receipt (best-effort).
        $subIndex = $this->buildSubIndex($subRows);

        $byDate = [];
        $grandKb = 0.0;
        $grandSt = 0.0;
        $grandByGrade = [
            'input' => [],
            'output' => [],
        ];

        // Output rows from the SP often have NULL date/meta fields; index receipt date by NoPenerimaanST
        // so those rows can be grouped with their corresponding input rows.
        $receiptDateIndex = $this->buildReceiptDateIndex($rows, $noPenColumn, $dateColumn);

        $lastDateKey = '';
        $lastKategoriByReceipt = [];
        $lineIndexByReceipt = [];

        foreach ($rows as $row) {
            $noPenValue = $noPenColumn !== null ? trim((string) ($row[$noPenColumn] ?? '')) : '';

            $dateKey = $this->normalizeDateKey($dateColumn !== null ? ($row[$dateColumn] ?? null) : null);
            if ($dateKey === '' && $noPenValue !== '' && isset($receiptDateIndex[$noPenValue])) {
                $dateKey = (string) $receiptDateIndex[$noPenValue];
            }
            if ($dateKey === '' && $lastDateKey !== '') {
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
                    'money' => [
                        'st' => 0.0,
                        'kb' => 0.0,
                        'upah' => 0.0,
                        'hasil' => 0.0,
                    ],
                    'balok_timbang_ulang' => $subIndex[$receiptKey] ?? [],
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

            // Merge meta from subsequent rows (input rows typically contain the complete fields).
            $this->mergeReceiptMeta(
                $byDate[$dateKey]['receipts'][$receiptKey]['meta'],
                $this->buildReceiptMeta(
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
            );

            $rawInOut = $inOutColumn !== null ? trim((string) ($row[$inOutColumn] ?? '')) : '';
            $grade = $gradeColumn !== null ? trim((string) ($row[$gradeColumn] ?? '')) : '';
            if ($grade === '' && $gradeAltColumn !== null) {
                $grade = trim((string) ($row[$gradeAltColumn] ?? ''));
            }
            $rawGradeEmpty = $grade === '';
            $grade = $grade !== '' ? $grade : 'Tanpa Grade';

            $kategori = $this->normalizeKategori($kategoriColumn !== null ? ($row[$kategoriColumn] ?? null) : null);
            if ($kategori === null && $rawInOut !== '') {
                $kategori = $this->normalizeKategori($rawInOut);
            }
            if ($kategori === null) {
                $kategori = $this->forceKategoriFromGrade($grade);
            }
            if ($kategori === null) {
                $kategori = $lastKategoriByReceipt[$receiptKey] ?? null;
            }
            if ($kategori === null) {
                $kategori = 'input';
            }
            $lastKategoriByReceipt[$receiptKey] = $kategori;

            $value = $valueColumn !== null ? ($this->toFloat($row[$valueColumn] ?? null) ?? 0.0) : 0.0;
            $percent = $percentColumn !== null ? ($this->toFloat($row[$percentColumn] ?? null) ?? 0.0) : 0.0;

            $kb = $kbColumn !== null ? ($this->toFloat($row[$kbColumn] ?? null) ?? 0.0) : 0.0;
            $st = $stColumn !== null ? ($this->toFloat($row[$stColumn] ?? null) ?? 0.0) : 0.0;

            // Fallback when SP only provides a single numeric column.
            if ($kbColumn === null && $stColumn === null && $valueColumn !== null) {
                $kb = $kategori === 'input' ? $value : 0.0;
                $st = $kategori === 'output' ? $value : 0.0;
            }

            $jmlhTruk = $jmlhTrukColumn !== null
                ? trim((string) ($row[$jmlhTrukColumn] ?? ''))
                : ($kategori === 'input' ? '1' : '0');

            // Costing (Rp): if the SP doesn't provide explicit Rp columns, approximate from Harga * tonnage.
            // Assumption: Harga is per Kg, and KB/ST columns are in tons => Kg = ton * 1000.
            if ($shouldCalcMoneyFromHarga) {
                $harga = $this->toFloat($row[$hargaColumn] ?? null) ?? 0.0;
                if ($harga > 0.0) {
                    if ($kategori === 'input' && abs($kb) > self::EPS) {
                        $byDate[$dateKey]['receipts'][$receiptKey]['money']['kb'] += ($kb * 1000.0) * $harga;
                    } elseif ($kategori === 'output' && abs($st) > self::EPS) {
                        $byDate[$dateKey]['receipts'][$receiptKey]['money']['st'] += ($st * 1000.0) * $harga;
                    }
                }

                // Upah is based on produced ST (Kg), independent of grade price.
                if ($upahPerKg > 0.0 && $kategori === 'output' && abs($st) > self::EPS) {
                    $byDate[$dateKey]['receipts'][$receiptKey]['money']['upah'] += ($st * 1000.0) * $upahPerKg;
                }
            }

            // Money values (best-effort: keep the latest non-empty values per receipt).
            $this->applyMoneyFromRow(
                $byDate[$dateKey]['receipts'][$receiptKey]['money'],
                $row,
                $moneyColumns,
            );

            if ($rawGradeEmpty && abs($kb) < self::EPS && abs($st) < self::EPS && abs($percent) < self::EPS) {
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

            if (!isset($grandByGrade[$kategori][$gradeKey])) {
                $grandByGrade[$kategori][$gradeKey] = [
                    'kategori' => $kategori,
                    'grade' => $grade,
                    'jmlh_truk' => $kategori === 'input' ? '1' : '0',
                    'kb' => 0.0,
                    'st' => 0.0,
                    'percent' => 0.0,
                ];
            }
            $grandByGrade[$kategori][$gradeKey]['kb'] += $kb;
            $grandByGrade[$kategori][$gradeKey]['st'] += $st;
        }

        foreach ($byDate as $dateKey => $dateGroup) {
            foreach ($dateGroup['receipts'] as $receiptKey => $receipt) {
                $kbTotal = (float) ($receipt['totals']['kb_total'] ?? 0.0);
                $stTotal = (float) ($receipt['totals']['st_total'] ?? 0.0);
                $receipt['totals']['rendemen'] = $kbTotal > 0.0 ? (($stTotal / $kbTotal) * 100.0) : 0.0;

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

                // Finalize money totals if the SP doesn't provide "hasil".
                $money = is_array($receipt['money'] ?? null) ? $receipt['money'] : [];
                $moneySt = (float) ($money['st'] ?? 0.0);
                $moneyKb = (float) ($money['kb'] ?? 0.0);
                $moneyUpah = (float) ($money['upah'] ?? 0.0);
                $moneyHasil = (float) ($money['hasil'] ?? 0.0);
                if (abs($moneyHasil) < self::EPS && (abs($moneySt) > self::EPS || abs($moneyKb) > self::EPS || abs($moneyUpah) > self::EPS)) {
                    $receipt['money']['hasil'] = $moneySt - $moneyKb - $moneyUpah;
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
            'rows_main' => $mainRows,
            'rows_sub' => $subRows,
            'rows' => $rows,
            'columns' => $columns,
            'date_column' => $dateColumn,
            'kategori_column' => $kategoriColumn,
            'grade_column' => $gradeColumn,
            'grade_alt_column' => $gradeAltColumn,
            'kb_column' => $kbColumn,
            'st_column' => $stColumn,
            'value_column' => $valueColumn,
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
        $mainRows = $this->fetchMain($startDate, $endDate);
        $subRows = $this->fetchSub($startDate, $endDate);

        $mainDetected = array_keys($mainRows[0] ?? []);
        $subDetected = array_keys($subRows[0] ?? []);

        $expectedMain = config('reports.rekap_produktivitas_sawmill_rp.expected_columns', []);
        $expectedMain = is_array($expectedMain) ? array_values($expectedMain) : [];

        $expectedSub = config('reports.rekap_produktivitas_sawmill_rp.sub_expected_columns', []);
        $expectedSub = is_array($expectedSub) ? array_values($expectedSub) : [];

        $mainMissing = array_values(array_diff($expectedMain, $mainDetected));
        $mainExtra = array_values(array_diff($mainDetected, $expectedMain));
        $subMissing = array_values(array_diff($expectedSub, $subDetected));
        $subExtra = array_values(array_diff($subDetected, $expectedSub));

        return [
            'is_healthy' => empty($mainMissing) && empty($subMissing),
            'main' => [
                'expected_columns' => $expectedMain,
                'detected_columns' => $mainDetected,
                'missing_columns' => $mainMissing,
                'extra_columns' => $mainExtra,
                'row_count' => count($mainRows),
            ],
            'sub' => [
                'expected_columns' => $expectedSub,
                'detected_columns' => $subDetected,
                'missing_columns' => $subMissing,
                'extra_columns' => $subExtra,
                'row_count' => count($subRows),
            ],
        ];
    }

    /**
     * @return array<int, object>
     */
    private function runProcedureQuery(string $startDate, string $endDate, string $procedureType = 'main'): array
    {
        $configKey = 'reports.rekap_produktivitas_sawmill_rp';
        $connectionName = config("{$configKey}.database_connection");
        $procedure = (string) config($procedureType === 'sub' ? "{$configKey}.sub_stored_procedure" : "{$configKey}.stored_procedure");
        $syntax = (string) config("{$configKey}.call_syntax", 'exec');
        $customQuery = config($procedureType === 'sub' ? "{$configKey}.sub_query" : "{$configKey}.query");
        $parameterCount = (int) config(
            $procedureType === 'sub' ? "{$configKey}.sub_parameter_count" : "{$configKey}.parameter_count",
            2,
        );

        if ($procedure === '' && !is_string($customQuery)) {
            throw new RuntimeException(
                $procedureType === 'sub'
                    ? 'Stored procedure sub laporan rekap produktivitas sawmill belum dikonfigurasi.'
                    : 'Stored procedure laporan rekap produktivitas sawmill belum dikonfigurasi.',
            );
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
                'Laporan rekap produktivitas sawmill dikonfigurasi untuk SQL Server. '
                . 'Set REKAP_PRODUKTIVITAS_SAWMILL_RP_REPORT_CALL_SYNTAX=query jika ingin memakai query manual pada driver lain.',
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

        $placeholders = $parameterCount >= 2 ? '?, ?' : ($parameterCount === 1 ? '?' : '');
        $sql = match ($syntax) {
            'exec' => $placeholders !== '' ? "EXEC {$procedure} {$placeholders}" : "EXEC {$procedure}",
            'call' => "CALL {$procedure}({$placeholders})",
            default => $driver === 'sqlsrv'
                ? ($placeholders !== '' ? "EXEC {$procedure} {$placeholders}" : "EXEC {$procedure}")
                : "CALL {$procedure}({$placeholders})",
        };

        return $connection->select($sql, $bindings);
    }

    private function hasNonEmptyGrade(array $rows): bool
    {
        if ($rows === []) {
            return false;
        }

        $columns = array_keys($rows[0] ?? []);
        $gradeColumn = $this->resolveGradeColumn($columns) ?? $this->resolveGradeAltColumn($columns);
        if ($gradeColumn === null) {
            return false;
        }

        foreach ($rows as $row) {
            $value = trim((string) ($row[$gradeColumn] ?? ''));
            if ($value !== '') {
                return true;
            }
        }

        return false;
    }

    private function normalizeDateKey(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        $raw = trim((string) $value);
        if ($raw === '') {
            return '';
        }

        $raw = str_replace('/', '-', $raw);

        try {
            return Carbon::parse($raw)->format('Y-m-d');
        } catch (\Throwable $exception) {
            return $raw;
        }
    }

    private function formatDateLabel(string $dateKey): string
    {
        try {
            return Carbon::parse($dateKey)->translatedFormat('d-M-y');
        } catch (\Throwable $exception) {
            return $dateKey;
        }
    }

    private function resolveReceiptKey(array $row, ?string $noPenColumn, ?string $noKbColumn, ?string $supplierColumn, ?string $noTrukColumn): string
    {
        // Prefer No Penerimaan ST as the stable receipt key so rows with NULL meta (often output rows)
        // still merge into the same receipt.
        if ($noPenColumn !== null) {
            $noPen = trim((string) ($row[$noPenColumn] ?? ''));
            if ($noPen !== '') {
                return $noPen;
            }
        }

        $parts = [];
        foreach ([$noPenColumn, $noKbColumn, $supplierColumn, $noTrukColumn] as $column) {
            if ($column === null) {
                continue;
            }
            $value = trim((string) ($row[$column] ?? ''));
            if ($value !== '') {
                $parts[] = $value;
            }
        }

        return $parts !== [] ? implode(' | ', $parts) : 'Tanpa Kunci';
    }

    /**
     * @return array<string, string>
     */
    private function buildReceiptDateIndex(array $rows, ?string $noPenColumn, ?string $dateColumn): array
    {
        if ($rows === [] || $noPenColumn === null || $dateColumn === null) {
            return [];
        }

        $out = [];
        foreach ($rows as $row) {
            $noPen = trim((string) ($row[$noPenColumn] ?? ''));
            if ($noPen === '') {
                continue;
            }

            $dateKey = $this->normalizeDateKey($row[$dateColumn] ?? null);
            if ($dateKey === '') {
                continue;
            }

            $out[$noPen] = $dateKey;
        }

        return $out;
    }

    private function mergeReceiptMeta(array &$meta, array $newMeta): void
    {
        foreach ($newMeta as $key => $value) {
            $incoming = trim((string) ($value ?? ''));
            if ($incoming === '') {
                continue;
            }

            $existing = trim((string) ($meta[$key] ?? ''));
            if ($existing === '') {
                $meta[$key] = $value;
            }
        }
    }

    private function buildReceiptMeta(
        array $row,
        ?string $noPenColumn,
        ?string $noKbColumn,
        ?string $dateCreateColumn,
        ?string $dateColumn,
        ?string $mejaColumn,
        ?string $supplierColumn,
        ?string $noTrukColumn,
        ?string $jenisKayuColumn,
    ): array {
        return [
            'no_pen_st' => $noPenColumn !== null ? (string) ($row[$noPenColumn] ?? '') : '',
            'no_kayu_bulat' => $noKbColumn !== null ? (string) ($row[$noKbColumn] ?? '') : '',
            'date_create' => $dateCreateColumn !== null ? (string) ($row[$dateCreateColumn] ?? '') : '',
            'tgl_penerimaan_st' => $dateColumn !== null ? (string) ($row[$dateColumn] ?? '') : '',
            'meja' => $mejaColumn !== null ? (string) ($row[$mejaColumn] ?? '') : '',
            'supplier' => $supplierColumn !== null ? (string) ($row[$supplierColumn] ?? '') : '',
            'no_truk' => $noTrukColumn !== null ? (string) ($row[$noTrukColumn] ?? '') : '',
            'jenis_kayu' => $jenisKayuColumn !== null ? (string) ($row[$jenisKayuColumn] ?? '') : '',
        ];
    }

    private function forceKategoriFromGrade(string $grade): ?string
    {
        $upper = strtoupper(trim($grade));
        $outputOnly = ['KAYU LAT', 'MC 1', 'MC 2', 'STD'];

        foreach ($outputOnly as $label) {
            if ($upper === $label) {
                return 'output';
            }
        }

        return null;
    }

    private function normalizeKategori(mixed $value): ?string
    {
        $raw = trim((string) ($value ?? ''));
        if ($raw === '') {
            return null;
        }

        $raw = strtolower($raw);
        if (in_array($raw, ['input', 'in', '1', 'masuk'], true)) {
            return 'input';
        }
        if (in_array($raw, ['output', 'out', '0', 'keluar'], true)) {
            return 'output';
        }

        return null;
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

        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }

        $trimmed = str_replace(',', '.', $trimmed);

        return is_numeric($trimmed) ? (float) $trimmed : null;
    }

    private function resolvePenerimaanDateColumn(array $columns): ?string
    {
        $candidates = ['TglPenerimaanST', 'TglPenerimaan', 'TglLaporan', 'Tanggal'];

        foreach ($candidates as $candidate) {
            foreach ($columns as $column) {
                if (strcasecmp(trim($column), $candidate) === 0) {
                    return $column;
                }
            }
        }

        return null;
    }

    private function resolveDateColumn(array $columns): ?string
    {
        foreach ($columns as $column) {
            $normalized = strtolower(str_replace([' ', '_', '-'], '', $column));
            if (str_contains($normalized, 'tgl') || str_contains($normalized, 'tanggal') || str_contains($normalized, 'date')) {
                return $column;
            }
        }

        return null;
    }

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

    private function resolveInOutColumn(array $columns): ?string
    {
        $candidates = ['InOut', 'In Out', 'INOUT', 'InOutFlag', 'MasukKeluar'];

        foreach ($candidates as $candidate) {
            foreach ($columns as $column) {
                if (strcasecmp(trim($column), $candidate) === 0) {
                    return $column;
                }
            }
        }

        foreach ($columns as $column) {
            $normalized = strtolower(str_replace([' ', '_', '-'], '', $column));
            if (str_contains($normalized, 'inout') || str_contains($normalized, 'masukkeluar')) {
                return $column;
            }
        }

        return null;
    }

    private function resolveGradeColumn(array $columns): ?string
    {
        $candidates = ['NamaGrade', 'Grade', 'GRADE', 'Produk', 'Grade / Produk'];

        foreach ($candidates as $candidate) {
            foreach ($columns as $column) {
                if (strcasecmp(trim($column), $candidate) === 0) {
                    return $column;
                }
            }
        }

        foreach ($columns as $column) {
            $normalized = strtolower(str_replace([' ', '_', '-'], '', $column));
            if (str_contains($normalized, 'grade') || str_contains($normalized, 'produk')) {
                return $column;
            }
        }

        return null;
    }

    private function resolveGradeAltColumn(array $columns): ?string
    {
        $candidates = ['NamaGrade1', 'Grade1', 'Nama Grade 1', 'NamaGradeAlt', 'NamaGradeAlt1'];

        foreach ($candidates as $candidate) {
            foreach ($columns as $column) {
                if (strcasecmp(trim($column), $candidate) === 0) {
                    return $column;
                }
            }
        }

        return null;
    }

    private function resolveValueColumn(array $columns): ?string
    {
        $candidates = [
            'Rp',
            'RP',
            'Rupiah',
            'JumlahRp',
            'TotalRp',
            'NilaiRp',
            'Nominal',
            'Nilai',
            'Amount',
            'Harga',
            'Berat',
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
            if (
                str_contains($normalized, 'rupiah')
                || (str_contains($normalized, 'rp') && !str_contains($normalized, 'trp'))
                || str_contains($normalized, 'nominal')
                || str_contains($normalized, 'nilai')
                || str_contains($normalized, 'amount')
                || str_contains($normalized, 'harga')
                || str_contains($normalized, 'berat')
            ) {
                return $column;
            }
        }

        return null;
    }

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

    private function resolveNoKbColumn(array $columns): ?string
    {
        $candidates = ['No Kayu Bulat', 'NoKayuBulat', 'NoKB', 'NoKayu', 'NoKayuBulatMasuk'];

        foreach ($candidates as $candidate) {
            foreach ($columns as $column) {
                if (strcasecmp(trim($column), $candidate) === 0) {
                    return $column;
                }
            }
        }

        foreach ($columns as $column) {
            $normalized = strtolower(str_replace([' ', '_', '-'], '', $column));
            if (str_contains($normalized, 'nokayubulat') || (str_contains($normalized, 'no') && str_contains($normalized, 'kb'))) {
                return $column;
            }
        }

        return null;
    }

    private function resolveDateCreateColumn(array $columns): ?string
    {
        $candidates = ['DateCreate', 'TglCreate', 'TanggalCreate', 'TglInput', 'CreatedAt', 'CreateDate'];

        foreach ($candidates as $candidate) {
            foreach ($columns as $column) {
                if (strcasecmp(trim($column), $candidate) === 0) {
                    return $column;
                }
            }
        }

        return null;
    }

    private function resolveMejaColumn(array $columns): ?string
    {
        $candidates = ['NoMeja', 'Meja', 'No Meja', 'NoMejaPotong'];

        foreach ($candidates as $candidate) {
            foreach ($columns as $column) {
                if (strcasecmp(trim($column), $candidate) === 0) {
                    return $column;
                }
            }
        }

        return null;
    }

    private function resolveSupplierColumn(array $columns): ?string
    {
        $candidates = ['NmSupplier', 'Nama Supplier', 'Supplier', 'NamaSupplier'];

        foreach ($candidates as $candidate) {
            foreach ($columns as $column) {
                if (strcasecmp(trim($column), $candidate) === 0) {
                    return $column;
                }
            }
        }

        foreach ($columns as $column) {
            $normalized = strtolower(str_replace([' ', '_', '-'], '', $column));
            if (str_contains($normalized, 'supplier')) {
                return $column;
            }
        }

        return null;
    }

    private function resolveNoTrukColumn(array $columns): ?string
    {
        $candidates = ['NoTruk', 'No Truk', 'Truck', 'NoTruck'];

        foreach ($candidates as $candidate) {
            foreach ($columns as $column) {
                if (strcasecmp(trim($column), $candidate) === 0) {
                    return $column;
                }
            }
        }

        foreach ($columns as $column) {
            $normalized = strtolower(str_replace([' ', '_', '-'], '', $column));
            if (str_contains($normalized, 'truk') || str_contains($normalized, 'truck')) {
                return $column;
            }
        }

        return null;
    }

    private function resolveJenisKayuColumn(array $columns): ?string
    {
        $candidates = ['Jenis', 'JenisKayu', 'Jenis Kayu', 'NamaJenis'];

        foreach ($candidates as $candidate) {
            foreach ($columns as $column) {
                if (strcasecmp(trim($column), $candidate) === 0) {
                    return $column;
                }
            }
        }

        return null;
    }

    private function resolveJmlhTrukColumn(array $columns): ?string
    {
        $candidates = ['JmlhTruk', 'JumlahTruk', 'Jumlah Truk', 'JmlTruk', 'Jmlh Truck'];

        foreach ($candidates as $candidate) {
            foreach ($columns as $column) {
                if (strcasecmp(trim($column), $candidate) === 0) {
                    return $column;
                }
            }
        }

        foreach ($columns as $column) {
            $normalized = strtolower(str_replace([' ', '_', '-'], '', $column));
            if (str_contains($normalized, 'jmlh') && (str_contains($normalized, 'truk') || str_contains($normalized, 'truck'))) {
                return $column;
            }
        }

        return null;
    }

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

    private function resolveHargaColumn(array $columns): ?string
    {
        $candidates = ['Harga', 'HargaRp', 'Harga Rp', 'Price', 'Tarif'];

        foreach ($candidates as $candidate) {
            foreach ($columns as $column) {
                if (strcasecmp(trim($column), $candidate) === 0) {
                    return $column;
                }
            }
        }

        foreach ($columns as $column) {
            $normalized = strtolower(str_replace([' ', '_', '-'], '', $column));
            if (str_contains($normalized, 'harga') || str_contains($normalized, 'price') || str_contains($normalized, 'tarif')) {
                return $column;
            }
        }

        return null;
    }

    /**
     * @return array{st: ?string, kb: ?string, upah: ?string, hasil: ?string}
     */
    private function resolveMoneyColumns(array $columns): array
    {
        return [
            // Avoid matching STTon/KBTon; only consider Rp/value columns.
            'st' => $this->findColumnByCandidates($columns, ['STRp', 'ST Rp', 'NilaiST', 'STValue', 'ST_Rp']),
            'kb' => $this->findColumnByCandidates($columns, ['KBRp', 'KB Rp', 'NilaiKB', 'KBValue', 'KB_Rp']),
            'upah' => $this->findColumnByCandidates($columns, ['Upah', 'UpahRp', 'BiayaUpah', 'Biaya', 'CostUpah']),
            'hasil' => $this->findColumnByCandidates($columns, ['Hasil', 'HasilRp', 'Profit', 'Rugi', 'Selisih']),
        ];
    }

    /**
     * @param array<int, string> $columns
     * @param array<int, string> $candidates
     */
    private function findColumnByCandidates(array $columns, array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            foreach ($columns as $column) {
                if (strcasecmp(trim($column), $candidate) === 0) {
                    return $column;
                }
            }
        }

        foreach ($columns as $column) {
            $normalized = strtolower(str_replace([' ', '_', '-'], '', $column));
            foreach ($candidates as $candidate) {
                $cand = strtolower(str_replace([' ', '_', '-'], '', $candidate));
                if ($cand !== '' && str_contains($normalized, $cand)) {
                    return $column;
                }
            }
        }

        return null;
    }

    /**
     * @param array{st: float, kb: float, upah: float, hasil: float} $money
     * @param array<string, mixed> $row
     * @param array{st: ?string, kb: ?string, upah: ?string, hasil: ?string} $columns
     */
    private function applyMoneyFromRow(array &$money, array $row, array $columns): void
    {
        foreach (['st', 'kb', 'upah', 'hasil'] as $key) {
            $col = $columns[$key] ?? null;
            if (!is_string($col) || $col === '') {
                continue;
            }
            $val = $this->toFloat($row[$col] ?? null);
            if ($val === null) {
                continue;
            }
            $money[$key] = $val;
        }
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function buildSubIndex(array $subRows): array
    {
        if ($subRows === []) {
            return [];
        }

        $columns = array_keys($subRows[0] ?? []);
        $noPenColumn = $this->resolveNoPenerimaanColumn($columns);
        $noKbColumn = $this->resolveNoKbColumn($columns);
        $supplierColumn = $this->resolveSupplierColumn($columns);
        $noTrukColumn = $this->resolveNoTrukColumn($columns);

        $kbCol = $this->resolveKbColumn($columns) ?? $this->findColumnByCandidates($columns, ['KBTon', 'KB']);
        $stCol = $this->resolveStColumn($columns) ?? $this->findColumnByCandidates($columns, ['STTon', 'ST']);
        $groupCol = $this->findColumnByCandidates($columns, ['Grup', 'Group', 'Kelompok', 'Ket', 'Keterangan']);
        $labelCol = $this->findColumnByCandidates($columns, ['Keterangan', 'Label', 'Nama', 'Judul']);
        $mejaCol = $this->resolveMejaColumn($columns) ?? $this->findColumnByCandidates($columns, ['NoMeja', 'Meja']);

        $out = [];
        foreach ($subRows as $row) {
            $key = $this->resolveReceiptKey($row, $noPenColumn, $noKbColumn, $supplierColumn, $noTrukColumn);

            $kb = $kbCol !== null ? ($this->toFloat($row[$kbCol] ?? null) ?? 0.0) : 0.0;
            $st = $stCol !== null ? ($this->toFloat($row[$stCol] ?? null) ?? 0.0) : 0.0;
            $rawInOut = trim((string) ($row['InOut'] ?? ($row['inout'] ?? '')));

            $label = '';
            if ($labelCol !== null) {
                $label = trim((string) ($row[$labelCol] ?? ''));
            }

            $meja = '';
            if ($label === '' && $mejaCol !== null) {
                $meja = trim((string) ($row[$mejaCol] ?? ''));
                if ($meja !== '') {
                    $label = "NoMeja {$meja}";
                }
            }

            // If there's no explicit label/meja, show group total.
            if ($label === '' && $groupCol !== null) {
                $group = trim((string) ($row[$groupCol] ?? ''));
                if ($group !== '') {
                    $label = "Total {$group}";
                }
            }

            // Merge kb/st from separate InOut rows into a single line per label.
            if (!isset($out[$key])) {
                $out[$key] = [];
            }
            if (!isset($out[$key][$label])) {
                $out[$key][$label] = [
                    'label' => $label,
                    'kb' => 0.0,
                    'st' => 0.0,
                    'percent' => 0.0,
                ];
            }

            if ($rawInOut !== '' && $this->normalizeKategori($rawInOut) === 'output') {
                $out[$key][$label]['st'] += $st;
            } elseif ($rawInOut !== '' && $this->normalizeKategori($rawInOut) === 'input') {
                $out[$key][$label]['kb'] += $kb;
            } else {
                // If InOut isn't available, just accumulate both.
                $out[$key][$label]['kb'] += $kb;
                $out[$key][$label]['st'] += $st;
            }
        }

        // Finalize percent and normalize to list.
        $final = [];
        foreach ($out as $key => $byLabel) {
            $lines = array_values($byLabel);
            foreach ($lines as $idx => $line) {
                $kb = (float) ($line['kb'] ?? 0.0);
                $st = (float) ($line['st'] ?? 0.0);
                $lines[$idx]['percent'] = $kb > 0.0 ? (($st / $kb) * 100.0) : 0.0;
            }
            $final[$key] = $lines;
        }

        return $final;
    }
}

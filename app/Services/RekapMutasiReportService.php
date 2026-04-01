<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class RekapMutasiReportService
{
    public function __construct(
        private readonly MutasiKayuBulatReportService $mutasiKayuBulatReportService,
        private readonly MutasiKayuBulatKGReportService $mutasiKayuBulatKGReportService,
        private readonly MutasiSTReportService $mutasiSTReportService,
        private readonly MutasiS4SReportService $mutasiS4SReportService,
        private readonly MutasiFingerJointReportService $mutasiFingerJointReportService,
        private readonly MutasiMouldingReportService $mutasiMouldingReportService,
        private readonly MutasiLaminatingReportService $mutasiLaminatingReportService,
        private readonly MutasiCCAkhirReportService $mutasiCCAkhirReportService,
        private readonly MutasiSandingReportService $mutasiSandingReportService,
        private readonly MutasiBarangJadiReportService $mutasiBarangJadiReportService,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function buildReportData(string $startDate, string $endDate): array
    {
        $sections = [
            $this->buildKayuBulatSection($startDate, $endDate),
            $this->buildKayuBulatKgSection($startDate, $endDate),
            $this->buildSawnTimberSection($startDate, $endDate),
            $this->buildS4SSection($startDate, $endDate),
            $this->buildFingerJointSection($startDate, $endDate),
            $this->buildMouldingSection($startDate, $endDate),
            $this->buildLaminatingSection($startDate, $endDate),
            $this->buildCCAkhirSection($startDate, $endDate),
            $this->buildSandingSection($startDate, $endDate),
            $this->buildBarangJadiSection($startDate, $endDate),
        ];

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'sections' => $sections,
            'summary' => [
                'section_count' => count($sections),
                'row_count' => array_sum(array_map(
                    static fn(array $section): int => count($section['rows'] ?? []),
                    $sections,
                )),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function healthCheck(string $startDate, string $endDate): array
    {
        $rows = $this->fetchRawRekapMutasi($startDate, $endDate);
        $detectedColumns = array_keys($rows[0] ?? []);
        $expectedColumns = config('reports.rekap_mutasi.expected_columns', []);
        $expectedColumns = is_array($expectedColumns) ? array_values($expectedColumns) : [];

        return [
            'is_healthy' => array_diff($expectedColumns, $detectedColumns) === [],
            'expected_columns' => $expectedColumns,
            'detected_columns' => $detectedColumns,
            'missing_columns' => array_values(array_diff($expectedColumns, $detectedColumns)),
            'extra_columns' => array_values(array_diff($detectedColumns, $expectedColumns)),
            'row_count' => count($rows),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildKayuBulatSection(string $startDate, string $endDate): array
    {
        $sourceRows = $this->mutasiKayuBulatReportService->fetch($startDate, $endDate);
        $rows = array_map(function (array $row, int $index): array {
            return [
                'No' => $index + 1,
                'Jenis' => $this->formatKayuBulatJenis((string) ($row['Jenis'] ?? '')),
                'Awal' => $this->toFloat($row['SaldoAwal'] ?? null),
                'Masuk' => $this->toFloat($row['SaldoMasuk'] ?? null),
                'Keluar' => $this->toFloat($row['SaldoKeluar'] ?? null),
                'Jual' => $this->toFloat($row['SaldoJual'] ?? null),
                'Akhir' => $this->toFloat($row['SaldoAkhir'] ?? null),
            ];
        }, $sourceRows, array_keys($sourceRows));

        return [
            'key' => 'kayu_bulat',
            'title' => '1. Kayu Bulat (Ton)',
            'value_format' => 'decimal4',
            'columns' => [
                'No' => 'No',
                'Jenis' => 'Jenis Kayu',
                'Awal' => 'Awal',
                'Masuk' => 'Masuk',
                'Keluar' => 'Keluar',
                'Jual' => 'Jual',
                'Akhir' => 'Akhir',
            ],
            'rows' => $rows,
            'totals' => $this->sumColumns($rows, ['Awal', 'Masuk', 'Keluar', 'Jual', 'Akhir']),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildKayuBulatKgSection(string $startDate, string $endDate): array
    {
        $sourceRows = $this->mutasiKayuBulatKGReportService->fetch($startDate, $endDate);
        $rows = array_map(function (array $row, int $index): array {
            return [
                'No' => $index + 1,
                'Jenis' => $this->stripPrefix((string) ($row['Jenis'] ?? ''), 'KB '),
                'Awal' => $this->toFloat($row['SaldoAwal'] ?? null),
                'Masuk' => $this->toFloat($row['SaldoMasuk'] ?? null),
                'Keluar' => $this->toFloat($row['SaldoKeluar'] ?? null),
                'Jual' => $this->toFloat($row['SaldoJual'] ?? null),
                'Akhir' => $this->toFloat($row['SaldoAkhir'] ?? null),
            ];
        }, $sourceRows, array_keys($sourceRows));

        return [
            'key' => 'kayu_bulat_rambung_kg',
            'title' => '2. Kayu Bulat - Rambung (Kg)',
            'value_format' => 'integer0',
            'columns' => [
                'No' => 'No',
                'Jenis' => 'Jenis Grade Kayu',
                'Awal' => 'Awal',
                'Masuk' => 'Masuk',
                'Keluar' => 'Keluar',
                'Jual' => 'Jual',
                'Akhir' => 'Akhir',
            ],
            'rows' => $rows,
            'totals' => $this->sumColumns($rows, ['Awal', 'Masuk', 'Keluar', 'Jual', 'Akhir']),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSawnTimberSection(string $startDate, string $endDate): array
    {
        $sourceRows = $this->mutasiSTReportService->fetch($startDate, $endDate);
        $rows = array_map(function (array $row, int $index): array {
            $label = (string) ($row['Jenis'] ?? '');
            if (str_starts_with($label, 'ST ')) {
                $label = $this->stripPrefix($label, 'ST ');
            }

            return [
                'No' => $index + 1,
                'Jenis' => $label,
                'Awal' => $this->toFloat($row['Awal'] ?? null),
                'Masuk' => $this->toFloat($row['Masuk'] ?? null),
                'Beli' => $this->toFloat($row['Beli'] ?? null),
                'AdjustmentPlus' => $this->toFloat($row['AdjustmentPlus'] ?? null),
                'AdjustmentMinus' => $this->toFloat($row['AdjustmentMinus'] ?? null),
                'BongkarSusunPlus' => $this->toFloat($row['BongkarSusunPlus'] ?? null),
                'BongkarSusunMinus' => $this->toFloat($row['BongkarSusunMinus'] ?? null),
                'Jual' => $this->toFloat($row['Jual'] ?? null),
                'Keluar' => $this->toFloat($row['Keluar'] ?? null),
                'Akhir' => $this->toFloat($row['Akhir'] ?? null),
            ];
        }, $sourceRows, array_keys($sourceRows));

        usort($rows, fn(array $a, array $b): int => $this->compareByOrder(
            $a['Jenis'] ?? '',
            $b['Jenis'] ?? '',
            ['JABON', 'JABON TG', 'KAYU LAT JABON', 'KAYU LAT RAMBUNG', 'PULAI', 'RAMBUNG - MC 1', 'RAMBUNG - MC 2', 'RAMBUNG - STD', 'SEMBARANG'],
        ));

        foreach ($rows as $index => &$row) {
            $row['No'] = $index + 1;
        }
        unset($row);

        return [
            'key' => 'sawntimber',
            'title' => '3. Sawntimber (Ton)',
            'value_format' => 'decimal4',
            'columns' => [
                'No' => 'No.',
                'Jenis' => 'Jenis Kayu',
                'Awal' => 'Awal',
                'Masuk' => 'Masuk',
                'Beli' => 'Beli',
                'AdjustmentPlus' => 'Adjust (+)',
                'AdjustmentMinus' => 'Adjust (-)',
                'BongkarSusunPlus' => 'B.Susun (+)',
                'BongkarSusunMinus' => 'B.Susun (-)',
                'Jual' => 'Jual',
                'Keluar' => 'Keluar',
                'Akhir' => 'Akhir',
            ],
            'rows' => $rows,
            'totals' => $this->sumColumns($rows, ['Awal', 'Masuk', 'Beli', 'AdjustmentPlus', 'AdjustmentMinus', 'BongkarSusunPlus', 'BongkarSusunMinus', 'Jual', 'Keluar', 'Akhir']),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildS4SSection(string $startDate, string $endDate): array
    {
        $mainRows = $this->aggregateByWoodGroup(
            $this->mutasiS4SReportService->fetch($startDate, $endDate),
            fn(array $row): array => [
                'Awal' => $this->toFloat($row['S4SAwal'] ?? null),
                'Masuk' => $this->sumValues($row, ['S4SMasuk', 'AdjOutputS4S', 'BSOutputS4S', 'ProdOutputS4S', 'CCAProdOutputS4S']),
                'Jual' => $this->toFloat($row['JualS4S'] ?? null),
                'Keluar' => $this->sumValues($row, ['AdjInputS4S', 'BsInputS4S', 'FJinputS4S', 'MldInputS4S', 'S4SInputS4S']),
                'Akhir' => $this->toFloat($row['AkhirS4S'] ?? null),
            ],
        );

        $inputRows = $this->buildInputRowsDetailed(
            $this->mutasiS4SReportService->fetchSubReport($startDate, $endDate),
            ['FJ', 'MLD', 'S4S', 'ST'],
        );

        return $this->buildProductionSection(
            's4s',
            '4. S4S (m3)',
            $mainRows,
            'Input S4S Produksi (m3)',
            $inputRows,
            ['FJ', 'MLD', 'S4S', 'ST'],
            'Input',
            'Output',
            'Keluar',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function buildFingerJointSection(string $startDate, string $endDate): array
    {
        $mainRows = $this->aggregateByWoodGroup(
            $this->mutasiFingerJointReportService->fetch($startDate, $endDate),
            fn(array $row): array => [
                'Awal' => $this->toFloat($row['FJAwal'] ?? null),
                'Masuk' => $this->sumValues($row, ['FJMasuk', 'AdjOutputFJ', 'BSOutputFJ', 'FJProdOutput']),
                'Jual' => $this->toFloat($row['FJJual'] ?? null),
                'Keluar' => $this->sumValues($row, ['AdjInptFJ', 'BSInptFJ', 'MldInptFJ', 'CCAInptFJ', 'S4SInptFJ', 'SandInptFJ']),
                'Akhir' => $this->toFloat($row['FJAkhir'] ?? null),
            ],
        );

        $inputRows = $this->buildInputRowsGroupedByFamily(
            $this->mutasiFingerJointReportService->fetchSubReport($startDate, $endDate),
            ['CCAkhir', 'S4S'],
        );

        return $this->buildProductionSection(
            'finger_joint',
            '5. Finger Joint (m3)',
            $mainRows,
            'Input FJ Produksi (m3)',
            $inputRows,
            ['CCAkhir', 'S4S'],
            'Input',
            'Output',
            'Keluar',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function buildMouldingSection(string $startDate, string $endDate): array
    {
        $mainRows = $this->aggregateByWoodGroup(
            $this->mutasiMouldingReportService->fetch($startDate, $endDate),
            fn(array $row): array => [
                'Awal' => $this->toFloat($row['MLDAwal'] ?? null),
                'Masuk' => $this->sumValues($row, ['MLDMasuk', 'AdjOutputMLD', 'BSOutptutMLD', 'MLDProdOutput']),
                'Jual' => $this->toFloat($row['MLDJual'] ?? null),
                'Keluar' => $this->sumValues($row, ['AdjInptMLD', 'BSInptMLD', 'MLDInptMLD', 'CCAInptMLD', 'LMTInptMLD', 'PACKInptMLD', 'SANDInptMLD', 'S4SinptMLD']),
                'Akhir' => $this->toFloat($row['MLDAkhir'] ?? null),
            ],
        );

        $inputRows = $this->buildInputRowsGroupedByFamily(
            $this->mutasiMouldingReportService->fetchSubReport($startDate, $endDate),
            ['BJ', 'CCAkhir', 'FJ', 'Laminating', 'Moulding', 'S4S', 'Sanding'],
        );

        return $this->buildProductionSection(
            'moulding',
            '6. Moulding (m3)',
            $mainRows,
            'Input Moulding Produksi (m3)',
            $inputRows,
            ['BJ', 'CCAkhir', 'FJ', 'Laminating', 'Moulding', 'S4S', 'Sanding'],
            'Input',
            'Output',
            'Keluar',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function buildLaminatingSection(string $startDate, string $endDate): array
    {
        $mainRows = $this->aggregateByWoodGroup(
            $this->mutasiLaminatingReportService->fetch($startDate, $endDate),
            fn(array $row): array => [
                'Awal' => $this->toFloat($row['LMTAwal'] ?? null),
                'Masuk' => $this->sumValues($row, ['LMTMasuk', 'AdjOutputLMT', 'BSOutputLMT', 'LMTProdOuput']),
                'Jual' => $this->toFloat($row['LMTJual'] ?? null),
                'Keluar' => $this->sumValues($row, ['AdjInptLMT', 'BSInptLMT', 'CCAProdInptLMT', 'MldProdInptLMT', 'S4SProdInptLMT']),
                'Akhir' => $this->toFloat($row['LMTAkhir'] ?? null),
            ],
        );

        $inputRows = $this->buildInputRowsGroupedByFamily(
            $this->mutasiLaminatingReportService->fetchSubReport($startDate, $endDate),
            ['Moulding', 'Reproses', 'Sanding', 'WIP'],
        );

        return $this->buildProductionSection(
            'laminating',
            '7. Laminating (m3)',
            $mainRows,
            'Input Laminating Produksi (m3)',
            $inputRows,
            ['Moulding', 'Reproses', 'Sanding', 'WIP'],
            'Input',
            'Output',
            'Keluar',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function buildCCAkhirSection(string $startDate, string $endDate): array
    {
        $mainRows = $this->aggregateByWoodGroup(
            $this->mutasiCCAkhirReportService->fetch($startDate, $endDate),
            fn(array $row): array => [
                'Awal' => $this->toFloat($row['CCAkhirAwal'] ?? null),
                'Masuk' => $this->sumValues($row, ['CCAMasuk', 'AdjOutputCCA', 'BSOutputCCA', 'CCAProdOutput']),
                'Jual' => $this->toFloat($row['CCAJual'] ?? null),
                'Keluar' => $this->sumValues($row, ['AdjInptCCA', 'BSInputCCA', 'FJProdInpt', 'MldProdinpt', 'S4SProdInpt', 'SandProdInpt', 'LMTProdInpt', 'PACKProdInpt', 'CCAInputCCA']),
                'Akhir' => $this->toFloat($row['CCAAkhir'] ?? null),
            ],
        );

        $inputRows = $this->buildInputRowsGroupedByFamily(
            $this->mutasiCCAkhirReportService->fetchSubReport($startDate, $endDate),
            ['BJ', 'CCAkhir', 'FJ', 'Sanding', 'Laminating'],
        );

        return $this->buildProductionSection(
            'cca_akhir',
            '8. CC Akhir (m3)',
            $mainRows,
            'Input Cross Cut Akhir Produksi (m3)',
            $inputRows,
            ['BJ', 'CCAkhir', 'FJ', 'Sanding', 'Laminating'],
            'Output',
            'Input',
            'Keluar',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSandingSection(string $startDate, string $endDate): array
    {
        $mainRows = $this->aggregateByWoodGroup(
            $this->mutasiSandingReportService->fetch($startDate, $endDate),
            fn(array $row): array => [
                'Awal' => $this->toFloat($row['SANDAwal'] ?? null),
                'Masuk' => $this->sumValues($row, ['SANDMasuk', 'AdjOutputSAND', 'BSOutputSAND', 'SANDProdOutput']),
                'Jual' => $this->toFloat($row['SANDJual'] ?? null),
                'Keluar' => $this->sumValues($row, ['AdjInptSAND', 'BSInptSAND', 'LMTProdInptSAND', 'PACKProdInptSAND', 'CCAProdInptSand', 'SANDProdInptSand', 'MLDProdInptSand']),
                'Akhir' => $this->toFloat($row['SANDAkhir'] ?? null),
            ],
        );

        $inputRows = $this->buildInputRowsGroupedByFamily(
            $this->mutasiSandingReportService->fetchSubReport($startDate, $endDate),
            ['BJ', 'CCAkhir', 'FJ', 'Moulding', 'Sanding'],
        );

        return $this->buildProductionSection(
            'sanding',
            '9. Sanding (m3)',
            $mainRows,
            'Input Sanding Produksi (m3)',
            $inputRows,
            ['BJ', 'CCAkhir', 'FJ', 'Moulding', 'Sanding'],
            'Input',
            'Output',
            'Keluar',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function buildBarangJadiSection(string $startDate, string $endDate): array
    {
        $sourceRows = $this->mutasiBarangJadiReportService->fetch($startDate, $endDate);
        $rows = array_map(function (array $row, int $index): array {
            return [
                'No' => $index + 1,
                'Jenis' => $this->stripPrefix((string) ($row['Jenis'] ?? ''), 'BJ '),
                'Awal' => $this->toFloat($row['Awal'] ?? null),
                'Masuk' => $this->toFloat($row['Masuk'] ?? null),
                'Plus' => $this->sumValues($row, ['AdjOutput', 'BSOutput']),
                'Minus' => $this->sumValues($row, ['AdjInput', 'BSInput', 'Keluar']),
                'Jual' => $this->toFloat($row['Jual'] ?? null),
                'Akhir' => $this->toFloat($row['Akhir'] ?? null),
            ];
        }, $sourceRows, array_keys($sourceRows));

        $inputRows = $this->buildInputRowsGroupedByFamily(
            $this->mutasiBarangJadiReportService->fetchSubReport($startDate, $endDate),
            ['Moulding', 'Sanding', 'CCAkhir', 'WIPLama', 'BarangJadi'],
        );

        $section = [
            'key' => 'barang_jadi',
            'title' => '10. Barang Jadi (m3)',
            'value_format' => 'decimal4',
            'columns' => [
                'No' => 'No',
                'Jenis' => 'Jenis Kayu',
                'Awal' => 'Awal',
                'Masuk' => 'Masuk',
                'Plus' => 'Plus',
                'Minus' => 'Minus (-)',
                'Jual' => 'Jual',
                'Akhir' => 'Akhir',
            ],
            'rows' => $rows,
            'totals' => $this->sumColumns($rows, ['Awal', 'Masuk', 'Plus', 'Minus', 'Jual', 'Akhir']),
        ];

        $section['input_table'] = $this->buildInputTable('Input Barang Jadi Produksi (m3)', $inputRows, ['Moulding', 'Sanding', 'CCAkhir', 'WIPLama', 'BarangJadi']);
        $section['performance'] = $this->buildPerformanceBlock($section, $section['input_table'], 'Input', 'Output', 'Jual');

        return $section;
    }

    /**
     * @param array<int, array<string, mixed>> $sourceRows
     * @param callable(array<string, mixed>): array<string, float|null> $mapper
     * @return array<int, array<string, mixed>>
     */
    private function aggregateByWoodGroup(array $sourceRows, callable $mapper): array
    {
        $groups = [];

        foreach ($sourceRows as $row) {
            $family = $this->woodFamilyFromJenis((string) ($row['Jenis'] ?? ''));
            if (!isset($groups[$family])) {
                $groups[$family] = [
                    'Jenis' => $family,
                    'Awal' => 0.0,
                    'Masuk' => 0.0,
                    'Jual' => 0.0,
                    'Keluar' => 0.0,
                    'Akhir' => 0.0,
                ];
            }

            foreach ($mapper($row) as $key => $value) {
                if ($value !== null) {
                    $groups[$family][$key] += $value;
                }
            }
        }

        $ordered = [];
        foreach (['JABON', 'PULAI', 'RAMBUNG'] as $family) {
            if (isset($groups[$family])) {
                $ordered[] = $groups[$family];
            }
        }

        foreach ($ordered as $index => &$row) {
            $row['No'] = $index + 1;
        }
        unset($row);

        return $ordered;
    }

    /**
     * @param array<int, array<string, mixed>> $sourceRows
     * @param array<int, string> $columns
     * @return array<int, array<string, mixed>>
     */
    private function buildInputRowsGroupedByFamily(array $sourceRows, array $columns): array
    {
        $groups = [];

        foreach ($sourceRows as $row) {
            $family = $this->woodFamilyFromJenis((string) ($row['Jenis'] ?? ''));
            if (!isset($groups[$family])) {
                $groups[$family] = ['Jenis' => $family];
                foreach ($columns as $column) {
                    $groups[$family][$column] = 0.0;
                }
            }

            foreach ($columns as $column) {
                $value = $this->toFloat($row[$column] ?? null);
                if ($value !== null) {
                    $groups[$family][$column] += $value;
                }
            }
        }

        $ordered = [];
        foreach (['JABON', 'PULAI', 'RAMBUNG'] as $family) {
            if (isset($groups[$family]) && $this->hasNonZeroMetric($groups[$family], $columns)) {
                $ordered[] = $groups[$family];
            }
        }

        foreach ($ordered as $index => &$row) {
            $row['No'] = $index + 1;
        }
        unset($row);

        return $ordered;
    }

    /**
     * @param array<int, array<string, mixed>> $sourceRows
     * @param array<int, string> $columns
     * @return array<int, array<string, mixed>>
     */
    private function buildInputRowsDetailed(array $sourceRows, array $columns): array
    {
        $rows = [];

        foreach ($sourceRows as $row) {
            $entry = [
                'Jenis' => $this->cleanInputJenis((string) ($row['Jenis'] ?? '')),
            ];

            foreach ($columns as $column) {
                $entry[$column] = $this->toFloat($row[$column] ?? null);
            }

            if ($this->hasNonZeroMetric($entry, $columns)) {
                $rows[] = $entry;
            }
        }

        foreach ($rows as $index => &$row) {
            $row['No'] = $index + 1;
        }
        unset($row);

        return $rows;
    }

    /**
     * @param array<int, array<string, mixed>> $mainRows
     * @param array<int, array<string, mixed>> $inputRows
     * @param array<int, string> $inputColumns
     * @return array<string, mixed>
     */
    private function buildProductionSection(
        string $key,
        string $title,
        array $mainRows,
        string $inputTitle,
        array $inputRows,
        array $inputColumns,
        string $summaryLeftLabel,
        string $summaryRightLabel,
        string $outputColumn,
    ): array {
        $section = [
            'key' => $key,
            'title' => $title,
            'value_format' => 'decimal4',
            'columns' => [
                'No' => 'No',
                'Jenis' => str_contains($title, 'S4S') ? 'Jenis Kayu' : 'Jenis',
                'Awal' => 'Awal',
                'Masuk' => 'Masuk',
                'Jual' => 'Jual',
                'Keluar' => 'Keluar',
                'Akhir' => 'Akhir',
            ],
            'rows' => $mainRows,
            'totals' => $this->sumColumns($mainRows, ['Awal', 'Masuk', 'Jual', 'Keluar', 'Akhir']),
        ];

        $section['input_table'] = $this->buildInputTable($inputTitle, $inputRows, $inputColumns);
        $section['performance'] = $this->buildPerformanceBlock($section, $section['input_table'], $summaryLeftLabel, $summaryRightLabel, $outputColumn);

        return $section;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @param array<int, string> $columns
     * @return array<string, mixed>
     */
    private function buildInputTable(string $title, array $rows, array $columns): array
    {
        return [
            'title' => $title,
            'columns' => array_merge(['No' => 'No', 'Jenis' => 'Jenis'], array_combine($columns, $columns)),
            'rows' => $rows,
            'totals' => $this->sumColumns($rows, $columns),
        ];
    }

    /**
     * @param array<string, mixed> $section
     * @param array<string, mixed> $inputTable
     * @return array<string, mixed>
     */
    private function buildPerformanceBlock(array $section, array $inputTable, string $leftLabel, string $rightLabel, string $outputColumn): array
    {
        $input = array_sum(array_map(
            fn(string $column): float => (float) ($inputTable['totals'][$column] ?? 0.0),
            array_keys(array_filter($inputTable['columns'], static fn(string $label, string $key): bool => !in_array($key, ['No', 'Jenis'], true), ARRAY_FILTER_USE_BOTH)),
        ));
        $output = (float) ($section['totals'][$outputColumn] ?? 0.0);

        return [
            'left_label' => $leftLabel,
            'right_label' => $rightLabel,
            'input' => $input,
            'output' => $output,
            'rendemen' => $input > 0.0 ? ($output / $input) * 100.0 : null,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @param array<int, string> $columns
     * @return array<string, float>
     */
    private function sumColumns(array $rows, array $columns): array
    {
        $totals = array_fill_keys($columns, 0.0);

        foreach ($rows as $row) {
            foreach ($columns as $column) {
                $value = $this->toFloat($row[$column] ?? null);
                if ($value !== null) {
                    $totals[$column] += $value;
                }
            }
        }

        return $totals;
    }

    /**
     * @param array<string, mixed> $row
     * @param array<int, string> $keys
     */
    private function sumValues(array $row, array $keys): ?float
    {
        $total = 0.0;
        $found = false;

        foreach ($keys as $key) {
            $value = $this->toFloat($row[$key] ?? null);
            if ($value !== null) {
                $total += $value;
                $found = true;
            }
        }

        return $found ? $total : null;
    }

    /**
     * @param array<string, mixed> $row
     * @param array<int, string> $columns
     */
    private function hasNonZeroMetric(array $row, array $columns): bool
    {
        foreach ($columns as $column) {
            if (abs((float) ($row[$column] ?? 0.0)) > 0.0000001) {
                return true;
            }
        }

        return false;
    }

    private function woodFamilyFromJenis(string $label): string
    {
        $normalized = strtoupper($label);

        return match (true) {
            str_contains($normalized, 'JABON') => 'JABON',
            str_contains($normalized, 'PULAI') => 'PULAI',
            str_contains($normalized, 'RAMBUNG') => 'RAMBUNG',
            default => trim($normalized) !== '' ? trim($normalized) : '-',
        };
    }

    private function formatKayuBulatJenis(string $label): string
    {
        $label = $this->stripPrefix($label, 'KB ');
        $label = str_replace(' - ', ' ', $label);
        $label = str_replace('MC MATA', 'MC-MATA', $label);

        return $label;
    }

    private function cleanInputJenis(string $label): string
    {
        $label = $this->stripPrefix($label, 'ST ');
        $label = $this->stripPrefix($label, 'S4S ');
        $label = $this->stripPrefix($label, 'FJ ');
        $label = $this->stripPrefix($label, 'MLD ');
        $label = $this->stripPrefix($label, 'LMT ');
        $label = $this->stripPrefix($label, 'CCA ');
        $label = $this->stripPrefix($label, 'SND ');
        $label = $this->stripPrefix($label, 'BJ ');

        return trim($label);
    }

    private function stripPrefix(string $label, string $prefix): string
    {
        return str_starts_with($label, $prefix) ? substr($label, strlen($prefix)) : $label;
    }

    private function compareByOrder(string $left, string $right, array $order): int
    {
        $leftIndex = array_search($left, $order, true);
        $rightIndex = array_search($right, $order, true);

        $leftIndex = $leftIndex === false ? PHP_INT_MAX : $leftIndex;
        $rightIndex = $rightIndex === false ? PHP_INT_MAX : $rightIndex;

        return $leftIndex <=> $rightIndex ?: strcmp($left, $right);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchRawRekapMutasi(string $startDate, string $endDate): array
    {
        $connection = DB::connection(config('reports.rekap_mutasi.database_connection'));
        $procedure = (string) config('reports.rekap_mutasi.stored_procedure', 'SP_LapRekapMutasi');

        if (!preg_match('/^[A-Za-z0-9_$.]+$/', $procedure)) {
            throw new RuntimeException('Nama stored procedure tidak valid.');
        }

        $rows = $connection->select("SET NOCOUNT ON; EXEC {$procedure} ?, ?", [$startDate, $endDate]);

        return array_map(static fn(object $row): array => (array) $row, $rows);
    }

    private function toFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (float) $value : null;
    }
}

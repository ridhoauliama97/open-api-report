<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Kontrak bersama untuk seluruh laporan "produksi per nomor produksi".
 *
 * Semua laporan dalam keluarga ini memanggil SP berbentuk sama (17 kolom, sama
 * dengan flag IsReject/IsRepair), jadi perilaku partisi Input / Output /
 * Repair / Afkir harus identik. Subkelas hanya perlu menyatakan service dan
 * view PDF-nya masing-masing.
 */
abstract class ProduksiPerNomorProduksiReportTestCase extends TestCase
{
    /**
     * Service laporan yang diuji.
     */
    abstract protected function service(): object;

    /**
     * View PDF laporan yang diuji.
     */
    abstract protected function pdfView(): string;

    /**
     * Baris SPVqwe sintetis mengikuti bentuk output SPWps_LapProduksi*.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function spRow(string $type, string $label, array $overrides = []): array
    {
        return array_merge([
            'NoProduksi' => 'SA.000001',
            'NamaOperator' => 'BUDI',
            'NamaMesin' => 'MESHIN-1',
            'Tanggal' => '2026-09-23',
            'Shift' => 1,
            'JamKerja' => 8,
            'JmlhAnggota' => 12,
            'Group' => $type === 'Input' ? 'S4S' : 'FJ',
            'Type' => $type,
            'NoLabel' => $label,
            'Tebal' => 45,
            'Lebar' => 50,
            'Panjang' => 4550,
            'JmlhBatang' => 100,
            'Kubik' => 1.5,
            'IsReject' => '0',
            'IsRepair' => null,
        ], $overrides);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<string, mixed>
     */
    protected function build(array $rows): array
    {
        return $this->service()->buildReportData('SA.000001', $rows);
    }

    public function test_reject_rows_are_partitioned_into_afkir_and_removed_from_output(): void
    {
        $report = $this->build([
            $this->spRow('Input', 'R.000001'),
            $this->spRow('Output', 'S.000001'),
            $this->spRow('Output', 'S.000002', ['IsReject' => '1', 'Kubik' => 0.75]),
        ]);

        $this->assertSame(['R.000001'], array_column($report['input_rows'], 'no_label'));
        $this->assertSame(['S.000001'], array_column($report['output_rows'], 'no_label'));
        $this->assertSame(['S.000002'], array_column($report['afkir_rows'], 'no_label'));
        $this->assertSame([], $report['repair_rows']);
    }

    public function test_reject_rows_are_partitioned_into_afkir_and_removed_from_input(): void
    {
        $report = $this->build([
            $this->spRow('Input', 'R.000001'),
            $this->spRow('Input', 'R.000002', ['IsReject' => '1']),
            $this->spRow('Output', 'S.000001'),
        ]);

        $this->assertSame(['R.000001'], array_column($report['input_rows'], 'no_label'));
        $this->assertSame(['R.000002'], array_column($report['afkir_rows'], 'no_label'));
    }

    public function test_repair_rows_are_partitioned_into_repair_bucket(): void
    {
        $report = $this->build([
            $this->spRow('Input', 'R.000001'),
            $this->spRow('Output', 'S.000001', ['IsRepair' => '1']),
        ]);

        $this->assertSame(['S.000001'], array_column($report['repair_rows'], 'no_label'));
        $this->assertSame([], $report['output_rows'], 'Baris repair dikeluarkan dari tabel Output.');
        $this->assertSame(['R.000001'], array_column($report['input_rows'], 'no_label'));
        $this->assertSame([], $report['afkir_rows']);
    }

    public function test_repair_flag_takes_precedence_over_reject_flag(): void
    {
        $report = $this->build([
            $this->spRow('Output', 'S.000001', ['IsRepair' => '1', 'IsReject' => '1']),
        ]);

        $this->assertSame(['S.000001'], array_column($report['repair_rows'], 'no_label'));
        $this->assertSame([], $report['afkir_rows']);
    }

    public function test_boolean_and_string_flag_values_are_both_recognised(): void
    {
        $report = $this->build([
            $this->spRow('Output', 'S.000001', ['IsReject' => true]),
            $this->spRow('Output', 'S.000002', ['IsRepair' => '1']),
            $this->spRow('Output', 'S.000003', ['IsReject' => false, 'IsRepair' => false]),
        ]);

        $this->assertSame(['S.000001'], array_column($report['afkir_rows'], 'no_label'));
        $this->assertSame(['S.000002'], array_column($report['repair_rows'], 'no_label'));
        $this->assertSame(['S.000003'], array_column($report['output_rows'], 'no_label'));
    }

    public function test_blank_flag_value_is_treated_as_not_set(): void
    {
        // Branch WIP mengirim string kosong karena WIP_h tidak punya kolom IsRepair.
        $report = $this->build([
            $this->spRow('Input', 'W.000001', ['Group' => 'WIP', 'IsRepair' => '']),
            $this->spRow('Input', 'W.000002', ['Group' => 'WIP', 'IsReject' => '']),
        ]);

        $this->assertSame(
            ['W.000001', 'W.000002'],
            array_column($report['input_rows'], 'no_label')
        );
        $this->assertSame([], $report['repair_rows']);
        $this->assertSame([], $report['afkir_rows']);
    }

    public function test_every_sp_row_lands_in_exactly_one_bucket(): void
    {
        $rows = [
            $this->spRow('Input', 'R.000001'),
            $this->spRow('Input', 'R.000002', ['IsReject' => '1']),
            $this->spRow('Output', 'S.000001'),
            $this->spRow('Output', 'S.000002', ['IsRepair' => '1']),
            $this->spRow('Output', 'S.000003', ['IsReject' => '1']),
        ];

        $report = $this->build($rows);

        $bucketCount = count($report['input_rows'])
            + count($report['output_rows'])
            + count($report['repair_rows'])
            + count($report['afkir_rows']);

        $this->assertSame(count($rows), $bucketCount);

        $labels = array_merge(
            array_column($report['input_rows'], 'no_label'),
            array_column($report['output_rows'], 'no_label'),
            array_column($report['repair_rows'], 'no_label'),
            array_column($report['afkir_rows'], 'no_label'),
        );

        $this->assertCount(5, array_unique($labels), 'Label harus muncul tepat satu kali di seluruh bucket.');
    }

    public function test_rendemen_uses_input_and_output_totals_only(): void
    {
        $rows = [
            $this->spRow('Input', 'R.000001', ['Kubik' => 10.0]),
            $this->spRow('Output', 'S.000001', ['Kubik' => 8.0]),
            $this->spRow('Output', 'S.000002', ['IsReject' => '1', 'Kubik' => 2.0]),
            $this->spRow('Output', 'S.000003', ['IsRepair' => '1', 'Kubik' => 1.0]),
        ];

        $report = $this->build($rows);

        $this->assertSame(10.0, $report['totals']['input']['kubik']);
        $this->assertSame(8.0, $report['totals']['output']['kubik']);
        $this->assertSame(2.0, $report['totals']['afkir']['kubik']);
        $this->assertSame(1.0, $report['totals']['repair']['kubik']);

        // Rendemen = 8 / 10, bukan (8 + 2 + 1) / (10 + 2)
        $this->assertEqualsWithDelta(80.0, $report['totals']['rendemen'], 0.0001);
    }

    public function test_repair_and_afkir_totals_default_to_zero_when_absent(): void
    {
        $report = $this->build([$this->spRow('Output', 'S.000001')]);

        $this->assertSame(
            ['count' => 0, 'jmlh_batang' => 0, 'kubik' => 0.0],
            $report['totals']['repair']
        );
        $this->assertSame(
            ['count' => 0, 'jmlh_batang' => 0, 'kubik' => 0.0],
            $report['totals']['afkir']
        );
    }

    public function test_pdf_view_renders_four_detail_tables_with_repair_and_afkir_sections(): void
    {
        $report = $this->build([
            $this->spRow('Input', 'R.000001'),
            $this->spRow('Output', 'S.000001'),
            $this->spRow('Output', 'S.000002', ['IsReject' => '1']),
        ]);

        $html = view($this->pdfView(), [
            'report' => $report,
            'generatedBy' => null,
            'generatedAt' => now(),
            'pdf_simple_tables' => false,
        ])->render();

        $this->assertSame(4, substr_count($html, '<table class="detail-table">'));
        $this->assertSame(4, substr_count($html, 'Total :'));
        $this->assertStringContainsString('Data repair tidak tersedia.', $html);
        $this->assertStringNotContainsString('Data afkir tidak tersedia.', $html);
        $this->assertStringContainsString('S.000002', $html);
    }

    public function test_pdf_view_paginates_when_second_table_pair_has_data(): void
    {
        $rows = [];
        for ($i = 1; $i <= 30; $i++) {
            $rows[] = $this->spRow('Input', sprintf('R.%04d', $i));
            $rows[] = $this->spRow('Output', sprintf('S.%04d', $i));
            $rows[] = $this->spRow('Output', sprintf('A.%04d', $i), ['IsReject' => '1']);
        }

        $report = $this->build($rows);

        $html = view($this->pdfView(), [
            'report' => $report,
            'generatedBy' => null,
            'generatedAt' => now(),
            'pdf_simple_tables' => false,
        ])->render();

        $this->assertGreaterThan(1, substr_count($html, 'class="page-block'), 'Data manyak harus dipaginasi.');
        $this->assertSame(4, substr_count($html, 'Total :'), 'Baris total hanya di halaman terakhir tiap tabel.');
    }
}

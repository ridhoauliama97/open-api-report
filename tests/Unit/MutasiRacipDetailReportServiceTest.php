<?php

namespace Tests\Unit;

use App\Services\MutasiRacipDetailReportService;
use Tests\TestCase;

class MutasiRacipDetailReportServiceTest extends TestCase
{
    public function test_rows_without_balance_or_movement_values_are_hidden_from_report_data(): void
    {
        config()->set('reports.mutasi_racip_detail.database_connection', null);
        config()->set('reports.mutasi_racip_detail.call_syntax', 'query');
        config()->set('reports.mutasi_racip_detail.query', implode(' UNION ALL ', [
            $this->selectMutasiRow("'RACIP AKTIF'", '1.25', '0', '0', '0'),
            $this->selectMutasiRow("'RACIP KOSONG'", '0', '0', '0', '0'),
            $this->selectMutasiRow("'RACIP MINUS KECIL'", '-3.46944695195361E-18', '0', '0', '0'),
            $this->selectMutasiRow("'RACIP MASUK'", '0', '2.5', '0', '0'),
            $this->selectMutasiRow("'RACIP AKHIR'", '0', '0', '0', '3.75'),
        ]));

        $reportData = (new MutasiRacipDetailReportService)->buildReportData('2026-01-01', '2026-01-31');

        $this->assertSame(['RACIP AKTIF', 'RACIP MASUK', 'RACIP AKHIR'], array_column($reportData['rows'], 'Jenis'));
        $this->assertSame(1.25, $reportData['totals']['Sawal']);
        $this->assertSame(2.5, $reportData['totals']['Masuk']);
        $this->assertSame(3.75, $reportData['totals']['Akhir']);
    }

    public function test_columns_and_totals_still_use_original_source_rows_when_display_rows_are_empty(): void
    {
        config()->set('reports.mutasi_racip_detail.database_connection', null);
        config()->set('reports.mutasi_racip_detail.call_syntax', 'query');
        config()->set('reports.mutasi_racip_detail.query', $this->selectMutasiRow("'RACIP KOSONG'", '0', '0', '0', '0'));

        $reportData = (new MutasiRacipDetailReportService)->buildReportData('2026-01-01', '2026-01-31');

        $this->assertSame([], $reportData['rows']);
        $this->assertContains('Sawal', $reportData['columns']);
        $this->assertArrayHasKey('Sawal', $reportData['totals']);
        $this->assertSame(0.0, $reportData['totals']['Sawal']);
    }

    public function test_period_dates_are_kept_in_iso_format_for_pdf_rendering(): void
    {
        config()->set('reports.mutasi_racip_detail.database_connection', null);
        config()->set('reports.mutasi_racip_detail.call_syntax', 'query');
        config()->set('reports.mutasi_racip_detail.query', $this->selectMutasiRow("'RACIP AKTIF'", '1.25', '0', '0', '0'));

        $reportData = (new MutasiRacipDetailReportService)->buildReportData('2026-05-01', '2026-05-12');

        $this->assertSame('2026-05-01', $reportData['start_date_text']);
        $this->assertSame('2026-05-12', $reportData['end_date_text']);
    }

    public function test_decimal_dot_values_from_sql_server_are_not_treated_as_thousands(): void
    {
        config()->set('reports.mutasi_racip_detail.database_connection', null);
        config()->set('reports.mutasi_racip_detail.call_syntax', 'query');
        config()->set('reports.mutasi_racip_detail.query', implode(' UNION ALL ', [
            $this->selectMutasiRow("'RACIP DECIMAL A'", '0.107', '0.002', '0.378', '0.001'),
            $this->selectMutasiRow("'RACIP DECIMAL B'", '0.001', '0.016', '0.154', '0.002'),
        ]));

        $reportData = (new MutasiRacipDetailReportService)->buildReportData('2026-05-01', '2026-05-12');

        $this->assertSame(0.108, $reportData['totals']['Sawal']);
        $this->assertSame(0.018, $reportData['totals']['Masuk']);
        $this->assertSame(0.532, $reportData['totals']['Keluar']);
        $this->assertSame(0.003, $reportData['totals']['Akhir']);
    }

    private function selectMutasiRow(string $jenis, string $sawal, string $masuk, string $keluar, string $akhir): string
    {
        return "SELECT {$jenis} AS Jenis, 10 AS Tebal, 20 AS Lebar, 8 AS Panjang, "
            ."{$sawal} AS Sawal, 0 AS SawalJlhBtg, {$masuk} AS Masuk, 0 AS MskJlhBtg, "
            ."0 AS AdjusmentOutput, 0 AS AdjOutJlhBtg, {$keluar} AS Keluar, 0 AS KeluarJlhBtg, "
            ."0 AS AdjusmentInput, 0 AS AdjInJlhBtg, {$akhir} AS Akhir, 0 AS AkhirJlhBtg";
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\PdfGenerator;
use App\Services\RekapPembelianKayuBulatReportService;
use Mockery;
use Tests\TestCase;

class RekapPembelianKayuBulatReportFeatureTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_form_page_is_accessible(): void
    {
        $service = Mockery::mock(RekapPembelianKayuBulatReportService::class);
        $service
            ->shouldReceive('buildReportData')
            ->once()
            ->andReturn([
                'rows' => [],
                'columns' => ['date' => null, 'type' => null, 'amount' => null],
                'dates' => [],
                'types' => [],
                'series_by_type' => [],
                'totals_by_type' => [],
                'daily_totals' => [],
                'table_rows' => [],
                'grand_total' => 0.0,
                'chart_years' => [2025, 2026],
                'chart_month_labels' => ['Jan'],
                'chart_series_by_year' => [],
                'yearly_totals' => [2025 => 0, 2026 => 0],
            ]);

        $this->app->instance(RekapPembelianKayuBulatReportService::class, $service);

        $this->get('/reports/kayu-bulat/rekap-pembelian?start_year=2025&end_year=2026')
            ->assertOk()
            ->assertSee('Rekap Pembelian Kayu Bulat')
            ->assertSee('Preview PDF');
    }

    public function test_pdf_download_endpoint_returns_attachment(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(RekapPembelianKayuBulatReportService::class);
        $service
            ->shouldReceive('buildReportData')
            ->once()
            ->with('2025-01-01', '2026-12-31')
            ->andReturn([
                'rows' => [],
                'table_rows' => [],
                'grand_total' => 0.0,
                'chart_years' => [2025, 2026],
                'chart_month_labels' => ['Jan'],
                'chart_series_by_year' => [2025 => [0], 2026 => [0]],
                'yearly_totals' => [2025 => 0, 2026 => 0],
            ]);

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(RekapPembelianKayuBulatReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $this->actingAs($user)
            ->get('/reports/kayu-bulat/rekap-pembelian/download?start_year=2025&end_year=2026')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'attachment; filename="Laporan-Rekap-Pembelian-Kayu-Bulat-2025-sd-2026.pdf"');
    }

    public function test_pdf_preview_endpoint_returns_inline_disposition(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(RekapPembelianKayuBulatReportService::class);
        $service
            ->shouldReceive('buildReportData')
            ->once()
            ->with('2025-01-01', '2026-12-31')
            ->andReturn([
                'rows' => [],
                'table_rows' => [],
                'grand_total' => 0.0,
                'chart_years' => [2025, 2026],
                'chart_month_labels' => ['Jan'],
                'chart_series_by_year' => [2025 => [0], 2026 => [0]],
                'yearly_totals' => [2025 => 0, 2026 => 0],
            ]);

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(RekapPembelianKayuBulatReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $this->actingAs($user)
            ->get('/reports/kayu-bulat/rekap-pembelian/download?start_year=2025&end_year=2026&preview_pdf=1')
            ->assertOk()
            ->assertHeader('Content-Disposition', 'inline; filename="Laporan-Rekap-Pembelian-Kayu-Bulat-2025-sd-2026.pdf"');
    }
}


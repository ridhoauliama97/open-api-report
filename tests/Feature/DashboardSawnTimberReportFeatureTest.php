<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\DashboardSawnTimberReportService;
use App\Services\PdfGenerator;
use Mockery;
use Tests\TestCase;

class DashboardSawnTimberReportFeatureTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_dashboard_form_page_is_accessible(): void
    {
        $service = Mockery::mock(DashboardSawnTimberReportService::class);
        $service
            ->shouldReceive('buildChartData')
            ->once()
            ->andReturn([
                'dates' => [],
                'types' => [],
                'series_by_type' => [],
                'totals_by_type' => [],
                'daily_in_totals' => [],
                'daily_out_totals' => [],
                'stock_by_type' => [],
                'stock_totals' => ['s_akhir' => 0.0, 'ctr' => 0.0],
                'column_mapping' => ['date' => null, 'type' => null, 'in' => null, 'out' => null],
                'raw_rows' => [],
            ]);

        $this->app->instance(DashboardSawnTimberReportService::class, $service);

        $this->get('/dashboard/sawn-timber?start_date=2026-01-01&end_date=2026-01-31')
            ->assertOk()
            ->assertSee('Dashboard Sawn Timber')
            ->assertSee('Preview PDF');
    }

    public function test_dashboard_pdf_download_returns_attachment(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(DashboardSawnTimberReportService::class);
        $service
            ->shouldReceive('buildChartData')
            ->once()
            ->with('2026-01-01', '2026-01-31')
            ->andReturn([
                'dates' => ['2026-01-01'],
                'types' => ['JABON'],
                'series_by_type' => ['JABON' => ['in' => [1], 'out' => [0.5]]],
                'totals_by_type' => ['JABON' => ['in' => 1.0, 'out' => 0.5]],
                'daily_in_totals' => [1.0],
                'daily_out_totals' => [0.5],
                'stock_by_type' => ['JABON' => ['s_akhir' => 0.5, 'ctr' => 0.01]],
                'stock_totals' => ['s_akhir' => 0.5, 'ctr' => 0.01],
                'column_mapping' => ['date' => 'DATE', 'type' => 'Jenis', 'in' => 'Masuk', 'out' => 'Keluar'],
                'raw_rows' => [['DATE' => '2026-01-01', 'Jenis' => 'JABON', 'Masuk' => 1, 'Keluar' => 0.5]],
            ]);

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(DashboardSawnTimberReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $this->actingAs($user)
            ->get('/dashboard/sawn-timber/download?start_date=2026-01-01&end_date=2026-01-31')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'attachment; filename="Dashboard-Sawn-Timber-2026-01-01-sd-2026-01-31.pdf"');
    }

    public function test_dashboard_pdf_preview_returns_inline_disposition(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(DashboardSawnTimberReportService::class);
        $service
            ->shouldReceive('buildChartData')
            ->once()
            ->with('2026-01-01', '2026-01-31')
            ->andReturn([
                'dates' => [],
                'types' => [],
                'series_by_type' => [],
                'totals_by_type' => [],
                'daily_in_totals' => [],
                'daily_out_totals' => [],
                'stock_by_type' => [],
                'stock_totals' => ['s_akhir' => 0.0, 'ctr' => 0.0],
                'column_mapping' => ['date' => null, 'type' => null, 'in' => null, 'out' => null],
                'raw_rows' => [],
            ]);

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(DashboardSawnTimberReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $this->actingAs($user)
            ->get('/dashboard/sawn-timber/download?start_date=2026-01-01&end_date=2026-01-31&preview_pdf=1')
            ->assertOk()
            ->assertHeader('Content-Disposition', 'inline; filename="Dashboard-Sawn-Timber-2026-01-01-sd-2026-01-31.pdf"');
    }
}


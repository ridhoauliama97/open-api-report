<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\PdfGenerator;
use App\Services\TargetMasukBBReportService;
use Mockery;
use Tests\TestCase;

class TargetMasukBBReportFeatureTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_target_masuk_bb_form_page_is_accessible(): void
    {
        $service = Mockery::mock(TargetMasukBBReportService::class);
        $service
            ->shouldReceive('buildReportData')
            ->once()
            ->andReturn([
                'rows' => [],
                'columns' => ['jenis' => 'Jenis', 'target_harian' => 'Tgt Hari', 'target_bulanan' => 'Tgt Bulan', 'total' => 'Total'],
                'day_columns' => [],
                'lb_columns' => [],
                'table_rows' => [],
                'summary_rows' => [],
                'chart_labels' => [],
                'chart_series' => [],
                'period_text' => 'Dari 01/02/2026 Sampai 28/02/2026',
            ]);

        $this->app->instance(TargetMasukBBReportService::class, $service);

        $this->get('/reports/kayu-bulat/target-masuk-bb')
            ->assertOk()
            ->assertSee('Laporan Target Masuk Bahan Baku Harian');
    }

    public function test_target_masuk_bb_preview_endpoint_returns_json_data(): void
    {
        $service = Mockery::mock(TargetMasukBBReportService::class);
        $service
            ->shouldReceive('buildReportData')
            ->once()
            ->with('2026-02-01', '2026-02-28')
            ->andReturn([
                'rows' => [
                    ['Jenis' => 'JABON', 'Tgt Hari' => 8, 'Tgt Bulan' => 200, '01' => 6, '02' => 0, 'Total' => 6],
                ],
                'columns' => ['jenis' => 'Jenis', 'target_harian' => 'Tgt Hari', 'target_bulanan' => 'Tgt Bulan', 'total' => 'Total'],
                'day_columns' => [['key' => '01', 'day' => 1], ['key' => '02', 'day' => 2]],
                'lb_columns' => ['LB'],
                'table_rows' => [],
                'summary_rows' => [],
                'chart_labels' => ['01', '02'],
                'chart_series' => ['JABON' => [6, 0]],
                'period_text' => 'Dari 01/02/2026 Sampai 28/02/2026',
            ]);

        $this->app->instance(TargetMasukBBReportService::class, $service);

        $this->getJson('/reports/kayu-bulat/target-masuk-bb/preview?start_date=2026-02-01&end_date=2026-02-28')
            ->assertOk()
            ->assertJsonPath('message', 'Data target masuk bahan baku berhasil diambil.')
            ->assertJsonPath('meta.total_rows', 1)
            ->assertJsonPath('meta.start_date', '2026-02-01')
            ->assertJsonPath('meta.end_date', '2026-02-28')
            ->assertJsonPath('data.chart_labels.0', '01');
    }

    public function test_target_masuk_bb_pdf_download_returns_attachment(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(TargetMasukBBReportService::class);
        $service
            ->shouldReceive('buildReportData')
            ->once()
            ->with('2026-01-01', '2026-01-31')
            ->andReturn([
                'rows' => [],
                'columns' => ['jenis' => 'NamaGroup', 'target_harian' => 'TgtPerHari', 'target_bulanan' => 'TargetBulanan', 'total' => null, 'hasil' => 'hasil', 'date' => 'Date', 'keterangan' => 'Keterangan'],
                'day_columns' => [['day' => 1, 'label' => '01', 'is_lb_after' => false]],
                'lb_columns' => [],
                'table_rows' => [['jenis' => 'JABON', 'target_harian' => 8, 'target_bulanan' => 200, 'daily_values' => [0], 'lb_values' => [], 'total' => 0]],
                'summary_rows' => [['jenis' => 'JABON', 'avg' => 0, 'min' => 0, 'max' => 0]],
                'chart_labels' => ['01'],
                'chart_series' => ['JABON' => [0]],
                'period_text' => 'Dari 01/01/2026 Sampai 31/01/2026',
                'group_column' => 'NamaGroup',
            ]);

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(TargetMasukBBReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $this->actingAs($user)
            ->get('/reports/kayu-bulat/target-masuk-bb/download?start_date=2026-01-01&end_date=2026-01-31')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'attachment; filename="Laporan-Target-Masuk-BB-2026-01-01-sd-2026-01-31.pdf"');
    }
}

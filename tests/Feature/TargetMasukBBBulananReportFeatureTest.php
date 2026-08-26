<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\PdfGenerator;
use App\Services\TargetMasukBBBulananReportService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class TargetMasukBBBulananReportFeatureTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_target_masuk_bb_bulanan_form_page_is_accessible(): void
    {
        $service = Mockery::mock(TargetMasukBBBulananReportService::class);
        $service
            ->shouldReceive('buildReportData')
            ->once()
            ->andReturn([
                'rows' => [],
                'month_columns' => [],
                'table_rows' => [],
                'summary_rows' => [],
                'chart_labels' => [],
                'chart_series' => [],
                'period_text' => 'Dari 01/01/2026 Sampai 31/12/2026',
            ]);

        $this->app->instance(TargetMasukBBBulananReportService::class, $service);

        $this->get('/reports/kayu-bulat/target-masuk-bb-bulanan')
            ->assertOk()
            ->assertSee('Laporan Target Masuk Bahan Baku Bulanan');
    }

    public function test_target_masuk_bb_bulanan_preview_endpoint_returns_json_data(): void
    {
        $service = Mockery::mock(TargetMasukBBBulananReportService::class);
        $service
            ->shouldReceive('buildReportData')
            ->once()
            ->with('2026-01-01', '2026-12-31')
            ->andReturn([
                'rows' => [
                    ['Tahun' => 2026, 'Bulan' => 1, 'NamaGroup' => 'JABON', 'hasil' => 53.8],
                ],
                'month_columns' => [['key' => '2026-01', 'label' => 'JAN-26']],
                'table_rows' => [],
                'summary_rows' => [],
                'chart_labels' => ['JAN-26'],
                'chart_series' => ['JABON' => [54]],
                'period_text' => 'Dari 01/01/2026 Sampai 31/12/2026',
            ]);

        $this->app->instance(TargetMasukBBBulananReportService::class, $service);

        $this->getJson('/reports/kayu-bulat/target-masuk-bb-bulanan/preview?start_date=2026-01-01&end_date=2026-12-31')
            ->assertOk()
            ->assertJsonPath('message', 'Data target masuk bahan baku bulanan berhasil diambil.')
            ->assertJsonPath('meta.total_rows', 1)
            ->assertJsonPath('meta.start_date', '2026-01-01')
            ->assertJsonPath('meta.end_date', '2026-12-31')
            ->assertJsonPath('data.chart_labels.0', 'JAN-26');
    }

    public function test_target_masuk_bb_bulanan_pdf_download_returns_attachment(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(TargetMasukBBBulananReportService::class);
        $service
            ->shouldReceive('buildReportData')
            ->once()
            ->with('2026-01-01', '2026-12-31')
            ->andReturn([
                'rows' => [],
                'month_columns' => [['key' => '2026-01', 'label' => 'JAN-26']],
                'table_rows' => [['jenis' => 'JABON', 'target_bulanan' => 200, 'monthly_values' => [54], 'total' => 54]],
                'summary_rows' => [['jenis' => 'JABON', 'avg' => 54, 'min' => 54, 'max' => 54, 'bulan_capai' => 0, 'total_bulan_target' => 1, 'persen_capai_group' => 0]],
                'chart_labels' => ['JAN-26'],
                'chart_series' => ['JABON' => [54]],
                'period_text' => 'Dari 01/01/2026 Sampai 31/12/2026',
            ]);

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('renderHtml')
            ->once()
            ->andReturn('<html><body>mocked HTML</body></html>');
        $pdfGenerator
            ->shouldReceive('paperMetrics')
            ->once()
            ->andReturn([
                'paper_width' => '21.0cm',
                'paper_height' => '29.7cm',
                'landscape' => false,
            ]);

        Http::fake([
            'http://localhost:3000/*' => Http::sequence()
                ->push('%PDF-1.4 mocked content', 200, ['Content-Type' => 'application/pdf']),
        ]);

        $this->app->instance(TargetMasukBBBulananReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->actingAs($user)
            ->get('/reports/kayu-bulat/target-masuk-bb-bulanan/download?start_date=2026-01-01&end_date=2026-12-31')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'attachment', 'Laporan Target Masuk BB Bulanan');

        Http::assertSent(fn (Request $request): bool => $request->url() === config('services.gotenberg.url').'/forms/chromium/convert/html'
            && $request->method() === 'POST');
    }
}

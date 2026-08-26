<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\MutasiRacipDetailReportService;
use App\Services\PdfGenerator;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class MutasiRacipDetailReportFeatureTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_form_page_is_accessible(): void
    {
        $this->get('/reports/mutasi-racip-detail')
            ->assertOk()
            ->assertSee('Generate Laporan Mutasi Racip Detail (PDF)');
    }

    public function test_preview_endpoint_returns_json_data(): void
    {
        $service = Mockery::mock(MutasiRacipDetailReportService::class);
        $service
            ->shouldReceive('buildReportData')
            ->once()
            ->with('2026-01-01', '2026-01-31')
            ->andReturn([
                'rows' => [
                    ['Jenis' => 'RACIP KAYU LAT JABON', 'Tebal' => 10, 'Akhir' => 1.2],
                ],
                'columns' => ['Jenis', 'Tebal', 'Akhir'],
                'detail_columns' => ['Tebal', 'Akhir'],
                'grouped_rows' => [],
                'numeric_columns' => ['Tebal' => true, 'Akhir' => true],
                'totals' => ['Tebal' => 10, 'Akhir' => 1.2],
            ]);

        $this->app->instance(MutasiRacipDetailReportService::class, $service);

        $this->postJson('/reports/mutasi-racip-detail/preview', [
            'TglAwal' => '2026-01-01',
            'TglAkhir' => '2026-01-31',
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Preview laporan berhasil diambil.')
            ->assertJsonPath('meta.total_rows', 1)
            ->assertJsonPath('meta.start_date', '2026-01-01')
            ->assertJsonPath('totals.Akhir', 1.2);
    }

    public function test_pdf_download_endpoint_returns_attachment(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(MutasiRacipDetailReportService::class);
        $service
            ->shouldReceive('buildReportData')
            ->once()
            ->with('2026-01-01', '2026-01-31')
            ->andReturn([
                'rows' => [],
                'columns' => [],
                'detail_columns' => [],
                'grouped_rows' => [],
                'numeric_columns' => [],
                'totals' => [],
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

        $this->app->instance(MutasiRacipDetailReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->actingAs($user)
            ->post('/reports/mutasi-racip-detail/download', [
                'TglAwal' => '2026-01-01',
                'TglAkhir' => '2026-01-31',
            ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'attachment', 'Laporan Mutasi Racip Detail');

        Http::assertSent(fn (Request $request): bool => $request->url() === config('services.gotenberg.url').'/forms/chromium/convert/html'
            && $request->method() === 'POST');
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\PdfGenerator;
use App\Services\ProduksiPerSpkReportService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class ProduksiPerSpkReportFeatureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('reports.report_auth.issuers', []);
        config()->set('reports.report_auth.audiences', []);
        config()->set('reports.report_auth.required_scope', null);
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_produksi_per_spk_pdf_download_endpoint_returns_pdf(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(ProduksiPerSpkReportService::class);
        $service
            ->shouldReceive('buildReportData')
            ->once()
            ->with('2026-SPK-001')
            ->andReturn([
                'header' => ['NoSPK' => '2026-SPK-001'],
                'dimensions' => [],
                'rendemen_rows' => [],
                'alive_labels' => [],
                'miss_labels' => [],
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

        $this->app->instance(ProduksiPerSpkReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->actingAs($user)
            ->post('/reports/rendemen-kayu/produksi-per-spk/download', [
                'no_spk' => '2026-SPK-001',
            ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        // Endpoint download tetap inline; hanya previewPdf yang attachment (semantik asli).
        $this->assertPdfDisposition($response, 'inline', 'laporan produksi per spk');

        Http::assertSent(fn (Request $request): bool => $request->url() === config('services.gotenberg.url').'/forms/chromium/convert/html'
            && $request->method() === 'POST');
    }
}

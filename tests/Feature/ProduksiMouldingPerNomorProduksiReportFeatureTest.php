<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\PdfGenerator;
use App\Services\ProduksiMouldingPerNomorProduksiReportService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class ProduksiMouldingPerNomorProduksiReportFeatureTest extends TestCase
{
    /**
     * Execute set up logic.
     */
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('reports.report_auth.issuers', []);
        config()->set('reports.report_auth.audiences', []);
        config()->set('reports.report_auth.required_scope', null);
    }

    /**
     * Execute tear down logic.
     */
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    /**
     * Execute test produksi moulding per nomor produksi pdf download endpoint returns inline via gotenberg logic.
     */
    public function test_produksi_moulding_per_nomor_produksi_pdf_download_endpoint_returns_inline_via_gotenberg(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(ProduksiMouldingPerNomorProduksiReportService::class);
        $service
            ->shouldReceive('fetch')
            ->once()
            ->with('PRD-2026-001')
            ->andReturn([
                'meta' => ['no_produksi' => 'PRD-2026-001'],
                'input_rows' => [],
                'output_rows' => [],
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

        $this->app->instance(ProduksiMouldingPerNomorProduksiReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->actingAs($user)
            ->post('/reports/proses-produksi/produksi-moulding-per-nomor-produksi/download', [
                'no_produksi' => 'PRD-2026-001',
            ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'inline', 'laporan produksi moulding per nomor produksi');

        Http::assertSent(fn (Request $request): bool => $request->url() === config('services.gotenberg.url').'/forms/chromium/convert/html'
            && $request->method() === 'POST');
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\PdfGenerator;
use App\Services\RekapProduksiFingerJointConsolidatedReportService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class RekapProduksiFingerJointConsolidatedReportFeatureTest extends TestCase
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
     * Execute test rekap produksi finger joint consolidated pdf download endpoint returns attachment logic.
     */
    public function test_rekap_produksi_finger_joint_consolidated_pdf_download_endpoint_returns_attachment(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(RekapProduksiFingerJointConsolidatedReportService::class);
        $service
            ->shouldReceive('fetch')
            ->once()
            ->with('2026-01-01', '2026-01-31')
            ->andReturn($this->rows());

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

        $this->app->instance(RekapProduksiFingerJointConsolidatedReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->actingAs($user)
            ->post('/reports/finger-joint/rekap-produksi-finger-joint-consolidated/download', [
                'TglAwal' => '2026-01-01',
                'TglAkhir' => '2026-01-31',
            ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'attachment', 'laporan rekap produksi finger joint consolidated');

        Http::assertSent(fn (Request $request): bool => $request->url() === config('services.gotenberg.url').'/forms/chromium/convert/html'
            && $request->method() === 'POST');
    }

    /**
     * Execute test rekap produksi finger joint consolidated pdf preview endpoint keeps inline disposition logic.
     */
    public function test_rekap_produksi_finger_joint_consolidated_pdf_preview_endpoint_keeps_inline_disposition(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(RekapProduksiFingerJointConsolidatedReportService::class);
        $service
            ->shouldReceive('fetch')
            ->once()
            ->with('2026-01-01', '2026-01-31')
            ->andReturn($this->rows());

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

        $this->app->instance(RekapProduksiFingerJointConsolidatedReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->actingAs($user)
            ->post('/reports/finger-joint/rekap-produksi-finger-joint-consolidated/preview-pdf', [
                'TglAwal' => '2026-01-01',
                'TglAkhir' => '2026-01-31',
            ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'inline', 'laporan rekap produksi finger joint consolidated');

        Http::assertSent(fn (Request $request): bool => $request->url() === config('services.gotenberg.url').'/forms/chromium/convert/html'
            && $request->method() === 'POST');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function rows(): array
    {
        return [
            [
                'Tanggal' => '2026-01-01',
                'Shift' => 1,
                'NamaMesin' => 'MESIN A',
                'CCAkhir' => 10.5,
                'S4S' => 4.2,
                'TotalInput' => 14.7,
                'OutputFJ' => 13.9,
                'Jam' => 8,
                'Org' => 4,
                'M3Jam' => 1.7375,
                'M3JamOrg' => 0.434375,
                'Rend' => 94.56,
            ],
        ];
    }
}

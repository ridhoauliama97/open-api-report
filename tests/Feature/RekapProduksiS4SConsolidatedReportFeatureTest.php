<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\PdfGenerator;
use App\Services\RekapProduksiS4SConsolidatedReportService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class RekapProduksiS4SConsolidatedReportFeatureTest extends TestCase
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
     * Execute test rekap produksi s4s consolidated form page is accessible logic.
     */
    public function test_rekap_produksi_s4s_consolidated_form_page_is_accessible(): void
    {
        $this->get('/reports/s4s/rekap-produksi-s4s-consolidated')
            ->assertOk();
    }

    /**
     * Execute test rekap produksi s4s consolidated preview endpoint returns json data logic.
     */
    public function test_rekap_produksi_s4s_consolidated_preview_endpoint_returns_json_data(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(RekapProduksiS4SConsolidatedReportService::class);
        $service
            ->shouldReceive('fetch')
            ->once()
            ->with('2026-01-01', '2026-01-31')
            ->andReturn([
                ['NamaMesin' => 'S4S 01', 'Tanggal' => '2026-01-01', 'Shift' => 1, 'CCAkhir' => 0.5, 'OutputS4S' => 1.8],
            ]);

        $this->app->instance(RekapProduksiS4SConsolidatedReportService::class, $service);

        $this->withHeaders($this->authJsonHeaders($user))
            ->postJson('/api/reports/s4s/rekap-produksi-s4s-consolidated', [
                'TglAwal' => '2026-01-01',
                'TglAkhir' => '2026-01-31',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Preview laporan berhasil diambil.')
            ->assertJsonPath('meta.total_rows', 1)
            ->assertJsonPath('meta.start_date', '2026-01-01')
            ->assertJsonPath('meta.end_date', '2026-01-31');
    }

    /**
     * Execute test rekap produksi s4s consolidated pdf download endpoint returns attachment logic.
     */
    public function test_rekap_produksi_s4s_consolidated_pdf_download_endpoint_returns_attachment(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(RekapProduksiS4SConsolidatedReportService::class);
        $service
            ->shouldReceive('fetch')
            ->once()
            ->with('2026-01-01', '2026-01-31')
            ->andReturn([
                ['NamaMesin' => 'S4S 01', 'Tanggal' => '2026-01-01', 'Shift' => 1, 'CCAkhir' => 0.5, 'OutputS4S' => 1.8],
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
                'paper_width' => '29.7cm',
                'paper_height' => '21.0cm',
                'landscape' => true,
            ]);

        Http::fake([
            'http://localhost:3000/*' => Http::sequence()
                ->push('%PDF-1.4 mocked content', 200, ['Content-Type' => 'application/pdf']),
        ]);

        $this->app->instance(RekapProduksiS4SConsolidatedReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->actingAs($user)
            ->post('/reports/s4s/rekap-produksi-s4s-consolidated/download', [
                'TglAwal' => '2026-01-01',
                'TglAkhir' => '2026-01-31',
            ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'attachment', 'laporan rekap produksi s4s consolidated');

        Http::assertSent(fn (Request $request): bool => $request->url() === config('services.gotenberg.url').'/forms/chromium/convert/html'
            && $request->method() === 'POST');
    }

    /**
     * Execute test rekap produksi s4s consolidated health endpoint returns structure status logic.
     */
    public function test_rekap_produksi_s4s_consolidated_health_endpoint_returns_structure_status(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(RekapProduksiS4SConsolidatedReportService::class);
        $service
            ->shouldReceive('healthCheck')
            ->once()
            ->with('2026-01-01', '2026-01-31')
            ->andReturn([
                'is_healthy' => true,
                'expected_columns' => ['Tanggal', 'TotalInput'],
                'detected_columns' => ['Tanggal', 'TotalInput'],
                'missing_columns' => [],
                'extra_columns' => [],
                'row_count' => 12,
            ]);

        $this->app->instance(RekapProduksiS4SConsolidatedReportService::class, $service);

        $this->withHeaders($this->authJsonHeaders($user))
            ->postJson('/api/reports/s4s/rekap-produksi-s4s-consolidated/health', [
                'TglAwal' => '2026-01-01',
                'TglAkhir' => '2026-01-31',
            ])
            ->assertOk()
            ->assertJsonPath('health.is_healthy', true)
            ->assertJsonPath('health.row_count', 12);
    }

    /**
     * @return array<string, string>
     */
    private function authJsonHeaders(User $user): array
    {
        return [
            'Authorization' => 'Bearer '.$this->createBearerToken($user),
            'Accept' => 'application/json',
        ];
    }

    /**
     * Create JWT token for test user without requiring auth guard lookup.
     */
    private function createBearerToken(User $user): string
    {
        return $this->issueJwtForUser($user);
    }
}

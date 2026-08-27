<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\KetahananBarangDagangReprosesReportService;
use App\Services\PdfGenerator;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class KetahananBarangDagangReprosesReportFeatureTest extends TestCase
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
     * Execute test ketahanan barang reproses form page is accessible logic.
     */
    public function test_ketahanan_barang_reproses_form_page_is_accessible(): void
    {
        $this->get('/reports/reproses/ketahanan-barang-reproses')
            ->assertOk()
            ->assertSee('Laporan Ketahanan Barang Dagang Reproses');
    }

    /**
     * Execute test ketahanan barang reproses preview endpoint returns json data logic.
     */
    public function test_ketahanan_barang_reproses_preview_endpoint_returns_json_data(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(KetahananBarangDagangReprosesReportService::class);
        $service
            ->shouldReceive('buildReportData')
            ->once()
            ->with('2026-01-01', '2026-01-31')
            ->andReturn([
                'rows' => [
                    ['Jenis' => 'KAYU JABON', 'Stock' => 10.5, 'Penjualan' => 2.1, 'AvgPenjualan' => 0.7, 'Ketahanan' => 5.0],
                ],
                'summary' => ['total_rows' => 1],
            ]);

        $this->app->instance(KetahananBarangDagangReprosesReportService::class, $service);

        $this->withHeaders($this->authJsonHeaders($user))
            ->postJson('/api/reports/reproses/ketahanan-barang-reproses', [
                'TglAwal' => '2026-01-01',
                'TglAkhir' => '2026-01-31',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Preview laporan berhasil diambil.')
            ->assertJsonPath('meta.total_rows', 1)
            ->assertJsonPath('meta.TglAwal', '2026-01-01')
            ->assertJsonPath('meta.TglAkhir', '2026-01-31')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('summary.total_rows', 1);
    }

    /**
     * Execute test ketahanan barang reproses pdf download endpoint returns attachment logic.
     */
    public function test_ketahanan_barang_reproses_pdf_download_endpoint_returns_attachment(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(KetahananBarangDagangReprosesReportService::class);
        $service
            ->shouldReceive('buildReportData')
            ->once()
            ->with('2026-01-01', '2026-01-31')
            ->andReturn([
                'rows' => [
                    ['Jenis' => 'KAYU JABON', 'Stock' => 10.5, 'Penjualan' => 2.1, 'AvgPenjualan' => 0.7, 'Ketahanan' => 5.0],
                ],
                'summary' => ['total_rows' => 1],
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

        $this->app->instance(KetahananBarangDagangReprosesReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->actingAs($user)
            ->post('/reports/reproses/ketahanan-barang-reproses/download', [
                'TglAwal' => '2026-01-01',
                'TglAkhir' => '2026-01-31',
            ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'attachment', 'laporan ketahanan barang dagang reproses');

        Http::assertSent(fn (Request $request): bool => $request->url() === config('services.gotenberg.url').'/forms/chromium/convert/html'
            && $request->method() === 'POST');
    }

    /**
     * Execute test ketahanan barang reproses health endpoint returns structure status logic.
     */
    public function test_ketahanan_barang_reproses_health_endpoint_returns_structure_status(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(KetahananBarangDagangReprosesReportService::class);
        $service
            ->shouldReceive('healthCheck')
            ->once()
            ->with('2026-01-01', '2026-01-31')
            ->andReturn([
                'is_healthy' => true,
                'expected_columns' => ['Jenis', 'Stock', 'Penjualan', 'AvgPenjualan', 'Ketahanan'],
                'detected_columns' => ['Jenis', 'Stock', 'Penjualan', 'AvgPenjualan', 'Ketahanan'],
                'missing_columns' => [],
                'extra_columns' => [],
                'row_count' => 12,
            ]);

        $this->app->instance(KetahananBarangDagangReprosesReportService::class, $service);

        $this->withHeaders($this->authJsonHeaders($user))
            ->postJson('/api/reports/reproses/ketahanan-barang-reproses/health', [
                'TglAwal' => '2026-01-01',
                'TglAkhir' => '2026-01-31',
            ])
            ->assertOk()
            ->assertJsonPath('health.is_healthy', true)
            ->assertJsonPath('health.row_count', 12)
            ->assertJsonPath('meta.TglAwal', '2026-01-01')
            ->assertJsonPath('meta.TglAkhir', '2026-01-31');
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

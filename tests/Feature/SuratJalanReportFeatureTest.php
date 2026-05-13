<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\PdfGenerator;
use App\Services\SuratJalanReportService;
use Mockery;
use Tests\TestCase;

class SuratJalanReportFeatureTest extends TestCase
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

    public function test_form_page_is_accessible(): void
    {
        $this->get('/reports/penjualan/surat-jalan')
            ->assertOk()
            ->assertSee('Generate Surat Jalan');
    }

    public function test_preview_endpoint_returns_report_data(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(SuratJalanReportService::class);
        $service
            ->shouldReceive('buildReportData')
            ->once()
            ->with('INV.2605-058')
            ->andReturn($this->reportData());

        $this->app->instance(SuratJalanReportService::class, $service);

        $this->withHeaders($this->authJsonHeaders($user))
            ->postJson('/api/reports/penjualan/surat-jalan', [
                'NoJual' => 'INV.2605-058',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Preview surat jalan berhasil diambil.')
            ->assertJsonPath('meta.no_jual', 'INV.2605-058')
            ->assertJsonPath('meta.no_surat_jalan', 'INV.2605-058')
            ->assertJsonPath('meta.total_rows', 2)
            ->assertJsonPath('meta.total_pcs', 2020)
            ->assertJsonPath('meta.total_m3', 1.2156)
            ->assertJsonPath('meta.total_ton', 0.8585);
    }

    public function test_pdf_download_endpoint_returns_attachment(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(SuratJalanReportService::class);
        $service
            ->shouldReceive('buildReportData')
            ->once()
            ->with('INV.2605-058')
            ->andReturn($this->reportData());

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->with('reports.penjualan.surat-jalan-pdf', Mockery::on(
                static fn (array $data): bool => ($data['pdf_orientation'] ?? null) === 'portrait'
                    && ($data['pdf_title'] ?? null) === 'Surat Jalan'
            ))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(SuratJalanReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->actingAs($user)
            ->post('/reports/penjualan/surat-jalan/download', [
                'NoJual' => 'INV.2605-058',
            ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'attachment', 'Surat Jalan INV.2605 058');
    }

    public function test_pdf_preview_endpoint_returns_inline_pdf(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(SuratJalanReportService::class);
        $service
            ->shouldReceive('buildReportData')
            ->once()
            ->with('INV.2605-058')
            ->andReturn($this->reportData());

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(SuratJalanReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->actingAs($user)
            ->post('/reports/penjualan/surat-jalan/preview-pdf', [
                'NoJual' => 'INV.2605-058',
            ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'inline', 'Surat Jalan INV.2605 058');
    }

    public function test_health_endpoint_returns_structure_status(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(SuratJalanReportService::class);
        $service
            ->shouldReceive('healthCheck')
            ->once()
            ->with('INV.2605-058')
            ->andReturn([
                'is_healthy' => true,
                'expected_columns' => ['NoSJ'],
                'detected_columns' => ['NoSJ'],
                'missing_columns' => [],
                'extra_columns' => [],
                'row_count' => 4,
            ]);

        $this->app->instance(SuratJalanReportService::class, $service);

        $this->withHeaders($this->authJsonHeaders($user))
            ->postJson('/api/reports/penjualan/surat-jalan/health', [
                'NoJual' => 'INV.2605-058',
            ])
            ->assertOk()
            ->assertJsonPath('health.is_healthy', true)
            ->assertJsonPath('health.row_count', 4)
            ->assertJsonPath('meta.NoJual', 'INV.2605-058');
    }

    /**
     * @return array<string, mixed>
     */
    private function reportData(): array
    {
        return [
            'no_jual' => 'INV.2605-058',
            'header' => [
                'tanggal' => '2026-05-13',
                'no_surat_jalan' => 'INV.2605-058',
                'buyer' => 'BUDI TANDEM',
                'no_plat' => 'BK 8936 RE',
                'jenis_kendaraan' => 'PICKUP - MITSUBISHI L300',
            ],
            'rows' => [
                [
                    'Tanggal' => '2026-05-11',
                    'DisplayTanggal' => '2026-05-11',
                    'NoST' => 'E.516695',
                    'JenisKayu' => 'KAYU LAT RAMBUNG',
                    'Pcs' => 540,
                    'M3' => 0.2611,
                    'Ton' => 0.1844,
                ],
                [
                    'Tanggal' => '2026-05-11',
                    'DisplayTanggal' => '',
                    'NoST' => 'E.516695',
                    'JenisKayu' => 'KAYU LAT RAMBUNG',
                    'Pcs' => 1480,
                    'M3' => 0.9545,
                    'Ton' => 0.6741,
                ],
            ],
            'summary' => [
                'total_rows' => 2,
                'total_pcs' => 2020,
                'total_m3' => 1.2156,
                'total_ton' => 0.8585,
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function authJsonHeaders(User $user): array
    {
        return [
            'Authorization' => 'Bearer '.$this->issueJwtForUser($user),
            'Accept' => 'application/json',
        ];
    }
}

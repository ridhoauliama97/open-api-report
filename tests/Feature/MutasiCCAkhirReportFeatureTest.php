<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\MutasiCCAkhirReportService;
use App\Services\PdfGenerator;
use Mockery;
use Tests\TestCase;

class MutasiCCAkhirReportFeatureTest extends TestCase
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

    public function test_mutasi_cca_akhir_form_page_is_accessible(): void
    {
        $this->get('/reports/mutasi/cca-akhir')
            ->assertOk()
            ->assertSee('Generate Laporan Mutasi CC Akhir (PDF)');
    }

    public function test_mutasi_cca_akhir_preview_endpoint_returns_json_data(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(MutasiCCAkhirReportService::class);
        $service
            ->shouldReceive('fetch')
            ->once()
            ->with('2026-01-01', '2026-01-31')
            ->andReturn([
                ['Jenis' => 'CCA JABON A/A', 'CCAkhirAwal' => 1.2, 'AdjOutputCCA' => 0.1, 'CCAAkhir' => 1.1],
                ['Jenis' => 'CCA JABON C/C', 'CCAkhirAwal' => 2.2, 'AdjOutputCCA' => 0.0, 'CCAAkhir' => 2.2],
            ]);
        $service
            ->shouldReceive('fetchSubReport')
            ->once()
            ->with('2026-01-01', '2026-01-31')
            ->andReturn([
                ['Jenis' => 'FJ JABON ISOBO', 'FJ' => 0.3, 'Laminating' => 0, 'Reproses' => 0, 'WIP' => 0, 'BJ' => 0, 'Sanding' => 0, 'CCAkhir' => 0],
            ]);

        $this->app->instance(MutasiCCAkhirReportService::class, $service);

        $this->withHeaders($this->authJsonHeaders($user))
            ->postJson('/api/reports/mutasi-cca-akhir', [
                'TglAwal' => '2026-01-01',
                'TglAkhir' => '2026-01-31',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Preview laporan berhasil diambil.')
            ->assertJsonPath('meta.total_rows', 2)
            ->assertJsonPath('meta.total_sub_rows', 1)
            ->assertJsonCount(2, 'data');
    }

    public function test_mutasi_cca_akhir_pdf_download_endpoint_returns_attachment(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(MutasiCCAkhirReportService::class);
        $service
            ->shouldReceive('fetch')
            ->once()
            ->with('2026-01-01', '2026-01-31')
            ->andReturn([
                ['Jenis' => 'CCA JABON A/A', 'CCAkhirAwal' => 1.2, 'AdjOutputCCA' => 0.1, 'CCAAkhir' => 1.1],
            ]);
        $service
            ->shouldReceive('fetchSubReport')
            ->once()
            ->with('2026-01-01', '2026-01-31')
            ->andReturn([
                ['Jenis' => 'FJ JABON ISOBO', 'FJ' => 0.3, 'Laminating' => 0, 'Reproses' => 0, 'WIP' => 0, 'BJ' => 0, 'Sanding' => 0, 'CCAkhir' => 0],
            ]);

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(MutasiCCAkhirReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $this->actingAs($user)
            ->post('/reports/mutasi/cca-akhir/download', [
                'TglAwal' => '2026-01-01',
                'TglAkhir' => '2026-01-31',
            ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'attachment; filename="Laporan-Mutasi-CCA-Akhir-2026-01-01-sd-2026-01-31.pdf"');
    }

    public function test_mutasi_cca_akhir_health_endpoint_returns_structure_status(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(MutasiCCAkhirReportService::class);
        $service
            ->shouldReceive('healthCheck')
            ->once()
            ->with('2026-01-01', '2026-01-31')
            ->andReturn([
                'is_healthy' => true,
                'expected_columns' => ['Jenis', 'CCAkhirAwal', 'AdjOutputCCA', 'CCAAkhir'],
                'detected_columns' => ['Jenis', 'CCAkhirAwal', 'AdjOutputCCA', 'CCAAkhir'],
                'missing_columns' => [],
                'extra_columns' => [],
                'row_count' => 10,
            ]);

        $this->app->instance(MutasiCCAkhirReportService::class, $service);

        $this->withHeaders($this->authJsonHeaders($user))
            ->postJson('/api/reports/mutasi-cca-akhir/health', [
                'TglAwal' => '2026-01-01',
                'TglAkhir' => '2026-01-31',
            ])
            ->assertOk()
            ->assertJsonPath('health.is_healthy', true)
            ->assertJsonPath('health.row_count', 10);
    }

    /**
     * @return array<string, string>
     */
    private function authJsonHeaders(User $user): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->createBearerToken($user),
            'Accept' => 'application/json',
        ];
    }

    private function createBearerToken(User $user): string
    {
        return $this->issueJwtForUser($user);
    }
}





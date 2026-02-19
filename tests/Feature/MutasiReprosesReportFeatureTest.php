<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\MutasiReprosesReportService;
use App\Services\PdfGenerator;
use Mockery;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class MutasiReprosesReportFeatureTest extends TestCase
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

    public function test_mutasi_reproses_form_page_is_accessible(): void
    {
        $this->get('/reports/mutasi/reproses')
            ->assertOk()
            ->assertSee('Generate Laporan Mutasi Reproses (PDF)');
    }

    public function test_mutasi_reproses_preview_endpoint_returns_json_data(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(MutasiReprosesReportService::class);
        $service
            ->shouldReceive('fetch')
            ->once()
            ->with('2026-01-01', '2026-01-31')
            ->andReturn([
                ['Jenis' => 'REPRO JABON', 'REPROAwal' => 1.1, 'AdjOutput' => 0.2, 'ReprosesAkhir' => 0.9],
            ]);

        $this->app->instance(MutasiReprosesReportService::class, $service);

        $this->withHeaders($this->authJsonHeaders($user))
            ->postJson('/api/reports/mutasi-reproses', [
                'TglAwal' => '2026-01-01',
                'TglAkhir' => '2026-01-31',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Preview laporan berhasil diambil.')
            ->assertJsonPath('meta.total_rows', 1)
            ->assertJsonCount(1, 'data');
    }

    public function test_mutasi_reproses_pdf_download_endpoint_returns_attachment(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(MutasiReprosesReportService::class);
        $service
            ->shouldReceive('fetch')
            ->once()
            ->with('2026-01-01', '2026-01-31')
            ->andReturn([
                ['Jenis' => 'REPRO JABON', 'REPROAwal' => 1.1, 'AdjOutput' => 0.2, 'ReprosesAkhir' => 0.9],
            ]);

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(MutasiReprosesReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $this->actingAs($user)
            ->post('/reports/mutasi/reproses/download', [
                'TglAwal' => '2026-01-01',
                'TglAkhir' => '2026-01-31',
            ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'attachment; filename="Laporan-Mutasi-Reproses-2026-01-01-sd-2026-01-31.pdf"');
    }

    public function test_mutasi_reproses_health_endpoint_returns_structure_status(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(MutasiReprosesReportService::class);
        $service
            ->shouldReceive('healthCheck')
            ->once()
            ->with('2026-01-01', '2026-01-31')
            ->andReturn([
                'is_healthy' => true,
                'expected_columns' => ['Jenis', 'REPROAwal', 'AdjOutput', 'ReprosesAkhir'],
                'detected_columns' => ['Jenis', 'REPROAwal', 'AdjOutput', 'ReprosesAkhir'],
                'missing_columns' => [],
                'extra_columns' => [],
                'row_count' => 5,
            ]);

        $this->app->instance(MutasiReprosesReportService::class, $service);

        $this->withHeaders($this->authJsonHeaders($user))
            ->postJson('/api/reports/mutasi-reproses/health', [
                'TglAwal' => '2026-01-01',
                'TglAkhir' => '2026-01-31',
            ])
            ->assertOk()
            ->assertJsonPath('health.is_healthy', true)
            ->assertJsonPath('health.row_count', 5);
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
        return (string) JWTAuth::fromUser($user);
    }
}

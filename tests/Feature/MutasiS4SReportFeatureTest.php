<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\MutasiS4SReportService;
use App\Services\PdfGenerator;
use Mockery;
use Tests\TestCase;

class MutasiS4SReportFeatureTest extends TestCase
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
     * Execute test mutasi s4s form page is accessible logic.
     */
    public function test_mutasi_s4s_form_page_is_accessible(): void
    {
        $this->get('/reports/mutasi/s4s')
            ->assertOk()
            ->assertSee('Generate Laporan Mutasi S4S (PDF)');
    }

    /**
     * Execute test mutasi s4s preview endpoint returns json data logic.
     */
    public function test_mutasi_s4s_preview_endpoint_returns_json_data(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(MutasiS4SReportService::class);
        $service
            ->shouldReceive('fetch')
            ->once()
            ->with('2026-01-01', '2026-01-31')
            ->andReturn([
                ['Jenis' => 'S4S JABON', 'Awal' => 10.25, 'Masuk' => 2.1, 'Keluar' => 1.4, 'Akhir' => 10.95],
                ['Jenis' => 'S4S MERANTI', 'Awal' => 8.2, 'Masuk' => 1.3, 'Keluar' => 0.9, 'Akhir' => 8.6],
            ]);
        $service
            ->shouldReceive('fetchSubReport')
            ->once()
            ->with('2026-01-01', '2026-01-31')
            ->andReturn([
                ['Jenis' => 'CCA JABON', 'BJ' => 0.0, 'CCAkhir' => 0.3039, 'FJ' => 0.0, 'Laminating' => 0.0, 'Moulding' => 0.0, 'Reproses' => 0.0, 'S4S' => 0.0, 'Sanding' => 0.0, 'WIP' => 0.0],
            ]);

        $this->app->instance(MutasiS4SReportService::class, $service);

        $this->withHeaders($this->authJsonHeaders($user))
            ->postJson('/api/reports/mutasi-s4s', [
                'TglAwal' => '2026-01-01',
                'TglAkhir' => '2026-01-31',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Preview laporan berhasil diambil.')
            ->assertJsonPath('meta.total_rows', 2)
            ->assertJsonPath('meta.total_sub_rows', 1)
            ->assertJsonPath('meta.TglAwal', '2026-01-01')
            ->assertJsonPath('meta.TglAkhir', '2026-01-31')
            ->assertJsonCount(2, 'data')
            ->assertJsonCount(1, 'sub_data');
    }

    /**
     * Execute test mutasi s4s pdf download endpoint returns attachment logic.
     */
    public function test_mutasi_s4s_pdf_download_endpoint_returns_attachment(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(MutasiS4SReportService::class);
        $service
            ->shouldReceive('fetch')
            ->once()
            ->with('2026-01-01', '2026-01-31')
            ->andReturn([
                ['Jenis' => 'S4S JABON', 'Awal' => 10.25, 'Masuk' => 2.1, 'Keluar' => 1.4, 'Akhir' => 10.95],
            ]);
        $service
            ->shouldReceive('fetchSubReport')
            ->once()
            ->with('2026-01-01', '2026-01-31')
            ->andReturn([
                ['Jenis' => 'CCA JABON', 'BJ' => 0.0, 'CCAkhir' => 0.3039, 'FJ' => 0.0, 'Laminating' => 0.0, 'Moulding' => 0.0, 'Reproses' => 0.0, 'S4S' => 0.0, 'Sanding' => 0.0, 'WIP' => 0.0],
            ]);

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(MutasiS4SReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $this->actingAs($user)
            ->post('/reports/mutasi/s4s/download', [
                'TglAwal' => '2026-01-01',
                'TglAkhir' => '2026-01-31',
            ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'attachment; filename="laporan-mutasi-s4s-2026-01-01-sd-2026-01-31.pdf"');
    }

    /**
     * Execute test mutasi s4s health endpoint returns structure status logic.
     */
    public function test_mutasi_s4s_health_endpoint_returns_structure_status(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(MutasiS4SReportService::class);
        $service
            ->shouldReceive('healthCheck')
            ->once()
            ->with('2026-01-01', '2026-01-31')
            ->andReturn([
                'is_healthy' => true,
                'expected_columns' => ['Jenis', 'Awal', 'Masuk', 'Keluar', 'Akhir'],
                'detected_columns' => ['Jenis', 'Awal', 'Masuk', 'Keluar', 'Akhir'],
                'missing_columns' => [],
                'extra_columns' => [],
                'row_count' => 12,
            ]);

        $this->app->instance(MutasiS4SReportService::class, $service);

        $this->withHeaders($this->authJsonHeaders($user))
            ->postJson('/api/reports/mutasi-s4s/health', [
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
            'Authorization' => 'Bearer ' . $this->createBearerToken($user),
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






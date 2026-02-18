<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\MutasiFingerJointReportService;
use App\Services\PdfGenerator;
use Mockery;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class MutasiFingerJointReportFeatureTest extends TestCase
{
    /**
     * Execute set up logic.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Isolasi test dari policy .env lokal agar skenario dasar tetap deterministik.
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
     * Execute test mutasi finger joint form page is accessible logic.
     */
    public function test_mutasi_finger_joint_form_page_is_accessible(): void
    {
        $this->get('/reports/mutasi/finger-joint')
            ->assertOk()
            ->assertSee('Generate Laporan Mutasi Finger Joint (PDF)');
    }

    /**
     * Execute test mutasi finger joint preview endpoint returns json data logic.
     */
    public function test_mutasi_finger_joint_preview_endpoint_returns_json_data(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(MutasiFingerJointReportService::class);
        $service
            ->shouldReceive('fetch')
            ->once()
            ->with('2026-01-01', '2026-01-31')
            ->andReturn([
                ['Jenis' => 'FJ JABON', 'Awal' => 10.25, 'Masuk' => 2.1, 'Keluar' => 1.4, 'Akhir' => 10.95],
                ['Jenis' => 'FJ MERANTI', 'Awal' => 8.2, 'Masuk' => 1.3, 'Keluar' => 0.9, 'Akhir' => 8.6],
            ]);
        $service
            ->shouldReceive('fetchSubReport')
            ->once()
            ->with('2026-01-01', '2026-01-31')
            ->andReturn([
                ['Jenis' => 'S4S JABON A/A', 'CCAkhir' => 0.0, 'S4S' => 30.0845],
                ['Jenis' => 'S4S JABON ISOBO', 'CCAkhir' => 0.0, 'S4S' => 0.4259],
            ]);

        $this->app->instance(MutasiFingerJointReportService::class, $service);

        $this->withHeaders($this->authJsonHeaders($user))
            ->postJson('/api/reports/mutasi-finger-joint', [
                'TglAwal' => '2026-01-01',
                'TglAkhir' => '2026-01-31',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Preview laporan berhasil diambil.')
            ->assertJsonPath('meta.total_rows', 2)
            ->assertJsonPath('meta.total_sub_rows', 2)
            ->assertJsonPath('meta.TglAwal', '2026-01-01')
            ->assertJsonPath('meta.TglAkhir', '2026-01-31')
            ->assertJsonCount(2, 'data')
            ->assertJsonCount(2, 'sub_data');
    }

    /**
     * Execute test mutasi finger joint web preview route returns json data logic.
     */
    public function test_mutasi_finger_joint_web_preview_route_returns_json_data(): void
    {
        $service = Mockery::mock(MutasiFingerJointReportService::class);
        $service
            ->shouldReceive('fetch')
            ->once()
            ->with('2026-01-01', '2026-01-31')
            ->andReturn([
                ['Jenis' => 'FJ JABON', 'Awal' => 10.25, 'Masuk' => 2.1, 'Keluar' => 1.4, 'Akhir' => 10.95],
            ]);
        $service
            ->shouldReceive('fetchSubReport')
            ->once()
            ->with('2026-01-01', '2026-01-31')
            ->andReturn([
                ['Jenis' => 'S4S JABON A/A', 'CCAkhir' => 0.0, 'S4S' => 30.0845],
            ]);

        $this->app->instance(MutasiFingerJointReportService::class, $service);

        $this->postJson('/reports/mutasi/finger-joint/preview', [
            'TglAwal' => '2026-01-01',
            'TglAkhir' => '2026-01-31',
        ])
            ->assertOk()
            ->assertJsonPath('meta.TglAwal', '2026-01-01')
            ->assertJsonPath('meta.total_rows', 1)
            ->assertJsonPath('data.0.Jenis', 'FJ JABON')
            ->assertJsonPath('sub_data.0.Jenis', 'S4S JABON A/A');
    }

    /**
     * Execute test mutasi finger joint pdf download endpoint returns attachment logic.
     */
    public function test_mutasi_finger_joint_pdf_download_endpoint_returns_attachment(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(MutasiFingerJointReportService::class);
        $service
            ->shouldReceive('fetch')
            ->once()
            ->with('2026-01-01', '2026-01-31')
            ->andReturn([
                ['Jenis' => 'FJ JABON', 'Awal' => 10.25, 'Masuk' => 2.1, 'Keluar' => 1.4, 'Akhir' => 10.95],
            ]);
        $service
            ->shouldReceive('fetchSubReport')
            ->once()
            ->with('2026-01-01', '2026-01-31')
            ->andReturn([
                ['Jenis' => 'S4S JABON A/A', 'CCAkhir' => 0.0, 'S4S' => 30.0845],
            ]);

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(MutasiFingerJointReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $this->actingAs($user)
            ->post('/reports/mutasi/finger-joint/download', [
                'TglAwal' => '2026-01-01',
                'TglAkhir' => '2026-01-31',
            ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'attachment; filename="laporan-mutasi-finger-joint-2026-01-01-sd-2026-01-31.pdf"');
    }

    /**
     * Execute test mutasi finger joint pdf download endpoint supports get query string logic.
     */
    public function test_mutasi_finger_joint_pdf_download_endpoint_supports_get_query_string(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(MutasiFingerJointReportService::class);
        $service
            ->shouldReceive('fetch')
            ->once()
            ->with('2026-01-01', '2026-01-31')
            ->andReturn([
                ['Jenis' => 'FJ JABON', 'Awal' => 10.25, 'Masuk' => 2.1, 'Keluar' => 1.4, 'Akhir' => 10.95],
            ]);
        $service
            ->shouldReceive('fetchSubReport')
            ->once()
            ->with('2026-01-01', '2026-01-31')
            ->andReturn([
                ['Jenis' => 'S4S JABON A/A', 'CCAkhir' => 0.0, 'S4S' => 30.0845],
            ]);

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(MutasiFingerJointReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->createBearerToken($user),
        ])->get('/api/reports/mutasi-finger-joint/pdf?TglAwal=2026-01-01&TglAkhir=2026-01-31')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'attachment; filename="laporan-mutasi-finger-joint-2026-01-01-sd-2026-01-31.pdf"');
    }

    /**
     * Execute test mutasi finger joint health endpoint returns structure status logic.
     */
    public function test_mutasi_finger_joint_health_endpoint_returns_structure_status(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(MutasiFingerJointReportService::class);
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

        $this->app->instance(MutasiFingerJointReportService::class, $service);

        $this->withHeaders($this->authJsonHeaders($user))
            ->postJson('/api/reports/mutasi-finger-joint/health', [
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
        return (string) JWTAuth::fromUser($user);
    }
}

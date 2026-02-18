<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\LabelNyangkutReportService;
use App\Services\PdfGenerator;
use Mockery;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class LabelNyangkutReportFeatureTest extends TestCase
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
     * Execute test label nyangkut form page is accessible logic.
     */
    public function test_label_nyangkut_form_page_is_accessible(): void
    {
        $this->get('/reports/label-nyangkut')
            ->assertOk()
            ->assertSee('Generate Laporan Label Nyangkut (PDF)');
    }

    /**
     * Execute test label nyangkut preview endpoint returns json data logic.
     */
    public function test_label_nyangkut_preview_endpoint_returns_json_data(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(LabelNyangkutReportService::class);
        $service
            ->shouldReceive('fetch')
            ->once()
            ->andReturn([
                ['Tanggal' => '2026-01-01', 'Shift' => '1', 'JumlahLabel' => 125],
                ['Tanggal' => '2026-01-02', 'Shift' => '2', 'JumlahLabel' => 140],
            ]);

        $this->app->instance(LabelNyangkutReportService::class, $service);

        $this->withHeaders($this->authJsonHeaders($user))
            ->postJson('/api/reports/label-nyangkut')
            ->assertOk()
            ->assertJsonPath('message', 'Preview laporan berhasil diambil.')
            ->assertJsonPath('meta.total_rows', 2)
            ->assertJsonCount(2, 'data');
    }

    /**
     * Execute test label nyangkut pdf download endpoint returns attachment logic.
     */
    public function test_label_nyangkut_pdf_download_endpoint_returns_attachment(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(LabelNyangkutReportService::class);
        $service
            ->shouldReceive('fetch')
            ->once()
            ->andReturn([
                ['Tanggal' => '2026-01-01', 'Shift' => '1', 'JumlahLabel' => 125],
            ]);

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(LabelNyangkutReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $this->actingAs($user)
            ->post('/reports/label-nyangkut/download')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader(
                'Content-Disposition',
                sprintf('attachment; filename="Laporan-Label-Nyangkut-per-%s.pdf"', now()->format('Y-m-d')),
            );
    }

    /**
     * Execute test label nyangkut health endpoint returns structure status logic.
     */
    public function test_label_nyangkut_health_endpoint_returns_structure_status(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(LabelNyangkutReportService::class);
        $service
            ->shouldReceive('healthCheck')
            ->once()
            ->andReturn([
                'is_healthy' => true,
                'expected_columns' => ['Tanggal', 'Shift', 'JumlahLabel'],
                'detected_columns' => ['Tanggal', 'Shift', 'JumlahLabel'],
                'missing_columns' => [],
                'extra_columns' => [],
                'row_count' => 31,
            ]);

        $this->app->instance(LabelNyangkutReportService::class, $service);

        $this->withHeaders($this->authJsonHeaders($user))
            ->postJson('/api/reports/label-nyangkut/health')
            ->assertOk()
            ->assertJsonPath('health.is_healthy', true)
            ->assertJsonPath('health.row_count', 31);
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

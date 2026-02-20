<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\HidupKBPerGroupReportService;
use App\Services\PdfGenerator;
use Mockery;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class HidupKBPerGroupReportFeatureTest extends TestCase
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
        $this->get('/reports/kayu-bulat/hidup-per-group')
            ->assertOk()
            ->assertSee('Generate Laporan Hidup KB Per Group (PDF)');
    }

    public function test_preview_endpoint_returns_json_data(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(HidupKBPerGroupReportService::class);
        $service
            ->shouldReceive('buildReportData')
            ->once()
            ->withNoArgs()
            ->andReturn([
                'rows' => [
                    ['Group' => 'JABON', 'Ton' => 12.4415],
                    ['Group' => 'PULAI', 'Ton' => 6.1101],
                ],
                'summary' => [
                    'total_rows' => 2,
                    'total_ton' => 18.5516,
                ],
            ]);

        $this->app->instance(HidupKBPerGroupReportService::class, $service);

        $this->withHeaders($this->authJsonHeaders($user))
            ->postJson('/api/reports/kayu-bulat/hidup-per-group', [])
            ->assertOk()
            ->assertJsonPath('message', 'Preview laporan berhasil diambil.')
            ->assertJsonPath('meta.total_rows', 2)
            ->assertJsonPath('summary.total_ton', 18.5516)
            ->assertJsonPath('data.0.Group', 'JABON');
    }

    public function test_pdf_download_endpoint_returns_attachment(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(HidupKBPerGroupReportService::class);
        $service
            ->shouldReceive('buildReportData')
            ->once()
            ->withNoArgs()
            ->andReturn([
                'rows' => [['Group' => 'JABON', 'Ton' => 12.4415]],
                'summary' => ['total_rows' => 1, 'total_ton' => 12.4415],
            ]);

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(HidupKBPerGroupReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $this->actingAs($user)
            ->post('/reports/kayu-bulat/hidup-per-group/download', [])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'attachment; filename="laporan-hidup-kb-per-group.pdf"');
    }

    public function test_health_endpoint_returns_structure_status(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(HidupKBPerGroupReportService::class);
        $service
            ->shouldReceive('healthCheck')
            ->once()
            ->withNoArgs()
            ->andReturn([
                'is_healthy' => true,
                'expected_columns' => ['Group', 'Ton'],
                'detected_columns' => ['Group', 'Ton'],
                'missing_columns' => [],
                'extra_columns' => [],
                'row_count' => 2,
            ]);

        $this->app->instance(HidupKBPerGroupReportService::class, $service);

        $this->withHeaders($this->authJsonHeaders($user))
            ->postJson('/api/reports/kayu-bulat/hidup-per-group/health', [])
            ->assertOk()
            ->assertJsonPath('health.is_healthy', true)
            ->assertJsonPath('health.row_count', 2);
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

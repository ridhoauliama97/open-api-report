<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\PdfGenerator;
use App\Services\PPS\StockBonggolanV2ReportService;
use Mockery;
use Tests\TestCase;

class PpsStockBonggolanV2ReportFeatureTest extends TestCase
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
        $this->get('/reports/pps/bonggolan/stock-bonggolan-v2')
            ->assertOk()
            ->assertSee('Generate Laporan Stock Bonggolan (PPS)');
    }

    public function test_preview_endpoint_returns_json_data(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(StockBonggolanV2ReportService::class);
        $service
            ->shouldReceive('fetch')
            ->once()
            ->andReturn([
                [
                    'KodeBarang' => 'BONG-001',
                    'NamaBarang' => 'Bonggolan A',
                    'Stock' => 12.5,
                ],
            ]);

        $this->app->instance(StockBonggolanV2ReportService::class, $service);

        $this->withHeaders($this->authJsonHeaders($user))
            ->postJson('/api/reports/pps/bonggolan/stock-bonggolan-v2', [])
            ->assertOk()
            ->assertJsonPath('message', 'Preview laporan berhasil diambil.')
            ->assertJsonPath('meta.total_rows', 1)
            ->assertJsonPath('data.0.KodeBarang', 'BONG-001');
    }

    public function test_pdf_download_endpoint_returns_attachment(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(StockBonggolanV2ReportService::class);
        $service
            ->shouldReceive('fetch')
            ->once()
            ->andReturn([]);

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(StockBonggolanV2ReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->actingAs($user)
            ->post('/reports/pps/bonggolan/stock-bonggolan-v2/download', [])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'attachment', 'laporan-stock-bonggolan');
    }

    public function test_health_endpoint_returns_structure_status(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(StockBonggolanV2ReportService::class);
        $service
            ->shouldReceive('healthCheck')
            ->once()
            ->andReturn([
                'is_healthy' => true,
                'expected_columns' => ['KodeBarang'],
                'detected_columns' => ['KodeBarang'],
                'missing_columns' => [],
                'extra_columns' => [],
                'row_count' => 3,
            ]);

        $this->app->instance(StockBonggolanV2ReportService::class, $service);

        $this->withHeaders($this->authJsonHeaders($user))
            ->postJson('/api/reports/pps/bonggolan/stock-bonggolan-v2/health', [])
            ->assertOk()
            ->assertJsonPath('health.is_healthy', true)
            ->assertJsonPath('health.row_count', 3);
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

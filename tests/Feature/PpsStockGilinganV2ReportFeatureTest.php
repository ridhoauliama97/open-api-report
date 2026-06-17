<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\PdfGenerator;
use App\Services\PPS\StockGilinganV2ReportService;
use Mockery;
use Tests\TestCase;

class PpsStockGilinganV2ReportFeatureTest extends TestCase
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
        $this->get('/reports/pps/gilingan/stock-gilingan-v2')
            ->assertOk()
            ->assertSee('Generate Laporan Stock Gilingan (PPS)');
    }

    public function test_preview_endpoint_returns_json_data(): void
    {
        $user = User::factory()->make(['id' => 1]);
        $service = Mockery::mock(StockGilinganV2ReportService::class);
        $service->shouldReceive('fetch')->once()->andReturn([
            ['NoGilingan' => 'G-01', 'NamaGilingan' => 'Gilingan A', 'Berat' => 6.5, 'NamaWarehouse' => 'GILINGAN'],
        ]);
        $this->app->instance(StockGilinganV2ReportService::class, $service);

        $this->withHeaders($this->authJsonHeaders($user))
            ->postJson('/api/reports/pps/gilingan/stock-gilingan-v2', [])
            ->assertOk()
            ->assertJsonPath('data.0.NoGilingan', 'G-01');
    }

    public function test_pdf_download_endpoint_returns_attachment(): void
    {
        $user = User::factory()->make(['id' => 1]);
        $service = Mockery::mock(StockGilinganV2ReportService::class);
        $service->shouldReceive('fetch')->once()->andReturn([]);
        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator->shouldReceive('render')->once()->andReturn('%PDF-1.4 mocked content');
        $this->app->instance(StockGilinganV2ReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->actingAs($user)
            ->post('/reports/pps/gilingan/stock-gilingan-v2/download', [])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'attachment', 'laporan-stok-gilingan');
    }

    public function test_health_endpoint_returns_structure_status(): void
    {
        $user = User::factory()->make(['id' => 1]);
        $service = Mockery::mock(StockGilinganV2ReportService::class);
        $service->shouldReceive('healthCheck')->once()->andReturn([
            'is_healthy' => true,
            'expected_columns' => ['NoGilingan'],
            'detected_columns' => ['NoGilingan'],
            'missing_columns' => [],
            'extra_columns' => [],
            'row_count' => 2,
        ]);
        $this->app->instance(StockGilinganV2ReportService::class, $service);

        $this->withHeaders($this->authJsonHeaders($user))
            ->postJson('/api/reports/pps/gilingan/stock-gilingan-v2/health', [])
            ->assertOk()
            ->assertJsonPath('health.row_count', 2);
    }

    private function authJsonHeaders(User $user): array
    {
        return ['Authorization' => 'Bearer '.$this->createBearerToken($user), 'Accept' => 'application/json'];
    }

    private function createBearerToken(User $user): string
    {
        return $this->issueJwtForUser($user);
    }
}

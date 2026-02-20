<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\PdfGenerator;
use App\Services\StockOpnameKayuBulatReportService;
use Mockery;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class StockOpnameKayuBulatReportFeatureTest extends TestCase
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
        $this->get('/reports/kayu-bulat/stock-opname')
            ->assertOk()
            ->assertSee('Generate Laporan Stock Opname Kayu Bulat (PDF)');
    }

    public function test_preview_endpoint_returns_json_data(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(StockOpnameKayuBulatReportService::class);
        $service
            ->shouldReceive('buildReportData')
            ->once()
            ->withNoArgs()
            ->andReturn([
                'rows' => [
                    [
                        'NoKayuBulat' => 'A.10001',
                        'Tanggal' => '2026-01-01',
                        'JenisKayu' => 'JABON',
                    ],
                ],
                'grouped_rows' => [
                    [
                        'no_kayu_bulat' => 'A.10001',
                        'rows' => [['NoKayuBulat' => 'A.10001']],
                    ],
                ],
                'summary' => [
                    'total_rows' => 1,
                    'total_no_kayu_bulat' => 1,
                    'total_pcs' => 1,
                    'total_ton' => 0.0011,
                    'per_no_kayu_bulat' => [['no_kayu_bulat' => 'A.10001', 'total_rows' => 1]],
                ],
            ]);

        $this->app->instance(StockOpnameKayuBulatReportService::class, $service);

        $this->withHeaders($this->authJsonHeaders($user))
            ->postJson('/api/reports/kayu-bulat/stock-opname', [])
            ->assertOk()
            ->assertJsonPath('message', 'Preview laporan berhasil diambil.')
            ->assertJsonPath('meta.total_rows', 1)
            ->assertJsonPath('summary.total_no_kayu_bulat', 1)
            ->assertJsonPath('data.0.NoKayuBulat', 'A.10001');
    }

    public function test_pdf_download_endpoint_returns_attachment(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(StockOpnameKayuBulatReportService::class);
        $service
            ->shouldReceive('buildReportData')
            ->once()
            ->withNoArgs()
            ->andReturn([
                'rows' => [],
                'grouped_rows' => [],
                'summary' => [
                    'total_rows' => 0,
                    'total_no_kayu_bulat' => 0,
                    'total_pcs' => 0,
                    'total_ton' => 0,
                    'per_no_kayu_bulat' => [],
                ],
            ]);

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(StockOpnameKayuBulatReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $this->actingAs($user)
            ->post('/reports/kayu-bulat/stock-opname/download', [])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'attachment; filename="laporan-stock-opname-kayu-bulat.pdf"');
    }

    public function test_health_endpoint_returns_structure_status(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(StockOpnameKayuBulatReportService::class);
        $service
            ->shouldReceive('healthCheck')
            ->once()
            ->withNoArgs()
            ->andReturn([
                'is_healthy' => true,
                'expected_columns' => ['NoKayuBulat'],
                'detected_columns' => ['NoKayuBulat'],
                'missing_columns' => [],
                'extra_columns' => [],
                'row_count' => 3,
            ]);

        $this->app->instance(StockOpnameKayuBulatReportService::class, $service);

        $this->withHeaders($this->authJsonHeaders($user))
            ->postJson('/api/reports/kayu-bulat/stock-opname/health', [])
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
        return (string) JWTAuth::fromUser($user);
    }
}

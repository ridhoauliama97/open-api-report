<?php

namespace Tests\Feature;

use App\Models\User;
use App\Http\Middleware\AuthenticateReportJwtClaims;
use App\Services\PdfGenerator;
use App\Services\PenerimaanKayuBulatPerSupplierBulananGrafikReportService;
use Mockery;
use Tests\TestCase;

class PenerimaanKayuBulatPerSupplierBulananGrafikReportFeatureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('reports.report_auth.issuers', []);
        config()->set('reports.report_auth.audiences', []);
        config()->set('reports.report_auth.required_scope', null);
        $this->withoutMiddleware(AuthenticateReportJwtClaims::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_form_page_is_accessible(): void
    {
        $this->get('/reports/kayu-bulat/penerimaan-bulanan-per-supplier-grafik')
            ->assertOk()
            ->assertSee('Laporan Penerimaan Kayu Bulat Per Supplier Bulanan (Grafik)');
    }

    public function test_preview_endpoint_returns_grouped_chart_data(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(PenerimaanKayuBulatPerSupplierBulananGrafikReportService::class);
        $service
            ->shouldReceive('buildReportData')
            ->once()
            ->with('2026-01-01', '2026-01-31')
            ->andReturn([
                'groups' => [
                    [
                        'name' => 'JABON',
                        'month_keys' => ['2026-01'],
                        'month_labels' => ['Jan-26'],
                        'suppliers' => [
                            ['supplier' => 'PUTRA T', 'month_values' => ['2026-01' => 49.4664], 'total' => 49.4664],
                        ],
                        'month_totals' => ['2026-01' => 49.4664],
                        'summary' => ['supplier_count' => 1, 'total' => 49.4664, 'avg' => 49.4664, 'min' => 49.4664, 'max' => 49.4664],
                    ],
                ],
                'period' => ['start_date' => '2026-01-01', 'end_date' => '2026-01-31'],
                'raw_row_count' => 17,
            ]);

        $this->app->instance(PenerimaanKayuBulatPerSupplierBulananGrafikReportService::class, $service);

        $this->withHeaders($this->authJsonHeaders($user))
            ->postJson('/api/reports/kayu-bulat/penerimaan-bulanan-per-supplier-grafik', [
                'TglAwal' => '2026-01-01',
                'TglAkhir' => '2026-01-31',
            ])
            ->assertOk()
            ->assertJsonPath('meta.total_groups', 1)
            ->assertJsonPath('meta.raw_row_count', 17)
            ->assertJsonPath('data.groups.0.name', 'JABON');
    }

    public function test_pdf_download_endpoint_returns_attachment(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(PenerimaanKayuBulatPerSupplierBulananGrafikReportService::class);
        $service
            ->shouldReceive('buildReportData')
            ->once()
            ->with('2026-01-01', '2026-01-31')
            ->andReturn([
                'groups' => [],
                'period' => ['start_date' => '2026-01-01', 'end_date' => '2026-01-31'],
                'raw_row_count' => 0,
            ]);

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(PenerimaanKayuBulatPerSupplierBulananGrafikReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $this->actingAs($user)
            ->post('/reports/kayu-bulat/penerimaan-bulanan-per-supplier-grafik/download', [
                'TglAwal' => '2026-01-01',
                'TglAkhir' => '2026-01-31',
            ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_health_endpoint_returns_structure_status(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(PenerimaanKayuBulatPerSupplierBulananGrafikReportService::class);
        $service
            ->shouldReceive('healthCheck')
            ->once()
            ->with('2026-01-01', '2026-01-31')
            ->andReturn([
                'is_healthy' => true,
                'row_count' => 10,
                'sub_row_count' => 10,
                'expected_columns' => [],
                'detected_columns' => [],
                'missing_columns' => [],
                'extra_columns' => [],
                'expected_sub_columns' => [],
                'detected_sub_columns' => [],
                'missing_sub_columns' => [],
                'extra_sub_columns' => [],
            ]);

        $this->app->instance(PenerimaanKayuBulatPerSupplierBulananGrafikReportService::class, $service);

        $this->withHeaders($this->authJsonHeaders($user))
            ->postJson('/api/reports/kayu-bulat/penerimaan-bulanan-per-supplier-grafik/health', [
                'TglAwal' => '2026-01-01',
                'TglAkhir' => '2026-01-31',
            ])
            ->assertOk()
            ->assertJsonPath('health.is_healthy', true)
            ->assertJsonPath('meta.TglAwal', '2026-01-01');
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

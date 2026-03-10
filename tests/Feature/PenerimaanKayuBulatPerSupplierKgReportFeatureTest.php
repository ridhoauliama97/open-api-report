<?php

namespace Tests\Feature;

use App\Http\Middleware\AuthenticateReportJwtClaims;
use App\Models\User;
use App\Services\PdfGenerator;
use App\Services\PenerimaanKayuBulatPerSupplierKgReportService;
use Mockery;
use Tests\TestCase;

class PenerimaanKayuBulatPerSupplierKgReportFeatureTest extends TestCase
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
        $this->get('/reports/kayu-bulat/penerimaan-per-supplier-kg')
            ->assertOk()
            ->assertSee('Generate Laporan Penerimaan Kayu Bulat Per-Supplier - Timbang KG (PDF)');
    }

    public function test_preview_endpoint_returns_grouped_data_by_supplier(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(PenerimaanKayuBulatPerSupplierKgReportService::class);
        $service
            ->shouldReceive('buildReportData')
            ->once()
            ->with('2026-01-01', '2026-01-31')
            ->andReturn([
                'rows' => [
                    ['Nama Supplier' => 'A Supplier', 'Jmlh Truk' => 2, 'Jenis' => 'RAMBUNG - STD', 'Berat' => 24.2],
                    ['Nama Supplier' => 'A Supplier', 'Jmlh Truk' => 2, 'Jenis' => 'RAMBUNG - MC', 'Berat' => 0.655],
                ],
                'columns' => [
                    'supplier' => 'Nama Supplier',
                    'truck' => 'Jmlh Truk',
                    'group' => 'Jenis',
                    'ton' => 'Berat',
                    'total_ton' => null,
                    'ratio' => null,
                ],
                'group_names' => ['RAMBUNG - MC', 'RAMBUNG - STD'],
                'suppliers' => [
                    [
                        'supplier' => 'A Supplier',
                        'trucks' => 2,
                        'groups' => [
                            'RAMBUNG - MC' => ['ton' => 0.655, 'ratio' => 2.63],
                            'RAMBUNG - STD' => ['ton' => 24.2, 'ratio' => 97.37],
                        ],
                        'total_ton' => 24.855,
                        'ratio' => 100.0,
                    ],
                ],
                'summary' => [
                    'total_groups' => 1,
                    'total_rows' => 2,
                    'total_suppliers' => 1,
                    'total_trucks' => 2,
                    'total_ton' => 24.855,
                    'working_days' => 31,
                    'daily_ton' => 0.8018,
                    'estimated_25_days_ton' => 20.045,
                    'group_totals' => ['RAMBUNG - MC' => 0.655, 'RAMBUNG - STD' => 24.2],
                    'group_ratios' => ['RAMBUNG - MC' => 2.63, 'RAMBUNG - STD' => 97.37],
                    'numeric_totals' => ['Berat' => 1950.75],
                ],
            ]);

        $this->app->instance(PenerimaanKayuBulatPerSupplierKgReportService::class, $service);

        $this->withHeaders($this->authJsonHeaders($user))
            ->postJson('/api/reports/kayu-bulat/penerimaan-per-supplier-kg', [
                'TglAwal' => '2026-01-01',
                'TglAkhir' => '2026-01-31',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Preview laporan berhasil diambil.')
            ->assertJsonPath('meta.total_rows', 2)
            ->assertJsonPath('meta.total_groups', 1)
            ->assertJsonPath('meta.columns.supplier', 'Nama Supplier')
            ->assertJsonPath('meta.group_names.0', 'RAMBUNG - MC')
            ->assertJsonPath('grouped_data.0.supplier', 'A Supplier')
            ->assertJsonPath('grouped_data.0.groups.RAMBUNG - STD.ton', 24.2)
            ->assertJsonPath('summary.numeric_totals.Berat', 1950.75);
    }

    public function test_pdf_download_endpoint_returns_attachment(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(PenerimaanKayuBulatPerSupplierKgReportService::class);
        $service
            ->shouldReceive('buildReportData')
            ->once()
            ->with('2026-01-01', '2026-01-31')
            ->andReturn([
                'rows' => [],
                'columns' => [],
                'group_names' => [],
                'suppliers' => [],
                'summary' => ['total_groups' => 0, 'total_rows' => 0, 'numeric_totals' => []],
            ]);

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(PenerimaanKayuBulatPerSupplierKgReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->actingAs($user)
            ->post('/reports/kayu-bulat/penerimaan-per-supplier-kg/download', [
                'TglAwal' => '2026-01-01',
                'TglAkhir' => '2026-01-31',
            ]);

        $response
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertStringContainsString(
            'attachment; filename=',
            (string) $response->headers->get('Content-Disposition'),
        );
    }

    public function test_health_endpoint_returns_structure_status(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(PenerimaanKayuBulatPerSupplierKgReportService::class);
        $service
            ->shouldReceive('healthCheck')
            ->once()
            ->with('2026-01-01', '2026-01-31')
            ->andReturn([
                'is_healthy' => true,
                'expected_columns' => ['No Kayu Bulat', 'Tanggal', 'Nama Supplier', 'Jenis', 'No Truk', 'Berat'],
                'detected_columns' => ['No Kayu Bulat', 'Tanggal', 'Nama Supplier', 'Jenis', 'No Truk', 'Berat'],
                'missing_columns' => [],
                'extra_columns' => [],
                'row_count' => 12,
            ]);

        $this->app->instance(PenerimaanKayuBulatPerSupplierKgReportService::class, $service);

        $this->withHeaders($this->authJsonHeaders($user))
            ->postJson('/api/reports/kayu-bulat/penerimaan-per-supplier-kg/health', [
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

    private function createBearerToken(User $user): string
    {
        return $this->issueJwtForUser($user);
    }
}

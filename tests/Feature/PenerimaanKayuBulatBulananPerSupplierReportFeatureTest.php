<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\PenerimaanKayuBulatBulananPerSupplierReportService;
use App\Services\PdfGenerator;
use Mockery;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class PenerimaanKayuBulatBulananPerSupplierReportFeatureTest extends TestCase
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
        $this->get('/reports/kayu-bulat/penerimaan-bulanan-per-supplier')
            ->assertOk()
            ->assertSee('Generate Laporan Penerimaan Kayu Bulat Bulanan Per Supplier (PDF)');
    }

    public function test_preview_endpoint_returns_grouped_data_and_summary(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $reportData = [
            'data' => [
                ['Nama Supplier' => 'A Supplier', 'Jenis' => 'JABON', 'Ton' => 10],
                ['Nama Supplier' => 'B Supplier', 'Jenis' => 'MERANTI', 'Ton' => 5],
            ],
            'sub_data' => [
                ['Nama Supplier' => 'A Supplier', 'Jenis' => 'A1', 'Ton' => 2],
            ],
            'grouped_data' => [
                ['supplier' => 'A Supplier', 'rows' => [['Nama Supplier' => 'A Supplier', 'Jenis' => 'JABON', 'Ton' => 10]]],
                ['supplier' => 'B Supplier', 'rows' => [['Nama Supplier' => 'B Supplier', 'Jenis' => 'MERANTI', 'Ton' => 5]]],
            ],
            'grouped_sub_data' => [
                ['supplier' => 'A Supplier', 'rows' => [['Nama Supplier' => 'A Supplier', 'Jenis' => 'A1', 'Ton' => 2]]],
            ],
            'summary' => [
                'main' => [
                    'total_suppliers' => 2,
                    'total_rows' => 2,
                    'numeric_totals' => ['Ton' => 15],
                ],
                'sub' => [
                    'total_suppliers' => 1,
                    'total_rows' => 1,
                    'numeric_totals' => ['Ton' => 2],
                ],
            ],
            'supplier_column' => 'Nama Supplier',
            'sub_supplier_column' => 'Nama Supplier',
        ];

        $service = Mockery::mock(PenerimaanKayuBulatBulananPerSupplierReportService::class);
        $service
            ->shouldReceive('buildReportData')
            ->once()
            ->with('2026-01-01', '2026-01-31')
            ->andReturn($reportData);

        $this->app->instance(PenerimaanKayuBulatBulananPerSupplierReportService::class, $service);

        $this->withHeaders($this->authJsonHeaders($user))
            ->postJson('/api/reports/kayu-bulat/penerimaan-bulanan-per-supplier', [
                'TglAwal' => '2026-01-01',
                'TglAkhir' => '2026-01-31',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Preview laporan berhasil diambil.')
            ->assertJsonPath('meta.total_rows', 2)
            ->assertJsonPath('meta.total_sub_rows', 1)
            ->assertJsonPath('meta.total_suppliers', 2)
            ->assertJsonPath('summary.main.total_suppliers', 2)
            ->assertJsonPath('summary.main.numeric_totals.Ton', 15)
            ->assertJsonPath('grouped_data.0.supplier', 'A Supplier')
            ->assertJsonPath('grouped_data.1.supplier', 'B Supplier');
    }

    public function test_pdf_download_endpoint_returns_attachment(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(PenerimaanKayuBulatBulananPerSupplierReportService::class);
        $service
            ->shouldReceive('buildReportData')
            ->once()
            ->with('2026-01-01', '2026-01-31')
            ->andReturn([
                'data' => [],
                'sub_data' => [],
                'grouped_data' => [],
                'grouped_sub_data' => [],
                'summary' => ['main' => ['total_suppliers' => 0, 'total_rows' => 0], 'sub' => ['total_suppliers' => 0, 'total_rows' => 0]],
                'supplier_column' => null,
                'sub_supplier_column' => null,
            ]);

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(PenerimaanKayuBulatBulananPerSupplierReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $this->actingAs($user)
            ->post('/reports/kayu-bulat/penerimaan-bulanan-per-supplier/download', [
                'TglAwal' => '2026-01-01',
                'TglAkhir' => '2026-01-31',
            ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'attachment; filename="laporan-penerimaan-kayu-bulat-bulanan-per-supplier-2026-01-01-sd-2026-01-31.pdf"');
    }

    public function test_health_endpoint_returns_structure_status(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(PenerimaanKayuBulatBulananPerSupplierReportService::class);
        $service
            ->shouldReceive('healthCheck')
            ->once()
            ->with('2026-01-01', '2026-01-31')
            ->andReturn([
                'is_healthy' => true,
                'expected_columns' => ['Nama Supplier', 'Ton'],
                'detected_columns' => ['Nama Supplier', 'Ton'],
                'missing_columns' => [],
                'extra_columns' => [],
                'row_count' => 20,
                'expected_sub_columns' => ['Nama Supplier', 'Ton'],
                'detected_sub_columns' => ['Nama Supplier', 'Ton'],
                'missing_sub_columns' => [],
                'extra_sub_columns' => [],
                'sub_row_count' => 4,
            ]);

        $this->app->instance(PenerimaanKayuBulatBulananPerSupplierReportService::class, $service);

        $this->withHeaders($this->authJsonHeaders($user))
            ->postJson('/api/reports/kayu-bulat/penerimaan-bulanan-per-supplier/health', [
                'TglAwal' => '2026-01-01',
                'TglAkhir' => '2026-01-31',
            ])
            ->assertOk()
            ->assertJsonPath('health.is_healthy', true)
            ->assertJsonPath('health.row_count', 20)
            ->assertJsonPath('health.sub_row_count', 4)
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
        return (string) JWTAuth::fromUser($user);
    }
}

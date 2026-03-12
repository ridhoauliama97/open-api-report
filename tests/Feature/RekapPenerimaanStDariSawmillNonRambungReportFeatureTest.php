<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\PdfGenerator;
use App\Services\RekapPenerimaanSTDariSawmillNonRambungReportService;
use Mockery;
use Tests\TestCase;

class RekapPenerimaanStDariSawmillNonRambungReportFeatureTest extends TestCase
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
        $this->get('/reports/sawn-timber/rekap-penerimaan-st-dari-sawmill-non-rambung')
            ->assertOk()
            ->assertSee('Generate Laporan Rekap Penerimaan ST Dari Sawmill (Non Rambung) (PDF)');
    }

    public function test_preview_endpoint_returns_grouped_data_by_supplier_sorted_by_date(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(RekapPenerimaanSTDariSawmillNonRambungReportService::class);
        $service
            ->shouldReceive('buildReportData')
            ->once()
            ->with('2026-01-01', '2026-01-31')
            ->andReturn([
                'rows' => [
                    ['Nama Supplier' => 'A Supplier', 'Tanggal' => '2026-01-01', 'Qty' => 1],
                    ['Nama Supplier' => 'A Supplier', 'Tanggal' => '2026-01-02', 'Qty' => 2],
                    ['Nama Supplier' => 'B Supplier', 'Tanggal' => '2026-01-01', 'Qty' => 3],
                ],
                'supplier_groups' => [
                    [
                        'supplier' => 'A Supplier',
                        'rows' => [
                            ['Nama Supplier' => 'A Supplier', 'Tanggal' => '2026-01-01', 'Qty' => 1],
                            ['Nama Supplier' => 'A Supplier', 'Tanggal' => '2026-01-02', 'Qty' => 2],
                        ],
                    ],
                    [
                        'supplier' => 'B Supplier',
                        'rows' => [
                            ['Nama Supplier' => 'B Supplier', 'Tanggal' => '2026-01-01', 'Qty' => 3],
                        ],
                    ],
                ],
                'supplier_column' => 'Nama Supplier',
                'date_column' => 'Tanggal',
                'summary' => [
                    'total_rows' => 3,
                    'total_suppliers' => 2,
                ],
            ]);

        $this->app->instance(RekapPenerimaanSTDariSawmillNonRambungReportService::class, $service);

        $this->withHeaders($this->authJsonHeaders($user))
            ->postJson('/api/reports/sawn-timber/rekap-penerimaan-st-dari-sawmill-non-rambung', [
                'TglAwal' => '2026-01-01',
                'TglAkhir' => '2026-01-31',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Preview laporan berhasil diambil.')
            ->assertJsonPath('meta.total_rows', 3)
            ->assertJsonPath('meta.total_suppliers', 2)
            ->assertJsonPath('meta.supplier_column', 'Nama Supplier')
            ->assertJsonPath('meta.date_column', 'Tanggal')
            ->assertJsonPath('grouped_data.0.supplier', 'A Supplier')
            ->assertJsonPath('grouped_data.0.rows.0.Tanggal', '2026-01-01')
            ->assertJsonPath('grouped_data.0.rows.1.Tanggal', '2026-01-02')
            ->assertJsonPath('grouped_data.1.supplier', 'B Supplier');
    }

    public function test_pdf_download_endpoint_returns_attachment(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(RekapPenerimaanSTDariSawmillNonRambungReportService::class);
        $service
            ->shouldReceive('buildReportData')
            ->once()
            ->with('2026-01-01', '2026-01-31')
            ->andReturn([
                'rows' => [],
                'supplier_groups' => [],
                'supplier_column' => null,
                'date_column' => null,
                'summary' => ['total_rows' => 0, 'total_suppliers' => 0],
            ]);

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(RekapPenerimaanSTDariSawmillNonRambungReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->actingAs($user)
            ->post('/reports/sawn-timber/rekap-penerimaan-st-dari-sawmill-non-rambung/download', [
                'TglAwal' => '2026-01-01',
                'TglAkhir' => '2026-01-31',
            ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'attachment', 'Laporan Rekap Penerimaan ST Dari Sawmill Non Rambung');
    }

    public function test_health_endpoint_returns_structure_status(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(RekapPenerimaanSTDariSawmillNonRambungReportService::class);
        $service
            ->shouldReceive('healthCheck')
            ->once()
            ->with('2026-01-01', '2026-01-31')
            ->andReturn([
                'is_healthy' => true,
                'expected_columns' => ['Nama Supplier', 'Tanggal'],
                'detected_columns' => ['Nama Supplier', 'Tanggal'],
                'missing_columns' => [],
                'extra_columns' => [],
                'row_count' => 12,
            ]);

        $this->app->instance(RekapPenerimaanSTDariSawmillNonRambungReportService::class, $service);

        $this->withHeaders($this->authJsonHeaders($user))
            ->postJson('/api/reports/sawn-timber/rekap-penerimaan-st-dari-sawmill-non-rambung/health', [
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


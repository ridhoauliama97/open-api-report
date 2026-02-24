<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\PdfGenerator;
use App\Services\PenerimaanStSawmillKgReportService;
use Mockery;
use Tests\TestCase;

class PenerimaanStSawmillKgReportFeatureTest extends TestCase
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
        $this->get('/reports/sawn-timber/penerimaan-st-dari-sawmill-kg')
            ->assertOk()
            ->assertSee('Generate Laporan Penerimaan ST Dari Sawmill - Timbang KG (PDF)');
    }

    public function test_preview_endpoint_returns_grouped_data_by_supplier(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(PenerimaanStSawmillKgReportService::class);
        $service
            ->shouldReceive('buildReportData')
            ->once()
            ->with('2026-01-01', '2026-01-31')
            ->andReturn([
                'rows' => [
                    ['NoPenST' => 'B.161727', 'Supplier' => 'A Supplier', 'TonKG' => 3.5],
                    ['NoPenST' => 'B.161728', 'Supplier' => 'B Supplier', 'TonKG' => 1.2],
                ],
                'grouped_rows' => [
                    [
                        'no_penerimaan_st' => 'B.161727',
                        'supplier' => 'A Supplier',
                        'rows' => [['NoPenST' => 'B.161727', 'Supplier' => 'A Supplier', 'TonKG' => 3.5]],
                    ],
                    [
                        'no_penerimaan_st' => 'B.161728',
                        'supplier' => 'B Supplier',
                        'rows' => [['NoPenST' => 'B.161728', 'Supplier' => 'B Supplier', 'TonKG' => 1.2]],
                    ],
                ],
                'no_penerimaan_column' => 'NoPenST',
                'supplier_column' => 'Supplier',
                'summary' => [
                    'total_groups' => 2,
                    'total_rows' => 2,
                    'numeric_totals' => ['TonKG' => 4.7],
                ],
            ]);

        $this->app->instance(PenerimaanStSawmillKgReportService::class, $service);

        $this->withHeaders($this->authJsonHeaders($user))
            ->postJson('/api/reports/sawn-timber/penerimaan-st-dari-sawmill-kg', [
                'TglAwal' => '2026-01-01',
                'TglAkhir' => '2026-01-31',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Preview laporan berhasil diambil.')
            ->assertJsonPath('meta.total_rows', 2)
            ->assertJsonPath('meta.total_groups', 2)
            ->assertJsonPath('meta.no_penerimaan_column', 'NoPenST')
            ->assertJsonPath('meta.supplier_column', 'Supplier')
            ->assertJsonPath('grouped_data.0.no_penerimaan_st', 'B.161727')
            ->assertJsonPath('grouped_data.1.no_penerimaan_st', 'B.161728')
            ->assertJsonPath('summary.numeric_totals.TonKG', 4.7);
    }

    public function test_pdf_download_endpoint_returns_attachment(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(PenerimaanStSawmillKgReportService::class);
        $service
            ->shouldReceive('buildReportData')
            ->once()
            ->with('2026-01-01', '2026-01-31')
            ->andReturn([
                'rows' => [],
                'grouped_rows' => [],
                'no_penerimaan_column' => null,
                'supplier_column' => null,
                'summary' => ['total_groups' => 0, 'total_rows' => 0, 'numeric_totals' => []],
            ]);

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(PenerimaanStSawmillKgReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $this->actingAs($user)
            ->post('/reports/sawn-timber/penerimaan-st-dari-sawmill-kg/download', [
                'TglAwal' => '2026-01-01',
                'TglAkhir' => '2026-01-31',
            ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'attachment; filename="Laporan-Penerimaan-ST-Dari-Sawmill-Timbang-KG-2026-01-01-sd-2026-01-31.pdf"');
    }

    public function test_health_endpoint_returns_structure_status(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(PenerimaanStSawmillKgReportService::class);
        $service
            ->shouldReceive('healthCheck')
            ->once()
            ->with('2026-01-01', '2026-01-31')
            ->andReturn([
                'is_healthy' => true,
                'expected_columns' => ['Supplier', 'TonKG'],
                'detected_columns' => ['Supplier', 'TonKG'],
                'missing_columns' => [],
                'extra_columns' => [],
                'row_count' => 12,
            ]);

        $this->app->instance(PenerimaanStSawmillKgReportService::class, $service);

        $this->withHeaders($this->authJsonHeaders($user))
            ->postJson('/api/reports/sawn-timber/penerimaan-st-dari-sawmill-kg/health', [
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

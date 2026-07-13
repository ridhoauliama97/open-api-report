<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\PdfGenerator;
use App\Services\PenerimaanStHasilSawmillReportService;
use Mockery;
use Tests\TestCase;

class PenerimaanStHasilSawmillReportFeatureTest extends TestCase
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
        $this->get('/reports/sawn-timber/penerimaan-st-hasil-sawmill')
            ->assertOk()
            ->assertSee('Generate Laporan Penerimaan ST Hasil Sawmill (PDF)');
    }

    public function test_preview_endpoint_returns_report_data(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(PenerimaanStHasilSawmillReportService::class);
        $service
            ->shouldReceive('buildReportData')
            ->once()
            ->with('B.162085')
            ->andReturn($this->reportData());

        $this->app->instance(PenerimaanStHasilSawmillReportService::class, $service);

        $this->withHeaders($this->authJsonHeaders($user))
            ->postJson('/api/reports/sawn-timber/penerimaan-st-hasil-sawmill', [
                'NoPenST' => 'B.162085',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Preview laporan berhasil diambil.')
            ->assertJsonPath('meta.no_pen_st', 'B.162085')
            ->assertJsonPath('meta.total_rows', 1)
            ->assertJsonPath('meta.total_sub_rows', 1)
            ->assertJsonPath('meta.total_pcs', 21)
            ->assertJsonPath('meta.total_ton', 0.0874)
            ->assertJsonPath('report_data.header.supplier', 'PUTRA T');
    }

    public function test_pdf_download_endpoint_returns_attachment(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(PenerimaanStHasilSawmillReportService::class);
        $service
            ->shouldReceive('buildReportData')
            ->once()
            ->with('B.162085')
            ->andReturn($this->reportData());

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(PenerimaanStHasilSawmillReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->actingAs($user)
            ->post('/reports/sawn-timber/penerimaan-st-hasil-sawmill/download', [
                'NoPenST' => 'B.162085',
            ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'attachment', 'Laporan Penerimaan ST Hasil Sawmill B.162085');
    }

    public function test_pdf_preview_endpoint_returns_attachment_pdf(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(PenerimaanStHasilSawmillReportService::class);
        $service
            ->shouldReceive('buildReportData')
            ->once()
            ->with('B.162085')
            ->andReturn($this->reportData());

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(PenerimaanStHasilSawmillReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->actingAs($user)
            ->post('/reports/sawn-timber/penerimaan-st-hasil-sawmill/preview-pdf', [
                'NoPenST' => 'B.162085',
            ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'attachment', 'Laporan Penerimaan ST Hasil Sawmill B.162085');
    }

    public function test_health_endpoint_returns_structure_status(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(PenerimaanStHasilSawmillReportService::class);
        $service
            ->shouldReceive('healthCheck')
            ->once()
            ->with('B.162085')
            ->andReturn([
                'is_healthy' => true,
                'expected_columns' => ['NamaGrade'],
                'detected_columns' => ['NamaGrade'],
                'missing_columns' => [],
                'extra_columns' => [],
                'expected_sub_columns' => ['Berat'],
                'detected_sub_columns' => ['Berat'],
                'missing_sub_columns' => [],
                'extra_sub_columns' => [],
                'row_count' => 12,
                'sub_row_count' => 3,
            ]);

        $this->app->instance(PenerimaanStHasilSawmillReportService::class, $service);

        $this->withHeaders($this->authJsonHeaders($user))
            ->postJson('/api/reports/sawn-timber/penerimaan-st-hasil-sawmill/health', [
                'NoPenST' => 'B.162085',
            ])
            ->assertOk()
            ->assertJsonPath('health.is_healthy', true)
            ->assertJsonPath('health.row_count', 12)
            ->assertJsonPath('health.sub_row_count', 3)
            ->assertJsonPath('meta.NoPenST', 'B.162085');
    }

    /**
     * @return array<string, mixed>
     */
    private function reportData(): array
    {
        return [
            'no_pen_st' => 'B.162085',
            'rows' => [
                ['NamaGrade' => 'STD', 'JmlhBatang' => 63, 'DisplayJmlhBatang' => 21],
            ],
            'sub_rows' => [
                ['NamaGrade' => 'RAMBUNG - STD', 'Berat' => 32.144],
            ],
            'header' => [
                'no_penerimaan_st' => 'B.162085',
                'supplier' => 'PUTRA T',
            ],
            'length_columns' => [
                ['key' => '1', 'label' => '1', 'raw_panjang' => 1.0, 'display_panjang' => 1],
            ],
            'grade_groups' => [],
            'sub_summary' => [
                'rows' => [['NamaGrade' => 'RAMBUNG - STD', 'Berat' => 32.144]],
                'total_berat' => 32.144,
            ],
            'summary' => [
                'total_rows' => 1,
                'total_pcs' => 21,
                'total_ton' => 0.0874,
                'totals' => ['1' => 21],
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function authJsonHeaders(User $user): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->issueJwtForUser($user),
            'Accept' => 'application/json',
        ];
    }
}

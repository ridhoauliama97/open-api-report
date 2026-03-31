<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\PdfGenerator;
use App\Services\RendemenSemuaProsesReportService;
use Mockery;
use Tests\TestCase;

class RendemenSemuaProsesReportFeatureTest extends TestCase
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
        $this->get('/reports/rendemen-kayu/rendemen-semua-proses')
            ->assertOk()
            ->assertSee('Generate Laporan Rendemen Semua Proses');
    }

    public function test_preview_endpoint_returns_report_payload(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(RendemenSemuaProsesReportService::class);
        $service->shouldReceive('buildReportData')
            ->once()
            ->with('2026-01-01', '2026-01-31')
            ->andReturn([
                'groups' => [
                    [
                        'name' => 'PACK',
                        'rows' => [
                            ['Tanggal' => '2026-01-01', 'Input' => 10, 'Output' => 8, 'Rendemen' => 80, 'GRP' => 'PACK'],
                        ],
                        'totals' => ['Input' => 10, 'Output' => 8, 'Rendemen' => 80],
                    ],
                ],
                'summary' => [
                    'total_rows' => 1,
                    'total_groups' => 1,
                    'grand_totals' => ['Input' => 10, 'Output' => 8, 'Rendemen' => 80],
                ],
            ]);

        $this->app->instance(RendemenSemuaProsesReportService::class, $service);

        $this->withHeaders($this->authJsonHeaders($user))
            ->postJson('/api/reports/rendemen-kayu/rendemen-semua-proses', [
                'TglAwal' => '2026-01-01',
                'TglAkhir' => '2026-01-31',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Preview laporan berhasil diambil.')
            ->assertJsonPath('meta.total_rows', 1)
            ->assertJsonPath('meta.total_groups', 1)
            ->assertJsonPath('summary.grand_totals.Rendemen', 80)
            ->assertJsonPath('data.0.GRP', 'PACK');
    }

    public function test_pdf_download_endpoint_returns_attachment(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(RendemenSemuaProsesReportService::class);
        $service->shouldReceive('buildReportData')
            ->once()
            ->with('2026-01-01', '2026-01-31')
            ->andReturn([
                'groups' => [],
                'summary' => ['total_rows' => 0, 'total_groups' => 0, 'grand_totals' => []],
            ]);

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator->shouldReceive('render')
            ->once()
            ->with('reports.rendemen-kayu.rendemen-semua-proses-pdf', Mockery::on(fn($data) => isset($data['pdf_orientation']) && $data['pdf_orientation'] === 'landscape'))
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(RendemenSemuaProsesReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->actingAs($user)
            ->post('/reports/rendemen-kayu/rendemen-semua-proses/download', [
                'TglAwal' => '2026-01-01',
                'TglAkhir' => '2026-01-31',
            ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'attachment', 'Laporan Rendemen Semua Proses');
    }

    public function test_health_endpoint_returns_structure_status(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(RendemenSemuaProsesReportService::class);
        $service->shouldReceive('healthCheck')
            ->once()
            ->with('2026-01-01', '2026-01-31')
            ->andReturn([
                'is_healthy' => true,
                'required_columns' => ['Tanggal', 'Input', 'Output', 'GRP'],
                'detected_columns' => ['Tanggal', 'Input', 'Output', 'GRP'],
                'missing_columns' => [],
                'row_count' => 12,
            ]);

        $this->app->instance(RendemenSemuaProsesReportService::class, $service);

        $this->withHeaders($this->authJsonHeaders($user))
            ->postJson('/api/reports/rendemen-kayu/rendemen-semua-proses/health', [
                'TglAwal' => '2026-01-01',
                'TglAkhir' => '2026-01-31',
            ])
            ->assertOk()
            ->assertJsonPath('health.is_healthy', true)
            ->assertJsonPath('health.row_count', 12)
            ->assertJsonPath('meta.start_date', '2026-01-01')
            ->assertJsonPath('meta.end_date', '2026-01-31');
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

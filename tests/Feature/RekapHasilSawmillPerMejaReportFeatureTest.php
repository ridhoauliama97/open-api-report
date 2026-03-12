<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\PdfGenerator;
use App\Services\RekapHasilSawmillPerMejaReportService;
use Mockery;
use Tests\TestCase;

class RekapHasilSawmillPerMejaReportFeatureTest extends TestCase
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
        $this->get('/reports/sawn-timber/rekap-hasil-sawmill-per-meja')
            ->assertOk()
            ->assertSee('Generate Laporan Rekap Hasil Sawmill / Meja');
    }

    public function test_preview_endpoint_returns_pivot_data(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(RekapHasilSawmillPerMejaReportService::class);
        $service
            ->shouldReceive('buildReportData')
            ->once()
            ->with('2026-01-01', '2026-01-31')
            ->andReturn([
                'rows' => [
                    ['NoMeja' => '1', 'TglSawmill' => '2026-01-05', 'Tebal' => 15.0, 'UOM' => 'mm', 'TonRacip' => 0.0681],
                ],
                'date_keys' => ['2026-01-05'],
                'meja_groups' => [
                    [
                        'no_meja' => 1,
                        'rows' => [
                            [
                                'tebal' => 15.0,
                                'uom' => 'mm',
                                'values' => ['2026-01-05' => 0.0681],
                                'row_total' => 0.0681,
                            ],
                        ],
                    ],
                ],
                'totals_by_date' => ['2026-01-05' => 0.0681],
                'grand_total' => 0.0681,
                'summary' => ['total_rows' => 1, 'total_meja' => 1, 'total_dates' => 1],
            ]);

        $this->app->instance(RekapHasilSawmillPerMejaReportService::class, $service);

        $this->withHeaders($this->authJsonHeaders($user))
            ->postJson('/api/reports/sawn-timber/rekap-hasil-sawmill-per-meja', [
                'TglAwal' => '2026-01-01',
                'TglAkhir' => '2026-01-31',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Preview laporan berhasil diambil.')
            ->assertJsonPath('meta.total_rows', 1)
            ->assertJsonPath('summary.total_meja', 1)
            ->assertJsonPath('data.date_keys.0', '2026-01-05')
            ->assertJsonPath('data.grand_total', 0.0681);
    }

    public function test_pdf_download_endpoint_returns_attachment(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(RekapHasilSawmillPerMejaReportService::class);
        $service
            ->shouldReceive('buildReportData')
            ->once()
            ->with('2026-01-01', '2026-01-31')
            ->andReturn([
                'rows' => [],
                'date_keys' => [],
                'meja_groups' => [],
                'totals_by_date' => [],
                'grand_total' => 0.0,
                'summary' => ['total_rows' => 0, 'total_meja' => 0, 'total_dates' => 0],
            ]);

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(RekapHasilSawmillPerMejaReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->actingAs($user)
            ->post('/reports/sawn-timber/rekap-hasil-sawmill-per-meja/download', [
                'TglAwal' => '2026-01-01',
                'TglAkhir' => '2026-01-31',
            ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'attachment', 'Laporan Rekap Hasil Sawmill Per Meja');
    }

    public function test_health_endpoint_returns_structure_status(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(RekapHasilSawmillPerMejaReportService::class);
        $service
            ->shouldReceive('healthCheck')
            ->once()
            ->with('2026-01-01', '2026-01-31')
            ->andReturn([
                'is_healthy' => true,
                'expected_columns' => ['NoMeja', 'TglSawmill', 'Tebal', 'UOM', 'TonRacip'],
                'detected_columns' => ['NoMeja', 'TglSawmill', 'Tebal', 'UOM', 'TonRacip'],
                'missing_columns' => [],
                'extra_columns' => [],
                'row_count' => 12,
            ]);

        $this->app->instance(RekapHasilSawmillPerMejaReportService::class, $service);

        $this->withHeaders($this->authJsonHeaders($user))
            ->postJson('/api/reports/sawn-timber/rekap-hasil-sawmill-per-meja/health', [
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
            'Authorization' => 'Bearer ' . $this->issueJwtForUser($user),
            'Accept' => 'application/json',
        ];
    }
}

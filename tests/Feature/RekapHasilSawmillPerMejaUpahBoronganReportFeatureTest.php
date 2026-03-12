<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\PdfGenerator;
use App\Services\RekapHasilSawmillPerMejaUpahBoronganReportService;
use Mockery;
use Tests\TestCase;

class RekapHasilSawmillPerMejaUpahBoronganReportFeatureTest extends TestCase
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
        $this->get('/reports/sawn-timber/rekap-hasil-sawmill-per-meja-upah-borongan')
            ->assertOk()
            ->assertSee('Generate Laporan Rekap Hasil Sawmill Per-Meja (Upah Borongan)');
    }

    public function test_preview_endpoint_returns_grouped_data_by_meja_and_date(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(RekapHasilSawmillPerMejaUpahBoronganReportService::class);
        $service
            ->shouldReceive('buildReportData')
            ->once()
            ->with('2026-01-01', '2026-01-31')
            ->andReturn([
                'rows' => [
                    ['NoMeja' => 1, 'TglSawmill' => '2026-01-05', 'Jenis' => 'STD', 'TonRacip' => 0.1],
                    ['NoMeja' => 1, 'TglSawmill' => '2026-01-06', 'Jenis' => 'STD', 'TonRacip' => 0.2],
                ],
                'sub_rows' => [
                    ['NoMeja' => 1, 'TglSawmill' => '2026-01-05', 'SM' => 1.0],
                ],
                'grouped_rows' => [
                    [
                        'no_meja' => 1,
                        'nama_meja' => 'Meja 1',
                        'date_groups' => [
                            ['date' => '2026-01-05', 'rows' => [['NoMeja' => 1, 'TglSawmill' => '2026-01-05']]],
                            ['date' => '2026-01-06', 'rows' => [['NoMeja' => 1, 'TglSawmill' => '2026-01-06']]],
                        ],
                    ],
                ],
                'grouped_sub_rows' => [
                    [
                        'no_meja' => 1,
                        'nama_meja' => 'Meja 1',
                        'date_groups' => [
                            ['date' => '2026-01-05', 'rows' => [['NoMeja' => 1, 'TglSawmill' => '2026-01-05']]],
                        ],
                    ],
                ],
                'summary' => [
                    'main' => ['total_meja' => 1, 'total_rows' => 2],
                    'sub' => ['total_meja' => 1, 'total_rows' => 1],
                ],
            ]);

        $this->app->instance(RekapHasilSawmillPerMejaUpahBoronganReportService::class, $service);

        $this->withHeaders($this->authJsonHeaders($user))
            ->postJson('/api/reports/sawn-timber/rekap-hasil-sawmill-per-meja-upah-borongan', [
                'TglAwal' => '2026-01-01',
                'TglAkhir' => '2026-01-31',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Preview laporan berhasil diambil.')
            ->assertJsonPath('meta.total_rows', 2)
            ->assertJsonPath('meta.total_sub_rows', 1)
            ->assertJsonPath('grouped_data.0.no_meja', 1)
            ->assertJsonPath('grouped_data.0.date_groups.0.date', '2026-01-05')
            ->assertJsonPath('grouped_data.0.date_groups.1.date', '2026-01-06');
    }

    public function test_pdf_download_endpoint_returns_attachment(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(RekapHasilSawmillPerMejaUpahBoronganReportService::class);
        $service
            ->shouldReceive('buildReportData')
            ->once()
            ->with('2026-01-01', '2026-01-31')
            ->andReturn([
                'rows' => [],
                'sub_rows' => [],
                'grouped_rows' => [],
                'grouped_sub_rows' => [],
                'summary' => ['main' => ['total_meja' => 0], 'sub' => ['total_meja' => 0]],
            ]);

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(RekapHasilSawmillPerMejaUpahBoronganReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->actingAs($user)
            ->post('/reports/sawn-timber/rekap-hasil-sawmill-per-meja-upah-borongan/download', [
                'TglAwal' => '2026-01-01',
                'TglAkhir' => '2026-01-31',
            ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'attachment', 'Laporan Rekap Hasil Sawmill Per Meja (Upah Borongan)');
    }

    public function test_health_endpoint_returns_structure_status(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(RekapHasilSawmillPerMejaUpahBoronganReportService::class);
        $service
            ->shouldReceive('healthCheck')
            ->once()
            ->with('2026-01-01', '2026-01-31')
            ->andReturn([
                'is_healthy' => true,
                'expected_columns' => ['NoMeja', 'TglSawmill'],
                'detected_columns' => ['NoMeja', 'TglSawmill'],
                'missing_columns' => [],
                'extra_columns' => [],
                'row_count' => 12,
                'expected_sub_columns' => ['NoMeja', 'TglSawmill'],
                'detected_sub_columns' => ['NoMeja', 'TglSawmill'],
                'missing_sub_columns' => [],
                'extra_sub_columns' => [],
                'sub_row_count' => 6,
            ]);

        $this->app->instance(RekapHasilSawmillPerMejaUpahBoronganReportService::class, $service);

        $this->withHeaders($this->authJsonHeaders($user))
            ->postJson('/api/reports/sawn-timber/rekap-hasil-sawmill-per-meja-upah-borongan/health', [
                'TglAwal' => '2026-01-01',
                'TglAkhir' => '2026-01-31',
            ])
            ->assertOk()
            ->assertJsonPath('health.is_healthy', true)
            ->assertJsonPath('health.row_count', 12)
            ->assertJsonPath('health.sub_row_count', 6)
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

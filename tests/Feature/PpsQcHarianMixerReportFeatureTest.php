<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\PdfGenerator;
use App\Services\PPS\QcHarianMixerReportService;
use Mockery;
use Tests\TestCase;

class PpsQcHarianMixerReportFeatureTest extends TestCase
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
        $this->get('/reports/pps/qc/qc-harian-mixer')
            ->assertOk()
            ->assertSee('Generate Laporan QC Harian Mixer (PPS)');
    }

    public function test_preview_endpoint_returns_json_data(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(QcHarianMixerReportService::class);
        $service
            ->shouldReceive('fetchByDate')
            ->once()
            ->with('2026-04-20')
            ->andReturn([
                [
                    'DateCreate' => '2026-04-20',
                    'NoMixer' => 'H.0000027177',
                    'Jenis' => 'PP MIX HITAM LEMARI',
                    'Moisture' => 0.29,
                    'Moisture2' => 0.41,
                    'Moisture3' => 0.34,
                    'MFI' => 11.4,
                    'MeltTemp' => '220 - 230',
                ],
            ]);

        $this->app->instance(QcHarianMixerReportService::class, $service);

        $this->withHeaders($this->authJsonHeaders($user))
            ->postJson('/api/reports/pps/qc/qc-harian-mixer', ['EndDate' => '2026-04-20'])
            ->assertOk()
            ->assertJsonPath('message', 'Preview laporan berhasil diambil.')
            ->assertJsonPath('meta.report_date', '2026-04-20')
            ->assertJsonPath('meta.EndDate', '2026-04-20')
            ->assertJsonPath('meta.total_rows', 1)
            ->assertJsonPath('data.0.NoMixer', 'H.0000027177');
    }

    public function test_pdf_download_endpoint_returns_attachment(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(QcHarianMixerReportService::class);
        $service
            ->shouldReceive('fetchByDate')
            ->once()
            ->with('2026-04-20')
            ->andReturn([]);

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(QcHarianMixerReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->actingAs($user)
            ->post('/reports/pps/qc/qc-harian-mixer/download', ['EndDate' => '2026-04-20'])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'attachment', 'Laporan-QC-Harian-Mixer-2026-04-20.pdf');
    }

    public function test_health_endpoint_returns_structure_status(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(QcHarianMixerReportService::class);
        $service
            ->shouldReceive('healthCheck')
            ->once()
            ->with('2026-04-20')
            ->andReturn([
                'is_healthy' => true,
                'expected_columns' => ['DateCreate', 'NoMixer'],
                'detected_columns' => ['DateCreate', 'NoMixer'],
                'missing_columns' => [],
                'extra_columns' => [],
                'row_count' => 4,
            ]);

        $this->app->instance(QcHarianMixerReportService::class, $service);

        $this->withHeaders($this->authJsonHeaders($user))
            ->postJson('/api/reports/pps/qc/qc-harian-mixer/health', ['EndDate' => '2026-04-20'])
            ->assertOk()
            ->assertJsonPath('health.is_healthy', true)
            ->assertJsonPath('health.row_count', 4);
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

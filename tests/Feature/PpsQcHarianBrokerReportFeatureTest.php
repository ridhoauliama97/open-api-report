<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\PdfGenerator;
use App\Services\PPS\QcHarianBrokerReportService;
use Mockery;
use Tests\TestCase;

class PpsQcHarianBrokerReportFeatureTest extends TestCase
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
        $this->get('/reports/pps/qc/qc-harian-broker')
            ->assertOk()
            ->assertSee('Generate Laporan QC Harian Broker (PPS)');
    }

    public function test_preview_endpoint_returns_json_data(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(QcHarianBrokerReportService::class);
        $service
            ->shouldReceive('fetchByDate')
            ->once()
            ->with('2026-04-20')
            ->andReturn([
                [
                    'DateCreate' => '2026-04-20',
                    'NamaMesin' => 'MESIN BROKER 2',
                    'Shift' => '1',
                    'Jenis' => 'PP GIL HITAM (BROKER)',
                    'NoBroker' => 'D.0000017863',
                    'Moisture' => 0.23,
                    'Moisture2' => 0.21,
                    'Moisture3' => 0.37,
                    'Density' => 0.817,
                    'Density2' => 0.705,
                    'Density3' => 0.77,
                    'MFI' => 9.93,
                    'VisualNote' => '',
                ],
            ]);

        $this->app->instance(QcHarianBrokerReportService::class, $service);

        $this->withHeaders($this->authJsonHeaders($user))
            ->postJson('/api/reports/pps/qc/qc-harian-broker', ['EndDate' => '2026-04-20'])
            ->assertOk()
            ->assertJsonPath('message', 'Preview laporan berhasil diambil.')
            ->assertJsonPath('meta.report_date', '2026-04-20')
            ->assertJsonPath('meta.EndDate', '2026-04-20')
            ->assertJsonPath('meta.total_rows', 1)
            ->assertJsonPath('data.0.NoBroker', 'D.0000017863');
    }

    public function test_pdf_download_endpoint_returns_attachment(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(QcHarianBrokerReportService::class);
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

        $this->app->instance(QcHarianBrokerReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->actingAs($user)
            ->post('/reports/pps/qc/qc-harian-broker/download', ['EndDate' => '2026-04-20'])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'attachment', 'Laporan-QC-Harian-Broker-2026-04-20.pdf');
    }

    public function test_health_endpoint_returns_structure_status(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(QcHarianBrokerReportService::class);
        $service
            ->shouldReceive('healthCheck')
            ->once()
            ->with('2026-04-20')
            ->andReturn([
                'is_healthy' => true,
                'expected_columns' => ['DateCreate', 'NoBroker'],
                'detected_columns' => ['DateCreate', 'NoBroker'],
                'missing_columns' => [],
                'extra_columns' => [],
                'row_count' => 8,
            ]);

        $this->app->instance(QcHarianBrokerReportService::class, $service);

        $this->withHeaders($this->authJsonHeaders($user))
            ->postJson('/api/reports/pps/qc/qc-harian-broker/health', ['EndDate' => '2026-04-20'])
            ->assertOk()
            ->assertJsonPath('health.is_healthy', true)
            ->assertJsonPath('health.row_count', 8);
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

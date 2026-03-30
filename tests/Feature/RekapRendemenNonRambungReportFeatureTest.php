<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\PdfGenerator;
use App\Services\RekapRendemenNonRambungReportService;
use Mockery;
use Tests\TestCase;

class RekapRendemenNonRambungReportFeatureTest extends TestCase
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
        $this->get('/reports/rendemen-kayu/rekap-rendemen-non-rambung')
            ->assertOk()
            ->assertSee('Generate Laporan Rekap Rendemen Non Rambung (PDF)');
    }

    public function test_preview_endpoint_returns_report_payload(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(RekapRendemenNonRambungReportService::class);
        $service
            ->shouldReceive('buildReportData')
            ->once()
            ->with('2026', '3')
            ->andReturn([
                'rows' => [
                    ['Tanggal' => '2026-01-01', 'Ton KB' => 10, 'Rendemen' => 0.65],
                ],
                'column_order' => ['Tanggal', 'Ton KB', 'Rendemen'],
                'column_schema' => [
                    ['key' => 'Tanggal', 'label' => 'Tanggal', 'type' => 'date'],
                    ['key' => 'Ton KB', 'label' => 'Ton KB', 'type' => 'number'],
                    ['key' => 'Rendemen', 'label' => 'Rendemen', 'type' => 'percent'],
                ],
                'summary' => [
                    'total_rows' => 1,
                    'total_columns' => 3,
                ],
            ]);

        $this->app->instance(RekapRendemenNonRambungReportService::class, $service);

        $this->withHeaders($this->authJsonHeaders($user))
            ->postJson('/api/reports/rendemen-kayu/rekap-rendemen-non-rambung', [
                'Tahun' => 2026,
                'Bulan' => 3,
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Preview laporan berhasil diambil.')
            ->assertJsonPath('meta.total_rows', 1)
            ->assertJsonPath('meta.Tahun', '2026')
            ->assertJsonPath('meta.Bulan', '3')
            ->assertJsonPath('meta.column_order.0', 'Tanggal')
            ->assertJsonPath('summary.total_columns', 3)
            ->assertJsonPath('data.0.Rendemen', 0.65);
    }

    public function test_pdf_download_endpoint_returns_attachment(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(RekapRendemenNonRambungReportService::class);
        $service
            ->shouldReceive('buildReportData')
            ->once()
            ->with('2026', '3')
            ->andReturn([
                'rows' => [],
                'column_order' => [],
                'column_schema' => [],
                'summary' => ['total_rows' => 0, 'total_columns' => 0],
            ]);

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(RekapRendemenNonRambungReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->actingAs($user)
            ->post('/reports/rendemen-kayu/rekap-rendemen-non-rambung/download', [
                'Tahun' => 2026,
                'Bulan' => 3,
            ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'attachment', 'Laporan Rekap Rendemen Non Rambung');
    }

    public function test_health_endpoint_returns_structure_status(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(RekapRendemenNonRambungReportService::class);
        $service
            ->shouldReceive('healthCheck')
            ->once()
            ->with('2026', '3')
            ->andReturn([
                'is_healthy' => true,
                'expected_columns' => ['Tanggal', 'Rendemen'],
                'detected_columns' => ['Tanggal', 'Rendemen'],
                'missing_columns' => [],
                'extra_columns' => [],
                'row_count' => 12,
            ]);

        $this->app->instance(RekapRendemenNonRambungReportService::class, $service);

        $this->withHeaders($this->authJsonHeaders($user))
            ->postJson('/api/reports/rendemen-kayu/rekap-rendemen-non-rambung/health', [
                'Tahun' => 2026,
                'Bulan' => 3,
            ])
            ->assertOk()
            ->assertJsonPath('health.is_healthy', true)
            ->assertJsonPath('health.row_count', 12)
            ->assertJsonPath('meta.Tahun', '2026')
            ->assertJsonPath('meta.Bulan', '3');
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

<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\KayuBulatHidupReportService;
use App\Services\PdfGenerator;
use Mockery;
use Tests\TestCase;

class KayuBulatHidupReportFeatureTest extends TestCase
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
        $this->get('/reports/kayu-bulat/hidup')
            ->assertOk()
            ->assertSee('Generate Laporan Kayu Bulat Hidup (PDF)');
    }

    public function test_preview_endpoint_returns_json_data(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(KayuBulatHidupReportService::class);
        $service
            ->shouldReceive('buildReportData')
            ->once()
            ->with('2026-01-01', '2026-01-31')
            ->andReturn([
                'rows' => [
                    [
                        'NoKayuBulat' => 'A.016755',
                        'Tanggal' => '2026-01-27',
                        'Supplier' => 'PUTRA T',
                        'NoTruk' => '1275',
                        'Jenis' => 'JABON',
                        'Pcs' => 297,
                        'BlkTepakai' => 237,
                    ],
                ],
                'summary' => [
                    'total_rows' => 1,
                    'total_pcs' => 297,
                    'total_blk_terpakai' => 237,
                ],
            ]);

        $this->app->instance(KayuBulatHidupReportService::class, $service);

        $this->withHeaders($this->authJsonHeaders($user))
            ->postJson('/api/reports/kayu-bulat/hidup', [
                'TglAwal' => '2026-01-01',
                'TglAkhir' => '2026-01-31',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Preview laporan berhasil diambil.')
            ->assertJsonPath('meta.TglAwal', '2026-01-01')
            ->assertJsonPath('meta.TglAkhir', '2026-01-31')
            ->assertJsonPath('summary.total_pcs', 297)
            ->assertJsonPath('data.0.NoKayuBulat', 'A.016755');
    }

    public function test_pdf_download_endpoint_returns_attachment(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(KayuBulatHidupReportService::class);
        $service
            ->shouldReceive('buildReportData')
            ->once()
            ->with('2026-01-01', '2026-01-31')
            ->andReturn([
                'rows' => [],
                'summary' => [
                    'total_rows' => 0,
                    'total_pcs' => 0,
                    'total_blk_terpakai' => 0,
                ],
            ]);

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(KayuBulatHidupReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $this->actingAs($user)
            ->post('/reports/kayu-bulat/hidup/download', [
                'TglAwal' => '2026-01-01',
                'TglAkhir' => '2026-01-31',
            ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'attachment; filename="laporan-kayu-bulat-hidup-2026-01-01-sd-2026-01-31.pdf"');
    }

    public function test_health_endpoint_returns_structure_status(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(KayuBulatHidupReportService::class);
        $service
            ->shouldReceive('healthCheck')
            ->once()
            ->with('2026-01-01', '2026-01-31')
            ->andReturn([
                'is_healthy' => true,
                'expected_columns' => ['NoKayuBulat', 'Tanggal'],
                'detected_columns' => ['NoKayuBulat', 'Tanggal'],
                'missing_columns' => [],
                'extra_columns' => [],
                'row_count' => 53,
            ]);

        $this->app->instance(KayuBulatHidupReportService::class, $service);

        $this->withHeaders($this->authJsonHeaders($user))
            ->postJson('/api/reports/kayu-bulat/hidup/health', [
                'TglAwal' => '2026-01-01',
                'TglAkhir' => '2026-01-31',
            ])
            ->assertOk()
            ->assertJsonPath('health.is_healthy', true)
            ->assertJsonPath('health.row_count', 53)
            ->assertJsonPath('meta.TglAwal', '2026-01-01');
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





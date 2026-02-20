<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\PdfGenerator;
use App\Services\SaldoKayuBulatReportService;
use Mockery;
use Tests\TestCase;

class SaldoKayuBulatReportFeatureTest extends TestCase
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

    public function test_saldo_kayu_bulat_form_page_is_accessible(): void
    {
        $this->get('/reports/kayu-bulat/saldo')
            ->assertOk()
            ->assertSee('Generate Laporan Saldo Kayu Bulat (PDF)');
    }

    public function test_saldo_kayu_bulat_preview_endpoint_returns_json_data(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(SaldoKayuBulatReportService::class);
        $service
            ->shouldReceive('fetch')
            ->once()
            ->with('2026-01-01', '2026-01-31')
            ->andReturn([
                [
                    'NokayuBulat' => 'KB-001',
                    'DateCreate' => '01 Jan 2026',
                    'DateUsage' => '',
                    'Jenis' => 'JABON',
                    'NmSupplier' => 'SUP A 1234',
                    'Ton' => 12.34,
                ],
            ]);

        $this->app->instance(SaldoKayuBulatReportService::class, $service);

        $this->withHeaders($this->authJsonHeaders($user))
            ->postJson('/api/reports/kayu-bulat/saldo', [
                'TglAwal' => '2026-01-01',
                'TglAkhir' => '2026-01-31',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Preview laporan berhasil diambil.')
            ->assertJsonPath('meta.total_rows', 1)
            ->assertJsonCount(1, 'data');
    }

    public function test_saldo_kayu_bulat_pdf_download_endpoint_returns_attachment(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(SaldoKayuBulatReportService::class);
        $service
            ->shouldReceive('fetch')
            ->once()
            ->with('2026-01-01', '2026-01-31')
            ->andReturn([
                [
                    'NokayuBulat' => 'KB-001',
                    'DateCreate' => '01 Jan 2026',
                    'DateUsage' => '',
                    'Jenis' => 'JABON',
                    'NmSupplier' => 'SUP A 1234',
                    'Ton' => 12.34,
                ],
            ]);

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(SaldoKayuBulatReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $this->actingAs($user)
            ->post('/reports/kayu-bulat/saldo/download', [
                'TglAwal' => '2026-01-01',
                'TglAkhir' => '2026-01-31',
            ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'attachment; filename="Laporan-Saldo-Kayu-Bulat-2026-01-01-sd-2026-01-31.pdf"');
    }

    public function test_saldo_kayu_bulat_health_endpoint_returns_structure_status(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(SaldoKayuBulatReportService::class);
        $service
            ->shouldReceive('healthCheck')
            ->once()
            ->with('2026-01-01', '2026-01-31')
            ->andReturn([
                'is_healthy' => true,
                'expected_columns' => ['NokayuBulat', 'DateCreate', 'DateUsage', 'Jenis', 'NmSupplier', 'Ton'],
                'detected_columns' => ['NokayuBulat', 'DateCreate', 'DateUsage', 'Jenis', 'NmSupplier', 'Ton'],
                'missing_columns' => [],
                'extra_columns' => [],
                'row_count' => 3,
            ]);

        $this->app->instance(SaldoKayuBulatReportService::class, $service);

        $this->withHeaders($this->authJsonHeaders($user))
            ->postJson('/api/reports/kayu-bulat/saldo/health', [
                'TglAwal' => '2026-01-01',
                'TglAkhir' => '2026-01-31',
            ])
            ->assertOk()
            ->assertJsonPath('health.is_healthy', true)
            ->assertJsonPath('health.row_count', 3);
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





<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\KdUpahPerCustomerReportService;
use App\Services\PdfGenerator;
use Mockery;
use Tests\TestCase;

class KdUpahPerCustomerReportFeatureTest extends TestCase
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
        $this->get('/reports/sawn-timber/kd-upah-per-customer')
            ->assertOk()
            ->assertSee('Generate Laporan KD Upah Per-Cutomer');
    }

    public function test_preview_endpoint_returns_report_data(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(KdUpahPerCustomerReportService::class);
        $service
            ->shouldReceive('buildReportData')
            ->once()
            ->andReturn($this->reportData());

        $this->app->instance(KdUpahPerCustomerReportService::class, $service);

        $this->withHeaders($this->authJsonHeaders($user))
            ->postJson('/api/reports/sawn-timber/kd-upah-per-customer', [])
            ->assertOk()
            ->assertJsonPath('message', 'Preview laporan berhasil diambil.')
            ->assertJsonPath('meta.total_rows', 2)
            ->assertJsonPath('meta.total_customers', 1)
            ->assertJsonPath('meta.grand_total_m3', 8.585)
            ->assertJsonPath('report_data.customer_groups.0.customer', 'PAK BENYAMIN');
    }

    public function test_pdf_download_endpoint_returns_attachment(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(KdUpahPerCustomerReportService::class);
        $service
            ->shouldReceive('buildReportData')
            ->once()
            ->andReturn($this->reportData());

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(KdUpahPerCustomerReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->actingAs($user)
            ->post('/reports/sawn-timber/kd-upah-per-customer/download', [])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'attachment', 'Laporan KD Upah Per Cutomer');
    }

    public function test_pdf_preview_endpoint_returns_attachment_pdf(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(KdUpahPerCustomerReportService::class);
        $service
            ->shouldReceive('buildReportData')
            ->once()
            ->andReturn($this->reportData());

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(KdUpahPerCustomerReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->actingAs($user)
            ->post('/reports/sawn-timber/kd-upah-per-customer/preview-pdf', [])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'attachment', 'Laporan KD Upah Per Cutomer');
    }

    public function test_health_endpoint_returns_structure_status(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(KdUpahPerCustomerReportService::class);
        $service
            ->shouldReceive('healthCheck')
            ->once()
            ->andReturn([
                'is_healthy' => true,
                'expected_columns' => ['NamaCustomer'],
                'detected_columns' => ['NamaCustomer'],
                'missing_columns' => [],
                'extra_columns' => [],
                'row_count' => 2,
            ]);

        $this->app->instance(KdUpahPerCustomerReportService::class, $service);

        $this->withHeaders($this->authJsonHeaders($user))
            ->postJson('/api/reports/sawn-timber/kd-upah-per-customer/health', [])
            ->assertOk()
            ->assertJsonPath('health.is_healthy', true)
            ->assertJsonPath('health.row_count', 2);
    }

    /**
     * @return array<string, mixed>
     */
    private function reportData(): array
    {
        return [
            'rows' => [
                [
                    'NamaCustomer' => 'PAK BENYAMIN',
                    'NoProcKD' => 'H.000771',
                    'NoRuangKD' => 3,
                    'TglMasuk' => '2023-07-27',
                    'TglKeluar' => '2023-08-03',
                    'Jenis' => 'DAMAR',
                    'm3' => 3.518,
                ],
                [
                    'NamaCustomer' => 'PAK BENYAMIN',
                    'NoProcKD' => 'H.000787',
                    'NoRuangKD' => 1,
                    'TglMasuk' => '2023-09-12',
                    'TglKeluar' => '2023-09-23',
                    'Jenis' => 'HOTING',
                    'm3' => 5.067,
                ],
            ],
            'customer_groups' => [
                [
                    'customer' => 'PAK BENYAMIN',
                    'rows' => [],
                    'total_m3' => 8.585,
                ],
            ],
            'summary' => [
                'total_rows' => 2,
                'total_customers' => 1,
                'grand_total_m3' => 8.585,
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

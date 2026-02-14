<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\PdfGenerator;
use App\Services\SalesReportService;
use Mockery;
use Tests\TestCase;

class SalesReportFeatureTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_sales_report_form_page_is_accessible(): void
    {
        $this->get('/reports/sales')
            ->assertOk()
            ->assertSee('Generate Laporan Penjualan (PDF)');
    }

    public function test_openapi_json_endpoint_is_available(): void
    {
        $this->get('/api/openapi.json')
            ->assertOk()
            ->assertJsonPath('openapi', '3.0.3')
            ->assertJsonPath('paths./api/auth/register.post.summary', 'Registrasi user baru')
            ->assertJsonPath('paths./api/auth/login.post.summary', 'Login user')
            ->assertJsonPath('paths./api/auth/logout.post.summary', 'Logout user (invalidate token)')
            ->assertJsonPath('paths./api/auth/refresh.post.summary', 'Refresh access token')
            ->assertJsonPath('paths./api/auth/me.get.summary', 'Data user yang sedang login')
            ->assertJsonPath('paths./api/reports/sales.post.summary', 'Preview data laporan penjualan')
            ->assertJsonPath('paths./api/reports/sales/pdf.post.summary', 'Generate laporan penjualan PDF')
            ->assertJsonPath('paths./api/reports/sales.post.security.0.bearerAuth.0', null)
            ->assertJsonPath('components.securitySchemes.bearerAuth.scheme', 'bearer')
            ->assertJsonPath('components.securitySchemes.bearerAuth.bearerFormat', 'JWT')
            ->assertJsonPath('components.schemas.AuthTokenResponse.properties.token_type.example', 'bearer');
    }

    public function test_sales_preview_endpoint_returns_json_data(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $salesReportService = Mockery::mock(SalesReportService::class);
        $salesReportService
            ->shouldReceive('fetch')
            ->once()
            ->with('2026-01-01', '2026-01-31')
            ->andReturn([
                ['invoice_no' => 'INV-001', 'customer_name' => 'Adi', 'total' => 150000],
                ['invoice_no' => 'INV-002', 'customer_name' => 'Budi', 'total' => 250000],
            ]);

        $this->app->instance(SalesReportService::class, $salesReportService);

        $this->actingAs($user, 'api')
            ->postJson('/api/reports/sales', [
                'start_date' => '2026-01-01',
                'end_date' => '2026-01-31',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Preview laporan berhasil diambil.')
            ->assertJsonPath('meta.total_rows', 2)
            ->assertJsonPath('meta.amount_field', 'total')
            ->assertJsonPath('meta.grand_total', 400000)
            ->assertJsonCount(2, 'data');
    }

    public function test_pdf_download_endpoint_returns_attachment(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $salesReportService = Mockery::mock(SalesReportService::class);
        $salesReportService
            ->shouldReceive('fetch')
            ->once()
            ->with('2026-01-01', '2026-01-31')
            ->andReturn([
                ['invoice_no' => 'INV-001', 'total' => 150000],
            ]);

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(SalesReportService::class, $salesReportService);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $this->actingAs($user)
            ->post('/reports/sales/download', [
                'start_date' => '2026-01-01',
                'end_date' => '2026-01-31',
            ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'attachment; filename="laporan-penjualan-2026-01-01-sd-2026-01-31.pdf"');
    }
}

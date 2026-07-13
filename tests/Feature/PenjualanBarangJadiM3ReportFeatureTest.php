<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\PdfGenerator;
use App\Services\PenjualanBarangJadiM3ReportService;
use Mockery;
use Tests\TestCase;

class PenjualanBarangJadiM3ReportFeatureTest extends TestCase
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
        $this->get('/reports/penjualan/penjualan-barang-jadi-m3')
            ->assertOk()
            ->assertSee('Generate Laporan Penjualan Barang Jadi (M3)');
    }

    public function test_preview_endpoint_returns_report_data(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(PenjualanBarangJadiM3ReportService::class);
        $service
            ->shouldReceive('buildReportData')
            ->once()
            ->with('J.001146')
            ->andReturn($this->reportData());

        $this->app->instance(PenjualanBarangJadiM3ReportService::class, $service);

        $this->withHeaders($this->authJsonHeaders($user))
            ->postJson('/api/reports/penjualan/penjualan-barang-jadi-m3', [
                'NoJual' => 'J.001146',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Preview laporan berhasil diambil.')
            ->assertJsonPath('meta.no_jual', 'J.001146')
            ->assertJsonPath('meta.total_rows', 2)
            ->assertJsonPath('meta.total_pcs', 4207)
            ->assertJsonPath('meta.grand_total_m3', 5.0296)
            ->assertJsonPath('report_data.header.buyer', 'SSB');
    }

    public function test_pdf_download_endpoint_returns_attachment(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(PenjualanBarangJadiM3ReportService::class);
        $service
            ->shouldReceive('buildReportData')
            ->once()
            ->with('J.001146')
            ->andReturn($this->reportData());

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(PenjualanBarangJadiM3ReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->actingAs($user)
            ->post('/reports/penjualan/penjualan-barang-jadi-m3/download', [
                'NoJual' => 'J.001146',
            ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'attachment', 'Laporan Penjualan Barang Jadi M3 J.001146');
    }

    public function test_pdf_preview_endpoint_returns_attachment_pdf(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(PenjualanBarangJadiM3ReportService::class);
        $service
            ->shouldReceive('buildReportData')
            ->once()
            ->with('J.001146')
            ->andReturn($this->reportData());

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(PenjualanBarangJadiM3ReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->actingAs($user)
            ->post('/reports/penjualan/penjualan-barang-jadi-m3/preview-pdf', [
                'NoJual' => 'J.001146',
            ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'attachment', 'Laporan Penjualan Barang Jadi M3 J.001146');
    }

    public function test_health_endpoint_returns_structure_status(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(PenjualanBarangJadiM3ReportService::class);
        $service
            ->shouldReceive('healthCheck')
            ->once()
            ->with('J.001146')
            ->andReturn([
                'is_healthy' => true,
                'expected_columns' => ['NoBJJual'],
                'detected_columns' => ['NoBJJual'],
                'missing_columns' => [],
                'extra_columns' => [],
                'row_count' => 8,
            ]);

        $this->app->instance(PenjualanBarangJadiM3ReportService::class, $service);

        $this->withHeaders($this->authJsonHeaders($user))
            ->postJson('/api/reports/penjualan/penjualan-barang-jadi-m3/health', [
                'NoJual' => 'J.001146',
            ])
            ->assertOk()
            ->assertJsonPath('health.is_healthy', true)
            ->assertJsonPath('health.row_count', 8)
            ->assertJsonPath('meta.NoJual', 'J.001146');
    }

    /**
     * @return array<string, mixed>
     */
    private function reportData(): array
    {
        return [
            'no_jual' => 'J.001146',
            'rows' => [
                ['No' => 1, 'Jenis' => 'JABON', 'NamaBarangJadi' => 'FJLB C/C', 'Pcs' => 3627, 'M3' => 4.3362],
                ['No' => 2, 'Jenis' => 'JABON', 'NamaBarangJadi' => 'FJLB C/C', 'Pcs' => 580, 'M3' => 0.6934],
            ],
            'header' => [
                'tanggal' => '2026-05-08',
                'no_spk' => '2026-12',
                'buyer' => 'SSB',
                'no_bj_jual' => 'J.001146',
            ],
            'jenis_groups' => [
                [
                    'jenis' => 'JABON',
                    'rows' => [
                        ['No' => 1, 'Jenis' => 'JABON', 'NamaBarangJadi' => 'FJLB C/C', 'Pcs' => 3627, 'M3' => 4.3362],
                        ['No' => 2, 'Jenis' => 'JABON', 'NamaBarangJadi' => 'FJLB C/C', 'Pcs' => 580, 'M3' => 0.6934],
                    ],
                    'product_totals' => ['FJLB C/C' => 5.0296],
                    'total_m3' => 5.0296,
                ],
            ],
            'summary' => [
                'total_rows' => 2,
                'total_pcs' => 4207,
                'grand_total_m3' => 5.0296,
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function authJsonHeaders(User $user): array
    {
        return [
            'Authorization' => 'Bearer '.$this->issueJwtForUser($user),
            'Accept' => 'application/json',
        ];
    }
}

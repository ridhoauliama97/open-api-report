<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\KdUpahPerNoProcKdPerCustomerDetailReportService;
use App\Services\PdfGenerator;
use Mockery;
use Tests\TestCase;

class KdUpahPerNoProcKdPerCustomerDetailReportFeatureTest extends TestCase
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
        $this->get('/reports/sawn-timber/kd-upah-per-no-proc-kd-per-customer-detail')
            ->assertOk()
            ->assertSee('Generate Laporan KD Upah Per-No.Proses KD Per-Cutomer Detail');
    }

    public function test_preview_endpoint_returns_report_data(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(KdUpahPerNoProcKdPerCustomerDetailReportService::class);
        $service
            ->shouldReceive('buildReportData')
            ->once()
            ->with(['no_proc_kd' => 'H.000771'])
            ->andReturn($this->reportData());

        $this->app->instance(KdUpahPerNoProcKdPerCustomerDetailReportService::class, $service);

        $this->withHeaders($this->authJsonHeaders($user))
            ->postJson('/api/reports/sawn-timber/kd-upah-per-no-proc-kd-per-customer-detail', [
                'no_proc_kd' => 'H.000771',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Preview laporan berhasil diambil.')
            ->assertJsonPath('meta.no_proc_kd', 'H.000771')
            ->assertJsonPath('meta.total_rows', 2)
            ->assertJsonPath('meta.total_no_st', 2)
            ->assertJsonPath('meta.total_pcs', 308)
            ->assertJsonPath('meta.grand_total_m3', 1.1779);
    }

    public function test_pdf_download_endpoint_returns_attachment(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(KdUpahPerNoProcKdPerCustomerDetailReportService::class);
        $service
            ->shouldReceive('buildReportData')
            ->once()
            ->with(['no_proc_kd' => 'H.000771'])
            ->andReturn($this->reportData());

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(KdUpahPerNoProcKdPerCustomerDetailReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->actingAs($user)
            ->post('/reports/sawn-timber/kd-upah-per-no-proc-kd-per-customer-detail/download', [
                'no_proc_kd' => 'H.000771',
            ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'attachment', 'Laporan KD Upah Per No Proses KD Per Cutomer Detail');
    }

    public function test_pdf_preview_endpoint_returns_attachment_pdf(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(KdUpahPerNoProcKdPerCustomerDetailReportService::class);
        $service
            ->shouldReceive('buildReportData')
            ->once()
            ->with(['no_proc_kd' => 'H.000771'])
            ->andReturn($this->reportData());

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(KdUpahPerNoProcKdPerCustomerDetailReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->actingAs($user)
            ->post('/reports/sawn-timber/kd-upah-per-no-proc-kd-per-customer-detail/preview-pdf', [
                'no_proc_kd' => 'H.000771',
            ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'attachment', 'Laporan KD Upah Per No Proses KD Per Cutomer Detail');
    }

    public function test_health_endpoint_returns_structure_status(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(KdUpahPerNoProcKdPerCustomerDetailReportService::class);
        $service
            ->shouldReceive('healthCheck')
            ->once()
            ->with(['no_proc_kd' => 'H.000771'])
            ->andReturn([
                'is_healthy' => true,
                'expected_columns' => ['NamaCustomer'],
                'detected_columns' => ['NamaCustomer'],
                'missing_columns' => [],
                'extra_columns' => [],
                'row_count' => 2,
            ]);

        $this->app->instance(KdUpahPerNoProcKdPerCustomerDetailReportService::class, $service);

        $this->withHeaders($this->authJsonHeaders($user))
            ->postJson('/api/reports/sawn-timber/kd-upah-per-no-proc-kd-per-customer-detail/health', [
                'no_proc_kd' => 'H.000771',
            ])
            ->assertOk()
            ->assertJsonPath('health.is_healthy', true)
            ->assertJsonPath('health.row_count', 2);
    }

    public function test_no_proc_kd_is_required(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $this->withHeaders($this->authJsonHeaders($user))
            ->postJson('/api/reports/sawn-timber/kd-upah-per-no-proc-kd-per-customer-detail', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['no_proc_kd']);
    }

    /**
     * @return array<string, mixed>
     */
    private function reportData(): array
    {
        return [
            'filters' => [
                'no_proc_kd' => 'H.000771',
            ],
            'header' => [
                'NamaCustomer' => 'PAK BENYAMIN',
                'NoProcKD' => 'H.000771',
                'NoRuangKD' => 3,
                'TglMasuk' => '2023-07-27',
                'TglKeluar' => '2023-08-03',
                'Jenis' => 'DAMAR',
            ],
            'rows' => [
                [
                    'NamaCustomer' => 'PAK BENYAMIN',
                    'NoProcKD' => 'H.000771',
                    'NoRuangKD' => 3,
                    'TglMasuk' => '2023-07-27',
                    'TglKeluar' => '2023-08-03',
                    'NoST' => 'E.464765',
                    'Jenis' => 'DAMAR',
                    'Tebal' => 1.0,
                    'Lebar' => 5.0,
                    'Panjang' => 5.0,
                    'JmlhBatang' => 137,
                    'M3' => 0.6735,
                ],
                [
                    'NamaCustomer' => 'PAK BENYAMIN',
                    'NoProcKD' => 'H.000771',
                    'NoRuangKD' => 3,
                    'TglMasuk' => '2023-07-27',
                    'TglKeluar' => '2023-08-03',
                    'NoST' => 'E.464772',
                    'Jenis' => 'DAMAR',
                    'Tebal' => 1.0,
                    'Lebar' => 5.0,
                    'Panjang' => 3.0,
                    'JmlhBatang' => 171,
                    'M3' => 0.5044,
                ],
            ],
            'no_st_groups' => [],
            'summary' => [
                'total_rows' => 2,
                'total_no_st' => 2,
                'total_pcs' => 308,
                'grand_total_m3' => 1.1779,
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

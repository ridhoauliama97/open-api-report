<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\PdfGenerator;
use App\Services\SerahTerimaStKamarKdReportService;
use Mockery;
use Tests\TestCase;

class SerahTerimaStKamarKdReportFeatureTest extends TestCase
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
        $this->get('/reports/sawn-timber/serah-terima-st-kamar-kd')
            ->assertOk()
            ->assertSee('Generate Laporan Serah Terima ST (Kamar KD)');
    }

    public function test_preview_endpoint_returns_report_data(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(SerahTerimaStKamarKdReportService::class);
        $service
            ->shouldReceive('buildReportData')
            ->once()
            ->with(['no_proc_kd' => 'H.000771'])
            ->andReturn($this->reportData());

        $this->app->instance(SerahTerimaStKamarKdReportService::class, $service);

        $this->withHeaders($this->authJsonHeaders($user))
            ->postJson('/api/reports/sawn-timber/serah-terima-st-kamar-kd', [
                'no_proc_kd' => 'H.000771',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Preview laporan berhasil diambil.')
            ->assertJsonPath('meta.no_proc_kd', 'H.000771')
            ->assertJsonPath('meta.total_rows', 2)
            ->assertJsonPath('meta.total_no_st', 1)
            ->assertJsonPath('meta.total_pcs', 3)
            ->assertJsonPath('meta.total_ton', 0.0025)
            ->assertJsonPath('meta.total_kubik', 0.0034);
    }

    public function test_pdf_download_endpoint_returns_attachment(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(SerahTerimaStKamarKdReportService::class);
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

        $this->app->instance(SerahTerimaStKamarKdReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->actingAs($user)
            ->post('/reports/sawn-timber/serah-terima-st-kamar-kd/download', [
                'no_proc_kd' => 'H.000771',
            ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'attachment', 'Laporan Serah Terima ST Kamar KD');
    }

    public function test_pdf_preview_endpoint_returns_attachment_pdf(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(SerahTerimaStKamarKdReportService::class);
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

        $this->app->instance(SerahTerimaStKamarKdReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->actingAs($user)
            ->post('/reports/sawn-timber/serah-terima-st-kamar-kd/preview-pdf', [
                'no_proc_kd' => 'H.000771',
            ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'attachment', 'Laporan Serah Terima ST Kamar KD');
    }

    public function test_health_endpoint_returns_structure_status(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(SerahTerimaStKamarKdReportService::class);
        $service
            ->shouldReceive('healthCheck')
            ->once()
            ->with(['no_proc_kd' => 'H.000771'])
            ->andReturn([
                'is_healthy' => true,
                'expected_columns' => ['NoProcKD'],
                'detected_columns' => ['NoProcKD'],
                'missing_columns' => [],
                'extra_columns' => [],
                'row_count' => 2,
            ]);

        $this->app->instance(SerahTerimaStKamarKdReportService::class, $service);

        $this->withHeaders($this->authJsonHeaders($user))
            ->postJson('/api/reports/sawn-timber/serah-terima-st-kamar-kd/health', [
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
            ->postJson('/api/reports/sawn-timber/serah-terima-st-kamar-kd', [])
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
                'NoProcKD' => 'H.000771',
                'NoRuangKD' => 3,
                'TglMasuk' => '2023-07-27',
                'TglKeluar' => '2023-08-03',
            ],
            'rows' => [
                [
                    'NoProcKD' => 'H.000771',
                    'NoRuangKD' => 3,
                    'TglMasuk' => '2023-07-27',
                    'TglKeluar' => '2023-08-03',
                    'NoST' => 'E.464315',
                    'Tebal' => 44.0,
                    'Lebar' => 35.0,
                    'Panjang' => 2.0,
                    'JmlhBatang' => 1,
                    'Ton' => 0.0006,
                    'Kubik' => 0.0008,
                ],
                [
                    'NoProcKD' => 'H.000771',
                    'NoRuangKD' => 3,
                    'TglMasuk' => '2023-07-27',
                    'TglKeluar' => '2023-08-03',
                    'NoST' => 'E.464315',
                    'Tebal' => 44.0,
                    'Lebar' => 35.0,
                    'Panjang' => 3.0,
                    'JmlhBatang' => 2,
                    'Ton' => 0.0019,
                    'Kubik' => 0.0026,
                ],
            ],
            'no_st_groups' => [],
            'summary' => [
                'total_rows' => 2,
                'total_no_st' => 1,
                'total_pcs' => 3,
                'total_ton' => 0.0025,
                'total_kubik' => 0.0034,
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

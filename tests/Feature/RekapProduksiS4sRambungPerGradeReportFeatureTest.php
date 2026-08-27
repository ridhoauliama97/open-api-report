<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\PdfGenerator;
use App\Services\RekapProduksiS4sRambungPerGradeReportService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class RekapProduksiS4sRambungPerGradeReportFeatureTest extends TestCase
{
    /**
     * Execute set up logic.
     */
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('reports.report_auth.issuers', []);
        config()->set('reports.report_auth.audiences', []);
        config()->set('reports.report_auth.required_scope', null);
    }

    /**
     * Execute tear down logic.
     */
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    /**
     * Execute test rekap produksi rambung per grade form page is accessible logic.
     */
    public function test_rekap_produksi_rambung_per_grade_form_page_is_accessible(): void
    {
        $this->get('/reports/s4s/rekap-produksi-rambung-per-grade')
            ->assertOk();
    }

    /**
     * Execute test rekap produksi rambung per grade preview endpoint returns json data logic.
     */
    public function test_rekap_produksi_rambung_per_grade_preview_endpoint_returns_json_data(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(RekapProduksiS4sRambungPerGradeReportService::class);
        $service
            ->shouldReceive('buildReportData')
            ->once()
            ->with('2026-01-01', '2026-01-31')
            ->andReturn($this->sampleReportData());

        $this->app->instance(RekapProduksiS4sRambungPerGradeReportService::class, $service);

        $this->withHeaders($this->authJsonHeaders($user))
            ->postJson('/api/reports/s4s/rekap-produksi-rambung-per-grade', [
                'TglAwal' => '2026-01-01',
                'TglAkhir' => '2026-01-31',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Preview laporan berhasil diambil.')
            ->assertJsonPath('meta.total_rows', 1)
            ->assertJsonPath('meta.TglAwal', '2026-01-01')
            ->assertJsonPath('meta.TglAkhir', '2026-01-31');
    }

    /**
     * Execute test rekap produksi rambung per grade pdf download endpoint returns attachment logic.
     */
    public function test_rekap_produksi_rambung_per_grade_pdf_download_endpoint_returns_attachment(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(RekapProduksiS4sRambungPerGradeReportService::class);
        $service
            ->shouldReceive('buildReportData')
            ->once()
            ->with('2026-01-01', '2026-01-31')
            ->andReturn($this->sampleReportData());

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('renderHtml')
            ->once()
            ->andReturn('<html><body>mocked HTML</body></html>');
        $pdfGenerator
            ->shouldReceive('paperMetrics')
            ->once()
            ->andReturn([
                'paper_width' => '29.7cm',
                'paper_height' => '21.0cm',
                'landscape' => true,
            ]);

        Http::fake([
            'http://localhost:3000/*' => Http::sequence()
                ->push('%PDF-1.4 mocked content', 200, ['Content-Type' => 'application/pdf']),
        ]);

        $this->app->instance(RekapProduksiS4sRambungPerGradeReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->actingAs($user)
            ->post('/reports/s4s/rekap-produksi-rambung-per-grade/download', [
                'TglAwal' => '2026-01-01',
                'TglAkhir' => '2026-01-31',
            ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'attachment', 'laporan rekap produksi rambung per grade');

        Http::assertSent(fn (Request $request): bool => $request->url() === config('services.gotenberg.url').'/forms/chromium/convert/html'
            && $request->method() === 'POST');
    }

    /**
     * Execute test rekap produksi rambung per grade health endpoint returns structure status logic.
     */
    public function test_rekap_produksi_rambung_per_grade_health_endpoint_returns_structure_status(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(RekapProduksiS4sRambungPerGradeReportService::class);
        $service
            ->shouldReceive('healthCheck')
            ->once()
            ->with('2026-01-01', '2026-01-31')
            ->andReturn([
                'is_healthy' => true,
                'expected_columns' => ['Tanggal', 'Jenis', 'Type', 'Total'],
                'detected_columns' => ['Tanggal', 'Jenis', 'Type', 'Total'],
                'missing_columns' => [],
                'extra_columns' => [],
                'row_count' => 12,
            ]);

        $this->app->instance(RekapProduksiS4sRambungPerGradeReportService::class, $service);

        $this->withHeaders($this->authJsonHeaders($user))
            ->postJson('/api/reports/s4s/rekap-produksi-rambung-per-grade/health', [
                'TglAwal' => '2026-01-01',
                'TglAkhir' => '2026-01-31',
            ])
            ->assertOk()
            ->assertJsonPath('health.is_healthy', true)
            ->assertJsonPath('health.row_count', 12);
    }

    /**
     * @return array<string, mixed>
     */
    private function sampleReportData(): array
    {
        return [
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-31',
            'input_columns' => ['RAMBUNG - MC 1', 'RAMBUNG - MC 2'],
            'output_columns' => ['A/A', 'A/B'],
            'rows' => [
                [
                    'date' => '2026-01-01',
                    'input' => [
                        'RAMBUNG - MC 1' => ['total' => 1.5, 'ratio' => 60.0],
                        'RAMBUNG - MC 2' => ['total' => 1.0, 'ratio' => 40.0],
                    ],
                    'output' => [
                        'A/A' => ['total' => 2.0, 'ratio' => 80.0],
                        'A/B' => ['total' => 0.5, 'ratio' => 20.0],
                    ],
                ],
            ],
            'total' => [
                'input' => [
                    'RAMBUNG - MC 1' => ['total' => 1.5, 'ratio' => 60.0],
                    'RAMBUNG - MC 2' => ['total' => 1.0, 'ratio' => 40.0],
                ],
                'output' => [
                    'A/A' => ['total' => 2.0, 'ratio' => 80.0],
                    'A/B' => ['total' => 0.5, 'ratio' => 20.0],
                ],
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function authJsonHeaders(User $user): array
    {
        return [
            'Authorization' => 'Bearer '.$this->createBearerToken($user),
            'Accept' => 'application/json',
        ];
    }

    /**
     * Create JWT token for test user without requiring auth guard lookup.
     */
    private function createBearerToken(User $user): string
    {
        return $this->issueJwtForUser($user);
    }
}

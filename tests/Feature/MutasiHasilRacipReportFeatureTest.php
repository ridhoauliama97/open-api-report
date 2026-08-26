<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\MutasiHasilRacipReportService;
use App\Services\PdfGenerator;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class MutasiHasilRacipReportFeatureTest extends TestCase
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
     * Execute test rangkuman label input form page is accessible logic.
     */
    public function test_rangkuman_label_input_form_page_is_accessible(): void
    {
        $this->get('/reports/mutasi-hasil-racip')
            ->assertOk()
            ->assertSee('Generate Laporan Mutasi Hasil Racip (PDF)');
    }

    /**
     * Execute test rangkuman label input preview endpoint returns json data logic.
     */
    public function test_rangkuman_label_input_preview_endpoint_returns_json_data(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(MutasiHasilRacipReportService::class);
        $service
            ->shouldReceive('fetch')
            ->once()
            ->with('2026-01-01', '2026-01-31')
            ->andReturn([
                ['Tanggal' => '2026-01-01', 'Shift' => '1', 'JumlahLabel' => 125],
                ['Tanggal' => '2026-01-02', 'Shift' => '2', 'JumlahLabel' => 140],
            ]);

        $this->app->instance(MutasiHasilRacipReportService::class, $service);

        $this->withHeaders($this->authJsonHeaders($user))
            ->postJson('/api/reports/mutasi-hasil-racip', [
                'TglAwal' => '2026-01-01',
                'TglAkhir' => '2026-01-31',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Preview laporan berhasil diambil.')
            ->assertJsonPath('meta.total_rows', 2)
            ->assertJsonCount(2, 'data');
    }

    /**
     * Execute test rangkuman label input pdf download endpoint returns attachment logic.
     */
    public function test_rangkuman_label_input_pdf_download_endpoint_returns_attachment(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(MutasiHasilRacipReportService::class);
        $service
            ->shouldReceive('fetch')
            ->once()
            ->with('2026-01-01', '2026-01-31')
            ->andReturn([
                ['Tanggal' => '2026-01-01', 'Shift' => '1', 'JumlahLabel' => 125],
            ]);

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('renderHtml')
            ->once()
            ->andReturn('<html><body>mocked HTML</body></html>');
        $pdfGenerator
            ->shouldReceive('paperMetrics')
            ->once()
            ->andReturn([
                'paper_width' => '21.0cm',
                'paper_height' => '29.7cm',
                'landscape' => false,
            ]);

        Http::fake([
            'http://localhost:3000/*' => Http::sequence()
                ->push('%PDF-1.4 mocked content', 200, ['Content-Type' => 'application/pdf']),
        ]);

        $this->app->instance(MutasiHasilRacipReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $response = $this->actingAs($user)
            ->post('/reports/mutasi-hasil-racip/download', [
                'TglAwal' => '2026-01-01',
                'TglAkhir' => '2026-01-31',
            ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertPdfDisposition($response, 'attachment', 'Laporan Mutasi Hasil Racip');

        Http::assertSent(fn (Request $request): bool => $request->url() === config('services.gotenberg.url').'/forms/chromium/convert/html'
            && $request->method() === 'POST');
    }

    /**
     * Execute test rangkuman label input health endpoint returns structure status logic.
     */
    public function test_rangkuman_label_input_health_endpoint_returns_structure_status(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(MutasiHasilRacipReportService::class);
        $service
            ->shouldReceive('healthCheck')
            ->once()
            ->with('2026-01-01', '2026-01-31')
            ->andReturn([
                'is_healthy' => true,
                'expected_columns' => ['Tanggal', 'Shift', 'JumlahLabel'],
                'detected_columns' => ['Tanggal', 'Shift', 'JumlahLabel'],
                'missing_columns' => [],
                'extra_columns' => [],
                'row_count' => 31,
            ]);

        $this->app->instance(MutasiHasilRacipReportService::class, $service);

        $this->withHeaders($this->authJsonHeaders($user))
            ->postJson('/api/reports/mutasi-hasil-racip/health', [
                'TglAwal' => '2026-01-01',
                'TglAkhir' => '2026-01-31',
            ])
            ->assertOk()
            ->assertJsonPath('health.is_healthy', true)
            ->assertJsonPath('health.row_count', 31);
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

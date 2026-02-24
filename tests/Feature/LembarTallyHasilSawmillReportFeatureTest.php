<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\LembarTallyHasilSawmillReportService;
use App\Services\PdfGenerator;
use Mockery;
use Tests\TestCase;

class LembarTallyHasilSawmillReportFeatureTest extends TestCase
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
        $this->get('/reports/sawn-timber/lembar-tally-hasil-sawmill')
            ->assertOk()
            ->assertSee('Generate Laporan Lembar Tally Hasil Sawmill (PDF)');
    }

    public function test_preview_endpoint_returns_json_data(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(LembarTallyHasilSawmillReportService::class);
        $service
            ->shouldReceive('fetch')
            ->once()
            ->with('D.040749')
            ->andReturn([
                ['NoSTSawmill' => 'D.040749', 'Tebal' => 45, 'Lebar' => 36, 'Panjang' => 1, 'JmlhBatang' => 28, 'Ket' => 'STD'],
            ]);

        $this->app->instance(LembarTallyHasilSawmillReportService::class, $service);

        $this->withHeaders($this->authJsonHeaders($user))
            ->postJson('/api/reports/sawn-timber/lembar-tally-hasil-sawmill', [
                'NoProduksi' => 'D.040749',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Preview laporan berhasil diambil.')
            ->assertJsonPath('meta.NoProduksi', 'D.040749')
            ->assertJsonPath('meta.total_rows', 1);
    }

    public function test_pdf_download_endpoint_returns_attachment(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(LembarTallyHasilSawmillReportService::class);
        $service
            ->shouldReceive('fetch')
            ->once()
            ->with('D.040749')
            ->andReturn([]);

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(LembarTallyHasilSawmillReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $this->actingAs($user)
            ->post('/reports/sawn-timber/lembar-tally-hasil-sawmill/download', [
                'NoProduksi' => 'D.040749',
            ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
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


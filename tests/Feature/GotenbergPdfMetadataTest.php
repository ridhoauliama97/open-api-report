<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\GotenbergPdfClient;
use App\Services\MutasiBarangJadiReportService;
use App\Services\PdfGenerator;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class GotenbergPdfMetadataTest extends TestCase
{
    /**
     * Execute test convert html sends metadata as json form field logic.
     */
    public function test_convert_html_sends_metadata_as_json_form_field(): void
    {
        Http::fake([
            'http://localhost:3000/*' => Http::response('%PDF-1.4 fake', 200),
        ]);

        app(GotenbergPdfClient::class)->convertHtml('<html></html>', [], null, [
            'metadata' => ['Title' => 'Laporan Uji'],
        ]);

        Http::assertSent(fn (Request $request): bool => str_contains((string) $request->body(), 'name="metadata"')
            && str_contains((string) $request->body(), 'Laporan Uji'));
    }

    /**
     * Execute test controller download sends metadata title derived from filename logic.
     */
    public function test_controller_download_sends_metadata_title_derived_from_filename(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(MutasiBarangJadiReportService::class);
        $service->shouldReceive('fetch')->once()->andReturn([]);
        $service->shouldReceive('fetchSubReport')->once()->andReturn([]);

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator->shouldReceive('renderHtml')->once()->andReturn('<html></html>');
        $pdfGenerator->shouldReceive('paperMetrics')->once()->andReturn([]);

        $this->app->instance(MutasiBarangJadiReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        Http::fake([
            'http://localhost:3000/*' => Http::response('%PDF-1.4 fake', 200),
        ]);

        $this->withHeaders([
            'Authorization' => 'Bearer '.$this->issueJwtForUser($user),
            'Accept' => 'application/json',
        ])
            ->get('/api/reports/mutasi-barang-jadi/pdf?TglAwal=2026-01-01&TglAkhir=2026-01-31')
            ->assertOk();

        Http::assertSent(fn (Request $request): bool => str_contains((string) $request->body(), 'name="metadata"')
            && str_contains((string) $request->body(), 'Laporan Mutasi Barang Jadi 2026 01 01 sd 2026 01 31'));
    }
}

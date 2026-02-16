<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\MutasiBarangJadiReportService;
use App\Services\PdfGenerator;
use Mockery;
use Tests\TestCase;

class MutasiBarangJadiReportFeatureTest extends TestCase
{
    /**
     * Execute tear down logic.
     */
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    /**
     * Execute test mutasi barang jadi form page is accessible logic.
     */
    public function test_mutasi_barang_jadi_form_page_is_accessible(): void
    {
        $this->get('/reports/mutasi/barang-jadi')
            ->assertOk()
            ->assertSee('Generate Laporan Mutasi Barang Jadi (PDF)');
    }

    /**
     * Execute test mutasi barang jadi preview endpoint returns json data logic.
     */
    public function test_mutasi_barang_jadi_preview_endpoint_returns_json_data(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(MutasiBarangJadiReportService::class);
        $service
            ->shouldReceive('fetch')
            ->once()
            ->with('2026-01-01', '2026-01-31')
            ->andReturn([
                [
                    'Jenis' => 'BJ JABON FJLB A/A',
                    'Awal' => 4.2935,
                    'Masuk' => 438.0548,
                    'AdjOutput' => 0,
                    'BSOutput' => 159.5689,
                    'AdjInput' => 0,
                    'BSInput' => 159.57,
                    'Keluar' => 9.2471,
                    'Jual' => 401.6065,
                    'MLDInput' => 0,
                    'LMTInput' => 0.0857,
                    'CCAInput' => 2.3059,
                    'SANDInput' => 0,
                    'Akhir' => 29.1020,
                ],
                [
                    'Jenis' => 'BJ MERANTI S4S',
                    'Awal' => 1.4,
                    'Masuk' => 35.2,
                    'AdjOutput' => 1.1,
                    'BSOutput' => 0.5,
                    'AdjInput' => 0.2,
                    'BSInput' => 0.3,
                    'Keluar' => 4.7,
                    'Jual' => 20.5,
                    'MLDInput' => 2.0,
                    'LMTInput' => 0.0,
                    'CCAInput' => 0.0,
                    'SANDInput' => 1.2,
                    'Akhir' => 8.3,
                ],
            ]);
        $service
            ->shouldReceive('fetchSubReport')
            ->once()
            ->with('2026-01-01', '2026-01-31')
            ->andReturn([
                [
                    'Jenis' => 'BJ JABON FJLB A/A',
                    'BarangJadi' => 0.2125,
                    'Moulding' => 0,
                    'Sanding' => 0,
                    'WIP' => 0,
                    'WIPLama' => 0,
                    'CCAkhir' => 14.8381,
                ],
            ]);

        $this->app->instance(MutasiBarangJadiReportService::class, $service);

        $this->actingAs($user, 'api')
            ->postJson('/api/reports/mutasi-barang-jadi', [
                'TglAwal' => '2026-01-01',
                'TglAkhir' => '2026-01-31',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Preview laporan berhasil diambil.')
            ->assertJsonPath('meta.total_rows', 2)
            ->assertJsonPath('meta.TglAwal', '2026-01-01')
            ->assertJsonPath('meta.TglAkhir', '2026-01-31')
            ->assertJsonCount(2, 'data');
    }

    /**
     * Execute test mutasi barang jadi web preview route returns json data logic.
     */
    public function test_mutasi_barang_jadi_web_preview_route_returns_json_data(): void
    {
        $service = Mockery::mock(MutasiBarangJadiReportService::class);
        $service
            ->shouldReceive('fetch')
            ->once()
            ->with('2026-01-01', '2026-01-31')
            ->andReturn([
                [
                    'Jenis' => 'BJ JABON FJLB A/A',
                    'Awal' => 4.2935,
                    'Masuk' => 438.0548,
                    'AdjOutput' => null,
                    'BSOutput' => 159.5689,
                    'AdjInput' => null,
                    'BSInput' => 159.57,
                    'Keluar' => 9.2471,
                    'Jual' => 401.6065,
                    'MLDInput' => null,
                    'LMTInput' => 0.0857,
                    'CCAInput' => 2.3059,
                    'SANDInput' => null,
                    'Akhir' => 29.1020,
                ],
            ]);
        $service
            ->shouldReceive('fetchSubReport')
            ->once()
            ->with('2026-01-01', '2026-01-31')
            ->andReturn([
                [
                    'Jenis' => 'BJ JABON FJLB A/A',
                    'BarangJadi' => 0.2125,
                    'Moulding' => 0,
                    'Sanding' => 0,
                    'WIP' => 0,
                    'WIPLama' => 0,
                    'CCAkhir' => 14.8381,
                ],
            ]);

        $this->app->instance(MutasiBarangJadiReportService::class, $service);

        $this->postJson('/reports/mutasi/barang-jadi/preview', [
            'TglAwal' => '2026-01-01',
            'TglAkhir' => '2026-01-31',
        ])
            ->assertOk()
            ->assertJsonPath('meta.TglAwal', '2026-01-01')
            ->assertJsonPath('meta.total_rows', 1)
            ->assertJsonPath('data.0.Jenis', 'BJ JABON FJLB A/A');
    }

    /**
     * Execute test mutasi barang jadi pdf download endpoint returns attachment logic.
     */
    public function test_mutasi_barang_jadi_pdf_download_endpoint_returns_attachment(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(MutasiBarangJadiReportService::class);
        $service
            ->shouldReceive('fetch')
            ->once()
            ->with('2026-01-01', '2026-01-31')
            ->andReturn([
                [
                    'Jenis' => 'BJ JABON FJLB A/A',
                    'Awal' => 4.2935,
                    'Masuk' => 438.0548,
                    'AdjOutput' => 0,
                    'BSOutput' => 159.5689,
                    'AdjInput' => 0,
                    'BSInput' => 159.57,
                    'Keluar' => 9.2471,
                    'Jual' => 401.6065,
                    'MLDInput' => 0,
                    'LMTInput' => 0.0857,
                    'CCAInput' => 2.3059,
                    'SANDInput' => 0,
                    'Akhir' => 29.1020,
                ],
            ]);
        $service
            ->shouldReceive('fetchSubReport')
            ->once()
            ->with('2026-01-01', '2026-01-31')
            ->andReturn([
                [
                    'Jenis' => 'BJ JABON FJLB A/A',
                    'BarangJadi' => 0.2125,
                    'Moulding' => 0,
                    'Sanding' => 0,
                    'WIP' => 0,
                    'WIPLama' => 0,
                    'CCAkhir' => 14.8381,
                ],
            ]);

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(MutasiBarangJadiReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $this->actingAs($user)
            ->post('/reports/mutasi/barang-jadi/download', [
                'TglAwal' => '2026-01-01',
                'TglAkhir' => '2026-01-31',
            ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'attachment; filename="laporan-mutasi-barang-jadi-2026-01-01-sd-2026-01-31.pdf"');
    }

    /**
     * Execute test mutasi barang jadi pdf download endpoint supports get query string logic.
     */
    public function test_mutasi_barang_jadi_pdf_download_endpoint_supports_get_query_string(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(MutasiBarangJadiReportService::class);
        $service
            ->shouldReceive('fetch')
            ->once()
            ->with('2026-01-01', '2026-01-31')
            ->andReturn([
                [
                    'Jenis' => 'BJ JABON FJLB A/A',
                    'Awal' => 4.2935,
                    'Masuk' => 438.0548,
                    'AdjOutput' => null,
                    'BSOutput' => 159.5689,
                    'AdjInput' => null,
                    'BSInput' => 159.57,
                    'Keluar' => 9.2471,
                    'Jual' => 401.6065,
                    'MLDInput' => null,
                    'LMTInput' => 0.0857,
                    'CCAInput' => 2.3059,
                    'SANDInput' => null,
                    'Akhir' => 29.1020,
                ],
            ]);
        $service
            ->shouldReceive('fetchSubReport')
            ->once()
            ->with('2026-01-01', '2026-01-31')
            ->andReturn([
                [
                    'Jenis' => 'BJ JABON FJLB A/A',
                    'BarangJadi' => 0.2125,
                    'Moulding' => 0,
                    'Sanding' => 0,
                    'WIP' => 0,
                    'WIPLama' => 0,
                    'CCAkhir' => 14.8381,
                ],
            ]);

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(MutasiBarangJadiReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $this->actingAs($user, 'api')
            ->get('/api/reports/mutasi-barang-jadi/pdf?TglAwal=2026-01-01&TglAkhir=2026-01-31')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'attachment; filename="laporan-mutasi-barang-jadi-2026-01-01-sd-2026-01-31.pdf"');
    }

    /**
     * Execute test mutasi barang jadi health endpoint returns structure status logic.
     */
    public function test_mutasi_barang_jadi_health_endpoint_returns_structure_status(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(MutasiBarangJadiReportService::class);
        $service
            ->shouldReceive('healthCheck')
            ->once()
            ->with('2026-01-01', '2026-01-31')
            ->andReturn([
                'is_healthy' => true,
                'expected_columns' => ['Jenis', 'Awal', 'Masuk', 'AdjOutput', 'BSOutput', 'AdjInput', 'BSInput', 'Keluar', 'Jual', 'MLDInput', 'LMTInput', 'CCAInput', 'SANDInput', 'Akhir'],
                'detected_columns' => ['Jenis', 'Awal', 'Masuk', 'AdjOutput', 'BSOutput', 'AdjInput', 'BSInput', 'Keluar', 'Jual', 'MLDInput', 'LMTInput', 'CCAInput', 'SANDInput', 'Akhir'],
                'missing_columns' => [],
                'extra_columns' => [],
                'row_count' => 14,
            ]);

        $this->app->instance(MutasiBarangJadiReportService::class, $service);

        $this->actingAs($user, 'api')
            ->postJson('/api/reports/mutasi-barang-jadi/health', [
                'TglAwal' => '2026-01-01',
                'TglAkhir' => '2026-01-31',
            ])
            ->assertOk()
            ->assertJsonPath('health.is_healthy', true)
            ->assertJsonPath('health.row_count', 14)
            ->assertJsonPath('meta.TglAwal', '2026-01-01')
            ->assertJsonPath('meta.TglAkhir', '2026-01-31');
    }
}

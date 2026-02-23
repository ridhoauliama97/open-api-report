<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\PdfGenerator;
use App\Services\StockRacipKayuLatReportService;
use Mockery;
use Tests\TestCase;

class StockRacipKayuLatReportFeatureTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_form_page_is_accessible(): void
    {
        $service = Mockery::mock(StockRacipKayuLatReportService::class);
        $service
            ->shouldReceive('buildReportData')
            ->once()
            ->andReturn([
                'rows' => [],
                'grouped_rows' => [],
                'summary' => ['total_rows' => 0, 'total_batang' => 0, 'total_hasil' => 0],
                'end_date_text' => '28/02/2026',
                'column_order' => [],
            ]);

        $this->app->instance(StockRacipKayuLatReportService::class, $service);

        $this->get('/reports/stock-racip-kayu-lat')
            ->assertOk()
            ->assertSee('Laporan Stok Racip Kayu Lat');
    }

    public function test_preview_endpoint_returns_json_data(): void
    {
        $service = Mockery::mock(StockRacipKayuLatReportService::class);
        $service
            ->shouldReceive('buildReportData')
            ->once()
            ->with('2026-02-28')
            ->andReturn([
                'rows' => [
                    ['Jenis' => 'RACIP KAYU LAT JABON', 'Tebal' => 10, 'Hasil' => 1.2],
                ],
                'grouped_rows' => [
                    [
                        'jenis' => 'RACIP KAYU LAT JABON',
                        'rows' => [
                            ['Jenis' => 'RACIP KAYU LAT JABON', 'Tebal' => 10, 'Lebar' => 20, 'Panjang' => 30, 'Hasil' => 1.2],
                        ],
                    ],
                ],
                'summary' => ['total_rows' => 1, 'total_batang' => 100, 'total_hasil' => 1.2],
                'end_date_text' => '28/02/2026',
                'column_order' => ['Jenis', 'Tebal', 'Hasil'],
            ]);

        $this->app->instance(StockRacipKayuLatReportService::class, $service);

        $this->getJson('/reports/stock-racip-kayu-lat/preview?end_date=2026-02-28')
            ->assertOk()
            ->assertJsonPath('message', 'Preview laporan berhasil diambil.')
            ->assertJsonPath('meta.total_rows', 1)
            ->assertJsonPath('meta.end_date', '2026-02-28')
            ->assertJsonPath('summary.total_hasil', 1.2);
    }

    public function test_pdf_download_endpoint_returns_attachment(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(StockRacipKayuLatReportService::class);
        $service
            ->shouldReceive('buildReportData')
            ->once()
            ->with('2026-02-28')
            ->andReturn([
                'rows' => [],
                'grouped_rows' => [],
                'summary' => ['total_rows' => 0, 'total_batang' => 0, 'total_hasil' => 0],
                'end_date_text' => '28/02/2026',
                'column_order' => [],
            ]);

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(StockRacipKayuLatReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $this->actingAs($user)
            ->get('/reports/stock-racip-kayu-lat/download?end_date=2026-02-28')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'attachment; filename="Laporan-Stok-Racip-Kayu-Lat-2026-02-28.pdf"');
    }
}

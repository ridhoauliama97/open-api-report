<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\MutasiRacipDetailReportService;
use App\Services\PdfGenerator;
use Mockery;
use Tests\TestCase;

class MutasiRacipDetailReportFeatureTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_form_page_is_accessible(): void
    {
        $this->get('/reports/mutasi-racip-detail')
            ->assertOk()
            ->assertSee('Generate Laporan Mutasi Racip Detail (PDF)');
    }

    public function test_preview_endpoint_returns_json_data(): void
    {
        $service = Mockery::mock(MutasiRacipDetailReportService::class);
        $service
            ->shouldReceive('buildReportData')
            ->once()
            ->with('2026-01-01', '2026-01-31')
            ->andReturn([
                'rows' => [
                    ['Jenis' => 'RACIP KAYU LAT JABON', 'Tebal' => 10, 'Akhir' => 1.2],
                ],
                'columns' => ['Jenis', 'Tebal', 'Akhir'],
                'detail_columns' => ['Tebal', 'Akhir'],
                'grouped_rows' => [],
                'numeric_columns' => ['Tebal' => true, 'Akhir' => true],
                'totals' => ['Tebal' => 10, 'Akhir' => 1.2],
            ]);

        $this->app->instance(MutasiRacipDetailReportService::class, $service);

        $this->postJson('/reports/mutasi-racip-detail/preview', [
            'TglAwal' => '2026-01-01',
            'TglAkhir' => '2026-01-31',
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Preview laporan berhasil diambil.')
            ->assertJsonPath('meta.total_rows', 1)
            ->assertJsonPath('meta.start_date', '2026-01-01')
            ->assertJsonPath('totals.Akhir', 1.2);
    }

    public function test_pdf_download_endpoint_returns_attachment(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $service = Mockery::mock(MutasiRacipDetailReportService::class);
        $service
            ->shouldReceive('buildReportData')
            ->once()
            ->with('2026-01-01', '2026-01-31')
            ->andReturn([
                'rows' => [],
                'columns' => [],
                'detail_columns' => [],
                'grouped_rows' => [],
                'numeric_columns' => [],
                'totals' => [],
            ]);

        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator
            ->shouldReceive('render')
            ->once()
            ->andReturn('%PDF-1.4 mocked content');

        $this->app->instance(MutasiRacipDetailReportService::class, $service);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $this->actingAs($user)
            ->post('/reports/mutasi-racip-detail/download', [
                'TglAwal' => '2026-01-01',
                'TglAkhir' => '2026-01-31',
            ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'attachment; filename="Laporan-Mutasi-Racip-Detail-2026-01-01-sd-2026-01-31.pdf"');
    }
}


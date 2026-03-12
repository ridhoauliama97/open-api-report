<?php

namespace Tests\Feature;

use App\Http\Middleware\AuthenticateReportJwtClaims;
use App\Models\User;
use App\Services\PdfGenerator;
use App\Services\RekapPembelianKayuBulatKgReportService;
use App\Services\RekapPenerimaanSTDariSawmillKgReportService;
use App\Services\RekapProduktivitasSawmillRpReportService;
use App\Services\SaldoHidupKayuBulatKgReportService;
use App\Services\TimelineKayuBulatBulananKgReportService;
use App\Services\TimelineKayuBulatHarianKgReportService;
use Mockery;
use Tests\TestCase;

class KayuBulatKgReportsFeatureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('reports.report_auth.issuers', []);
        config()->set('reports.report_auth.audiences', []);
        config()->set('reports.report_auth.required_scope', null);
        $this->withoutMiddleware(AuthenticateReportJwtClaims::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_new_form_pages_are_accessible(): void
    {
        $this->get('/reports/kayu-bulat/saldo-hidup-kg')->assertOk()->assertSee('Laporan Saldo Hidup Kayu Bulat - Timbang KG');
        $this->get('/reports/kayu-bulat/rekap-pembelian-kg')->assertOk()->assertSee('Laporan Rekap Pembelian Kayu Bulat (Ton) - Timbang KG');
        $this->get('/reports/kayu-bulat/rekap-penerimaan-st-dari-sawmill-kg')->assertOk()->assertSee('Laporan Rekap Penerimaan ST Dari Sawmill - Timbang KG');
        $this->get('/reports/kayu-bulat/rekap-produktivitas-sawmill-rp')->assertOk()->assertSee('Rekap Produktivitas Sawmill');
        $this->get('/reports/kayu-bulat/perbandingan-kb-masuk-periode-1-dan-2-kg')->assertOk()->assertSee('Laporan Perbanding KB Masuk Periode 1 dan 2 - Timbang KG');
        $this->get('/reports/kayu-bulat/timeline-kayu-bulat-bulanan-kg')->assertOk()->assertSee('Laporan Time Line KB - Bulanan (Rambung)');
        $this->get('/reports/kayu-bulat/timeline-kayu-bulat-harian-kg')->assertOk()->assertSee('Laporan Time Line KB - Harian (Rambung)');
    }

    public function test_saldo_hidup_kg_preview_returns_main_and_sub_data(): void
    {
        $user = User::factory()->make(['id' => 1]);
        $service = Mockery::mock(SaldoHidupKayuBulatKgReportService::class);
        $service->shouldReceive('buildReportData')->once()->andReturn([
            'rows' => [['NoKayuBulat' => 'A.1', 'Berat' => 10.25]],
            'sub_rows' => [['NamaGrade' => 'RAMBUNG - STD', 'Berat' => 10.25]],
            'summary' => ['total_rows' => 1, 'total_berat' => 10.25],
        ]);

        $this->app->instance(SaldoHidupKayuBulatKgReportService::class, $service);

        $this->withHeaders($this->authJsonHeaders($user))
            ->postJson('/api/reports/kayu-bulat/saldo-hidup-kg', [])
            ->assertOk()
            ->assertJsonPath('meta.total_rows', 1)
            ->assertJsonPath('summary.total_berat', 10.25)
            ->assertJsonPath('sub_data.0.NamaGrade', 'RAMBUNG - STD');
    }

    public function test_rekap_pembelian_kg_preview_returns_grouped_year_rows(): void
    {
        $user = User::factory()->make(['id' => 1]);
        $service = Mockery::mock(RekapPembelianKayuBulatKgReportService::class);
        $service->shouldReceive('buildReportData')->once()->andReturn([
            'rows' => [['Tahun' => 2026, 'Bulan' => 1, 'Ton' => 25.5]],
            'year_rows' => [['tahun' => 2026, 'months' => [1 => 25.5], 'total' => 25.5]],
            'summary' => ['total_rows' => 1, 'total_years' => 1, 'grand_total' => 25.5],
        ]);

        $this->app->instance(RekapPembelianKayuBulatKgReportService::class, $service);

        $this->withHeaders($this->authJsonHeaders($user))
            ->postJson('/api/reports/kayu-bulat/rekap-pembelian-kg', [])
            ->assertOk()
            ->assertJsonPath('meta.total_years', 1)
            ->assertJsonPath('grouped_data.0.tahun', 2026)
            ->assertJsonPath('summary.grand_total', 25.5);
    }

    public function test_rekap_penerimaan_st_dari_sawmill_kg_preview_returns_grouped_dates_and_grades(): void
    {
        $user = User::factory()->make(['id' => 1]);
        $service = Mockery::mock(RekapPenerimaanSTDariSawmillKgReportService::class);
        $service->shouldReceive('buildReportData')->once()->with('2026-01-01', '2026-01-02')->andReturn([
            'rows' => [['Tanggal' => '2026-01-01', 'NamaGrade' => 'A', 'InOut' => '1', 'Berat' => 10]],
            'date_groups' => [[
                'date_key' => '2026-01-01',
                'date_label' => '01-Jan-26',
                'receipts' => [[
                    'meta' => ['no_pen_st' => 'B.1', 'tgl_penerimaan_st' => '2026-01-01'],
                    'rows' => [
                        'input' => [['grade' => 'A', 'kb' => 10.0, 'st' => 0.0, 'percent' => 0.0]],
                        'output' => [],
                    ],
                    'totals' => ['kb_total' => 10.0, 'st_total' => 0.0, 'rendemen' => 0.0],
                ]],
            ]],
            'summary' => ['total_rows' => 1, 'total_dates' => 1, 'total_receipts' => 1],
        ]);
        $this->app->instance(RekapPenerimaanSTDariSawmillKgReportService::class, $service);

        $this->withHeaders($this->authJsonHeaders($user))
            ->postJson('/api/reports/kayu-bulat/rekap-penerimaan-st-dari-sawmill-kg', [
                'TglAwal' => '2026-01-01',
                'TglAkhir' => '2026-01-02',
            ])
            ->assertOk()
            ->assertJsonPath('meta.total_dates', 1)
            ->assertJsonPath('meta.total_receipts', 1)
            ->assertJsonPath('grouped_data.0.date_key', '2026-01-01');
    }

    public function test_rekap_produktivitas_sawmill_rp_preview_returns_grouped_dates_and_rows(): void
    {
        $user = User::factory()->make(['id' => 1]);
        $service = Mockery::mock(RekapProduktivitasSawmillRpReportService::class);
        $service->shouldReceive('buildReportData')->once()->with('2026-01-01', '2026-01-02')->andReturn([
            'rows' => [['Tanggal' => '2026-01-01', 'NamaGrade' => 'A', 'InOut' => '1', 'Rp' => 10000]],
            'rows_sub' => [['Tanggal' => '2026-01-01', 'NamaGrade' => 'A', 'InOut' => '1', 'Rp' => 10000]],
            'date_groups' => [[
                'date_key' => '2026-01-01',
                'date_label' => '01-Jan-26',
                'receipts' => [[
                    'meta' => ['no_pen_st' => 'B.1', 'tgl_penerimaan_st' => '2026-01-01'],
                    'rows' => [
                        'input' => [['grade' => 'A', 'kb' => 10000.0, 'st' => 0.0, 'percent' => 0.0]],
                        'output' => [],
                    ],
                    'totals' => ['kb_total' => 10000.0, 'st_total' => 0.0, 'rendemen' => 0.0],
                ]],
            ]],
            'summary' => ['total_rows' => 1, 'total_dates' => 1, 'total_receipts' => 1],
        ]);
        $this->app->instance(RekapProduktivitasSawmillRpReportService::class, $service);

        $this->withHeaders($this->authJsonHeaders($user))
            ->postJson('/api/reports/kayu-bulat/rekap-produktivitas-sawmill-rp', [
                'TglAwal' => '2026-01-01',
                'TglAkhir' => '2026-01-02',
            ])
            ->assertOk()
            ->assertJsonPath('meta.total_dates', 1)
            ->assertJsonPath('meta.total_receipts', 1)
            ->assertJsonPath('grouped_data.0.date_key', '2026-01-01')
            ->assertJsonPath('sub_data.0.Rp', 10000);
    }

    public function test_timeline_kg_previews_return_grouped_periods(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $bulananService = Mockery::mock(TimelineKayuBulatBulananKgReportService::class);
        $bulananService->shouldReceive('fetch')->once()->with('2026-01-01', '2026-01-31')->andReturn([
            ['Tahun' => 2026, 'Bulan' => 1, 'NmSupplier' => 'A', 'TonBerat' => 5.25, 'Ranking' => 1],
        ]);
        $this->app->instance(TimelineKayuBulatBulananKgReportService::class, $bulananService);

        $this->withHeaders($this->authJsonHeaders($user))
            ->postJson('/api/reports/kayu-bulat/timeline-kayu-bulat-bulanan-kg', [
                'TglAwal' => '2026-01-01',
                'TglAkhir' => '2026-01-31',
            ])
            ->assertOk()
            ->assertJsonPath('meta.total_rows', 1)
            ->assertJsonPath('data.0.NmSupplier', 'A');

        $harianService = Mockery::mock(TimelineKayuBulatHarianKgReportService::class);
        $harianService->shouldReceive('buildReportData')->once()->with('2026-01-01', '2026-01-31')->andReturn([
            'rows' => [['NmSupplier' => 'A', 'TonBerat' => 5.25]],
            'periods' => [['label' => '2026-01-02', 'total_ton' => 5.25, 'rows' => [['NmSupplier' => 'A']]]],
            'summary' => ['total_rows' => 1, 'total_periods' => 1, 'total_ton' => 5.25],
        ]);
        $this->app->instance(TimelineKayuBulatHarianKgReportService::class, $harianService);

        $this->withHeaders($this->authJsonHeaders($user))
            ->postJson('/api/reports/kayu-bulat/timeline-kayu-bulat-harian-kg', [
                'TglAwal' => '2026-01-01',
                'TglAkhir' => '2026-01-31',
            ])
            ->assertOk()
            ->assertJsonPath('meta.total_periods', 1)
            ->assertJsonPath('grouped_data.0.label', '2026-01-02');
    }

    public function test_new_pdf_download_endpoints_return_pdf(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $saldoService = Mockery::mock(SaldoHidupKayuBulatKgReportService::class);
        $saldoService->shouldReceive('buildReportData')->once()->andReturn(['rows' => [], 'sub_rows' => [], 'summary' => []]);
        $pdfGenerator = Mockery::mock(PdfGenerator::class);
        $pdfGenerator->shouldReceive('render')->once()->andReturn('%PDF-1.4 mocked content');
        $this->app->instance(SaldoHidupKayuBulatKgReportService::class, $saldoService);
        $this->app->instance(PdfGenerator::class, $pdfGenerator);

        $this->actingAs($user)
            ->post('/reports/kayu-bulat/saldo-hidup-kg/download', [])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    /**
     * @return array<string, string>
     */
    private function authJsonHeaders(User $user): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->issueJwtForUser($user),
            'Accept' => 'application/json',
        ];
    }
}

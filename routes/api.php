<?php

use App\Http\Controllers\BahanTerpakaiController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OpenApiController;
use App\Http\Controllers\BalokSudahSemprotController;
use App\Http\Controllers\DashboardBarangJadiController;
use App\Http\Controllers\DashboardCrossCutAkhirController;
use App\Http\Controllers\DashboardFingerJointController;
use App\Http\Controllers\DashboardLaminatingController;
use App\Http\Controllers\DashboardMouldingController;
use App\Http\Controllers\DashboardS4SController;
use App\Http\Controllers\DashboardS4SV2Controller;
use App\Http\Controllers\DashboardSandingController;
use App\Http\Controllers\HasilOutputRacipHarianController;
use App\Http\Controllers\HidupKBPerGroupController;
use App\Http\Controllers\KayuBulatHidupController;
use App\Http\Controllers\KbKhususBangkangController;
use App\Http\Controllers\LabelNyangkutController;
use App\Http\Controllers\LembarTallyHasilSawmillController;
use App\Http\Controllers\MutasiBarangJadiController;
use App\Http\Controllers\MutasiCCAkhirController;
use App\Http\Controllers\MutasiFingerJointController;
use App\Http\Controllers\MutasiKayuBulatController;
use App\Http\Controllers\MutasiKayuBulatV2Controller;
use App\Http\Controllers\MutasiKayuBulatKGController;
use App\Http\Controllers\MutasiKayuBulatKGV2Controller;
use App\Http\Controllers\MutasiLaminatingController;
use App\Http\Controllers\MutasiMouldingController;
use App\Http\Controllers\MutasiReprosesController;
use App\Http\Controllers\MutasiSandingController;
use App\Http\Controllers\MutasiHasilRacipController;
use App\Http\Controllers\MutasiSTController;
use App\Http\Controllers\PenerimaanKayuBulatBulananPerSupplierController;
use App\Http\Controllers\PenerimaanKayuBulatPerSupplierBulananGrafikController;
use App\Http\Controllers\PenerimaanKayuBulatPerSupplierKgController;
use App\Http\Controllers\PenerimaanKayuBulatPerSupplierGroupController;
use App\Http\Controllers\PenerimaanStSawmillKgController;
use App\Http\Controllers\RekapPembelianKayuBulatKgController;
use App\Http\Controllers\MutasiS4SController;
use App\Http\Controllers\PerbandinganKbMasukPeriode1Dan2Controller;
use App\Http\Controllers\RekapHasilSawmillPerMejaUpahBoronganV2Controller;
use App\Http\Controllers\RangkumanJlhLabelInputController;
use App\Http\Controllers\SaldoKayuBulatController;
use App\Http\Controllers\SaldoHidupKayuBulatKgController;
use App\Http\Controllers\StockSTBasahController;
use App\Http\Controllers\StockSTKeringController;
use App\Http\Controllers\SupplierIntelController;
use App\Http\Controllers\TimelineKayuBulatHarianController;
use App\Http\Controllers\TimelineKayuBulatBulananController;
use App\Http\Controllers\TimelineKayuBulatHarianKgController;
use App\Http\Controllers\TimelineKayuBulatBulananKgController;
use App\Http\Controllers\UmurKayuBulatNonRambungController;
use App\Http\Controllers\UmurKayuBulatRambungController;
use App\Http\Controllers\UmurSawnTimberDetailTonController;
use App\Http\Controllers\PPS\RekapProduksiInjectBjController;
use App\Http\Controllers\PPS\RekapProduksiInjectController;
use App\Http\Controllers\PPS\RekapProduksiBrokerController;
use App\Http\Controllers\PPS\RekapProduksiCrusherController;
use App\Http\Controllers\PPS\RekapProduksiGilinganController;
use App\Http\Controllers\PPS\MutasiGilinganController;
use App\Http\Controllers\PPS\RekapProduksiHotStampingFwipController;
use App\Http\Controllers\PPS\RekapProduksiMixerController;
use App\Http\Controllers\PPS\RekapProduksiPackingBjController;
use App\Http\Controllers\PPS\RekapProduksiPasangKunciFwipController;
use App\Http\Controllers\PPS\RekapProduksiSpannerFwipController;
use App\Http\Controllers\PPS\RekapProduksiWashingController;
use App\Http\Controllers\PPS\MutasiBahanBakuController;
use App\Http\Controllers\PPS\MutasiBonggolanController;
use App\Http\Controllers\PPS\MutasiCrusherController;
use App\Http\Controllers\PPS\MutasiBrokerController;
use App\Http\Controllers\PPS\MutasiFurnitureWipController;
use App\Http\Controllers\PPS\MutasiMixerController;
use App\Http\Controllers\PPS\MutasiBarangJadiPpsController;
use App\Http\Controllers\PPS\SemuaLabelController;
use App\Http\Controllers\StSawmillMasukPerGroupController;
use App\Http\Controllers\StockOpnameKayuBulatController;
use Illuminate\Support\Facades\Route;

Route::get('/openapi.json', [OpenApiController::class, 'index'])->name('api.openapi');

/**
 * Group route autentikasi API publik.
 */
Route::prefix('auth')->group(function (): void {
    Route::post('/register', [AuthController::class, 'register'])->name('api.auth.register');
    Route::post('/login', [AuthController::class, 'login'])->name('api.auth.login');
    Route::post('/logout', [AuthController::class, 'logout'])->name('api.auth.logout');
    Route::post('/refresh', [AuthController::class, 'refresh'])->name('api.auth.refresh');

    /**
     * Group route autentikasi API yang memerlukan token JWT.
     */
    Route::middleware('report.jwt.claims')->group(function (): void {
        Route::get('/me', [AuthController::class, 'me'])->name('api.auth.me');
    });
});

/**
 * Group route laporan yang hanya bisa diakses user terautentikasi.
 */
Route::middleware('report.jwt.claims')->group(function (): void {
    /**
     * @param class-string $controller
     */
    $registerReportRoutes = static function (string $path, string $namePrefix, string $controller): void {
        Route::post($path, [$controller, 'preview'])->name("{$namePrefix}.preview");
        Route::match(['get', 'post'], "{$path}/pdf", [$controller, 'download'])->name("{$namePrefix}.pdf");
        Route::post("{$path}/health", [$controller, 'health'])->name("{$namePrefix}.health");
    };

    /**
     * Mutasi report API routes.
     *
     * @var array<int, array{0: string, 1: string, 2: class-string}>
     */
    $mutasiReportRouteDefinitions = [
        ['/reports/mutasi-barang-jadi', 'api.reports.mutasi-barang-jadi', MutasiBarangJadiController::class],
        ['/reports/mutasi-finger-joint', 'api.reports.mutasi-finger-joint', MutasiFingerJointController::class],
        ['/reports/mutasi-moulding', 'api.reports.mutasi-moulding', MutasiMouldingController::class],
        ['/reports/mutasi-laminating', 'api.reports.mutasi-laminating', MutasiLaminatingController::class],
        ['/reports/mutasi-sanding', 'api.reports.mutasi-sanding', MutasiSandingController::class],
        ['/reports/mutasi-s4s', 'api.reports.mutasi-s4s', MutasiS4SController::class],
        ['/reports/mutasi-st', 'api.reports.mutasi-st', MutasiSTController::class],
        ['/reports/mutasi-cca-akhir', 'api.reports.mutasi-cca-akhir', MutasiCCAkhirController::class],
        ['/reports/mutasi-reproses', 'api.reports.mutasi-reproses', MutasiReprosesController::class],
        ['/reports/mutasi-kayu-bulat', 'api.reports.mutasi-kayu-bulat', MutasiKayuBulatController::class],
        ['/reports/mutasi-kayu-bulat-v2', 'api.reports.mutasi-kayu-bulat-v2', MutasiKayuBulatV2Controller::class],
        ['/reports/mutasi-kayu-bulat-kgv2', 'api.reports.mutasi-kayu-bulat-kgv2', MutasiKayuBulatKGV2Controller::class],
        ['/reports/mutasi-kayu-bulat-kg', 'api.reports.mutasi-kayu-bulat-kg', MutasiKayuBulatKGController::class],
    ];

    /**
     * Kayu bulat report API routes.
     *
     * @var array<int, array{0: string, 1: string, 2: class-string}>
     */
    $kayuBulatReportRouteDefinitions = [
        ['/reports/kayu-bulat/saldo', 'api.reports.kayu-bulat.saldo', SaldoKayuBulatController::class],
        ['/reports/kayu-bulat/penerimaan-bulanan-per-supplier', 'api.reports.kayu-bulat.penerimaan-bulanan-per-supplier', PenerimaanKayuBulatBulananPerSupplierController::class],
        ['/reports/kayu-bulat/penerimaan-bulanan-per-supplier-grafik', 'api.reports.kayu-bulat.penerimaan-bulanan-per-supplier-grafik', PenerimaanKayuBulatPerSupplierBulananGrafikController::class],
        ['/reports/kayu-bulat/penerimaan-per-supplier-group', 'api.reports.kayu-bulat.penerimaan-per-supplier-group', PenerimaanKayuBulatPerSupplierGroupController::class],
        ['/reports/kayu-bulat/penerimaan-per-supplier-kg', 'api.reports.kayu-bulat.penerimaan-per-supplier-kg', PenerimaanKayuBulatPerSupplierKgController::class],
        ['/reports/kayu-bulat/saldo-hidup-kg', 'api.reports.kayu-bulat.saldo-hidup-kg', SaldoHidupKayuBulatKgController::class],
        ['/reports/kayu-bulat/rekap-pembelian-kg', 'api.reports.kayu-bulat.rekap-pembelian-kg', RekapPembelianKayuBulatKgController::class],
        ['/reports/kayu-bulat/stock-opname', 'api.reports.kayu-bulat.stock-opname', StockOpnameKayuBulatController::class],
        ['/reports/kayu-bulat/hidup-per-group', 'api.reports.kayu-bulat.hidup-per-group', HidupKBPerGroupController::class],
        ['/reports/kayu-bulat/hidup', 'api.reports.kayu-bulat.hidup', KayuBulatHidupController::class],
        ['/reports/kayu-bulat/perbandingan-kb-masuk-periode-1-dan-2', 'api.reports.kayu-bulat.perbandingan-kb-masuk-periode-1-dan-2', PerbandinganKbMasukPeriode1Dan2Controller::class],
        ['/reports/kayu-bulat/kb-khusus-bangkang', 'api.reports.kayu-bulat.kb-khusus-bangkang', KbKhususBangkangController::class],
        ['/reports/kayu-bulat/balok-sudah-semprot', 'api.reports.kayu-bulat.balok-sudah-semprot', BalokSudahSemprotController::class],
        ['/reports/kayu-bulat/timeline-kayu-bulat-harian', 'api.reports.kayu-bulat.timeline-kayu-bulat-harian', TimelineKayuBulatHarianController::class],
        ['/reports/kayu-bulat/timeline-kayu-bulat-bulanan', 'api.reports.kayu-bulat.timeline-kayu-bulat-bulanan', TimelineKayuBulatBulananController::class],
        ['/reports/kayu-bulat/timeline-kayu-bulat-harian-kg', 'api.reports.kayu-bulat.timeline-kayu-bulat-harian-kg', TimelineKayuBulatHarianKgController::class],
        ['/reports/kayu-bulat/timeline-kayu-bulat-bulanan-kg', 'api.reports.kayu-bulat.timeline-kayu-bulat-bulanan-kg', TimelineKayuBulatBulananKgController::class],
        ['/reports/kayu-bulat/umur-kayu-bulat-non-rambung', 'api.reports.kayu-bulat.umur-kayu-bulat-non-rambung', UmurKayuBulatNonRambungController::class],
        ['/reports/kayu-bulat/umur-kayu-bulat-rambung', 'api.reports.kayu-bulat.umur-kayu-bulat-rambung', UmurKayuBulatRambungController::class],
        ['/reports/kayu-bulat/supplier-intel', 'api.reports.kayu-bulat.supplier-intel', SupplierIntelController::class],
    ];

    /**
     * Sawn timber report API routes.
     *
     * @var array<int, array{0: string, 1: string, 2: class-string}>
     */
    $sawnTimberReportRouteDefinitions = [
        ['/reports/sawn-timber/stock-st-basah', 'api.reports.sawn-timber.stock-st-basah', StockSTBasahController::class],
        ['/reports/sawn-timber/stock-st-kering', 'api.reports.sawn-timber.stock-st-kering', StockSTKeringController::class],
        ['/reports/sawn-timber/penerimaan-st-dari-sawmill-kg', 'api.reports.sawn-timber.penerimaan-st-dari-sawmill-kg', PenerimaanStSawmillKgController::class],
        ['/reports/sawn-timber/lembar-tally-hasil-sawmill', 'api.reports.sawn-timber.lembar-tally-hasil-sawmill', LembarTallyHasilSawmillController::class],
        ['/reports/sawn-timber/umur-sawn-timber-detail-ton', 'api.reports.sawn-timber.umur-sawn-timber-detail-ton', UmurSawnTimberDetailTonController::class],
        ['/reports/sawn-timber/st-sawmill-masuk-per-group', 'api.reports.sawn-timber.st-sawmill-masuk-per-group', StSawmillMasukPerGroupController::class],
    ];

    /**
     * Standalone report API routes.
     *
     * @var array<int, array{0: string, 1: string, 2: class-string}>
     */
    $standaloneReportRouteDefinitions = [
        ['/reports/hasil-output-racip-harian', 'api.reports.hasil-output-racip-harian', HasilOutputRacipHarianController::class],
        ['/reports/rangkuman-label-input', 'api.reports.rangkuman-label-input', RangkumanJlhLabelInputController::class],
        ['/reports/mutasi-hasil-racip', 'api.reports.mutasi-hasil-racip', MutasiHasilRacipController::class],
        ['/reports/label-nyangkut', 'api.reports.label-nyangkut', LabelNyangkutController::class],
        ['/reports/bahan-terpakai', 'api.reports.bahan-terpakai', BahanTerpakaiController::class],
        ['/reports/pps/rekap-produksi/inject', 'api.reports.pps.rekap-produksi.inject', RekapProduksiInjectController::class],
        ['/reports/pps/rekap-produksi/inject-bj', 'api.reports.pps.rekap-produksi.inject-bj', RekapProduksiInjectBjController::class],
        ['/reports/pps/rekap-produksi/hot-stamping-fwip', 'api.reports.pps.rekap-produksi.hot-stamping-fwip', RekapProduksiHotStampingFwipController::class],
        ['/reports/pps/rekap-produksi/packing-bj', 'api.reports.pps.rekap-produksi.packing-bj', RekapProduksiPackingBjController::class],
        ['/reports/pps/rekap-produksi/pasang-kunci-fwip', 'api.reports.pps.rekap-produksi.pasang-kunci-fwip', RekapProduksiPasangKunciFwipController::class],
        ['/reports/pps/rekap-produksi/spanner-fwip', 'api.reports.pps.rekap-produksi.spanner-fwip', RekapProduksiSpannerFwipController::class],
        ['/reports/pps/rekap-produksi/broker', 'api.reports.pps.rekap-produksi.broker', RekapProduksiBrokerController::class],
        ['/reports/pps/rekap-produksi/washing', 'api.reports.pps.rekap-produksi.washing', RekapProduksiWashingController::class],
        ['/reports/pps/rekap-produksi/mixer', 'api.reports.pps.rekap-produksi.mixer', RekapProduksiMixerController::class],
        ['/reports/pps/rekap-produksi/gilingan', 'api.reports.pps.rekap-produksi.gilingan', RekapProduksiGilinganController::class],
        ['/reports/pps/rekap-produksi/crusher', 'api.reports.pps.rekap-produksi.crusher', RekapProduksiCrusherController::class],
        ['/reports/pps/semua-label', 'api.reports.pps.semua-label', SemuaLabelController::class],
        ['/reports/pps/bahan-baku/mutasi-bahan-baku', 'api.reports.pps.bahan-baku.mutasi-bahan-baku', MutasiBahanBakuController::class],
        ['/reports/pps/barang-jadi/mutasi-barang-jadi', 'api.reports.pps.barang-jadi.mutasi-barang-jadi', MutasiBarangJadiPpsController::class],
        ['/reports/pps/broker/mutasi-broker', 'api.reports.pps.broker.mutasi-broker', MutasiBrokerController::class],
        ['/reports/pps/bonggolan/mutasi-bonggolan', 'api.reports.pps.bonggolan.mutasi-bonggolan', MutasiBonggolanController::class],
        ['/reports/pps/crusher/mutasi-crusher', 'api.reports.pps.crusher.mutasi-crusher', MutasiCrusherController::class],
        ['/reports/pps/gilingan/mutasi-gilingan', 'api.reports.pps.gilingan.mutasi-gilingan', MutasiGilinganController::class],
        ['/reports/pps/mixer/mutasi-mixer', 'api.reports.pps.mixer.mutasi-mixer', MutasiMixerController::class],
        ['/reports/pps/furniture-wip/mutasi-furniture-wip', 'api.reports.pps.furniture-wip.mutasi-furniture-wip', MutasiFurnitureWipController::class],
        ['/reports/dashboard-barang-jadi', 'api.reports.dashboard-barang-jadi', DashboardBarangJadiController::class],
        ['/reports/dashboard-cross-cut-akhir', 'api.reports.dashboard-cross-cut-akhir', DashboardCrossCutAkhirController::class],
        ['/reports/dashboard-finger-joint', 'api.reports.dashboard-finger-joint', DashboardFingerJointController::class],
        ['/reports/dashboard-laminating', 'api.reports.dashboard-laminating', DashboardLaminatingController::class],
        ['/reports/dashboard-moulding', 'api.reports.dashboard-moulding', DashboardMouldingController::class],
        ['/reports/dashboard-sanding', 'api.reports.dashboard-sanding', DashboardSandingController::class],
        ['/reports/dashboard-s4s', 'api.reports.dashboard-s4s', DashboardS4SController::class],
        ['/reports/dashboard-s4s-v2', 'api.reports.dashboard-s4s-v2', DashboardS4SV2Controller::class],
    ];

    /** @var array<int, array<int, array{0: string, 1: string, 2: class-string}>> $routeGroups */
    $routeGroups = [
        $mutasiReportRouteDefinitions,
        $kayuBulatReportRouteDefinitions,
        $sawnTimberReportRouteDefinitions,
        $standaloneReportRouteDefinitions,
    ];

    foreach ($routeGroups as $routeGroup) {
        foreach ($routeGroup as [$path, $namePrefix, $controller]) {
            $registerReportRoutes($path, $namePrefix, $controller);
        }
    }

    Route::prefix('/reports/sawn-timber/rekap-hasil-sawmill-per-meja-upah-borongan-v2')
        ->name('api.reports.sawn-timber.rekap-hasil-sawmill-per-meja-upah-borongan-v2.')
        ->group(function (): void {
            Route::post('/', [RekapHasilSawmillPerMejaUpahBoronganV2Controller::class, 'preview'])->name('preview');
            Route::match(['get', 'post'], '/pdf', [RekapHasilSawmillPerMejaUpahBoronganV2Controller::class, 'download'])->name('pdf');
            Route::post('/health', [RekapHasilSawmillPerMejaUpahBoronganV2Controller::class, 'health'])->name('health');
        });

    // Alias endpoint khusus PPS Rekap Produksi Inject agar konsisten dengan pola endpoint web.
    Route::prefix('/reports/pps/rekap-produksi/inject')->name('api.reports.pps.rekap-produksi.inject.')->group(function (): void {
        Route::post('/preview', [RekapProduksiInjectController::class, 'preview'])->name('preview-explicit');
        Route::match(['get', 'post'], '/download', [RekapProduksiInjectController::class, 'download'])->name('download');
        Route::post('/health', [RekapProduksiInjectController::class, 'health'])->name('health-explicit');
    });
});

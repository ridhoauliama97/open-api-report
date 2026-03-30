<?php

use App\Http\Controllers\BahanTerpakaiController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OpenApiController;
use App\Http\Controllers\BalokSudahSemprotController;
use App\Http\Controllers\DashboardBarangJadiController;
use App\Http\Controllers\BarangJadiHidupDetailController;
use App\Http\Controllers\UmurBarangJadiDetailController;
use App\Http\Controllers\RekapProduksiBarangJadiConsolidatedController;
use App\Http\Controllers\RekapProduksiPackingPerJenisPerGradeController;
use App\Http\Controllers\DashboardCrossCutAkhirController;
use App\Http\Controllers\RekapProduksiCrossCutAkhirConsolidatedController;
use App\Http\Controllers\RekapProduksiCrossCutAkhirPerJenisPerGradeController;
use App\Http\Controllers\KetahananBarangDagangCrossCutAkhirController;
use App\Http\Controllers\CrossCutAkhirHidupDetailController;
use App\Http\Controllers\UmurCrossCutAkhirDetailController;
use App\Http\Controllers\DashboardFingerJointController;
use App\Http\Controllers\DashboardLaminatingController;
use App\Http\Controllers\DashboardMouldingController;
use App\Http\Controllers\DashboardReprosesController;
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
use App\Http\Controllers\MutasiBarangJadiPerJenisPerUkuranController;
use App\Http\Controllers\SaldoBarangJadiHidupPerJenisPerProdukController;
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
use App\Http\Controllers\RekapPenerimaanSTDariSawmillKgController;
use App\Http\Controllers\RekapPenerimaanSTDariSawmillNonRambungController;
use App\Http\Controllers\RekapRendemenNonRambungController;
use App\Http\Controllers\RekapProduktivitasSawmillRpController;
use App\Http\Controllers\RekapProduktivitasSawmillSawnTimberController;
use App\Http\Controllers\PemakaianObatVacuumController;
use App\Http\Controllers\MutasiS4SController;
use App\Http\Controllers\PembelianStTimelineTonController;
use App\Http\Controllers\LabelStHidupDetailController;
use App\Http\Controllers\KetahananBarangDagangStController;
use App\Http\Controllers\KetahananBarangDagangS4sController;
use App\Http\Controllers\KetahananBarangDagangFingerJointController;
use App\Http\Controllers\GradeAbcHarianController;
use App\Http\Controllers\RekapProduksiS4sRambungPerGradeController;
use App\Http\Controllers\OutputProduksiS4sPerGradeController;
use App\Http\Controllers\StRambungMc1Mc2DetailController;
use App\Http\Controllers\RekapProduksiFingerJointConsolidatedController;
use App\Http\Controllers\StRambungMc1Mc2RangkumanController;
use App\Http\Controllers\StHidupKeringController;
use App\Http\Controllers\PerbandinganKbMasukPeriode1Dan2Controller;
use App\Http\Controllers\PerbandinganKbMasukPeriode1Dan2KgController;
use App\Http\Controllers\RekapHasilSawmillPerMejaController;
use App\Http\Controllers\RekapHasilSawmillPerMejaUpahBoronganController;
use App\Http\Controllers\RekapHasilSawmillPerMejaUpahBoronganV2Controller;
use App\Http\Controllers\RangkumanJlhLabelInputController;
use App\Http\Controllers\SaldoKayuBulatController;
use App\Http\Controllers\SaldoHidupKayuBulatKgController;
use App\Http\Controllers\StockSTBasahController;
use App\Http\Controllers\StockHidupPerNoSpkController;
use App\Http\Controllers\StockSTKeringController;
use App\Http\Controllers\SupplierIntelController;
use App\Http\Controllers\TimelineKayuBulatHarianController;
use App\Http\Controllers\TimelineKayuBulatBulananController;
use App\Http\Controllers\TimelineKayuBulatHarianKgController;
use App\Http\Controllers\TimelineKayuBulatBulananKgController;
use App\Http\Controllers\UmurKayuBulatNonRambungController;
use App\Http\Controllers\UmurKayuBulatRambungController;
use App\Http\Controllers\UmurFingerJointDetailController;
use App\Http\Controllers\UmurLaminatingDetailController;
use App\Http\Controllers\UmurMouldingDetailController;
use App\Http\Controllers\UmurReprosesDetailController;
use App\Http\Controllers\ReprosesHidupDetailController;
use App\Http\Controllers\KetahananBarangDagangReprosesController;
use App\Http\Controllers\UmurSandingDetailController;
use App\Http\Controllers\RekapProduksiSandingConsolidatedController;
use App\Http\Controllers\RekapProduksiSandingPerJenisPerGradeController;
use App\Http\Controllers\KetahananBarangDagangSandingController;
use App\Http\Controllers\SandingHidupDetailController;
use App\Http\Controllers\RekapProduksiLaminatingConsolidatedController;
use App\Http\Controllers\RekapProduksiLaminatingPerJenisPerGradeController;
use App\Http\Controllers\LaminatingHidupDetailController;
use App\Http\Controllers\KetahananBarangDagangLaminatingController;
use App\Http\Controllers\RekapProduksiMouldingConsolidatedController;
use App\Http\Controllers\MouldingHidupDetailController;
use App\Http\Controllers\RekapProduksiMouldingPerJenisPerGradeController;
use App\Http\Controllers\KetahananBarangDagangMouldingController;
use App\Http\Controllers\UmurS4SDetailController;
use App\Http\Controllers\RekapProduksiS4SConsolidatedController;
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
use App\Http\Controllers\StSawmillMasukPerGroupMejaController;
use App\Http\Controllers\StSawmillHariTebalLebarController;
use App\Http\Controllers\StBasahHidupPerUmurKayuTonController;
use App\Http\Controllers\KdKeluarMasukController;
use App\Http\Controllers\RekapKamarKdController;
use App\Http\Controllers\MutasiKdController;
use App\Http\Controllers\RekapStPenjualanController;
use App\Http\Controllers\PembelianStPerSupplierTonController;
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
        ['/reports/barang-jadi/mutasi-barang-jadi-per-jenis-per-ukuran', 'api.reports.barang-jadi.mutasi-barang-jadi-per-jenis-per-ukuran', MutasiBarangJadiPerJenisPerUkuranController::class],
        ['/reports/barang-jadi/saldo-barang-jadi-hidup-per-jenis-per-produk', 'api.reports.barang-jadi.saldo-barang-jadi-hidup-per-jenis-per-produk', SaldoBarangJadiHidupPerJenisPerProdukController::class],
        ['/reports/barang-jadi/barang-jadi-hidup-detail', 'api.reports.barang-jadi.barang-jadi-hidup-detail', BarangJadiHidupDetailController::class],
        ['/reports/barang-jadi/umur-barang-jadi-detail', 'api.reports.barang-jadi.umur-barang-jadi-detail', UmurBarangJadiDetailController::class],
        ['/reports/barang-jadi/rekap-produksi-barang-jadi-consolidated', 'api.reports.barang-jadi.rekap-produksi-barang-jadi-consolidated', RekapProduksiBarangJadiConsolidatedController::class],
        ['/reports/barang-jadi/rekap-produksi-packing-per-jenis-per-grade', 'api.reports.barang-jadi.rekap-produksi-packing-per-jenis-per-grade', RekapProduksiPackingPerJenisPerGradeController::class],
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
        ['/reports/kayu-bulat/rekap-penerimaan-st-dari-sawmill-kg', 'api.reports.kayu-bulat.rekap-penerimaan-st-dari-sawmill-kg', RekapPenerimaanSTDariSawmillKgController::class],
        ['/reports/kayu-bulat/rekap-produktivitas-sawmill-rp', 'api.reports.kayu-bulat.rekap-produktivitas-sawmill-rp', RekapProduktivitasSawmillRpController::class],
        ['/reports/kayu-bulat/stock-opname', 'api.reports.kayu-bulat.stock-opname', StockOpnameKayuBulatController::class],
        ['/reports/kayu-bulat/hidup-per-group', 'api.reports.kayu-bulat.hidup-per-group', HidupKBPerGroupController::class],
        ['/reports/kayu-bulat/hidup', 'api.reports.kayu-bulat.hidup', KayuBulatHidupController::class],
        ['/reports/kayu-bulat/perbandingan-kb-masuk-periode-1-dan-2', 'api.reports.kayu-bulat.perbandingan-kb-masuk-periode-1-dan-2', PerbandinganKbMasukPeriode1Dan2Controller::class],
        ['/reports/kayu-bulat/perbandingan-kb-masuk-periode-1-dan-2-kg', 'api.reports.kayu-bulat.perbandingan-kb-masuk-periode-1-dan-2-kg', PerbandinganKbMasukPeriode1Dan2KgController::class],
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
        ['/reports/sawn-timber/st-basah-hidup-per-umur-kayu-ton', 'api.reports.sawn-timber.st-basah-hidup-per-umur-kayu-ton', StBasahHidupPerUmurKayuTonController::class],
        ['/reports/sawn-timber/kd-keluar-masuk', 'api.reports.sawn-timber.kd-keluar-masuk', KdKeluarMasukController::class],
        ['/reports/sawn-timber/rekap-kamar-kd', 'api.reports.sawn-timber.rekap-kamar-kd', RekapKamarKdController::class],
        ['/reports/sawn-timber/mutasi-kd', 'api.reports.sawn-timber.mutasi-kd', MutasiKdController::class],
        ['/reports/sawn-timber/rekap-st-penjualan', 'api.reports.sawn-timber.rekap-st-penjualan', RekapStPenjualanController::class],
        ['/reports/sawn-timber/pembelian-st-per-supplier-ton', 'api.reports.sawn-timber.pembelian-st-per-supplier-ton', PembelianStPerSupplierTonController::class],
        ['/reports/sawn-timber/pembelian-st-timeline-ton', 'api.reports.sawn-timber.pembelian-st-timeline-ton', PembelianStTimelineTonController::class],
        ['/reports/sawn-timber/label-st-hidup-detail', 'api.reports.sawn-timber.label-st-hidup-detail', LabelStHidupDetailController::class],
        ['/reports/sawn-timber/ketahanan-barang-st', 'api.reports.sawn-timber.ketahanan-barang-st', KetahananBarangDagangStController::class],
        ['/reports/sawn-timber/st-rambung-mc1-mc2-detail', 'api.reports.sawn-timber.st-rambung-mc1-mc2-detail', StRambungMc1Mc2DetailController::class],
        ['/reports/sawn-timber/st-rambung-mc1-mc2-rangkuman', 'api.reports.sawn-timber.st-rambung-mc1-mc2-rangkuman', StRambungMc1Mc2RangkumanController::class],
        ['/reports/sawn-timber/st-hidup-kering', 'api.reports.sawn-timber.st-hidup-kering', StHidupKeringController::class],
        ['/reports/sawn-timber/penerimaan-st-dari-sawmill-kg', 'api.reports.sawn-timber.penerimaan-st-dari-sawmill-kg', PenerimaanStSawmillKgController::class],
        ['/reports/sawn-timber/rekap-penerimaan-st-dari-sawmill-non-rambung', 'api.reports.sawn-timber.rekap-penerimaan-st-dari-sawmill-non-rambung', RekapPenerimaanSTDariSawmillNonRambungController::class],
        ['/reports/sawn-timber/lembar-tally-hasil-sawmill', 'api.reports.sawn-timber.lembar-tally-hasil-sawmill', LembarTallyHasilSawmillController::class],
        ['/reports/sawn-timber/rekap-hasil-sawmill-per-meja-upah-borongan', 'api.reports.sawn-timber.rekap-hasil-sawmill-per-meja-upah-borongan', RekapHasilSawmillPerMejaUpahBoronganController::class],
        ['/reports/sawn-timber/rekap-hasil-sawmill-per-meja', 'api.reports.sawn-timber.rekap-hasil-sawmill-per-meja', RekapHasilSawmillPerMejaController::class],
        ['/reports/sawn-timber/rekap-produktivitas-sawmill', 'api.reports.sawn-timber.rekap-produktivitas-sawmill', RekapProduktivitasSawmillSawnTimberController::class],
        ['/reports/sawn-timber/pemakaian-obat-vacuum', 'api.reports.sawn-timber.pemakaian-obat-vacuum', PemakaianObatVacuumController::class],
        ['/reports/sawn-timber/st-sawmill-hari-tebal-lebar', 'api.reports.sawn-timber.st-sawmill-hari-tebal-lebar', StSawmillHariTebalLebarController::class],
        ['/reports/sawn-timber/umur-sawn-timber-detail-ton', 'api.reports.sawn-timber.umur-sawn-timber-detail-ton', UmurSawnTimberDetailTonController::class],
        ['/reports/sawn-timber/st-sawmill-masuk-per-group', 'api.reports.sawn-timber.st-sawmill-masuk-per-group', StSawmillMasukPerGroupController::class],
        ['/reports/sawn-timber/st-sawmill-masuk-per-group-meja', 'api.reports.sawn-timber.st-sawmill-masuk-per-group-meja', StSawmillMasukPerGroupMejaController::class],
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
        ['/reports/management/stock-hidup-per-nospk', 'api.reports.management.stock-hidup-per-nospk', StockHidupPerNoSpkController::class],
        ['/reports/finger-joint/umur-finger-joint-detail', 'api.reports.finger-joint.umur-finger-joint-detail', UmurFingerJointDetailController::class],
        ['/reports/finger-joint/rekap-produksi-finger-joint-consolidated', 'api.reports.finger-joint.rekap-produksi-finger-joint-consolidated', RekapProduksiFingerJointConsolidatedController::class],
        ['/reports/finger-joint/ketahanan-barang-finger-joint', 'api.reports.finger-joint.ketahanan-barang-finger-joint', KetahananBarangDagangFingerJointController::class],
        ['/reports/laminating/umur-laminating-detail', 'api.reports.laminating.umur-laminating-detail', UmurLaminatingDetailController::class],
        ['/reports/moulding/umur-moulding-detail', 'api.reports.moulding.umur-moulding-detail', UmurMouldingDetailController::class],
        ['/reports/reproses/umur-reproses-detail', 'api.reports.reproses.umur-reproses-detail', UmurReprosesDetailController::class],
        ['/reports/reproses/reproses-hidup-detail', 'api.reports.reproses.reproses-hidup-detail', ReprosesHidupDetailController::class],
        ['/reports/reproses/ketahanan-barang-reproses', 'api.reports.reproses.ketahanan-barang-reproses', KetahananBarangDagangReprosesController::class],
        ['/reports/cross-cut-akhir/umur-cc-akhir-detail', 'api.reports.cross-cut-akhir.umur-cc-akhir-detail', UmurCrossCutAkhirDetailController::class],
        ['/reports/cross-cut-akhir/cc-akhir-hidup-detail', 'api.reports.cross-cut-akhir.cc-akhir-hidup-detail', CrossCutAkhirHidupDetailController::class],
        ['/reports/cross-cut-akhir/rekap-produksi-cc-akhir-consolidated', 'api.reports.cross-cut-akhir.rekap-produksi-cc-akhir-consolidated', RekapProduksiCrossCutAkhirConsolidatedController::class],
        ['/reports/cross-cut-akhir/rekap-produksi-cc-akhir-per-jenis-per-grade', 'api.reports.cross-cut-akhir.rekap-produksi-cc-akhir-per-jenis-per-grade', RekapProduksiCrossCutAkhirPerJenisPerGradeController::class],
        ['/reports/cross-cut-akhir/ketahanan-barang-cc-akhir', 'api.reports.cross-cut-akhir.ketahanan-barang-cc-akhir', KetahananBarangDagangCrossCutAkhirController::class],
        ['/reports/sanding/umur-sanding-detail', 'api.reports.sanding.umur-sanding-detail', UmurSandingDetailController::class],
        ['/reports/sanding/sanding-hidup-detail', 'api.reports.sanding.sanding-hidup-detail', SandingHidupDetailController::class],
        ['/reports/sanding/rekap-produksi-sanding-consolidated', 'api.reports.sanding.rekap-produksi-sanding-consolidated', RekapProduksiSandingConsolidatedController::class],
        ['/reports/sanding/rekap-produksi-sanding-per-jenis-per-grade', 'api.reports.sanding.rekap-produksi-sanding-per-jenis-per-grade', RekapProduksiSandingPerJenisPerGradeController::class],
        ['/reports/sanding/ketahanan-barang-sanding', 'api.reports.sanding.ketahanan-barang-sanding', KetahananBarangDagangSandingController::class],
        ['/reports/laminating/rekap-produksi-laminating-consolidated', 'api.reports.laminating.rekap-produksi-laminating-consolidated', RekapProduksiLaminatingConsolidatedController::class],
        ['/reports/laminating/laminating-hidup-detail', 'api.reports.laminating.laminating-hidup-detail', LaminatingHidupDetailController::class],
        ['/reports/laminating/rekap-produksi-laminating-per-jenis-per-grade', 'api.reports.laminating.rekap-produksi-laminating-per-jenis-per-grade', RekapProduksiLaminatingPerJenisPerGradeController::class],
        ['/reports/laminating/ketahanan-barang-laminating', 'api.reports.laminating.ketahanan-barang-laminating', KetahananBarangDagangLaminatingController::class],
        ['/reports/moulding/rekap-produksi-moulding-consolidated', 'api.reports.moulding.rekap-produksi-moulding-consolidated', RekapProduksiMouldingConsolidatedController::class],
        ['/reports/moulding/moulding-hidup-detail', 'api.reports.moulding.moulding-hidup-detail', MouldingHidupDetailController::class],
        ['/reports/moulding/rekap-produksi-moulding-per-jenis-per-grade', 'api.reports.moulding.rekap-produksi-moulding-per-jenis-per-grade', RekapProduksiMouldingPerJenisPerGradeController::class],
        ['/reports/moulding/ketahanan-barang-moulding', 'api.reports.moulding.ketahanan-barang-moulding', KetahananBarangDagangMouldingController::class],
        ['/reports/s4s/umur-s4s-detail', 'api.reports.s4s.umur-s4s-detail', UmurS4SDetailController::class],
        ['/reports/s4s/rekap-produksi-s4s-consolidated', 'api.reports.s4s.rekap-produksi-s4s-consolidated', RekapProduksiS4SConsolidatedController::class],
        ['/reports/s4s/ketahanan-barang-s4s', 'api.reports.s4s.ketahanan-barang-s4s', KetahananBarangDagangS4sController::class],
        ['/reports/s4s/output-produksi-s4s-per-grade', 'api.reports.s4s.output-produksi-s4s-per-grade', OutputProduksiS4sPerGradeController::class],
        ['/reports/s4s/grade-abc-harian', 'api.reports.s4s.grade-abc-harian', GradeAbcHarianController::class],
        ['/reports/s4s/rekap-produksi-rambung-per-grade', 'api.reports.s4s.rekap-produksi-rambung-per-grade', RekapProduksiS4sRambungPerGradeController::class],
        ['/reports/rendemen-kayu/rekap-rendemen-non-rambung', 'api.reports.rendemen-kayu.rekap-rendemen-non-rambung', RekapRendemenNonRambungController::class],
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
        ['/reports/dashboard-reproses', 'api.reports.dashboard-reproses', DashboardReprosesController::class],
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

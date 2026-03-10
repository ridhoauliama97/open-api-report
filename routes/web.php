<?php

use App\Http\Controllers\BahanTerpakaiController;
use App\Http\Controllers\BalokSudahSemprotController;
use App\Http\Controllers\DashboardBarangJadiController;
use App\Http\Controllers\DashboardCrossCutAkhirController;
use App\Http\Controllers\DashboardFingerJointController;
use App\Http\Controllers\DashboardLaminatingController;
use App\Http\Controllers\DashboardMouldingController;
use App\Http\Controllers\DashboardS4SController;
use App\Http\Controllers\DashboardS4SV2Controller;
use App\Http\Controllers\DashboardSandingController;
use App\Http\Controllers\DashboardSawnTimberController;
use App\Http\Controllers\HidupKBPerGroupController;
use App\Http\Controllers\HasilOutputRacipHarianController;
use App\Http\Controllers\KbKhususBangkangController;
use App\Http\Controllers\KayuBulatHidupController;
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
use App\Http\Controllers\MutasiRacipDetailController;
use App\Http\Controllers\MutasiSTController;
use App\Http\Controllers\PenerimaanKayuBulatBulananPerSupplierController;
use App\Http\Controllers\PenerimaanKayuBulatPerSupplierBulananGrafikController;
use App\Http\Controllers\PenerimaanKayuBulatPerSupplierKgController;
use App\Http\Controllers\PenerimaanKayuBulatPerSupplierGroupController;
use App\Http\Controllers\PenerimaanStSawmillKgController;
use App\Http\Controllers\PerbandinganKbMasukPeriode1Dan2Controller;
use App\Http\Controllers\RekapHasilSawmillPerMejaUpahBoronganV2Controller;
use App\Http\Controllers\RekapPembelianKayuBulatKgController;
use App\Http\Controllers\MutasiS4SController;
use App\Http\Controllers\SaldoKayuBulatController;
use App\Http\Controllers\SaldoHidupKayuBulatKgController;
use App\Http\Controllers\StockSTBasahController;
use App\Http\Controllers\TargetMasukBBController;
use App\Http\Controllers\TargetMasukBBBulananController;
use App\Http\Controllers\TimelineKayuBulatHarianController;
use App\Http\Controllers\TimelineKayuBulatBulananController;
use App\Http\Controllers\TimelineKayuBulatHarianKgController;
use App\Http\Controllers\TimelineKayuBulatBulananKgController;
use App\Http\Controllers\UmurKayuBulatNonRambungController;
use App\Http\Controllers\UmurKayuBulatRambungController;
use App\Http\Controllers\UmurSawnTimberDetailTonController;
use App\Http\Controllers\StSawmillMasukPerGroupController;
use App\Http\Controllers\StockRacipKayuLatController;
use App\Http\Controllers\StockOpnameKayuBulatController;
use App\Http\Controllers\LabelNyangkutController;
use App\Http\Controllers\LembarTallyHasilSawmillController;
use App\Http\Controllers\RangkumanJlhLabelInputController;
use App\Http\Controllers\RekapPembelianKayuBulatController;
use App\Http\Controllers\StockSTKeringController;
use App\Http\Controllers\SupplierIntelController;
use App\Http\Controllers\WebAuthController;
use App\Http\Controllers\PPS\RekapProduksiInjectBjController;
use App\Http\Controllers\PPS\RekapProduksiInjectController;
use App\Http\Controllers\PPS\RekapProduksiHotStampingFwipController;
use App\Http\Controllers\PPS\RekapProduksiCrusherController;
use App\Http\Controllers\PPS\RekapProduksiGilinganController;
use App\Http\Controllers\PPS\MutasiGilinganController;
use App\Http\Controllers\PPS\RekapProduksiMixerController;
use App\Http\Controllers\PPS\RekapProduksiBrokerController;
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
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

// PPS Reports Routes
Route::prefix('reports/pps')->name('reports.pps.')->group(function (): void {
    /** Rekap produksi inject routes. */
    Route::prefix('rekap-produksi/inject')->name('rekap-produksi.inject.')->group(function (): void {
        Route::get('/', [RekapProduksiInjectController::class, 'index'])->name('index');
        Route::post('/download', [RekapProduksiInjectController::class, 'download'])->name('download');
        Route::post('/preview', [RekapProduksiInjectController::class, 'preview'])->name('preview');
        Route::post('/health', [RekapProduksiInjectController::class, 'health'])->name('health');
    });

    /** Rekap produksi inject BJ routes. */
    Route::prefix('rekap-produksi/inject-bj')->name('rekap-produksi.inject-bj.')->group(function (): void {
        Route::get('/', [RekapProduksiInjectBjController::class, 'index'])->name('index');
        Route::post('/download', [RekapProduksiInjectBjController::class, 'download'])->name('download');
        Route::post('/preview', [RekapProduksiInjectBjController::class, 'preview'])->name('preview');
        Route::post('/health', [RekapProduksiInjectBjController::class, 'health'])->name('health');
    });

    Route::prefix('rekap-produksi/hot-stamping-fwip')->name('rekap-produksi.hot-stamping-fwip.')->group(function (): void {
        Route::get('/', [RekapProduksiHotStampingFwipController::class, 'index'])->name('index');
        Route::post('/download', [RekapProduksiHotStampingFwipController::class, 'download'])->name('download');
        Route::post('/preview', [RekapProduksiHotStampingFwipController::class, 'preview'])->name('preview');
        Route::post('/health', [RekapProduksiHotStampingFwipController::class, 'health'])->name('health');
    });

    Route::prefix('rekap-produksi/packing-bj')->name('rekap-produksi.packing-bj.')->group(function (): void {
        Route::get('/', [RekapProduksiPackingBjController::class, 'index'])->name('index');
        Route::post('/download', [RekapProduksiPackingBjController::class, 'download'])->name('download');
        Route::post('/preview', [RekapProduksiPackingBjController::class, 'preview'])->name('preview');
        Route::post('/health', [RekapProduksiPackingBjController::class, 'health'])->name('health');
    });

    Route::prefix('rekap-produksi/pasang-kunci-fwip')->name('rekap-produksi.pasang-kunci-fwip.')->group(function (): void {
        Route::get('/', [RekapProduksiPasangKunciFwipController::class, 'index'])->name('index');
        Route::post('/download', [RekapProduksiPasangKunciFwipController::class, 'download'])->name('download');
        Route::post('/preview', [RekapProduksiPasangKunciFwipController::class, 'preview'])->name('preview');
        Route::post('/health', [RekapProduksiPasangKunciFwipController::class, 'health'])->name('health');
    });

    Route::prefix('rekap-produksi/spanner-fwip')->name('rekap-produksi.spanner-fwip.')->group(function (): void {
        Route::get('/', [RekapProduksiSpannerFwipController::class, 'index'])->name('index');
        Route::post('/download', [RekapProduksiSpannerFwipController::class, 'download'])->name('download');
        Route::post('/preview', [RekapProduksiSpannerFwipController::class, 'preview'])->name('preview');
        Route::post('/health', [RekapProduksiSpannerFwipController::class, 'health'])->name('health');
    });

    Route::prefix('rekap-produksi/broker')->name('rekap-produksi.broker.')->group(function (): void {
        Route::get('/', [RekapProduksiBrokerController::class, 'index'])->name('index');
        Route::post('/download', [RekapProduksiBrokerController::class, 'download'])->name('download');
        Route::post('/preview', [RekapProduksiBrokerController::class, 'preview'])->name('preview');
        Route::post('/health', [RekapProduksiBrokerController::class, 'health'])->name('health');
    });

    Route::prefix('rekap-produksi/washing')->name('rekap-produksi.washing.')->group(function (): void {
        Route::get('/', [RekapProduksiWashingController::class, 'index'])->name('index');
        Route::post('/download', [RekapProduksiWashingController::class, 'download'])->name('download');
        Route::post('/preview', [RekapProduksiWashingController::class, 'preview'])->name('preview');
        Route::post('/health', [RekapProduksiWashingController::class, 'health'])->name('health');
    });

    Route::prefix('rekap-produksi/mixer')->name('rekap-produksi.mixer.')->group(function (): void {
        Route::get('/', [RekapProduksiMixerController::class, 'index'])->name('index');
        Route::post('/download', [RekapProduksiMixerController::class, 'download'])->name('download');
        Route::post('/preview', [RekapProduksiMixerController::class, 'preview'])->name('preview');
        Route::post('/health', [RekapProduksiMixerController::class, 'health'])->name('health');
    });

    Route::prefix('rekap-produksi/gilingan')->name('rekap-produksi.gilingan.')->group(function (): void {
        Route::get('/', [RekapProduksiGilinganController::class, 'index'])->name('index');
        Route::post('/download', [RekapProduksiGilinganController::class, 'download'])->name('download');
        Route::post('/preview', [RekapProduksiGilinganController::class, 'preview'])->name('preview');
        Route::post('/health', [RekapProduksiGilinganController::class, 'health'])->name('health');
    });

    Route::prefix('rekap-produksi/crusher')->name('rekap-produksi.crusher.')->group(function (): void {
        Route::get('/', [RekapProduksiCrusherController::class, 'index'])->name('index');
        Route::post('/download', [RekapProduksiCrusherController::class, 'download'])->name('download');
        Route::post('/preview', [RekapProduksiCrusherController::class, 'preview'])->name('preview');
        Route::post('/health', [RekapProduksiCrusherController::class, 'health'])->name('health');
    });

    Route::prefix('semua-label')->name('semua-label.')->group(function (): void {
        Route::get('/', [SemuaLabelController::class, 'index'])->name('index');
        Route::post('/download', [SemuaLabelController::class, 'download'])->name('download');
        Route::post('/preview', [SemuaLabelController::class, 'preview'])->name('preview');
        Route::post('/health', [SemuaLabelController::class, 'health'])->name('health');
    });

    Route::prefix('bahan-baku/mutasi-bahan-baku')->name('bahan-baku.mutasi-bahan-baku.')->group(function (): void {
        Route::get('/', [MutasiBahanBakuController::class, 'index'])->name('index');
        Route::post('/download', [MutasiBahanBakuController::class, 'download'])->name('download');
        Route::post('/preview', [MutasiBahanBakuController::class, 'preview'])->name('preview');
        Route::post('/health', [MutasiBahanBakuController::class, 'health'])->name('health');
    });

    Route::prefix('barang-jadi/mutasi-barang-jadi')->name('barang-jadi.mutasi-barang-jadi.')->group(function (): void {
        Route::get('/', [MutasiBarangJadiPpsController::class, 'index'])->name('index');
        Route::post('/download', [MutasiBarangJadiPpsController::class, 'download'])->name('download');
        Route::post('/preview', [MutasiBarangJadiPpsController::class, 'preview'])->name('preview');
        Route::post('/health', [MutasiBarangJadiPpsController::class, 'health'])->name('health');
    });

    Route::prefix('broker/mutasi-broker')->name('broker.mutasi-broker.')->group(function (): void {
        Route::get('/', [MutasiBrokerController::class, 'index'])->name('index');
        Route::post('/download', [MutasiBrokerController::class, 'download'])->name('download');
        Route::post('/preview', [MutasiBrokerController::class, 'preview'])->name('preview');
        Route::post('/health', [MutasiBrokerController::class, 'health'])->name('health');
    });

    Route::prefix('bonggolan/mutasi-bonggolan')->name('bonggolan.mutasi-bonggolan.')->group(function (): void {
        Route::get('/', [MutasiBonggolanController::class, 'index'])->name('index');
        Route::post('/download', [MutasiBonggolanController::class, 'download'])->name('download');
        Route::post('/preview', [MutasiBonggolanController::class, 'preview'])->name('preview');
        Route::post('/health', [MutasiBonggolanController::class, 'health'])->name('health');
    });

    Route::prefix('crusher/mutasi-crusher')->name('crusher.mutasi-crusher.')->group(function (): void {
        Route::get('/', [MutasiCrusherController::class, 'index'])->name('index');
        Route::post('/download', [MutasiCrusherController::class, 'download'])->name('download');
        Route::post('/preview', [MutasiCrusherController::class, 'preview'])->name('preview');
        Route::post('/health', [MutasiCrusherController::class, 'health'])->name('health');
    });

    Route::prefix('gilingan/mutasi-gilingan')->name('gilingan.mutasi-gilingan.')->group(function (): void {
        Route::get('/', [MutasiGilinganController::class, 'index'])->name('index');
        Route::post('/download', [MutasiGilinganController::class, 'download'])->name('download');
        Route::post('/preview', [MutasiGilinganController::class, 'preview'])->name('preview');
        Route::post('/health', [MutasiGilinganController::class, 'health'])->name('health');
    });

    Route::prefix('mixer/mutasi-mixer')->name('mixer.mutasi-mixer.')->group(function (): void {
        Route::get('/', [MutasiMixerController::class, 'index'])->name('index');
        Route::post('/download', [MutasiMixerController::class, 'download'])->name('download');
        Route::post('/preview', [MutasiMixerController::class, 'preview'])->name('preview');
        Route::post('/health', [MutasiMixerController::class, 'health'])->name('health');
    });

    Route::prefix('furniture-wip/mutasi-furniture-wip')->name('furniture-wip.mutasi-furniture-wip.')->group(function (): void {
        Route::get('/', [MutasiFurnitureWipController::class, 'index'])->name('index');
        Route::post('/download', [MutasiFurnitureWipController::class, 'download'])->name('download');
        Route::post('/preview', [MutasiFurnitureWipController::class, 'preview'])->name('preview');
        Route::post('/health', [MutasiFurnitureWipController::class, 'health'])->name('health');
    });
});


// WPS Reports Routes
/**
 * Dashboard route group.
 */
Route::prefix('dashboard/sawn-timber')->name('dashboard.sawn-timber.')->group(function (): void {
    Route::get('/', [DashboardSawnTimberController::class, 'index'])->name('index');
    Route::get('/preview', [DashboardSawnTimberController::class, 'preview'])->name('preview');
    Route::match(['get', 'post'], '/download', [DashboardSawnTimberController::class, 'download'])->name('download');
});

Route::prefix('dashboard/barang-jadi')->name('dashboard.barang-jadi.')->group(function (): void {
    Route::get('/', [DashboardBarangJadiController::class, 'index'])->name('index');
    Route::get('/preview', [DashboardBarangJadiController::class, 'preview'])->name('preview');
    Route::match(['get', 'post'], '/download', [DashboardBarangJadiController::class, 'download'])->name('download');
});

Route::prefix('dashboard/cross-cut-akhir')->name('dashboard.cross-cut-akhir.')->group(function (): void {
    Route::get('/', [DashboardCrossCutAkhirController::class, 'index'])->name('index');
    Route::get('/preview', [DashboardCrossCutAkhirController::class, 'preview'])->name('preview');
    Route::match(['get', 'post'], '/download', [DashboardCrossCutAkhirController::class, 'download'])->name('download');
});

Route::prefix('dashboard/finger-joint')->name('dashboard.finger-joint.')->group(function (): void {
    Route::get('/', [DashboardFingerJointController::class, 'index'])->name('index');
    Route::get('/preview', [DashboardFingerJointController::class, 'preview'])->name('preview');
    Route::match(['get', 'post'], '/download', [DashboardFingerJointController::class, 'download'])->name('download');
});

Route::prefix('dashboard/laminating')->name('dashboard.laminating.')->group(function (): void {
    Route::get('/', [DashboardLaminatingController::class, 'index'])->name('index');
    Route::get('/preview', [DashboardLaminatingController::class, 'preview'])->name('preview');
    Route::match(['get', 'post'], '/download', [DashboardLaminatingController::class, 'download'])->name('download');
});

Route::prefix('dashboard/moulding')->name('dashboard.moulding.')->group(function (): void {
    Route::get('/', [DashboardMouldingController::class, 'index'])->name('index');
    Route::get('/preview', [DashboardMouldingController::class, 'preview'])->name('preview');
    Route::match(['get', 'post'], '/download', [DashboardMouldingController::class, 'download'])->name('download');
});

Route::prefix('dashboard/sanding')->name('dashboard.sanding.')->group(function (): void {
    Route::get('/', [DashboardSandingController::class, 'index'])->name('index');
    Route::get('/preview', [DashboardSandingController::class, 'preview'])->name('preview');
    Route::match(['get', 'post'], '/download', [DashboardSandingController::class, 'download'])->name('download');
});

Route::prefix('dashboard/s4s')->name('dashboard.s4s.')->group(function (): void {
    Route::get('/', [DashboardS4SController::class, 'index'])->name('index');
    Route::get('/preview', [DashboardS4SController::class, 'preview'])->name('preview');
    Route::match(['get', 'post'], '/download', [DashboardS4SController::class, 'download'])->name('download');
});

Route::prefix('dashboard/s4s-v2')->name('dashboard.s4s-v2.')->group(function (): void {
    Route::get('/', [DashboardS4SV2Controller::class, 'index'])->name('index');
    Route::get('/preview', [DashboardS4SV2Controller::class, 'preview'])->name('preview');
    Route::match(['get', 'post'], '/download', [DashboardS4SV2Controller::class, 'download'])->name('download');
});

/**
 * Sawn timber report route groups.
 */
Route::prefix('reports/sawn-timber')->name('reports.sawn-timber.')->group(function (): void {
    /** Stock ST Basah routes. */
    Route::prefix('stock-st-basah')->name('stock-st-basah.')->group(function (): void {
        Route::get('/', [StockSTBasahController::class, 'index'])->name('index');
        Route::post('/download', [StockSTBasahController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [StockSTBasahController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [StockSTBasahController::class, 'preview'])->name('preview');
    });

    /** Stock ST Kering routes. */
    Route::prefix('stock-st-kering')->name('stock-st-kering.')->group(function (): void {
        Route::get('/', [StockSTKeringController::class, 'index'])->name('index');
        Route::post('/download', [StockSTKeringController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [StockSTKeringController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [StockSTKeringController::class, 'preview'])->name('preview');
    });

    /** Penerimaan ST dari sawmill KG routes. */
    Route::prefix('penerimaan-st-dari-sawmill-kg')->name('penerimaan-st-dari-sawmill-kg.')->group(function (): void {
        Route::get('/', [PenerimaanStSawmillKgController::class, 'index'])->name('index');
        Route::post('/download', [PenerimaanStSawmillKgController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [PenerimaanStSawmillKgController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [PenerimaanStSawmillKgController::class, 'preview'])->name('preview');
    });

    /** Rekap hasil sawmill per-meja upah borongan V2 routes. */
    Route::prefix('rekap-hasil-sawmill-per-meja-upah-borongan-v2')->name('rekap-hasil-sawmill-per-meja-upah-borongan-v2.')->group(function (): void {
        Route::get('/', [RekapHasilSawmillPerMejaUpahBoronganV2Controller::class, 'index'])->name('index');
        Route::post('/download', [RekapHasilSawmillPerMejaUpahBoronganV2Controller::class, 'download'])->name('download');
        Route::post('/preview-pdf', [RekapHasilSawmillPerMejaUpahBoronganV2Controller::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [RekapHasilSawmillPerMejaUpahBoronganV2Controller::class, 'preview'])->name('preview');
        Route::post('/health', [RekapHasilSawmillPerMejaUpahBoronganV2Controller::class, 'health'])->name('health');
    });

    /** Lembar tally hasil sawmill routes. */
    Route::prefix('lembar-tally-hasil-sawmill')->name('lembar-tally-hasil-sawmill.')->group(function (): void {
        Route::get('/', [LembarTallyHasilSawmillController::class, 'index'])->name('index');
        Route::post('/download', [LembarTallyHasilSawmillController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [LembarTallyHasilSawmillController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [LembarTallyHasilSawmillController::class, 'preview'])->name('preview');
    });

    /** Umur sawn timber detail ton routes. */
    Route::prefix('umur-sawn-timber-detail-ton')->name('umur-sawn-timber-detail-ton.')->group(function (): void {
        Route::get('/', [UmurSawnTimberDetailTonController::class, 'index'])->name('index');
        Route::post('/download', [UmurSawnTimberDetailTonController::class, 'download'])->name('download');
        Route::post('/preview', [UmurSawnTimberDetailTonController::class, 'preview'])->name('preview');
    });

    /** ST sawmill masuk per-group routes. */
    Route::prefix('st-sawmill-masuk-per-group')->name('st-sawmill-masuk-per-group.')->group(function (): void {
        Route::get('/', [StSawmillMasukPerGroupController::class, 'index'])->name('index');
        Route::post('/download', [StSawmillMasukPerGroupController::class, 'download'])->name('download');
        Route::post('/preview', [StSawmillMasukPerGroupController::class, 'preview'])->name('preview');
    });
});

/**
 * Mutasi report route groups.
 */
Route::prefix('reports/mutasi')->name('reports.mutasi.')->group(function (): void {
    /** Mutasi barang jadi routes. */
    Route::prefix('barang-jadi')->name('barang-jadi.')->group(function (): void {
        Route::get('/', [MutasiBarangJadiController::class, 'index'])->name('index');
        Route::post('/download', [MutasiBarangJadiController::class, 'download'])->name('download');
        Route::post('/preview', [MutasiBarangJadiController::class, 'preview'])->name('preview');
    });

    /** Mutasi finger-joint routes. */
    Route::prefix('finger-joint')->name('finger-joint.')->group(function (): void {
        Route::get('/', [MutasiFingerJointController::class, 'index'])->name('index');
        Route::post('/download', [MutasiFingerJointController::class, 'download'])->name('download');
        Route::post('/preview', [MutasiFingerJointController::class, 'preview'])->name('preview');
    });

    /** Mutasi moulding routes. */
    Route::prefix('moulding')->name('moulding.')->group(function (): void {
        Route::get('/', [MutasiMouldingController::class, 'index'])->name('index');
        Route::post('/download', [MutasiMouldingController::class, 'download'])->name('download');
        Route::post('/preview', [MutasiMouldingController::class, 'preview'])->name('preview');
    });

    /** Mutasi laminating routes. */
    Route::prefix('laminating')->name('laminating.')->group(function (): void {
        Route::get('/', [MutasiLaminatingController::class, 'index'])->name('index');
        Route::post('/download', [MutasiLaminatingController::class, 'download'])->name('download');
        Route::post('/preview', [MutasiLaminatingController::class, 'preview'])->name('preview');
    });

    /** Mutasi sanding routes. */
    Route::prefix('sanding')->name('sanding.')->group(function (): void {
        Route::get('/', [MutasiSandingController::class, 'index'])->name('index');
        Route::post('/download', [MutasiSandingController::class, 'download'])->name('download');
        Route::post('/preview', [MutasiSandingController::class, 'preview'])->name('preview');
    });

    /** Mutasi s4s routes. */
    Route::prefix('s4s')->name('s4s.')->group(function (): void {
        Route::get('/', [MutasiS4SController::class, 'index'])->name('index');
        Route::post('/download', [MutasiS4SController::class, 'download'])->name('download');
        Route::post('/preview', [MutasiS4SController::class, 'preview'])->name('preview');
    });

    /** Mutasi ST routes. */
    Route::prefix('st')->name('st.')->group(function (): void {
        Route::get('/', [MutasiSTController::class, 'index'])->name('index');
        Route::post('/download', [MutasiSTController::class, 'download'])->name('download');
        Route::post('/preview', [MutasiSTController::class, 'preview'])->name('preview');
    });

    /** Mutasi CCA akhir routes. */
    Route::prefix('cca-akhir')->name('cca-akhir.')->group(function (): void {
        Route::get('/', [MutasiCCAkhirController::class, 'index'])->name('index');
        Route::post('/download', [MutasiCCAkhirController::class, 'download'])->name('download');
        Route::post('/preview', [MutasiCCAkhirController::class, 'preview'])->name('preview');
    });

    /** Mutasi reproses routes. */
    Route::prefix('reproses')->name('reproses.')->group(function (): void {
        Route::get('/', [MutasiReprosesController::class, 'index'])->name('index');
        Route::post('/download', [MutasiReprosesController::class, 'download'])->name('download');
        Route::post('/preview', [MutasiReprosesController::class, 'preview'])->name('preview');
    });

    /** Mutasi kayu bulat routes. */
    Route::prefix('kayu-bulat')->name('kayu-bulat.')->group(function (): void {
        Route::get('/', [MutasiKayuBulatController::class, 'index'])->name('index');
        Route::post('/download', [MutasiKayuBulatController::class, 'download'])->name('download');
        Route::post('/preview', [MutasiKayuBulatController::class, 'preview'])->name('preview');
    });

    /** Mutasi kayu bulat v2 routes. */
    Route::prefix('kayu-bulat-v2')->name('kayu-bulat-v2.')->group(function (): void {
        Route::get('/', [MutasiKayuBulatV2Controller::class, 'index'])->name('index');
        Route::post('/download', [MutasiKayuBulatV2Controller::class, 'download'])->name('download');
        Route::post('/preview', [MutasiKayuBulatV2Controller::class, 'preview'])->name('preview');
    });

    /** Mutasi kayu bulat kgv2 routes. */
    Route::prefix('kayu-bulat-kgv2')->name('kayu-bulat-kgv2.')->group(function (): void {
        Route::get('/', [MutasiKayuBulatKGV2Controller::class, 'index'])->name('index');
        Route::post('/download', [MutasiKayuBulatKGV2Controller::class, 'download'])->name('download');
        Route::post('/preview', [MutasiKayuBulatKGV2Controller::class, 'preview'])->name('preview');
    });

    /** Mutasi kayu bulat kg routes. */
    Route::prefix('kayu-bulat-kg')->name('kayu-bulat-kg.')->group(function (): void {
        Route::get('/', [MutasiKayuBulatKGController::class, 'index'])->name('index');
        Route::post('/download', [MutasiKayuBulatKGController::class, 'download'])->name('download');
        Route::post('/preview', [MutasiKayuBulatKGController::class, 'preview'])->name('preview');
    });
});

/**
 * Kayu bulat report route groups.
 */
Route::prefix('reports/kayu-bulat')->name('reports.kayu-bulat.')->group(function (): void {
    /** Saldo kayu bulat routes. */
    Route::prefix('saldo')->name('saldo.')->group(function (): void {
        Route::get('/', [SaldoKayuBulatController::class, 'index'])->name('index');
        Route::post('/download', [SaldoKayuBulatController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [SaldoKayuBulatController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [SaldoKayuBulatController::class, 'preview'])->name('preview');
    });

    /** Rekap pembelian kayu bulat routes. */
    Route::prefix('rekap-pembelian')->name('rekap-pembelian.')->group(function (): void {
        Route::get('/', [RekapPembelianKayuBulatController::class, 'index'])->name('index');
        Route::get('/preview', [RekapPembelianKayuBulatController::class, 'preview'])->name('preview');
        Route::match(['get', 'post'], '/download', [RekapPembelianKayuBulatController::class, 'download'])->name('download');
    });

    /** Rekap pembelian kayu bulat timbang KG routes. */
    Route::prefix('rekap-pembelian-kg')->name('rekap-pembelian-kg.')->group(function (): void {
        Route::get('/', [RekapPembelianKayuBulatKgController::class, 'index'])->name('index');
        Route::post('/download', [RekapPembelianKayuBulatKgController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [RekapPembelianKayuBulatKgController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [RekapPembelianKayuBulatKgController::class, 'preview'])->name('preview');
    });

    /** Target masuk bahan baku routes. */
    Route::prefix('target-masuk-bb')->name('target-masuk-bb.')->group(function (): void {
        Route::get('/', [TargetMasukBBController::class, 'index'])->name('index');
        Route::match(['get', 'post'], '/download', [TargetMasukBBController::class, 'download'])->name('download');
        Route::get('/preview', [TargetMasukBBController::class, 'preview'])->name('preview');
    });

    /** Target masuk bahan baku bulanan routes. */
    Route::prefix('target-masuk-bb-bulanan')->name('target-masuk-bb-bulanan.')->group(function (): void {
        Route::get('/', [TargetMasukBBBulananController::class, 'index'])->name('index');
        Route::match(['get', 'post'], '/download', [TargetMasukBBBulananController::class, 'download'])->name('download');
        Route::get('/preview', [TargetMasukBBBulananController::class, 'preview'])->name('preview');
    });

    /** Penerimaan bulanan per supplier routes. */
    Route::prefix('penerimaan-bulanan-per-supplier')->name('penerimaan-bulanan-per-supplier.')->group(function (): void {
        Route::get('/', [PenerimaanKayuBulatBulananPerSupplierController::class, 'index'])->name('index');
        Route::post('/download', [PenerimaanKayuBulatBulananPerSupplierController::class, 'download'])->name('download');
        Route::post('/preview', [PenerimaanKayuBulatBulananPerSupplierController::class, 'preview'])->name('preview');
    });

    /** Penerimaan bulanan per supplier grafik routes. */
    Route::prefix('penerimaan-bulanan-per-supplier-grafik')->name('penerimaan-bulanan-per-supplier-grafik.')->group(function (): void {
        Route::get('/', [PenerimaanKayuBulatPerSupplierBulananGrafikController::class, 'index'])->name('index');
        Route::post('/download', [PenerimaanKayuBulatPerSupplierBulananGrafikController::class, 'download'])->name('download');
        Route::post('/preview', [PenerimaanKayuBulatPerSupplierBulananGrafikController::class, 'preview'])->name('preview');
    });

    /** Penerimaan per supplier group routes. */
    Route::prefix('penerimaan-per-supplier-group')->name('penerimaan-per-supplier-group.')->group(function (): void {
        Route::get('/', [PenerimaanKayuBulatPerSupplierGroupController::class, 'index'])->name('index');
        Route::post('/download', [PenerimaanKayuBulatPerSupplierGroupController::class, 'download'])->name('download');
        Route::post('/preview', [PenerimaanKayuBulatPerSupplierGroupController::class, 'preview'])->name('preview');
    });

    /** Penerimaan per supplier timbang KG routes. */
    Route::prefix('penerimaan-per-supplier-kg')->name('penerimaan-per-supplier-kg.')->group(function (): void {
        Route::get('/', [PenerimaanKayuBulatPerSupplierKgController::class, 'index'])->name('index');
        Route::post('/download', [PenerimaanKayuBulatPerSupplierKgController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [PenerimaanKayuBulatPerSupplierKgController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [PenerimaanKayuBulatPerSupplierKgController::class, 'preview'])->name('preview');
    });

    /** Saldo hidup kayu bulat timbang KG routes. */
    Route::prefix('saldo-hidup-kg')->name('saldo-hidup-kg.')->group(function (): void {
        Route::get('/', [SaldoHidupKayuBulatKgController::class, 'index'])->name('index');
        Route::post('/download', [SaldoHidupKayuBulatKgController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [SaldoHidupKayuBulatKgController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [SaldoHidupKayuBulatKgController::class, 'preview'])->name('preview');
    });

    /** Stock opname kayu bulat routes. */
    Route::prefix('stock-opname')->name('stock-opname.')->group(function (): void {
        Route::get('/', [StockOpnameKayuBulatController::class, 'index'])->name('index');
        Route::post('/download', [StockOpnameKayuBulatController::class, 'download'])->name('download');
        Route::post('/preview', [StockOpnameKayuBulatController::class, 'preview'])->name('preview');
    });

    /** Hidup kayu bulat per group routes. */
    Route::prefix('hidup-per-group')->name('hidup-per-group.')->group(function (): void {
        Route::get('/', [HidupKBPerGroupController::class, 'index'])->name('index');
        Route::post('/download', [HidupKBPerGroupController::class, 'download'])->name('download');
        Route::post('/preview', [HidupKBPerGroupController::class, 'preview'])->name('preview');
    });

    /** Hidup kayu bulat routes. */
    Route::prefix('hidup')->name('hidup.')->group(function (): void {
        Route::get('/', [KayuBulatHidupController::class, 'index'])->name('index');
        Route::post('/download', [KayuBulatHidupController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [KayuBulatHidupController::class, 'previewPdf'])->name('preview-pdf');
        Route::get('/preview-pdf/{downloadName}', [KayuBulatHidupController::class, 'previewPdfLink'])->name('preview-pdf-link');
        Route::post('/preview', [KayuBulatHidupController::class, 'preview'])->name('preview');
    });

    /** Perbandingan KB masuk periode routes. */
    Route::prefix('perbandingan-kb-masuk-periode-1-dan-2')->name('perbandingan-kb-masuk-periode-1-dan-2.')->group(function (): void {
        Route::get('/', [PerbandinganKbMasukPeriode1Dan2Controller::class, 'index'])->name('index');
        Route::post('/download', [PerbandinganKbMasukPeriode1Dan2Controller::class, 'download'])->name('download');
        Route::post('/preview-pdf', [PerbandinganKbMasukPeriode1Dan2Controller::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [PerbandinganKbMasukPeriode1Dan2Controller::class, 'preview'])->name('preview');
    });

    /** KB khusus bangkang routes. */
    Route::prefix('kb-khusus-bangkang')->name('kb-khusus-bangkang.')->group(function (): void {
        Route::get('/', [KbKhususBangkangController::class, 'index'])->name('index');
        Route::post('/download', [KbKhususBangkangController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [KbKhususBangkangController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [KbKhususBangkangController::class, 'preview'])->name('preview');
    });

    /** Balok sudah semprot routes. */
    Route::prefix('balok-sudah-semprot')->name('balok-sudah-semprot.')->group(function (): void {
        Route::get('/', [BalokSudahSemprotController::class, 'index'])->name('index');
        Route::post('/download', [BalokSudahSemprotController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [BalokSudahSemprotController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [BalokSudahSemprotController::class, 'preview'])->name('preview');
    });

    /** Timeline kayu bulat harian routes. */
    Route::prefix('timeline-kayu-bulat-harian')->name('timeline-kayu-bulat-harian.')->group(function (): void {
        Route::get('/', [TimelineKayuBulatHarianController::class, 'index'])->name('index');
        Route::post('/download', [TimelineKayuBulatHarianController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [TimelineKayuBulatHarianController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [TimelineKayuBulatHarianController::class, 'preview'])->name('preview');
    });

    /** Timeline kayu bulat harian timbang KG routes. */
    Route::prefix('timeline-kayu-bulat-harian-kg')->name('timeline-kayu-bulat-harian-kg.')->group(function (): void {
        Route::get('/', [TimelineKayuBulatHarianKgController::class, 'index'])->name('index');
        Route::post('/download', [TimelineKayuBulatHarianKgController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [TimelineKayuBulatHarianKgController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [TimelineKayuBulatHarianKgController::class, 'preview'])->name('preview');
    });

    /** Timeline kayu bulat bulanan routes. */
    Route::prefix('timeline-kayu-bulat-bulanan')->name('timeline-kayu-bulat-bulanan.')->group(function (): void {
        Route::get('/', [TimelineKayuBulatBulananController::class, 'index'])->name('index');
        Route::post('/download', [TimelineKayuBulatBulananController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [TimelineKayuBulatBulananController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [TimelineKayuBulatBulananController::class, 'preview'])->name('preview');
    });

    /** Timeline kayu bulat bulanan timbang KG routes. */
    Route::prefix('timeline-kayu-bulat-bulanan-kg')->name('timeline-kayu-bulat-bulanan-kg.')->group(function (): void {
        Route::get('/', [TimelineKayuBulatBulananKgController::class, 'index'])->name('index');
        Route::post('/download', [TimelineKayuBulatBulananKgController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [TimelineKayuBulatBulananKgController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [TimelineKayuBulatBulananKgController::class, 'preview'])->name('preview');
    });

    /** Umur kayu bulat non-rambung routes. */
    Route::prefix('umur-kayu-bulat-non-rambung')->name('umur-kayu-bulat-non-rambung.')->group(function (): void {
        Route::get('/', [UmurKayuBulatNonRambungController::class, 'index'])->name('index');
        Route::post('/download', [UmurKayuBulatNonRambungController::class, 'download'])->name('download');
        Route::post('/preview', [UmurKayuBulatNonRambungController::class, 'preview'])->name('preview');
    });

    /** Umur kayu bulat rambung routes. */
    Route::prefix('umur-kayu-bulat-rambung')->name('umur-kayu-bulat-rambung.')->group(function (): void {
        Route::get('/', [UmurKayuBulatRambungController::class, 'index'])->name('index');
        Route::post('/download', [UmurKayuBulatRambungController::class, 'download'])->name('download');
        Route::post('/preview', [UmurKayuBulatRambungController::class, 'preview'])->name('preview');
    });

    /** Supplier Intel routes. */
    Route::prefix('supplier-intel')->name('supplier-intel.')->group(function (): void {
        Route::get('/', [SupplierIntelController::class, 'index'])->name('index');
        Route::post('/download', [SupplierIntelController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [SupplierIntelController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [SupplierIntelController::class, 'preview'])->name('preview');
    });
});

/**
 * Standalone report route groups.
 */
Route::prefix('reports')->name('reports.')->group(function (): void {
    /** Stock racip kayu lat routes. */
    Route::prefix('stock-racip-kayu-lat')->name('stock-racip-kayu-lat.')->group(function (): void {
        Route::get('/', [StockRacipKayuLatController::class, 'index'])->name('index');
        Route::match(['get', 'post'], '/download', [StockRacipKayuLatController::class, 'download'])->name('download');
        Route::get('/preview', [StockRacipKayuLatController::class, 'preview'])->name('preview');
    });

    /** Hasil output racip harian routes. */
    Route::prefix('hasil-output-racip-harian')->name('hasil-output-racip-harian.')->group(function (): void {
        Route::get('/', [HasilOutputRacipHarianController::class, 'index'])->name('index');
        Route::post('/download', [HasilOutputRacipHarianController::class, 'download'])->name('download');
        Route::post('/preview', [HasilOutputRacipHarianController::class, 'preview'])->name('preview');
    });

    /** Rangkuman label input routes. */
    Route::prefix('rangkuman-label-input')->name('rangkuman-label-input.')->group(function (): void {
        Route::get('/', [RangkumanJlhLabelInputController::class, 'index'])->name('index');
        Route::post('/download', [RangkumanJlhLabelInputController::class, 'download'])->name('download');
        Route::post('/preview', [RangkumanJlhLabelInputController::class, 'preview'])->name('preview');
    });

    /** Mutasi hasil racip routes. */
    Route::prefix('mutasi-hasil-racip')->name('mutasi-hasil-racip.')->group(function (): void {
        Route::get('/', [MutasiHasilRacipController::class, 'index'])->name('index');
        Route::post('/download', [MutasiHasilRacipController::class, 'download'])->name('download');
        Route::post('/preview', [MutasiHasilRacipController::class, 'preview'])->name('preview');
    });

    /** Mutasi racip detail routes. */
    Route::prefix('mutasi-racip-detail')->name('mutasi-racip-detail.')->group(function (): void {
        Route::get('/', [MutasiRacipDetailController::class, 'index'])->name('index');
        Route::post('/download', [MutasiRacipDetailController::class, 'download'])->name('download');
        Route::post('/preview', [MutasiRacipDetailController::class, 'preview'])->name('preview');
    });

    /** Label nyangkut routes. */
    Route::prefix('label-nyangkut')->name('label-nyangkut.')->group(function (): void {
        Route::get('/', [LabelNyangkutController::class, 'index'])->name('index');
        Route::post('/download', [LabelNyangkutController::class, 'download'])->name('download');
        Route::post('/preview', [LabelNyangkutController::class, 'preview'])->name('preview');
    });

    /** Bahan terpakai routes. */
    Route::prefix('bahan-terpakai')->name('bahan-terpakai.')->group(function (): void {
        Route::get('/', [BahanTerpakaiController::class, 'index'])->name('index');
        Route::post('/download', [BahanTerpakaiController::class, 'download'])->name('download');
        Route::post('/preview', [BahanTerpakaiController::class, 'preview'])->name('preview');
    });
});

/**
 * Authentication route group for web UI.
 */
Route::middleware('guest')->post('/login', [WebAuthController::class, 'login'])->name('web.login');
Route::middleware('auth')->post('/logout', [WebAuthController::class, 'logout'])->name('web.logout');

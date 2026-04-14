<?php

use App\Http\Controllers\BahanTerpakaiController;
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
use App\Http\Controllers\DashboardSawnTimberController;
use App\Http\Controllers\HidupKBPerGroupController;
use App\Http\Controllers\HasilOutputRacipHarianController;
use App\Http\Controllers\KbKhususBangkangController;
use App\Http\Controllers\KayuBulatHidupController;
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
use App\Http\Controllers\MutasiRacipDetailController;
use App\Http\Controllers\MutasiSTController;
use App\Http\Controllers\PenerimaanKayuBulatBulananPerSupplierController;
use App\Http\Controllers\PenerimaanKayuBulatPerSupplierBulananGrafikController;
use App\Http\Controllers\PenerimaanKayuBulatPerSupplierKgController;
use App\Http\Controllers\PenerimaanKayuBulatPerSupplierGroupController;
use App\Http\Controllers\PenerimaanStSawmillKgController;
use App\Http\Controllers\PerbandinganKbMasukPeriode1Dan2Controller;
use App\Http\Controllers\PerbandinganKbMasukPeriode1Dan2KgController;
use App\Http\Controllers\PemakaianObatVacuumController;
use App\Http\Controllers\RekapHasilSawmillPerMejaUpahBoronganV2Controller;
use App\Http\Controllers\RekapHasilSawmillPerMejaUpahBoronganController;
use App\Http\Controllers\RekapHasilSawmillPerMejaController;
use App\Http\Controllers\RekapProduktivitasSawmillSawnTimberController;
use App\Http\Controllers\StSawmillHariTebalLebarController;
use App\Http\Controllers\RekapPembelianKayuBulatKgController;
use App\Http\Controllers\RekapPenerimaanSTDariSawmillKgController;
use App\Http\Controllers\RekapPenerimaanSTDariSawmillNonRambungController;
use App\Http\Controllers\RekapRendemenNonRambungController;
use App\Http\Controllers\RekapRendemenRambungController;
use App\Http\Controllers\RekapPenjualanPerProdukController;
use App\Http\Controllers\RekapPenjualanEksporPerProdukPerBuyerController;
use App\Http\Controllers\RekapPenjualanEksporPerBuyerPerProdukController;
use App\Http\Controllers\TimelineRekapPenjualanPerProdukController;
use App\Http\Controllers\PenjualanLokalController;
use App\Http\Controllers\KoordinatTanahController;
use App\Http\Controllers\ProduksiPerSpkController;
use App\Http\Controllers\RendemenSemuaProsesController;
use App\Http\Controllers\RekapProduktivitasSawmillRpController;
use App\Http\Controllers\MutasiS4SController;
use App\Http\Controllers\SaldoKayuBulatController;
use App\Http\Controllers\SaldoHidupKayuBulatKgController;
use App\Http\Controllers\StockSTBasahController;
use App\Http\Controllers\StockHidupPerNoSpkController;
use App\Http\Controllers\StockHidupPerNoSpkDiscrepancyController;
use App\Http\Controllers\DiscrepancyRekapMutasiController;
use App\Http\Controllers\RekapMutasiController;
use App\Http\Controllers\RekapMutasiCrossTabController;
use App\Http\Controllers\FlowProduksiPerPeriodeController;
use App\Http\Controllers\DashboardRuController;
use App\Http\Controllers\HasilProduksiMesinLemburDanNonLemburController;
use App\Http\Controllers\LabelPerhariController;
use App\Http\Controllers\RekapStockOnHandController;
use App\Http\Controllers\RangkumanBongkarSusunController;
use App\Http\Controllers\KapasitasRacipKayuBulatHidupController;
use App\Http\Controllers\BahanYangDihasilkanController;
use App\Http\Controllers\ProduksiSemuaMesinController;
use App\Http\Controllers\ProduksiHuluHilirController;
use App\Http\Controllers\TargetMasukBBController;
use App\Http\Controllers\TargetMasukBBBulananController;
use App\Http\Controllers\TimelineKayuBulatHarianController;
use App\Http\Controllers\TimelineKayuBulatBulananController;
use App\Http\Controllers\TimelineKayuBulatHarianKgController;
use App\Http\Controllers\TimelineKayuBulatBulananKgController;
use App\Http\Controllers\UmurKayuBulatNonRambungController;
use App\Http\Controllers\UmurKayuBulatRambungController;
use App\Http\Controllers\UmurS4SDetailController;
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
use App\Http\Controllers\FingerJointHidupDetailController;
use App\Http\Controllers\RekapProduksiFingerJointPerJenisPerGradeController;
use App\Http\Controllers\RekapProduksiS4SConsolidatedController;
use App\Http\Controllers\S4SHidupDetailController;
use App\Http\Controllers\LabelS4SHidupPerJenisKayuController;
use App\Http\Controllers\LabelS4SHidupPerProdukPerJenisKayuController;
use App\Http\Controllers\RekapProduksiS4SPerJenisPerGradeController;
use App\Http\Controllers\UmurSawnTimberDetailTonController;
use App\Http\Controllers\StSawmillMasukPerGroupController;
use App\Http\Controllers\StSawmillMasukPerGroupMejaController;
use App\Http\Controllers\StockRacipKayuLatController;
use App\Http\Controllers\StockOpnameKayuBulatController;
use App\Http\Controllers\LabelNyangkutController;
use App\Http\Controllers\LembarTallyHasilSawmillController;
use App\Http\Controllers\RangkumanJlhLabelInputController;
use App\Http\Controllers\RekapPembelianKayuBulatController;
use App\Http\Controllers\StockSTKeringController;
use App\Http\Controllers\StBasahHidupPerUmurKayuTonController;
use App\Http\Controllers\KdKeluarMasukController;
use App\Http\Controllers\RekapKamarKdController;
use App\Http\Controllers\MutasiKdController;
use App\Http\Controllers\RekapStPenjualanController;
use App\Http\Controllers\PembelianStPerSupplierTonController;
use App\Http\Controllers\PembelianStTimelineTonController;
use App\Http\Controllers\LabelStHidupDetailController;
use App\Http\Controllers\KetahananBarangDagangStController;
use App\Http\Controllers\KetahananBarangDagangS4sController;
use App\Http\Controllers\KetahananBarangDagangFingerJointController;
use App\Http\Controllers\OutputProduksiS4sPerGradeController;
use App\Http\Controllers\GradeAbcHarianController;
use App\Http\Controllers\RekapProduksiS4sRambungPerGradeController;
use App\Http\Controllers\RekapProduksiFingerJointConsolidatedController;
use App\Http\Controllers\StRambungMc1Mc2DetailController;
use App\Http\Controllers\StRambungMc1Mc2RangkumanController;
use App\Http\Controllers\SupplierIntelController;
use App\Http\Controllers\WebAuthController;
use App\Http\Controllers\SaldoStHidupPerProdukController;
use App\Http\Controllers\StHidupPerSpkController;
use App\Http\Controllers\StHidupKeringController;
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
use App\Http\Controllers\PPS\HasilProduksiHarianWashingProduksiController;
use App\Http\Controllers\PPS\HasilProduksiHarianBrokerProduksiController;
use App\Http\Controllers\PPS\HasilProduksiHarianCrusherProduksiController;
use App\Http\Controllers\PPS\HasilProduksiHarianGilinganProduksiController;
use App\Http\Controllers\PPS\HasilProduksiHarianMixerProduksiController;
use App\Http\Controllers\PPS\HasilProduksiHarianPackingProduksiController;
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

    Route::prefix('washing/washing-produksi')->name('washing.washing-produksi.')->group(function (): void {
        Route::get('/', [HasilProduksiHarianWashingProduksiController::class, 'index'])->name('index');
        Route::post('/download', [HasilProduksiHarianWashingProduksiController::class, 'download'])->name('download');
        Route::post('/preview', [HasilProduksiHarianWashingProduksiController::class, 'preview'])->name('preview');
        Route::post('/health', [HasilProduksiHarianWashingProduksiController::class, 'health'])->name('health');
    });

    Route::prefix('broker/broker-produksi')->name('broker.broker-produksi.')->group(function (): void {
        Route::get('/', [HasilProduksiHarianBrokerProduksiController::class, 'index'])->name('index');
        Route::post('/download', [HasilProduksiHarianBrokerProduksiController::class, 'download'])->name('download');
        Route::post('/preview', [HasilProduksiHarianBrokerProduksiController::class, 'preview'])->name('preview');
        Route::post('/health', [HasilProduksiHarianBrokerProduksiController::class, 'health'])->name('health');
    });

    Route::prefix('crusher/crusher-produksi')->name('crusher.crusher-produksi.')->group(function (): void {
        Route::get('/', [HasilProduksiHarianCrusherProduksiController::class, 'index'])->name('index');
        Route::post('/download', [HasilProduksiHarianCrusherProduksiController::class, 'download'])->name('download');
        Route::post('/preview', [HasilProduksiHarianCrusherProduksiController::class, 'preview'])->name('preview');
        Route::post('/health', [HasilProduksiHarianCrusherProduksiController::class, 'health'])->name('health');
    });

    Route::prefix('gilingan/gilingan-produksi')->name('gilingan.gilingan-produksi.')->group(function (): void {
        Route::get('/', [HasilProduksiHarianGilinganProduksiController::class, 'index'])->name('index');
        Route::post('/download', [HasilProduksiHarianGilinganProduksiController::class, 'download'])->name('download');
        Route::post('/preview', [HasilProduksiHarianGilinganProduksiController::class, 'preview'])->name('preview');
        Route::post('/health', [HasilProduksiHarianGilinganProduksiController::class, 'health'])->name('health');
    });

    Route::prefix('mixer/mixer-produksi')->name('mixer.mixer-produksi.')->group(function (): void {
        Route::get('/', [HasilProduksiHarianMixerProduksiController::class, 'index'])->name('index');
        Route::post('/download', [HasilProduksiHarianMixerProduksiController::class, 'download'])->name('download');
        Route::post('/preview', [HasilProduksiHarianMixerProduksiController::class, 'preview'])->name('preview');
        Route::post('/health', [HasilProduksiHarianMixerProduksiController::class, 'health'])->name('health');
    });

    Route::prefix('inject/packing/packing-produksi')->name('inject.packing.packing-produksi.')->group(function (): void {
        Route::get('/', [HasilProduksiHarianPackingProduksiController::class, 'index'])->name('index');
        Route::post('/download', [HasilProduksiHarianPackingProduksiController::class, 'download'])->name('download');
        Route::post('/preview', [HasilProduksiHarianPackingProduksiController::class, 'preview'])->name('preview');
        Route::post('/health', [HasilProduksiHarianPackingProduksiController::class, 'health'])->name('health');
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

Route::prefix('dashboard/reproses')->name('dashboard.reproses.')->group(function (): void {
    Route::get('/', [DashboardReprosesController::class, 'index'])->name('index');
    Route::get('/preview', [DashboardReprosesController::class, 'preview'])->name('preview');
    Route::match(['get', 'post'], '/download', [DashboardReprosesController::class, 'download'])->name('download');
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

    /** ST basah hidup per-umur kayu (Ton) routes. */
    Route::prefix('st-basah-hidup-per-umur-kayu-ton')->name('st-basah-hidup-per-umur-kayu-ton.')->group(function (): void {
        Route::get('/', [StBasahHidupPerUmurKayuTonController::class, 'index'])->name('index');
        Route::post('/download', [StBasahHidupPerUmurKayuTonController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [StBasahHidupPerUmurKayuTonController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [StBasahHidupPerUmurKayuTonController::class, 'preview'])->name('preview');
        Route::post('/health', [StBasahHidupPerUmurKayuTonController::class, 'health'])->name('health');
    });

    /** KD keluar - masuk routes. */
    Route::prefix('kd-keluar-masuk')->name('kd-keluar-masuk.')->group(function (): void {
        Route::get('/', [KdKeluarMasukController::class, 'index'])->name('index');
        Route::post('/download', [KdKeluarMasukController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [KdKeluarMasukController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [KdKeluarMasukController::class, 'preview'])->name('preview');
        Route::post('/health', [KdKeluarMasukController::class, 'health'])->name('health');
    });

    /** Rekap kamar KD routes. */
    Route::prefix('rekap-kamar-kd')->name('rekap-kamar-kd.')->group(function (): void {
        Route::get('/', [RekapKamarKdController::class, 'index'])->name('index');
        Route::post('/download', [RekapKamarKdController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [RekapKamarKdController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [RekapKamarKdController::class, 'preview'])->name('preview');
        Route::post('/health', [RekapKamarKdController::class, 'health'])->name('health');
    });

    /** Mutasi KD routes. */
    Route::prefix('mutasi-kd')->name('mutasi-kd.')->group(function (): void {
        Route::get('/', [MutasiKdController::class, 'index'])->name('index');
        Route::post('/download', [MutasiKdController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [MutasiKdController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [MutasiKdController::class, 'preview'])->name('preview');
        Route::post('/health', [MutasiKdController::class, 'health'])->name('health');
    });

    /** Rekap ST Penjualan routes. */
    Route::prefix('rekap-st-penjualan')->name('rekap-st-penjualan.')->group(function (): void {
        Route::get('/', [RekapStPenjualanController::class, 'index'])->name('index');
        Route::post('/download', [RekapStPenjualanController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [RekapStPenjualanController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [RekapStPenjualanController::class, 'preview'])->name('preview');
        Route::post('/health', [RekapStPenjualanController::class, 'health'])->name('health');
    });

    /** Pembelian ST per supplier (Ton) routes. */
    Route::prefix('pembelian-st-per-supplier-ton')->name('pembelian-st-per-supplier-ton.')->group(function (): void {
        Route::get('/', [PembelianStPerSupplierTonController::class, 'index'])->name('index');
        Route::post('/download', [PembelianStPerSupplierTonController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [PembelianStPerSupplierTonController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [PembelianStPerSupplierTonController::class, 'preview'])->name('preview');
        Route::post('/health', [PembelianStPerSupplierTonController::class, 'health'])->name('health');
    });

    /** Pembelian ST time line (Ton) routes. */
    Route::prefix('pembelian-st-timeline-ton')->name('pembelian-st-timeline-ton.')->group(function (): void {
        Route::get('/', [PembelianStTimelineTonController::class, 'index'])->name('index');
        Route::post('/download', [PembelianStTimelineTonController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [PembelianStTimelineTonController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [PembelianStTimelineTonController::class, 'preview'])->name('preview');
        Route::post('/health', [PembelianStTimelineTonController::class, 'health'])->name('health');
    });

    /** Label ST (Hidup) Detail routes. */
    Route::prefix('label-st-hidup-detail')->name('label-st-hidup-detail.')->group(function (): void {
        Route::get('/', [LabelStHidupDetailController::class, 'index'])->name('index');
        Route::post('/download', [LabelStHidupDetailController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [LabelStHidupDetailController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [LabelStHidupDetailController::class, 'preview'])->name('preview');
        Route::post('/health', [LabelStHidupDetailController::class, 'health'])->name('health');
    });

    /** Ketahanan barang dagang ST routes. */
    Route::prefix('ketahanan-barang-st')->name('ketahanan-barang-st.')->group(function (): void {
        Route::get('/', [KetahananBarangDagangStController::class, 'index'])->name('index');
        Route::post('/download', [KetahananBarangDagangStController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [KetahananBarangDagangStController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [KetahananBarangDagangStController::class, 'preview'])->name('preview');
        Route::post('/health', [KetahananBarangDagangStController::class, 'health'])->name('health');
    });

    /** ST Rambung MC1 dan MC2 (Detail) routes. */
    Route::prefix('st-rambung-mc1-mc2-detail')->name('st-rambung-mc1-mc2-detail.')->group(function (): void {
        Route::get('/', [StRambungMc1Mc2DetailController::class, 'index'])->name('index');
        Route::post('/download', [StRambungMc1Mc2DetailController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [StRambungMc1Mc2DetailController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [StRambungMc1Mc2DetailController::class, 'preview'])->name('preview');
        Route::post('/health', [StRambungMc1Mc2DetailController::class, 'health'])->name('health');
    });

    /** ST Rambung MC1 dan MC2 (Rangkuman) routes. */
    Route::prefix('st-rambung-mc1-mc2-rangkuman')->name('st-rambung-mc1-mc2-rangkuman.')->group(function (): void {
        Route::get('/', [StRambungMc1Mc2RangkumanController::class, 'index'])->name('index');
        Route::post('/download', [StRambungMc1Mc2RangkumanController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [StRambungMc1Mc2RangkumanController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [StRambungMc1Mc2RangkumanController::class, 'preview'])->name('preview');
        Route::post('/health', [StRambungMc1Mc2RangkumanController::class, 'health'])->name('health');
    });

    /** Penerimaan ST dari sawmill KG routes. */
    Route::prefix('penerimaan-st-dari-sawmill-kg')->name('penerimaan-st-dari-sawmill-kg.')->group(function (): void {
        Route::get('/', [PenerimaanStSawmillKgController::class, 'index'])->name('index');
        Route::post('/download', [PenerimaanStSawmillKgController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [PenerimaanStSawmillKgController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [PenerimaanStSawmillKgController::class, 'preview'])->name('preview');
    });

    /** Rekap penerimaan ST dari sawmill (Non Rambung) routes. */
    Route::prefix('rekap-penerimaan-st-dari-sawmill-non-rambung')->name('rekap-penerimaan-st-dari-sawmill-non-rambung.')->group(function (): void {
        Route::get('/', [RekapPenerimaanSTDariSawmillNonRambungController::class, 'index'])->name('index');
        Route::post('/download', [RekapPenerimaanSTDariSawmillNonRambungController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [RekapPenerimaanSTDariSawmillNonRambungController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [RekapPenerimaanSTDariSawmillNonRambungController::class, 'preview'])->name('preview');
        Route::post('/health', [RekapPenerimaanSTDariSawmillNonRambungController::class, 'health'])->name('health');
    });

    /** Rekap hasil sawmill per-meja upah borongan V2 routes. */
    Route::prefix('rekap-hasil-sawmill-per-meja-upah-borongan-v2')->name('rekap-hasil-sawmill-per-meja-upah-borongan-v2.')->group(function (): void {
        Route::get('/', [RekapHasilSawmillPerMejaUpahBoronganV2Controller::class, 'index'])->name('index');
        Route::post('/download', [RekapHasilSawmillPerMejaUpahBoronganV2Controller::class, 'download'])->name('download');
        Route::post('/preview-pdf', [RekapHasilSawmillPerMejaUpahBoronganV2Controller::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [RekapHasilSawmillPerMejaUpahBoronganV2Controller::class, 'preview'])->name('preview');
        Route::post('/health', [RekapHasilSawmillPerMejaUpahBoronganV2Controller::class, 'health'])->name('health');
    });

    /** Rekap hasil sawmill per-meja (upah borongan) routes. */
    Route::prefix('rekap-hasil-sawmill-per-meja-upah-borongan')->name('rekap-hasil-sawmill-per-meja-upah-borongan.')->group(function (): void {
        Route::get('/', [RekapHasilSawmillPerMejaUpahBoronganController::class, 'index'])->name('index');
        Route::post('/download', [RekapHasilSawmillPerMejaUpahBoronganController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [RekapHasilSawmillPerMejaUpahBoronganController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [RekapHasilSawmillPerMejaUpahBoronganController::class, 'preview'])->name('preview');
        Route::post('/health', [RekapHasilSawmillPerMejaUpahBoronganController::class, 'health'])->name('health');
    });

    /** Rekap hasil sawmill per-meja routes. */
    Route::prefix('rekap-hasil-sawmill-per-meja')->name('rekap-hasil-sawmill-per-meja.')->group(function (): void {
        Route::get('/', [RekapHasilSawmillPerMejaController::class, 'index'])->name('index');
        Route::post('/download', [RekapHasilSawmillPerMejaController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [RekapHasilSawmillPerMejaController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [RekapHasilSawmillPerMejaController::class, 'preview'])->name('preview');
        Route::post('/health', [RekapHasilSawmillPerMejaController::class, 'health'])->name('health');
    });

    /** Rekap produktivitas sawmill routes. */
    Route::prefix('rekap-produktivitas-sawmill')->name('rekap-produktivitas-sawmill.')->group(function (): void {
        Route::get('/', [RekapProduktivitasSawmillSawnTimberController::class, 'index'])->name('index');
        Route::post('/download', [RekapProduktivitasSawmillSawnTimberController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [RekapProduktivitasSawmillSawnTimberController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [RekapProduktivitasSawmillSawnTimberController::class, 'preview'])->name('preview');
        Route::post('/health', [RekapProduktivitasSawmillSawnTimberController::class, 'health'])->name('health');
    });

    /** Pemakaian obat vacuum routes. */
    Route::prefix('pemakaian-obat-vacuum')->name('pemakaian-obat-vacuum.')->group(function (): void {
        Route::get('/', [PemakaianObatVacuumController::class, 'index'])->name('index');
        Route::post('/download', [PemakaianObatVacuumController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [PemakaianObatVacuumController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [PemakaianObatVacuumController::class, 'preview'])->name('preview');
        Route::post('/health', [PemakaianObatVacuumController::class, 'health'])->name('health');
    });

    /** ST sawmill per hari/tebal/lebar routes. */
    Route::prefix('st-sawmill-hari-tebal-lebar')->name('st-sawmill-hari-tebal-lebar.')->group(function (): void {
        Route::get('/', [StSawmillHariTebalLebarController::class, 'index'])->name('index');
        Route::post('/download', [StSawmillHariTebalLebarController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [StSawmillHariTebalLebarController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [StSawmillHariTebalLebarController::class, 'preview'])->name('preview');
        Route::post('/health', [StSawmillHariTebalLebarController::class, 'health'])->name('health');
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

    /** ST (sawmill) masuk per-group (pivot meja) routes. */
    Route::prefix('st-sawmill-masuk-per-group-meja')->name('st-sawmill-masuk-per-group-meja.')->group(function (): void {
        Route::get('/', [StSawmillMasukPerGroupMejaController::class, 'index'])->name('index');
        Route::post('/download', [StSawmillMasukPerGroupMejaController::class, 'download'])->name('download');
        Route::post('/preview', [StSawmillMasukPerGroupMejaController::class, 'preview'])->name('preview');
        Route::post('/health', [StSawmillMasukPerGroupMejaController::class, 'health'])->name('health');
    });

    /** Saldo ST hidup per produk routes. */
    Route::prefix('saldo-st-hidup-per-produk')->name('saldo-st-hidup-per-produk.')->group(function (): void {
        Route::get('/', [SaldoStHidupPerProdukController::class, 'index'])->name('index');
        Route::post('/download', [SaldoStHidupPerProdukController::class, 'download'])->name('download');
        Route::post('/preview', [SaldoStHidupPerProdukController::class, 'preview'])->name('preview');
        Route::post('/health', [SaldoStHidupPerProdukController::class, 'health'])->name('health');
    });

    /** ST hidup per SPK routes. */
    Route::prefix('st-hidup-per-spk')->name('st-hidup-per-spk.')->group(function (): void {
        Route::get('/', [StHidupPerSpkController::class, 'index'])->name('index');
        Route::post('/download', [StHidupPerSpkController::class, 'download'])->name('download');
        Route::post('/preview', [StHidupPerSpkController::class, 'preview'])->name('preview');
        Route::post('/health', [StHidupPerSpkController::class, 'health'])->name('health');
    });

    /** ST hidup kering routes. */
    Route::prefix('st-hidup-kering')->name('st-hidup-kering.')->group(function (): void {
        Route::get('/', [StHidupKeringController::class, 'index'])->name('index');
        Route::post('/download', [StHidupKeringController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [StHidupKeringController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [StHidupKeringController::class, 'preview'])->name('preview');
        Route::post('/health', [StHidupKeringController::class, 'health'])->name('health');
    });
});

Route::prefix('reports/rendemen-kayu')->name('reports.rendemen-kayu.')->group(function (): void {
    Route::prefix('rekap-rendemen-non-rambung')->name('rekap-rendemen-non-rambung.')->group(function (): void {
        Route::get('/', [RekapRendemenNonRambungController::class, 'index'])->name('index');
        Route::post('/download', [RekapRendemenNonRambungController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [RekapRendemenNonRambungController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [RekapRendemenNonRambungController::class, 'preview'])->name('preview');
        Route::post('/health', [RekapRendemenNonRambungController::class, 'health'])->name('health');
    });

    Route::prefix('rekap-rendemen-rambung')->name('rekap-rendemen-rambung.')->group(function (): void {
        Route::get('/', [RekapRendemenRambungController::class, 'index'])->name('index');
        Route::post('/download', [RekapRendemenRambungController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [RekapRendemenRambungController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [RekapRendemenRambungController::class, 'preview'])->name('preview');
        Route::post('/health', [RekapRendemenRambungController::class, 'health'])->name('health');
    });

    Route::prefix('rendemen-semua-proses')->name('rendemen-semua-proses.')->group(function (): void {
        Route::get('/', [RendemenSemuaProsesController::class, 'index'])->name('index');
        Route::post('/download', [RendemenSemuaProsesController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [RendemenSemuaProsesController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [RendemenSemuaProsesController::class, 'preview'])->name('preview');
        Route::post('/health', [RendemenSemuaProsesController::class, 'health'])->name('health');
    });

    Route::prefix('produksi-per-spk')->name('produksi-per-spk.')->group(function (): void {
        Route::get('/', [ProduksiPerSpkController::class, 'index'])->name('index');
        Route::post('/download', [ProduksiPerSpkController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [ProduksiPerSpkController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [ProduksiPerSpkController::class, 'preview'])->name('preview');
        Route::post('/health', [ProduksiPerSpkController::class, 'health'])->name('health');
    });
});

Route::prefix('reports/penjualan-kayu')->name('reports.penjualan-kayu.')->group(function (): void {
    Route::prefix('penjualan-lokal')->name('penjualan-lokal.')->group(function (): void {
        Route::get('/', [PenjualanLokalController::class, 'index'])->name('index');
        Route::post('/download', [PenjualanLokalController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [PenjualanLokalController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [PenjualanLokalController::class, 'preview'])->name('preview');
        Route::post('/health', [PenjualanLokalController::class, 'health'])->name('health');
    });

    Route::prefix('koordinat-tanah')->name('koordinat-tanah.')->group(function (): void {
        Route::get('/', [KoordinatTanahController::class, 'index'])->name('index');
        Route::post('/download', [KoordinatTanahController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [KoordinatTanahController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [KoordinatTanahController::class, 'preview'])->name('preview');
        Route::post('/health', [KoordinatTanahController::class, 'health'])->name('health');
    });

    Route::prefix('rekap-penjualan-per-produk')->name('rekap-penjualan-per-produk.')->group(function (): void {
        Route::get('/', [RekapPenjualanPerProdukController::class, 'index'])->name('index');
        Route::post('/download', [RekapPenjualanPerProdukController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [RekapPenjualanPerProdukController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [RekapPenjualanPerProdukController::class, 'preview'])->name('preview');
        Route::post('/health', [RekapPenjualanPerProdukController::class, 'health'])->name('health');
    });

    Route::prefix('rekap-penjualan-ekspor-per-produk-per-buyer')->name('rekap-penjualan-ekspor-per-produk-per-buyer.')->group(function (): void {
        Route::get('/', [RekapPenjualanEksporPerProdukPerBuyerController::class, 'index'])->name('index');
        Route::post('/download', [RekapPenjualanEksporPerProdukPerBuyerController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [RekapPenjualanEksporPerProdukPerBuyerController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [RekapPenjualanEksporPerProdukPerBuyerController::class, 'preview'])->name('preview');
        Route::post('/health', [RekapPenjualanEksporPerProdukPerBuyerController::class, 'health'])->name('health');
    });

    Route::prefix('rekap-penjualan-ekspor-per-buyer-per-produk')->name('rekap-penjualan-ekspor-per-buyer-per-produk.')->group(function (): void {
        Route::get('/', [RekapPenjualanEksporPerBuyerPerProdukController::class, 'index'])->name('index');
        Route::post('/download', [RekapPenjualanEksporPerBuyerPerProdukController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [RekapPenjualanEksporPerBuyerPerProdukController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [RekapPenjualanEksporPerBuyerPerProdukController::class, 'preview'])->name('preview');
        Route::post('/health', [RekapPenjualanEksporPerBuyerPerProdukController::class, 'health'])->name('health');
    });

    Route::prefix('timeline-rekap-penjualan-per-produk')->name('timeline-rekap-penjualan-per-produk.')->group(function (): void {
        Route::get('/', [TimelineRekapPenjualanPerProdukController::class, 'index'])->name('index');
        Route::post('/download', [TimelineRekapPenjualanPerProdukController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [TimelineRekapPenjualanPerProdukController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [TimelineRekapPenjualanPerProdukController::class, 'preview'])->name('preview');
        Route::post('/health', [TimelineRekapPenjualanPerProdukController::class, 'health'])->name('health');
    });
});

Route::prefix('reports/management')->name('reports.management.')->group(function (): void {
    Route::prefix('stock-hidup-per-nospk')->name('stock-hidup-per-nospk.')->group(function (): void {
        Route::get('/', [StockHidupPerNoSpkController::class, 'index'])->name('index');
        Route::post('/download', [StockHidupPerNoSpkController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [StockHidupPerNoSpkController::class, 'download'])->name('preview-pdf');
        Route::post('/preview', [StockHidupPerNoSpkController::class, 'preview'])->name('preview');
        Route::post('/health', [StockHidupPerNoSpkController::class, 'health'])->name('health');
    });

    Route::prefix('stock-hidup-per-nospk-discrepancy')->name('stock-hidup-per-nospk-discrepancy.')->group(function (): void {
        Route::get('/', [StockHidupPerNoSpkDiscrepancyController::class, 'index'])->name('index');
        Route::post('/download', [StockHidupPerNoSpkDiscrepancyController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [StockHidupPerNoSpkDiscrepancyController::class, 'download'])->name('preview-pdf');
        Route::post('/preview', [StockHidupPerNoSpkDiscrepancyController::class, 'preview'])->name('preview');
        Route::post('/health', [StockHidupPerNoSpkDiscrepancyController::class, 'health'])->name('health');
    });
    Route::prefix('discrepancy-rekap-mutasi')->name('discrepancy-rekap-mutasi.')->group(function (): void {
        Route::get('/', [DiscrepancyRekapMutasiController::class, 'index'])->name('index');
        Route::post('/download', [DiscrepancyRekapMutasiController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [DiscrepancyRekapMutasiController::class, 'download'])->name('preview-pdf');
        Route::post('/preview', [DiscrepancyRekapMutasiController::class, 'preview'])->name('preview');
        Route::post('/health', [DiscrepancyRekapMutasiController::class, 'health'])->name('health');
    });
    Route::prefix('rekap-mutasi')->name('rekap-mutasi.')->group(function (): void {
        Route::get('/', [RekapMutasiController::class, 'index'])->name('index');
        Route::post('/download', [RekapMutasiController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [RekapMutasiController::class, 'download'])->name('preview-pdf');
        Route::post('/preview', [RekapMutasiController::class, 'preview'])->name('preview');
        Route::post('/health', [RekapMutasiController::class, 'health'])->name('health');
    });
    Route::prefix('rekap-mutasi-cross-tab')->name('rekap-mutasi-cross-tab.')->group(function (): void {
        Route::get('/', [RekapMutasiCrossTabController::class, 'index'])->name('index');
        Route::post('/download', [RekapMutasiCrossTabController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [RekapMutasiCrossTabController::class, 'download'])->name('preview-pdf');
        Route::post('/preview', [RekapMutasiCrossTabController::class, 'preview'])->name('preview');
        Route::post('/health', [RekapMutasiCrossTabController::class, 'health'])->name('health');
    });
    Route::prefix('flow-produksi-per-periode')->name('flow-produksi-per-periode.')->group(function (): void {
        Route::get('/', [FlowProduksiPerPeriodeController::class, 'index'])->name('index');
        Route::post('/download', [FlowProduksiPerPeriodeController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [FlowProduksiPerPeriodeController::class, 'download'])->name('preview-pdf');
        Route::post('/preview', [FlowProduksiPerPeriodeController::class, 'preview'])->name('preview');
        Route::post('/health', [FlowProduksiPerPeriodeController::class, 'health'])->name('health');
    });
    Route::prefix('dashboard-ru')->name('dashboard-ru.')->group(function (): void {
        Route::get('/', [DashboardRuController::class, 'index'])->name('index');
        Route::post('/download', [DashboardRuController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [DashboardRuController::class, 'download'])->name('preview-pdf');
        Route::post('/preview', [DashboardRuController::class, 'preview'])->name('preview');
        Route::post('/health', [DashboardRuController::class, 'health'])->name('health');
    });
    Route::prefix('produksi-semua-mesin')->name('produksi-semua-mesin.')->group(function (): void {
        Route::get('/', [ProduksiSemuaMesinController::class, 'index'])->name('index');
        Route::post('/download', [ProduksiSemuaMesinController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [ProduksiSemuaMesinController::class, 'download'])->name('preview-pdf');
        Route::post('/preview', [ProduksiSemuaMesinController::class, 'preview'])->name('preview');
        Route::post('/health', [ProduksiSemuaMesinController::class, 'health'])->name('health');
    });
    Route::prefix('produksi-hulu-hilir')->name('produksi-hulu-hilir.')->group(function (): void {
        Route::get('/', [ProduksiHuluHilirController::class, 'index'])->name('index');
        Route::post('/download', [ProduksiHuluHilirController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [ProduksiHuluHilirController::class, 'download'])->name('preview-pdf');
        Route::post('/preview', [ProduksiHuluHilirController::class, 'preview'])->name('preview');
        Route::post('/health', [ProduksiHuluHilirController::class, 'health'])->name('health');
    });

    Route::prefix('hasil-produksi-mesin-lembur-dan-non-lembur')->name('hasil-produksi-mesin-lembur-dan-non-lembur.')->group(function (): void {
        Route::get('/', [HasilProduksiMesinLemburDanNonLemburController::class, 'index'])->name('index');
        Route::post('/download', [HasilProduksiMesinLemburDanNonLemburController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [HasilProduksiMesinLemburDanNonLemburController::class, 'download'])->name('preview-pdf');
        Route::post('/preview', [HasilProduksiMesinLemburDanNonLemburController::class, 'preview'])->name('preview');
        Route::post('/health', [HasilProduksiMesinLemburDanNonLemburController::class, 'health'])->name('health');
    });

    Route::prefix('label-perhari')->name('label-perhari.')->group(function (): void {
        Route::get('/', [LabelPerhariController::class, 'index'])->name('index');
        Route::post('/download', [LabelPerhariController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [LabelPerhariController::class, 'download'])->name('preview-pdf');
        Route::post('/preview', [LabelPerhariController::class, 'preview'])->name('preview');
        Route::post('/health', [LabelPerhariController::class, 'health'])->name('health');
    });

    Route::prefix('rekap-stock-on-hand')->name('rekap-stock-on-hand.')->group(function (): void {
        Route::get('/', [RekapStockOnHandController::class, 'index'])->name('index');
        Route::post('/download', [RekapStockOnHandController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [RekapStockOnHandController::class, 'download'])->name('preview-pdf');
        Route::post('/preview', [RekapStockOnHandController::class, 'preview'])->name('preview');
        Route::post('/health', [RekapStockOnHandController::class, 'health'])->name('health');
    });
});

Route::prefix('reports/verifikasi')->name('reports.verifikasi.')->group(function (): void {
    Route::prefix('rangkuman-bongkar-susun')->name('rangkuman-bongkar-susun.')->group(function (): void {
        Route::get('/', [RangkumanBongkarSusunController::class, 'index'])->name('index');
        Route::post('/download', [RangkumanBongkarSusunController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [RangkumanBongkarSusunController::class, 'download'])->name('preview-pdf');
        Route::post('/preview', [RangkumanBongkarSusunController::class, 'preview'])->name('preview');
        Route::post('/health', [RangkumanBongkarSusunController::class, 'health'])->name('health');
    });
    Route::prefix('bahan-yang-dihasilkan')->name('bahan-yang-dihasilkan.')->group(function (): void {
        Route::get('/', [BahanYangDihasilkanController::class, 'index'])->name('index');
        Route::post('/download', [BahanYangDihasilkanController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [BahanYangDihasilkanController::class, 'download'])->name('preview-pdf');
        Route::post('/preview', [BahanYangDihasilkanController::class, 'preview'])->name('preview');
        Route::post('/health', [BahanYangDihasilkanController::class, 'health'])->name('health');
    });
    Route::prefix('kapasitas-racip-kayu-bulat-hidup')->name('kapasitas-racip-kayu-bulat-hidup.')->group(function (): void {
        Route::get('/', [KapasitasRacipKayuBulatHidupController::class, 'index'])->name('index');
        Route::post('/download', [KapasitasRacipKayuBulatHidupController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [KapasitasRacipKayuBulatHidupController::class, 'download'])->name('preview-pdf');
        Route::post('/preview', [KapasitasRacipKayuBulatHidupController::class, 'preview'])->name('preview');
        Route::post('/health', [KapasitasRacipKayuBulatHidupController::class, 'health'])->name('health');
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

Route::prefix('reports/barang-jadi')->name('reports.barang-jadi.')->group(function (): void {
    Route::prefix('mutasi-barang-jadi-per-jenis-per-ukuran')->name('mutasi-barang-jadi-per-jenis-per-ukuran.')->group(function (): void {
        Route::get('/', [MutasiBarangJadiPerJenisPerUkuranController::class, 'index'])->name('index');
        Route::post('/download', [MutasiBarangJadiPerJenisPerUkuranController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [MutasiBarangJadiPerJenisPerUkuranController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [MutasiBarangJadiPerJenisPerUkuranController::class, 'preview'])->name('preview');
    });
    Route::prefix('saldo-barang-jadi-hidup-per-jenis-per-produk')->name('saldo-barang-jadi-hidup-per-jenis-per-produk.')->group(function (): void {
        Route::get('/', [SaldoBarangJadiHidupPerJenisPerProdukController::class, 'index'])->name('index');
        Route::post('/download', [SaldoBarangJadiHidupPerJenisPerProdukController::class, 'download'])->name('download');
        Route::post('/preview', [SaldoBarangJadiHidupPerJenisPerProdukController::class, 'preview'])->name('preview');
        Route::post('/preview-pdf', [SaldoBarangJadiHidupPerJenisPerProdukController::class, 'download'])->name('preview-pdf');
    });
    Route::prefix('barang-jadi-hidup-detail')->name('barang-jadi-hidup-detail.')->group(function (): void {
        Route::get('/', [BarangJadiHidupDetailController::class, 'index'])->name('index');
        Route::post('/download', [BarangJadiHidupDetailController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [BarangJadiHidupDetailController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [BarangJadiHidupDetailController::class, 'preview'])->name('preview');
        Route::post('/health', [BarangJadiHidupDetailController::class, 'health'])->name('health');
    });
    Route::prefix('umur-barang-jadi-detail')->name('umur-barang-jadi-detail.')->group(function (): void {
        Route::get('/', [UmurBarangJadiDetailController::class, 'index'])->name('index');
        Route::post('/download', [UmurBarangJadiDetailController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [UmurBarangJadiDetailController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [UmurBarangJadiDetailController::class, 'preview'])->name('preview');
        Route::post('/health', [UmurBarangJadiDetailController::class, 'health'])->name('health');
    });
    Route::prefix('rekap-produksi-barang-jadi-consolidated')->name('rekap-produksi-barang-jadi-consolidated.')->group(function (): void {
        Route::get('/', [RekapProduksiBarangJadiConsolidatedController::class, 'index'])->name('index');
        Route::post('/download', [RekapProduksiBarangJadiConsolidatedController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [RekapProduksiBarangJadiConsolidatedController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [RekapProduksiBarangJadiConsolidatedController::class, 'preview'])->name('preview');
        Route::post('/health', [RekapProduksiBarangJadiConsolidatedController::class, 'health'])->name('health');
    });
    Route::prefix('rekap-produksi-packing-per-jenis-per-grade')->name('rekap-produksi-packing-per-jenis-per-grade.')->group(function (): void {
        Route::get('/', [RekapProduksiPackingPerJenisPerGradeController::class, 'index'])->name('index');
        Route::post('/download', [RekapProduksiPackingPerJenisPerGradeController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [RekapProduksiPackingPerJenisPerGradeController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [RekapProduksiPackingPerJenisPerGradeController::class, 'preview'])->name('preview');
        Route::post('/health', [RekapProduksiPackingPerJenisPerGradeController::class, 'health'])->name('health');
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

    /** Rekap penerimaan ST dari sawmill timbang KG routes. */
    Route::prefix('rekap-penerimaan-st-dari-sawmill-kg')->name('rekap-penerimaan-st-dari-sawmill-kg.')->group(function (): void {
        Route::get('/', [RekapPenerimaanSTDariSawmillKgController::class, 'index'])->name('index');
        Route::post('/download', [RekapPenerimaanSTDariSawmillKgController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [RekapPenerimaanSTDariSawmillKgController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [RekapPenerimaanSTDariSawmillKgController::class, 'preview'])->name('preview');
    });

    /** Rekap produktivitas sawmill (Rp) routes. */
    Route::prefix('rekap-produktivitas-sawmill-rp')->name('rekap-produktivitas-sawmill-rp.')->group(function (): void {
        Route::get('/', [RekapProduktivitasSawmillRpController::class, 'index'])->name('index');
        Route::post('/download', [RekapProduktivitasSawmillRpController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [RekapProduktivitasSawmillRpController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [RekapProduktivitasSawmillRpController::class, 'preview'])->name('preview');
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

    /** Perbandingan KB masuk periode routes (Timbang KG). */
    Route::prefix('perbandingan-kb-masuk-periode-1-dan-2-kg')->name('perbandingan-kb-masuk-periode-1-dan-2-kg.')->group(function (): void {
        Route::get('/', [PerbandinganKbMasukPeriode1Dan2KgController::class, 'index'])->name('index');
        Route::post('/download', [PerbandinganKbMasukPeriode1Dan2KgController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [PerbandinganKbMasukPeriode1Dan2KgController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [PerbandinganKbMasukPeriode1Dan2KgController::class, 'preview'])->name('preview');
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

    /** Umur S4S detail routes. */
    Route::prefix('s4s/umur-s4s-detail')->name('s4s.umur-s4s-detail.')->group(function (): void {
        Route::get('/', [UmurS4SDetailController::class, 'index'])->name('index');
        Route::post('/download', [UmurS4SDetailController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [UmurS4SDetailController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [UmurS4SDetailController::class, 'preview'])->name('preview');
    });

    /** Umur Finger Joint detail routes. */
    Route::prefix('finger-joint/umur-finger-joint-detail')->name('finger-joint.umur-finger-joint-detail.')->group(function (): void {
        Route::get('/', [UmurFingerJointDetailController::class, 'index'])->name('index');
        Route::post('/download', [UmurFingerJointDetailController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [UmurFingerJointDetailController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [UmurFingerJointDetailController::class, 'preview'])->name('preview');
        Route::post('/health', [UmurFingerJointDetailController::class, 'health'])->name('health');
    });

    /** Umur laminating detail routes. */
    Route::prefix('laminating/umur-laminating-detail')->name('laminating.umur-laminating-detail.')->group(function (): void {
        Route::get('/', [UmurLaminatingDetailController::class, 'index'])->name('index');
        Route::post('/download', [UmurLaminatingDetailController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [UmurLaminatingDetailController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [UmurLaminatingDetailController::class, 'preview'])->name('preview');
        Route::post('/health', [UmurLaminatingDetailController::class, 'health'])->name('health');
    });

    /** Umur moulding detail routes. */
    Route::prefix('moulding/umur-moulding-detail')->name('moulding.umur-moulding-detail.')->group(function (): void {
        Route::get('/', [UmurMouldingDetailController::class, 'index'])->name('index');
        Route::post('/download', [UmurMouldingDetailController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [UmurMouldingDetailController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [UmurMouldingDetailController::class, 'preview'])->name('preview');
        Route::post('/health', [UmurMouldingDetailController::class, 'health'])->name('health');
    });

    /** Umur reproses detail routes. */
    Route::prefix('reproses/umur-reproses-detail')->name('reproses.umur-reproses-detail.')->group(function (): void {
        Route::get('/', [UmurReprosesDetailController::class, 'index'])->name('index');
        Route::post('/download', [UmurReprosesDetailController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [UmurReprosesDetailController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [UmurReprosesDetailController::class, 'preview'])->name('preview');
        Route::post('/health', [UmurReprosesDetailController::class, 'health'])->name('health');
    });

    /** Reproses (Hidup) detail routes. */
    Route::prefix('reproses/reproses-hidup-detail')->name('reproses.reproses-hidup-detail.')->group(function (): void {
        Route::get('/', [ReprosesHidupDetailController::class, 'index'])->name('index');
        Route::post('/download', [ReprosesHidupDetailController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [ReprosesHidupDetailController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [ReprosesHidupDetailController::class, 'preview'])->name('preview');
        Route::post('/health', [ReprosesHidupDetailController::class, 'health'])->name('health');
    });

    /** Ketahanan Barang Dagang Reproses routes. */
    Route::prefix('reproses/ketahanan-barang-reproses')->name('reproses.ketahanan-barang-reproses.')->group(function (): void {
        Route::get('/', [KetahananBarangDagangReprosesController::class, 'index'])->name('index');
        Route::post('/download', [KetahananBarangDagangReprosesController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [KetahananBarangDagangReprosesController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [KetahananBarangDagangReprosesController::class, 'preview'])->name('preview');
        Route::post('/health', [KetahananBarangDagangReprosesController::class, 'health'])->name('health');
    });

    /** Umur CCAkhir detail routes. */
    Route::prefix('cross-cut-akhir/umur-cc-akhir-detail')->name('cross-cut-akhir.umur-cc-akhir-detail.')->group(function (): void {
        Route::get('/', [UmurCrossCutAkhirDetailController::class, 'index'])->name('index');
        Route::post('/download', [UmurCrossCutAkhirDetailController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [UmurCrossCutAkhirDetailController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [UmurCrossCutAkhirDetailController::class, 'preview'])->name('preview');
        Route::post('/health', [UmurCrossCutAkhirDetailController::class, 'health'])->name('health');
    });

    /** Cross Cut Akhir (Hidup) detail routes. */
    Route::prefix('cross-cut-akhir/cc-akhir-hidup-detail')->name('cross-cut-akhir.cc-akhir-hidup-detail.')->group(function (): void {
        Route::get('/', [CrossCutAkhirHidupDetailController::class, 'index'])->name('index');
        Route::post('/download', [CrossCutAkhirHidupDetailController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [CrossCutAkhirHidupDetailController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [CrossCutAkhirHidupDetailController::class, 'preview'])->name('preview');
        Route::post('/health', [CrossCutAkhirHidupDetailController::class, 'health'])->name('health');
    });

    /** Rekap Produksi CCAkhir Consolidated routes. */
    Route::prefix('cross-cut-akhir/rekap-produksi-cc-akhir-consolidated')->name('cross-cut-akhir.rekap-produksi-cc-akhir-consolidated.')->group(function (): void {
        Route::get('/', [RekapProduksiCrossCutAkhirConsolidatedController::class, 'index'])->name('index');
        Route::post('/download', [RekapProduksiCrossCutAkhirConsolidatedController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [RekapProduksiCrossCutAkhirConsolidatedController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [RekapProduksiCrossCutAkhirConsolidatedController::class, 'preview'])->name('preview');
        Route::post('/health', [RekapProduksiCrossCutAkhirConsolidatedController::class, 'health'])->name('health');
    });

    /** Rekap Produksi CCAkhir Per-Jenis & Per-Grade routes. */
    Route::prefix('cross-cut-akhir/rekap-produksi-cc-akhir-per-jenis-per-grade')->name('cross-cut-akhir.rekap-produksi-cc-akhir-per-jenis-per-grade.')->group(function (): void {
        Route::get('/', [RekapProduksiCrossCutAkhirPerJenisPerGradeController::class, 'index'])->name('index');
        Route::post('/download', [RekapProduksiCrossCutAkhirPerJenisPerGradeController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [RekapProduksiCrossCutAkhirPerJenisPerGradeController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [RekapProduksiCrossCutAkhirPerJenisPerGradeController::class, 'preview'])->name('preview');
        Route::post('/health', [RekapProduksiCrossCutAkhirPerJenisPerGradeController::class, 'health'])->name('health');
    });

    /** Ketahanan Barang Dagang CCAkhir routes. */
    Route::prefix('cross-cut-akhir/ketahanan-barang-cc-akhir')->name('cross-cut-akhir.ketahanan-barang-cc-akhir.')->group(function (): void {
        Route::get('/', [KetahananBarangDagangCrossCutAkhirController::class, 'index'])->name('index');
        Route::post('/download', [KetahananBarangDagangCrossCutAkhirController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [KetahananBarangDagangCrossCutAkhirController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [KetahananBarangDagangCrossCutAkhirController::class, 'preview'])->name('preview');
        Route::post('/health', [KetahananBarangDagangCrossCutAkhirController::class, 'health'])->name('health');
    });

    /** Umur sanding detail routes. */
    Route::prefix('sanding/umur-sanding-detail')->name('sanding.umur-sanding-detail.')->group(function (): void {
        Route::get('/', [UmurSandingDetailController::class, 'index'])->name('index');
        Route::post('/download', [UmurSandingDetailController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [UmurSandingDetailController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [UmurSandingDetailController::class, 'preview'])->name('preview');
        Route::post('/health', [UmurSandingDetailController::class, 'health'])->name('health');
    });

    /** Sanding (Hidup) detail routes. */
    Route::prefix('sanding/sanding-hidup-detail')->name('sanding.sanding-hidup-detail.')->group(function (): void {
        Route::get('/', [SandingHidupDetailController::class, 'index'])->name('index');
        Route::post('/download', [SandingHidupDetailController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [SandingHidupDetailController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [SandingHidupDetailController::class, 'preview'])->name('preview');
        Route::post('/health', [SandingHidupDetailController::class, 'health'])->name('health');
    });

    /** Rekap Produksi Sanding Consolidated routes. */
    Route::prefix('sanding/rekap-produksi-sanding-consolidated')->name('sanding.rekap-produksi-sanding-consolidated.')->group(function (): void {
        Route::get('/', [RekapProduksiSandingConsolidatedController::class, 'index'])->name('index');
        Route::post('/download', [RekapProduksiSandingConsolidatedController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [RekapProduksiSandingConsolidatedController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [RekapProduksiSandingConsolidatedController::class, 'preview'])->name('preview');
        Route::post('/health', [RekapProduksiSandingConsolidatedController::class, 'health'])->name('health');
    });

    /** Rekap Produksi Sanding Per-Jenis & Per-Grade routes. */
    Route::prefix('sanding/rekap-produksi-sanding-per-jenis-per-grade')->name('sanding.rekap-produksi-sanding-per-jenis-per-grade.')->group(function (): void {
        Route::get('/', [RekapProduksiSandingPerJenisPerGradeController::class, 'index'])->name('index');
        Route::post('/download', [RekapProduksiSandingPerJenisPerGradeController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [RekapProduksiSandingPerJenisPerGradeController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [RekapProduksiSandingPerJenisPerGradeController::class, 'preview'])->name('preview');
        Route::post('/health', [RekapProduksiSandingPerJenisPerGradeController::class, 'health'])->name('health');
    });

    /** Ketahanan Barang Dagang Sanding routes. */
    Route::prefix('sanding/ketahanan-barang-sanding')->name('sanding.ketahanan-barang-sanding.')->group(function (): void {
        Route::get('/', [KetahananBarangDagangSandingController::class, 'index'])->name('index');
        Route::post('/download', [KetahananBarangDagangSandingController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [KetahananBarangDagangSandingController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [KetahananBarangDagangSandingController::class, 'preview'])->name('preview');
        Route::post('/health', [KetahananBarangDagangSandingController::class, 'health'])->name('health');
    });

    /** Rekap Produksi Laminating Consolidated routes. */
    Route::prefix('laminating/rekap-produksi-laminating-consolidated')->name('laminating.rekap-produksi-laminating-consolidated.')->group(function (): void {
        Route::get('/', [RekapProduksiLaminatingConsolidatedController::class, 'index'])->name('index');
        Route::post('/download', [RekapProduksiLaminatingConsolidatedController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [RekapProduksiLaminatingConsolidatedController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [RekapProduksiLaminatingConsolidatedController::class, 'preview'])->name('preview');
        Route::post('/health', [RekapProduksiLaminatingConsolidatedController::class, 'health'])->name('health');
    });

    /** Laminating (Hidup) detail routes. */
    Route::prefix('laminating/laminating-hidup-detail')->name('laminating.laminating-hidup-detail.')->group(function (): void {
        Route::get('/', [LaminatingHidupDetailController::class, 'index'])->name('index');
        Route::post('/download', [LaminatingHidupDetailController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [LaminatingHidupDetailController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [LaminatingHidupDetailController::class, 'preview'])->name('preview');
        Route::post('/health', [LaminatingHidupDetailController::class, 'health'])->name('health');
    });

    /** Rekap Produksi Laminating per-jenis dan per-grade routes. */
    Route::prefix('laminating/rekap-produksi-laminating-per-jenis-per-grade')->name('laminating.rekap-produksi-laminating-per-jenis-per-grade.')->group(function (): void {
        Route::get('/', [RekapProduksiLaminatingPerJenisPerGradeController::class, 'index'])->name('index');
        Route::post('/download', [RekapProduksiLaminatingPerJenisPerGradeController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [RekapProduksiLaminatingPerJenisPerGradeController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [RekapProduksiLaminatingPerJenisPerGradeController::class, 'preview'])->name('preview');
        Route::post('/health', [RekapProduksiLaminatingPerJenisPerGradeController::class, 'health'])->name('health');
    });

    /** Ketahanan Barang Dagang Laminating routes. */
    Route::prefix('laminating/ketahanan-barang-laminating')->name('laminating.ketahanan-barang-laminating.')->group(function (): void {
        Route::get('/', [KetahananBarangDagangLaminatingController::class, 'index'])->name('index');
        Route::post('/download', [KetahananBarangDagangLaminatingController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [KetahananBarangDagangLaminatingController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [KetahananBarangDagangLaminatingController::class, 'preview'])->name('preview');
        Route::post('/health', [KetahananBarangDagangLaminatingController::class, 'health'])->name('health');
    });

    /** Rekap Produksi Moulding Consolidated routes. */
    Route::prefix('moulding/rekap-produksi-moulding-consolidated')->name('moulding.rekap-produksi-moulding-consolidated.')->group(function (): void {
        Route::get('/', [RekapProduksiMouldingConsolidatedController::class, 'index'])->name('index');
        Route::post('/download', [RekapProduksiMouldingConsolidatedController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [RekapProduksiMouldingConsolidatedController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [RekapProduksiMouldingConsolidatedController::class, 'preview'])->name('preview');
        Route::post('/health', [RekapProduksiMouldingConsolidatedController::class, 'health'])->name('health');
    });

    /** Moulding (Hidup) Detail routes. */
    Route::prefix('moulding/moulding-hidup-detail')->name('moulding.moulding-hidup-detail.')->group(function (): void {
        Route::get('/', [MouldingHidupDetailController::class, 'index'])->name('index');
        Route::post('/download', [MouldingHidupDetailController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [MouldingHidupDetailController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [MouldingHidupDetailController::class, 'preview'])->name('preview');
        Route::post('/health', [MouldingHidupDetailController::class, 'health'])->name('health');
    });

    /** Rekap Produksi Moulding per-jenis dan per-grade routes. */
    Route::prefix('moulding/rekap-produksi-moulding-per-jenis-per-grade')->name('moulding.rekap-produksi-moulding-per-jenis-per-grade.')->group(function (): void {
        Route::get('/', [RekapProduksiMouldingPerJenisPerGradeController::class, 'index'])->name('index');
        Route::post('/download', [RekapProduksiMouldingPerJenisPerGradeController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [RekapProduksiMouldingPerJenisPerGradeController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [RekapProduksiMouldingPerJenisPerGradeController::class, 'preview'])->name('preview');
        Route::post('/health', [RekapProduksiMouldingPerJenisPerGradeController::class, 'health'])->name('health');
    });

    /** Ketahanan Barang Dagang Moulding routes. */
    Route::prefix('moulding/ketahanan-barang-moulding')->name('moulding.ketahanan-barang-moulding.')->group(function (): void {
        Route::get('/', [KetahananBarangDagangMouldingController::class, 'index'])->name('index');
        Route::post('/download', [KetahananBarangDagangMouldingController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [KetahananBarangDagangMouldingController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [KetahananBarangDagangMouldingController::class, 'preview'])->name('preview');
        Route::post('/health', [KetahananBarangDagangMouldingController::class, 'health'])->name('health');
    });

    /** Rekap Produksi Finger Joint Consolidated routes. */
    Route::prefix('finger-joint/rekap-produksi-finger-joint-consolidated')->name('finger-joint.rekap-produksi-finger-joint-consolidated.')->group(function (): void {
        Route::get('/', [RekapProduksiFingerJointConsolidatedController::class, 'index'])->name('index');
        Route::post('/download', [RekapProduksiFingerJointConsolidatedController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [RekapProduksiFingerJointConsolidatedController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [RekapProduksiFingerJointConsolidatedController::class, 'preview'])->name('preview');
        Route::post('/health', [RekapProduksiFingerJointConsolidatedController::class, 'health'])->name('health');
    });

    /** Rekap Produksi Finger Joint per-jenis dan per-grade routes. */
    Route::prefix('finger-joint/rekap-produksi-finger-joint-per-jenis-per-grade')->name('finger-joint.rekap-produksi-finger-joint-per-jenis-per-grade.')->group(function (): void {
        Route::get('/', [RekapProduksiFingerJointPerJenisPerGradeController::class, 'index'])->name('index');
        Route::post('/download', [RekapProduksiFingerJointPerJenisPerGradeController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [RekapProduksiFingerJointPerJenisPerGradeController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [RekapProduksiFingerJointPerJenisPerGradeController::class, 'preview'])->name('preview');
    });

    /** Finger Joint (Hidup) detail routes. */
    Route::prefix('finger-joint/finger-joint-hidup-detail')->name('finger-joint.finger-joint-hidup-detail.')->group(function (): void {
        Route::get('/', [FingerJointHidupDetailController::class, 'index'])->name('index');
        Route::post('/download', [FingerJointHidupDetailController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [FingerJointHidupDetailController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [FingerJointHidupDetailController::class, 'preview'])->name('preview');
        Route::post('/health', [FingerJointHidupDetailController::class, 'health'])->name('health');
    });

    /** Ketahanan Barang Dagang Finger Joint routes. */
    Route::prefix('finger-joint/ketahanan-barang-finger-joint')->name('finger-joint.ketahanan-barang-finger-joint.')->group(function (): void {
        Route::get('/', [KetahananBarangDagangFingerJointController::class, 'index'])->name('index');
        Route::post('/download', [KetahananBarangDagangFingerJointController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [KetahananBarangDagangFingerJointController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [KetahananBarangDagangFingerJointController::class, 'preview'])->name('preview');
        Route::post('/health', [KetahananBarangDagangFingerJointController::class, 'health'])->name('health');
    });

    /** S4S (Hidup) detail routes. */
    Route::prefix('s4s/s4s-hidup-detail')->name('s4s.s4s-hidup-detail.')->group(function (): void {
        Route::get('/', [S4SHidupDetailController::class, 'index'])->name('index');
        Route::post('/download', [S4SHidupDetailController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [S4SHidupDetailController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [S4SHidupDetailController::class, 'preview'])->name('preview');
    });

    /** Label S4S (Hidup) per-jenis kayu routes. */
    Route::prefix('s4s/label-s4s-hidup-per-jenis-kayu')->name('s4s.label-s4s-hidup-per-jenis-kayu.')->group(function (): void {
        Route::get('/', [LabelS4SHidupPerJenisKayuController::class, 'index'])->name('index');
        Route::post('/download', [LabelS4SHidupPerJenisKayuController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [LabelS4SHidupPerJenisKayuController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [LabelS4SHidupPerJenisKayuController::class, 'preview'])->name('preview');
    });

    /** Label S4S (Hidup) per-produk dan per-jenis kayu routes. */
    Route::prefix('s4s/label-s4s-hidup-per-produk-per-jenis-kayu')->name('s4s.label-s4s-hidup-per-produk-per-jenis-kayu.')->group(function (): void {
        Route::get('/', [LabelS4SHidupPerProdukPerJenisKayuController::class, 'index'])->name('index');
        Route::post('/download', [LabelS4SHidupPerProdukPerJenisKayuController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [LabelS4SHidupPerProdukPerJenisKayuController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [LabelS4SHidupPerProdukPerJenisKayuController::class, 'preview'])->name('preview');
    });

    /** Rekap Produksi S4S per-jenis dan per-grade routes. */
    Route::prefix('s4s/rekap-produksi-s4s-per-jenis-per-grade')->name('s4s.rekap-produksi-s4s-per-jenis-per-grade.')->group(function (): void {
        Route::get('/', [RekapProduksiS4SPerJenisPerGradeController::class, 'index'])->name('index');
        Route::post('/download', [RekapProduksiS4SPerJenisPerGradeController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [RekapProduksiS4SPerJenisPerGradeController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [RekapProduksiS4SPerJenisPerGradeController::class, 'preview'])->name('preview');
    });

    /** Rekap Produksi S4S Consolidated routes. */
    Route::prefix('s4s/rekap-produksi-s4s-consolidated')->name('s4s.rekap-produksi-s4s-consolidated.')->group(function (): void {
        Route::get('/', [RekapProduksiS4SConsolidatedController::class, 'index'])->name('index');
        Route::post('/download', [RekapProduksiS4SConsolidatedController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [RekapProduksiS4SConsolidatedController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [RekapProduksiS4SConsolidatedController::class, 'preview'])->name('preview');
    });

    /** Ketahanan Barang Dagang S4S routes. */
    Route::prefix('s4s/ketahanan-barang-s4s')->name('s4s.ketahanan-barang-s4s.')->group(function (): void {
        Route::get('/', [KetahananBarangDagangS4sController::class, 'index'])->name('index');
        Route::post('/download', [KetahananBarangDagangS4sController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [KetahananBarangDagangS4sController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [KetahananBarangDagangS4sController::class, 'preview'])->name('preview');
        Route::post('/health', [KetahananBarangDagangS4sController::class, 'health'])->name('health');
    });

    /** Output Produksi S4S Per Grade routes. */
    Route::prefix('s4s/output-produksi-s4s-per-grade')->name('s4s.output-produksi-s4s-per-grade.')->group(function (): void {
        Route::get('/', [OutputProduksiS4sPerGradeController::class, 'index'])->name('index');
        Route::post('/download', [OutputProduksiS4sPerGradeController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [OutputProduksiS4sPerGradeController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [OutputProduksiS4sPerGradeController::class, 'preview'])->name('preview');
        Route::post('/health', [OutputProduksiS4sPerGradeController::class, 'health'])->name('health');
    });

    /** Grade ABC Harian routes. */
    Route::prefix('s4s/grade-abc-harian')->name('s4s.grade-abc-harian.')->group(function (): void {
        Route::get('/', [GradeAbcHarianController::class, 'index'])->name('index');
        Route::post('/download', [GradeAbcHarianController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [GradeAbcHarianController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [GradeAbcHarianController::class, 'preview'])->name('preview');
        Route::post('/health', [GradeAbcHarianController::class, 'health'])->name('health');
    });

    /** Rekap Produksi Rambung Per Grade routes. */
    Route::prefix('s4s/rekap-produksi-rambung-per-grade')->name('s4s.rekap-produksi-rambung-per-grade.')->group(function (): void {
        Route::get('/', [RekapProduksiS4sRambungPerGradeController::class, 'index'])->name('index');
        Route::post('/download', [RekapProduksiS4sRambungPerGradeController::class, 'download'])->name('download');
        Route::post('/preview-pdf', [RekapProduksiS4sRambungPerGradeController::class, 'previewPdf'])->name('preview-pdf');
        Route::post('/preview', [RekapProduksiS4sRambungPerGradeController::class, 'preview'])->name('preview');
        Route::post('/health', [RekapProduksiS4sRambungPerGradeController::class, 'health'])->name('health');
    });
});

/**
 * Authentication route group for web UI.
 */
Route::middleware('guest')->post('/login', [WebAuthController::class, 'login'])->name('web.login');
Route::middleware('auth')->post('/logout', [WebAuthController::class, 'logout'])->name('web.logout');

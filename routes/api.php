<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OpenApiController;
use App\Http\Controllers\Api\PdfJobController;
use App\Http\Controllers\Ascends\Ru\Hrm\EmployeeListController;
use App\Http\Controllers\AscendXmlTestController;
use App\Http\Controllers\BahanTerpakaiController;
use App\Http\Controllers\BahanYangDihasilkanController;
use App\Http\Controllers\BalokSudahSemprotController;
use App\Http\Controllers\BarangJadiHidupDetailController;
use App\Http\Controllers\CrossCutAkhirHidupDetailController;
use App\Http\Controllers\DashboardBarangJadiController;
use App\Http\Controllers\DashboardCrossCutAkhirController;
use App\Http\Controllers\DashboardFingerJointController;
use App\Http\Controllers\DashboardLaminatingController;
use App\Http\Controllers\DashboardMouldingController;
use App\Http\Controllers\DashboardReprosesController;
use App\Http\Controllers\DashboardRuController;
use App\Http\Controllers\DashboardS4SController;
use App\Http\Controllers\DashboardS4SV2Controller;
use App\Http\Controllers\DashboardSandingController;
use App\Http\Controllers\DashboardSawnTimberController;
use App\Http\Controllers\DetailLembarTallyHasilSawmillController;
use App\Http\Controllers\DiscrepancyRekapMutasiController;
use App\Http\Controllers\FingerJointHidupDetailController;
use App\Http\Controllers\FlowProduksiPerPeriodeController;
use App\Http\Controllers\GradeAbcHarianController;
use App\Http\Controllers\HasilOutputRacipHarianController;
use App\Http\Controllers\HasilProduksiMesinLemburDanNonLemburController;
use App\Http\Controllers\HidupKBPerGroupController;
use App\Http\Controllers\KapasitasRacipKayuBulatHidupController;
use App\Http\Controllers\KayuBulatHidupController;
use App\Http\Controllers\KbKhususBangkangController;
use App\Http\Controllers\KdKeluarMasukController;
use App\Http\Controllers\KdUpahPerCustomerController;
use App\Http\Controllers\KdUpahPerNoProcKdPerCustomerDetailController;
use App\Http\Controllers\KetahananBarangDagangCrossCutAkhirController;
use App\Http\Controllers\KetahananBarangDagangFingerJointController;
use App\Http\Controllers\KetahananBarangDagangLaminatingController;
use App\Http\Controllers\KetahananBarangDagangMouldingController;
use App\Http\Controllers\KetahananBarangDagangReprosesController;
use App\Http\Controllers\KetahananBarangDagangS4sController;
use App\Http\Controllers\KetahananBarangDagangSandingController;
use App\Http\Controllers\KetahananBarangDagangStController;
use App\Http\Controllers\KoordinatTanahController;
use App\Http\Controllers\LabelNyangkutController;
use App\Http\Controllers\LabelPerhariController;
use App\Http\Controllers\LabelS4SHidupPerJenisKayuController;
use App\Http\Controllers\LabelS4SHidupPerProdukPerJenisKayuController;
use App\Http\Controllers\LabelStHidupDetailController;
use App\Http\Controllers\LaminatingHidupDetailController;
use App\Http\Controllers\LembarTallyHasilSawmillController;
use App\Http\Controllers\MouldingHidupDetailController;
use App\Http\Controllers\MutasiBarangJadiController;
use App\Http\Controllers\MutasiBarangJadiPerJenisPerUkuranController;
use App\Http\Controllers\MutasiCCAkhirController;
use App\Http\Controllers\MutasiFingerJointController;
use App\Http\Controllers\MutasiHasilRacipController;
use App\Http\Controllers\MutasiKayuBulatController;
use App\Http\Controllers\MutasiKayuBulatKGController;
use App\Http\Controllers\MutasiKayuBulatKGV2Controller;
use App\Http\Controllers\MutasiKayuBulatV2Controller;
use App\Http\Controllers\MutasiKdController;
use App\Http\Controllers\MutasiLaminatingController;
use App\Http\Controllers\MutasiMouldingController;
use App\Http\Controllers\MutasiRacipDetailController;
use App\Http\Controllers\MutasiReprosesController;
use App\Http\Controllers\MutasiS4SController;
use App\Http\Controllers\MutasiSandingController;
use App\Http\Controllers\MutasiSTController;
use App\Http\Controllers\OutputProduksiS4sPerGradeController;
use App\Http\Controllers\PemakaianObatVacuumController;
use App\Http\Controllers\PembelianStPerSupplierTonController;
use App\Http\Controllers\PembelianStTimelineTonController;
use App\Http\Controllers\PenerimaanKayuBulatBulananPerSupplierController;
use App\Http\Controllers\PenerimaanKayuBulatExtKgController;
use App\Http\Controllers\PenerimaanKayuBulatExtTonController;
use App\Http\Controllers\PenerimaanKayuBulatIntTonController;
use App\Http\Controllers\PenerimaanKayuBulatKgController;
use App\Http\Controllers\PenerimaanKayuBulatPerSupplierBulananGrafikController;
use App\Http\Controllers\PenerimaanKayuBulatPerSupplierGroupController;
use App\Http\Controllers\PenerimaanKayuBulatPerSupplierKgController;
use App\Http\Controllers\PenerimaanStHasilSawmillController;
use App\Http\Controllers\PenerimaanStSawmillKgController;
use App\Http\Controllers\PenjualanBarangJadiM3Controller;
use App\Http\Controllers\PenjualanLokalController;
use App\Http\Controllers\PerbandinganKbMasukPeriode1Dan2Controller;
use App\Http\Controllers\PerbandinganKbMasukPeriode1Dan2KgController;
use App\Http\Controllers\PPS\HasilProduksiHarianBrokerProduksiController;
use App\Http\Controllers\PPS\HasilProduksiHarianCrusherProduksiController;
use App\Http\Controllers\PPS\HasilProduksiHarianGilinganProduksiController;
use App\Http\Controllers\PPS\HasilProduksiHarianHotStampingProduksiController;
use App\Http\Controllers\PPS\HasilProduksiHarianInjectProduksiController;
use App\Http\Controllers\PPS\HasilProduksiHarianMixerProduksiController;
use App\Http\Controllers\PPS\HasilProduksiHarianPackingProduksiController;
use App\Http\Controllers\PPS\HasilProduksiHarianPasangKunciProduksiController;
use App\Http\Controllers\PPS\HasilProduksiHarianSpannerProduksiController;
use App\Http\Controllers\PPS\HasilProduksiHarianWashingProduksiController;
use App\Http\Controllers\PPS\MutasiBahanBakuController;
use App\Http\Controllers\PPS\MutasiBarangJadiPpsController;
use App\Http\Controllers\PPS\MutasiBonggolanController;
use App\Http\Controllers\PPS\MutasiBrokerController;
use App\Http\Controllers\PPS\MutasiCrusherController;
use App\Http\Controllers\PPS\MutasiFurnitureWipController;
use App\Http\Controllers\PPS\MutasiGilinganController;
use App\Http\Controllers\PPS\MutasiMixerController;
use App\Http\Controllers\PPS\MutasiRejectController;
use App\Http\Controllers\PPS\QcHarianBahanBakuController;
use App\Http\Controllers\PPS\QcHarianBrokerController;
use App\Http\Controllers\PPS\QcHarianMixerController;
use App\Http\Controllers\PPS\QcHarianWashingController;
use App\Http\Controllers\PPS\RekapProduksiBrokerController;
use App\Http\Controllers\PPS\RekapProduksiCrusherController;
use App\Http\Controllers\PPS\RekapProduksiGilinganController;
use App\Http\Controllers\PPS\RekapProduksiHotStampingFwipController;
use App\Http\Controllers\PPS\RekapProduksiInjectBjController;
use App\Http\Controllers\PPS\RekapProduksiInjectController;
use App\Http\Controllers\PPS\RekapProduksiMixerController;
use App\Http\Controllers\PPS\RekapProduksiPackingBjController;
use App\Http\Controllers\PPS\RekapProduksiPasangKunciFwipController;
use App\Http\Controllers\PPS\RekapProduksiSpannerFwipController;
use App\Http\Controllers\PPS\RekapProduksiWashingController;
use App\Http\Controllers\PPS\SemuaLabelController;
use App\Http\Controllers\PPS\StockBahanBakuV2Controller;
use App\Http\Controllers\PPS\StockBonggolanController;
use App\Http\Controllers\PPS\StockBonggolanV2Controller;
use App\Http\Controllers\PPS\StockBrokerController;
use App\Http\Controllers\PPS\StockBrokerV2Controller;
use App\Http\Controllers\PPS\StockCrusherController;
use App\Http\Controllers\PPS\StockCrusherV2Controller;
use App\Http\Controllers\PPS\StockFurnitureWipV2Controller;
use App\Http\Controllers\PPS\StockGilinganController;
use App\Http\Controllers\PPS\StockGilinganV2Controller;
use App\Http\Controllers\PPS\StockLabelBarangJadiV2Controller;
use App\Http\Controllers\PPS\StockMixerController;
use App\Http\Controllers\PPS\StockMixerV2Controller;
use App\Http\Controllers\PPS\StockRejectController;
use App\Http\Controllers\PPS\StockWashingController;
use App\Http\Controllers\PPS\StockWashingV2Controller;
use App\Http\Controllers\ProduksiFjPerNomorProduksiController;
use App\Http\Controllers\ProduksiHuluHilirController;
use App\Http\Controllers\ProduksiLaminatingPerNomorProduksiController;
use App\Http\Controllers\ProduksiMouldingPerNomorProduksiController;
use App\Http\Controllers\ProduksiPackingPerNomorProduksiController;
use App\Http\Controllers\ProduksiPerNomorProduksiController;
use App\Http\Controllers\ProduksiPerSpkController;
use App\Http\Controllers\ProduksiS4sPerNomorProduksiController;
use App\Http\Controllers\ProduksiSandingPerNomorProduksiController;
use App\Http\Controllers\ProduksiSemuaMesinController;
use App\Http\Controllers\QcSawmillController;
use App\Http\Controllers\QcSawmillDiscrepancyController;
use App\Http\Controllers\QcSawmillSummaryController;
use App\Http\Controllers\RangkumanBongkarSusunController;
use App\Http\Controllers\RangkumanJlhLabelInputController;
use App\Http\Controllers\RekapHasilSawmillPerMejaController;
use App\Http\Controllers\RekapHasilSawmillPerMejaUpahBoronganController;
use App\Http\Controllers\RekapHasilSawmillPerMejaUpahBoronganV2Controller;
use App\Http\Controllers\RekapKamarKdController;
use App\Http\Controllers\RekapMutasiController;
use App\Http\Controllers\RekapMutasiCrossTabController;
use App\Http\Controllers\RekapPcsTellyHasilSawmillController;
use App\Http\Controllers\RekapPembelianKayuBulatController;
use App\Http\Controllers\RekapPembelianKayuBulatKgController;
use App\Http\Controllers\RekapPenerimaanSTDariSawmillKgController;
use App\Http\Controllers\RekapPenerimaanSTDariSawmillNonRambungController;
use App\Http\Controllers\RekapPenjualanEksporPerBuyerPerProdukController;
use App\Http\Controllers\RekapPenjualanEksporPerProdukPerBuyerController;
use App\Http\Controllers\RekapPenjualanPerProdukController;
use App\Http\Controllers\RekapProduksiBarangJadiConsolidatedController;
use App\Http\Controllers\RekapProduksiCrossCutAkhirConsolidatedController;
use App\Http\Controllers\RekapProduksiCrossCutAkhirPerJenisPerGradeController;
use App\Http\Controllers\RekapProduksiFingerJointConsolidatedController;
use App\Http\Controllers\RekapProduksiFingerJointPerJenisPerGradeController;
use App\Http\Controllers\RekapProduksiLaminatingConsolidatedController;
use App\Http\Controllers\RekapProduksiLaminatingPerJenisPerGradeController;
use App\Http\Controllers\RekapProduksiMouldingConsolidatedController;
use App\Http\Controllers\RekapProduksiMouldingPerJenisPerGradeController;
use App\Http\Controllers\RekapProduksiPackingPerJenisPerGradeController;
use App\Http\Controllers\RekapProduksiS4SConsolidatedController;
use App\Http\Controllers\RekapProduksiS4SPerJenisPerGradeController;
use App\Http\Controllers\RekapProduksiS4sRambungPerGradeController;
use App\Http\Controllers\RekapProduksiSandingConsolidatedController;
use App\Http\Controllers\RekapProduksiSandingPerJenisPerGradeController;
use App\Http\Controllers\RekapProduktivitasSawmillRpController;
use App\Http\Controllers\RekapProduktivitasSawmillSawnTimberController;
use App\Http\Controllers\RekapRendemenNonRambungController;
use App\Http\Controllers\RekapRendemenRambungController;
use App\Http\Controllers\RekapRendemenRambungPerSupplierController;
use App\Http\Controllers\RekapStockOnHandController;
use App\Http\Controllers\RekapStPenjualanController;
use App\Http\Controllers\RendemenSemuaProsesController;
use App\Http\Controllers\ReprosesHidupDetailController;
use App\Http\Controllers\S4SHidupDetailController;
use App\Http\Controllers\SaldoBarangJadiHidupPerJenisPerProdukController;
use App\Http\Controllers\SaldoHidupKayuBulatKgController;
use App\Http\Controllers\SaldoKayuBulatController;
use App\Http\Controllers\SaldoStHidupPerProdukController;
use App\Http\Controllers\SandingHidupDetailController;
use App\Http\Controllers\SerahTerimaStKamarKdController;
use App\Http\Controllers\SpkSawmillController;
use App\Http\Controllers\StBasahHidupPerUmurKayuTonController;
use App\Http\Controllers\StHidupKeringController;
use App\Http\Controllers\StHidupPerSpkController;
use App\Http\Controllers\StockHidupPerNoSpkController;
use App\Http\Controllers\StockHidupPerNoSpkDiscrepancyController;
use App\Http\Controllers\StockOpnameKayuBulatController;
use App\Http\Controllers\StockRacipKayuLatController;
use App\Http\Controllers\StockSTBasahController;
use App\Http\Controllers\StockSTKeringController;
use App\Http\Controllers\StokOpnameStDetailKdController;
use App\Http\Controllers\StRambungMc1Mc2DetailController;
use App\Http\Controllers\StRambungMc1Mc2RangkumanController;
use App\Http\Controllers\StSawmillHariTebalLebarController;
use App\Http\Controllers\StSawmillMasukPerGroupController;
use App\Http\Controllers\StSawmillMasukPerGroupMejaController;
use App\Http\Controllers\SupplierIntelController;
use App\Http\Controllers\SuratJalanController;
use App\Http\Controllers\TargetMasukBBBulananController;
use App\Http\Controllers\TargetMasukBBController;
use App\Http\Controllers\TimelineKayuBulatBulananController;
use App\Http\Controllers\TimelineKayuBulatBulananKgController;
use App\Http\Controllers\TimelineKayuBulatHarianController;
use App\Http\Controllers\TimelineKayuBulatHarianKgController;
use App\Http\Controllers\TimelineRekapPenjualanPerProdukController;
use App\Http\Controllers\TotalBagusKulitRambungController;
use App\Http\Controllers\TracingStController;
use App\Http\Controllers\UmurBarangJadiDetailController;
use App\Http\Controllers\UmurCrossCutAkhirDetailController;
use App\Http\Controllers\UmurFingerJointDetailController;
use App\Http\Controllers\UmurKayuBulatNonRambungController;
use App\Http\Controllers\UmurKayuBulatRambungController;
use App\Http\Controllers\UmurLaminatingDetailController;
use App\Http\Controllers\UmurMouldingDetailController;
use App\Http\Controllers\UmurReprosesDetailController;
use App\Http\Controllers\UmurS4SDetailController;
use App\Http\Controllers\UmurSandingDetailController;
use App\Http\Controllers\UmurSawnTimberDetailTonController;
use Illuminate\Support\Facades\Route;

Route::get('/openapi.json', [OpenApiController::class, 'index'])->name('api.openapi');

Route::post(
    '/internal/ascends/ru/hrm/list-karyawan/pdf',
    [AscendXmlTestController::class, 'apiPdf']
)->name('api.internal.ascends.ru.hrm.list-karyawan.pdf');

Route::post(
    '/internal/ascends/uc/hrm/list-karyawan/pdf',
    [AscendXmlTestController::class, 'apiUcListKaryawanPdf']
)->name('api.internal.ascends.uc.hrm.list-karyawan.pdf');

Route::post(
    '/internal/ascends/uc/hrm/karyawan-aktif-per-departemen/pdf',
    [AscendXmlTestController::class, 'apiUcKaryawanAktifPerDepartemenPdf']
)->name('api.internal.ascends.uc.hrm.karyawan-aktif-per-departemen.pdf');

Route::post(
    '/internal/ascends/uc/hrm/daftar-karyawan/pdf',
    [AscendXmlTestController::class, 'apiUcDaftarKaryawanPdf']
)->name('api.internal.ascends.uc.hrm.daftar-karyawan.pdf');

Route::post(
    '/internal/ascends/uc/hrm/daftar-karyawan-berdasarkan-abjad/pdf',
    [AscendXmlTestController::class, 'apiUcDaftarKaryawanBerdasarkanAbjadPdf']
)->name('api.internal.ascends.uc.hrm.daftar-karyawan-berdasarkan-abjad.pdf');

Route::post(
    '/internal/ascends/uc/hrm/data-karyawan-status-kerja/pdf',
    [AscendXmlTestController::class, 'apiUcDataKaryawanStatusKerjaPdf']
)->name('api.internal.ascends.uc.hrm.data-karyawan-status-kerja.pdf');

Route::post(
    '/internal/ascends/uc/hrm/karyawan-masuk-per-departemen-per-tanggal-masuk/pdf',
    [AscendXmlTestController::class, 'apiUcKaryawanMasukPerDepartemenPerTanggalMasukPdf']
)->name('api.internal.ascends.uc.hrm.karyawan-masuk-per-departemen-per-tanggal-masuk.pdf');

Route::post(
    '/internal/ascends/shared/hrm/karyawan-masuk-per-departemen-per-tanggal-masuk/pdf',
    [AscendXmlTestController::class, 'apiSharedHrmKaryawanMasukPerDepartemenPerTanggalMasukPdf']
)->name('api.internal.ascends.shared.hrm.karyawan-masuk-per-departemen-per-tanggal-masuk.pdf');

Route::post(
    '/internal/ascends/shared/hrm/{report}/pdf',
    [AscendXmlTestController::class, 'apiSharedHrmReportPdf']
)
    ->whereIn('report', [
        'list-karyawan',
        'daftar-karyawan',
        'daftar-karyawan-berdasarkan-abjad',
        'data-karyawan-status-kerja',
        'karyawan-aktif-per-departemen',
        'karyawan-masuk-per-departemen-per-tanggal-masuk',
        'karyawan-per-agama',
        'karyawan-per-departemen-per-jabatan',
        'karyawan-per-etnis',
        'karyawan-per-level',
        'karyawan-per-masa-kerja',
        'karyawan-per-umur',
        'perbandingan-jumlah-karyawan-tahunan-per-bulan',
        'usia-generasi-tahun-kelahiran-masa-kerja',
    ])
    ->name('api.internal.ascends.shared.hrm.report.pdf');

Route::post(
    '/internal/ascends/gsu/hrm/list-karyawan/pdf',
    [AscendXmlTestController::class, 'apiGsuListKaryawanPdf']
)->name('api.internal.ascends.gsu.hrm.list-karyawan.pdf');

Route::post(
    '/internal/ascends/ru/hrm/karyawan-per-masa-kerja/pdf',
    [AscendXmlTestController::class, 'apiKaryawanPerMasaKerjaPdf']
)->name('api.internal.ascends.ru.hrm.karyawan-per-masa-kerja.pdf');

Route::post(
    '/internal/ascends/ru/hrm/data-karyawan-status-kerja/pdf',
    [AscendXmlTestController::class, 'apiDataKaryawanStatusKerjaPdf']
)->name('api.internal.ascends.ru.hrm.data-karyawan-status-kerja.pdf');

Route::post(
    '/internal/ascends/ru/hrm/daftar-karyawan-berdasarkan-abjad/pdf',
    [AscendXmlTestController::class, 'apiDaftarKaryawanBerdasarkanAbjadPdf']
)->name('api.internal.ascends.ru.hrm.daftar-karyawan-berdasarkan-abjad.pdf');

Route::post(
    '/internal/ascends/ru/hrm/daftar-karyawan/pdf',
    [AscendXmlTestController::class, 'apiDaftarKaryawanPdf']
)->name('api.internal.ascends.ru.hrm.daftar-karyawan.pdf');

Route::post(
    '/internal/ascends/ru/hrm/karyawan-aktif-per-departemen/pdf',
    [AscendXmlTestController::class, 'apiKaryawanAktifPerDepartemenPdf']
)->name('api.internal.ascends.ru.hrm.karyawan-aktif-per-departemen.pdf');

Route::post(
    '/internal/ascends/ru/hrm/karyawan-per-agama/pdf',
    [AscendXmlTestController::class, 'apiKaryawanPerAgamaPdf']
)->name('api.internal.ascends.ru.hrm.karyawan-per-agama.pdf');

Route::post(
    '/internal/ascends/ru/hrm/karyawan-per-etnis/pdf',
    [AscendXmlTestController::class, 'apiKaryawanPerEtnisPdf']
)->name('api.internal.ascends.ru.hrm.karyawan-per-etnis.pdf');

Route::post(
    '/internal/ascends/ru/hrm/karyawan-per-level/pdf',
    [AscendXmlTestController::class, 'apiKaryawanPerLevelPdf']
)->name('api.internal.ascends.ru.hrm.karyawan-per-level.pdf');

Route::post(
    '/internal/ascends/ru/hrm/karyawan-per-umur/pdf',
    [AscendXmlTestController::class, 'apiKaryawanPerUmurPdf']
)->name('api.internal.ascends.ru.hrm.karyawan-per-umur.pdf');

Route::post(
    '/internal/ascends/ru/hrm/karyawan-per-departemen-per-jabatan/pdf',
    [AscendXmlTestController::class, 'apiKaryawanPerDepartemenPerJabatanPdf']
)->name('api.internal.ascends.ru.hrm.karyawan-per-departemen-per-jabatan.pdf');

Route::post(
    '/internal/ascends/ru/sales/sales-invoice/pdf',
    [AscendXmlTestController::class, 'apiSalesInvoicePdf']
)->name('api.internal.ascends.ru.sales.sales-invoice.pdf');

Route::post(
    '/internal/ascends/ru/sales/sales-invoice/panjang/pdf',
    [AscendXmlTestController::class, 'apiSalesInvoicePanjangPdf']
)->name('api.internal.ascends.ru.sales.sales-invoice.panjang.pdf');

Route::post(
    '/internal/ascends/ru/sales/sales-invoice/normal/pdf',
    [AscendXmlTestController::class, 'apiSalesInvoiceNormalPdf']
)->name('api.internal.ascends.ru.sales.sales-invoice.normal.pdf');

Route::post(
    '/internal/ascends/gsu/sales/sales-invoice/pdf',
    [AscendXmlTestController::class, 'apiGsuSalesInvoicePdf']
)->name('api.internal.ascends.gsu.sales.sales-invoice.pdf');

Route::post(
    '/internal/ascends/gsu/sales/sales-invoice/panjang/pdf',
    [AscendXmlTestController::class, 'apiGsuSalesInvoicePanjangPdf']
)->name('api.internal.ascends.gsu.sales.sales-invoice.panjang.pdf');

Route::post(
    '/internal/ascends/gsu/sales/sales-invoice/normal/pdf',
    [AscendXmlTestController::class, 'apiGsuSalesInvoiceNormalPdf']
)->name('api.internal.ascends.gsu.sales.sales-invoice.normal.pdf');

Route::post(
    '/internal/ascends/ru/sales/surat-jalan/pdf',
    [AscendXmlTestController::class, 'apiSuratJalanPdf']
)->name('api.internal.ascends.ru.sales.surat-jalan.pdf');

Route::post(
    '/internal/ascends/ru/sales/surat-jalan/panjang/pdf',
    [AscendXmlTestController::class, 'apiSuratJalanPanjangPdf']
)->name('api.internal.ascends.ru.sales.surat-jalan.panjang.pdf');

Route::post(
    '/internal/ascends/ru/sales/surat-jalan/normal/pdf',
    [AscendXmlTestController::class, 'apiSuratJalanNormalPdf']
)->name('api.internal.ascends.ru.sales.surat-jalan.normal.pdf');

Route::post(
    '/internal/ascends/gsu/sales/surat-jalan/pdf',
    [AscendXmlTestController::class, 'apiGsuSuratJalanPdf']
)->name('api.internal.ascends.gsu.sales.surat-jalan.pdf');

Route::post(
    '/internal/ascends/gsu/sales/surat-jalan/panjang/pdf',
    [AscendXmlTestController::class, 'apiGsuSuratJalanPanjangPdf']
)->name('api.internal.ascends.gsu.sales.surat-jalan.panjang.pdf');

Route::post(
    '/internal/ascends/gsu/sales/surat-jalan/normal/pdf',
    [AscendXmlTestController::class, 'apiGsuSuratJalanNormalPdf']
)->name('api.internal.ascends.gsu.sales.surat-jalan.normal.pdf');

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
    Route::post('/reports/sawn-timber/label-st-hidup-detail/pdf/async', [LabelStHidupDetailController::class, 'apiDispatchAsync'])
        ->name('api.reports.sawn-timber.label-st-hidup-detail.async');
    Route::match(['get', 'post'], '/reports/sawn-timber/label-st-hidup-detail/pdf/async-wait', [LabelStHidupDetailController::class, 'apiDownloadWait'])
        ->name('api.reports.sawn-timber.label-st-hidup-detail.async-wait');
    Route::get('/reports/sawn-timber/label-st-hidup-detail/jobs/{jobId}/status', [LabelStHidupDetailController::class, 'apiAsyncStatus'])
        ->name('api.reports.sawn-timber.label-st-hidup-detail.async-status');
    Route::get('/reports/sawn-timber/label-st-hidup-detail/jobs/{jobId}/download', [LabelStHidupDetailController::class, 'apiAsyncDownload'])
        ->name('api.reports.sawn-timber.label-st-hidup-detail.async-download');
    Route::post('/reports/sawn-timber/stock-st-kering/pdf/async', [StockSTKeringController::class, 'apiDispatchAsync'])
        ->name('api.reports.sawn-timber.stock-st-kering.async');
    Route::match(['get', 'post'], '/reports/sawn-timber/stock-st-kering/pdf/async-wait', [StockSTKeringController::class, 'apiDownloadWait'])
        ->name('api.reports.sawn-timber.stock-st-kering.async-wait');
    Route::get('/reports/sawn-timber/stock-st-kering/jobs/{jobId}/status', [StockSTKeringController::class, 'apiAsyncStatus'])
        ->name('api.reports.sawn-timber.stock-st-kering.async-status');
    Route::get('/reports/sawn-timber/stock-st-kering/jobs/{jobId}/download', [StockSTKeringController::class, 'apiAsyncDownload'])
        ->name('api.reports.sawn-timber.stock-st-kering.async-download');

    Route::get('/reports/jobs/{jobId}/status', [PdfJobController::class, 'status'])->name('api.pdf-jobs.status');
    Route::get('/reports/jobs/{jobId}/download', [PdfJobController::class, 'download'])->name('api.pdf-jobs.download');
    Route::post('/reports/{reportPath}/pdf/async', [PdfJobController::class, 'dispatch'])
        ->where('reportPath', '.*')
        ->name('api.pdf-jobs.dispatch');

    //   @param  class-string  $controller
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
        ['/reports/kayu-bulat/penerimaan-kayu-bulat-int-ton', 'api.reports.kayu-bulat.penerimaan-kayu-bulat-int-ton', PenerimaanKayuBulatIntTonController::class],
        ['/reports/kayu-bulat/penerimaan-kayu-bulat-ext-ton', 'api.reports.kayu-bulat.penerimaan-kayu-bulat-ext-ton', PenerimaanKayuBulatExtTonController::class],
        ['/reports/kayu-bulat/penerimaan-kayu-bulat-kg', 'api.reports.kayu-bulat.penerimaan-kayu-bulat-kg', PenerimaanKayuBulatKgController::class],
        ['/reports/kayu-bulat/penerimaan-kayu-bulat-extkg', 'api.reports.kayu-bulat.penerimaan-kayu-bulat-extkg', PenerimaanKayuBulatExtKgController::class],
        ['/reports/kayu-bulat/saldo-hidup-kg', 'api.reports.kayu-bulat.saldo-hidup-kg', SaldoHidupKayuBulatKgController::class],
        ['/reports/kayu-bulat/rekap-pembelian-kg', 'api.reports.kayu-bulat.rekap-pembelian-kg', RekapPembelianKayuBulatKgController::class],
        ['/reports/kayu-bulat/rekap-pembelian', 'api.reports.kayu-bulat.rekap-pembelian', RekapPembelianKayuBulatController::class],
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
        ['/reports/kayu-bulat/target-masuk-bb', 'api.reports.kayu-bulat.target-masuk-bb', TargetMasukBBController::class],
        ['/reports/kayu-bulat/target-masuk-bb-bulanan', 'api.reports.kayu-bulat.target-masuk-bb-bulanan', TargetMasukBBBulananController::class],
        ['/reports/kayu-bulat/umur-kayu-bulat-non-rambung', 'api.reports.kayu-bulat.umur-kayu-bulat-non-rambung', UmurKayuBulatNonRambungController::class],
        ['/reports/kayu-bulat/umur-kayu-bulat-rambung', 'api.reports.kayu-bulat.umur-kayu-bulat-rambung', UmurKayuBulatRambungController::class],
        ['/reports/kayu-bulat/rekap-rendemen-rambung-per-supplier', 'api.reports.kayu-bulat.rekap-rendemen-rambung-per-supplier', RekapRendemenRambungPerSupplierController::class],
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
        ['/reports/sawn-timber/kd-upah-per-customer', 'api.reports.sawn-timber.kd-upah-per-customer', KdUpahPerCustomerController::class],
        ['/reports/sawn-timber/kd-upah-per-no-proc-kd-per-customer-detail', 'api.reports.sawn-timber.kd-upah-per-no-proc-kd-per-customer-detail', KdUpahPerNoProcKdPerCustomerDetailController::class],
        ['/reports/sawn-timber/serah-terima-st-kamar-kd', 'api.reports.sawn-timber.serah-terima-st-kamar-kd', SerahTerimaStKamarKdController::class],
        ['/reports/sawn-timber/rekap-kamar-kd', 'api.reports.sawn-timber.rekap-kamar-kd', RekapKamarKdController::class],
        ['/reports/sawn-timber/mutasi-kd', 'api.reports.sawn-timber.mutasi-kd', MutasiKdController::class],
        ['/reports/sawn-timber/rekap-st-penjualan', 'api.reports.sawn-timber.rekap-st-penjualan', RekapStPenjualanController::class],
        ['/reports/sawn-timber/pembelian-st-per-supplier-ton', 'api.reports.sawn-timber.pembelian-st-per-supplier-ton', PembelianStPerSupplierTonController::class],
        ['/reports/sawn-timber/pembelian-st-timeline-ton', 'api.reports.sawn-timber.pembelian-st-timeline-ton', PembelianStTimelineTonController::class],
        ['/reports/sawn-timber/label-st-hidup-detail', 'api.reports.sawn-timber.label-st-hidup-detail', LabelStHidupDetailController::class],
        ['/reports/sawn-timber/ketahanan-barang-st', 'api.reports.sawn-timber.ketahanan-barang-st', KetahananBarangDagangStController::class],
        ['/reports/sawn-timber/st-rambung-mc1-mc2-detail', 'api.reports.sawn-timber.st-rambung-mc1-mc2-detail', StRambungMc1Mc2DetailController::class],
        ['/reports/sawn-timber/st-rambung-mc1-mc2-rangkuman', 'api.reports.sawn-timber.st-rambung-mc1-mc2-rangkuman', StRambungMc1Mc2RangkumanController::class],
        ['/reports/sawn-timber/stok-opname-st-detail-kd', 'api.reports.sawn-timber.stok-opname-st-detail-kd', StokOpnameStDetailKdController::class],
        ['/reports/sawn-timber/st-hidup-kering', 'api.reports.sawn-timber.st-hidup-kering', StHidupKeringController::class],
        ['/reports/sawn-timber/penerimaan-st-dari-sawmill-kg', 'api.reports.sawn-timber.penerimaan-st-dari-sawmill-kg', PenerimaanStSawmillKgController::class],
        ['/reports/sawn-timber/penerimaan-st-hasil-sawmill', 'api.reports.sawn-timber.penerimaan-st-hasil-sawmill', PenerimaanStHasilSawmillController::class],
        ['/reports/sawn-timber/rekap-penerimaan-st-dari-sawmill-non-rambung', 'api.reports.sawn-timber.rekap-penerimaan-st-dari-sawmill-non-rambung', RekapPenerimaanSTDariSawmillNonRambungController::class],
        ['/reports/sawn-timber/lembar-tally-hasil-sawmill', 'api.reports.sawn-timber.lembar-tally-hasil-sawmill', LembarTallyHasilSawmillController::class],
        ['/reports/sawn-timber/detail-lembar-tally-hasil-sawmill', 'api.reports.sawn-timber.detail-lembar-tally-hasil-sawmill', DetailLembarTallyHasilSawmillController::class],
        ['/reports/sawn-timber/rekap-pcs-telly-hasil-sawmill', 'api.reports.sawn-timber.rekap-pcs-telly-hasil-sawmill', RekapPcsTellyHasilSawmillController::class],
        ['/reports/sawn-timber/tracing-st', 'api.reports.sawn-timber.tracing-st', TracingStController::class],
        ['/reports/sawn-timber/total-bagus-kulit-rambung', 'api.reports.sawn-timber.total-bagus-kulit-rambung', TotalBagusKulitRambungController::class],
        ['/reports/sawn-timber/qc-sawmill', 'api.reports.sawn-timber.qc-sawmill', QcSawmillController::class],
        ['/reports/sawn-timber/qc-sawmill-discrepancy', 'api.reports.sawn-timber.qc-sawmill-discrepancy', QcSawmillDiscrepancyController::class],
        ['/reports/sawn-timber/qc-sawmill-summary', 'api.reports.sawn-timber.qc-sawmill-summary', QcSawmillSummaryController::class],
        ['/reports/sawn-timber/rekap-hasil-sawmill-per-meja-upah-borongan', 'api.reports.sawn-timber.rekap-hasil-sawmill-per-meja-upah-borongan', RekapHasilSawmillPerMejaUpahBoronganController::class],
        ['/reports/sawn-timber/rekap-hasil-sawmill-per-meja', 'api.reports.sawn-timber.rekap-hasil-sawmill-per-meja', RekapHasilSawmillPerMejaController::class],
        ['/reports/sawn-timber/rekap-produktivitas-sawmill', 'api.reports.sawn-timber.rekap-produktivitas-sawmill', RekapProduktivitasSawmillSawnTimberController::class],
        ['/reports/sawn-timber/pemakaian-obat-vacuum', 'api.reports.sawn-timber.pemakaian-obat-vacuum', PemakaianObatVacuumController::class],
        ['/reports/sawn-timber/st-sawmill-hari-tebal-lebar', 'api.reports.sawn-timber.st-sawmill-hari-tebal-lebar', StSawmillHariTebalLebarController::class],
        ['/reports/sawn-timber/umur-sawn-timber-detail-ton', 'api.reports.sawn-timber.umur-sawn-timber-detail-ton', UmurSawnTimberDetailTonController::class],
        ['/reports/sawn-timber/st-sawmill-masuk-per-group', 'api.reports.sawn-timber.st-sawmill-masuk-per-group', StSawmillMasukPerGroupController::class],
        ['/reports/sawn-timber/st-sawmill-masuk-per-group-meja', 'api.reports.sawn-timber.st-sawmill-masuk-per-group-meja', StSawmillMasukPerGroupMejaController::class],
        ['/reports/sawn-timber/dashboard-sawn-timber', 'api.reports.sawn-timber.dashboard-sawn-timber', DashboardSawnTimberController::class],
        ['/reports/sawn-timber/saldo-st-hidup-per-produk', 'api.reports.sawn-timber.saldo-st-hidup-per-produk', SaldoStHidupPerProdukController::class],
        ['/reports/sawn-timber/st-hidup-per-spk', 'api.reports.sawn-timber.st-hidup-per-spk', StHidupPerSpkController::class],
        ['/reports/spk/spk-sawmill', 'api.reports.spk.spk-sawmill', SpkSawmillController::class],
    ];

    /**
     * Standalone report API routes.
     *
     * @var array<int, array{0: string, 1: string, 2: class-string}>
     */
    $standaloneReportRouteDefinitions = [
        ['/reports/ascends/ru/hrm/employee-list/list-karyawan', 'api.reports.ascends.ru.hrm.employee-list.list-karyawan', EmployeeListController::class],
        ['/reports/hasil-output-racip-harian', 'api.reports.hasil-output-racip-harian', HasilOutputRacipHarianController::class],
        ['/reports/stock-racip-kayu-lat', 'api.reports.stock-racip-kayu-lat', StockRacipKayuLatController::class],
        ['/reports/mutasi-racip-detail', 'api.reports.mutasi-racip-detail', MutasiRacipDetailController::class],
        ['/reports/rangkuman-label-input', 'api.reports.rangkuman-label-input', RangkumanJlhLabelInputController::class],
        ['/reports/mutasi-hasil-racip', 'api.reports.mutasi-hasil-racip', MutasiHasilRacipController::class],
        ['/reports/label-nyangkut', 'api.reports.label-nyangkut', LabelNyangkutController::class],
        ['/reports/bahan-terpakai', 'api.reports.bahan-terpakai', BahanTerpakaiController::class],
        ['/reports/management/stock-hidup-per-nospk', 'api.reports.management.stock-hidup-per-nospk', StockHidupPerNoSpkController::class],
        ['/reports/management/stock-hidup-per-nospk-discrepancy', 'api.reports.management.stock-hidup-per-nospk-discrepancy', StockHidupPerNoSpkDiscrepancyController::class],
        ['/reports/management/discrepancy-rekap-mutasi', 'api.reports.management.discrepancy-rekap-mutasi', DiscrepancyRekapMutasiController::class],
        ['/reports/management/rekap-mutasi', 'api.reports.management.rekap-mutasi', RekapMutasiController::class],
        ['/reports/management/rekap-mutasi-cross-tab', 'api.reports.management.rekap-mutasi-cross-tab', RekapMutasiCrossTabController::class],
        ['/reports/management/flow-produksi-per-periode', 'api.reports.management.flow-produksi-per-periode', FlowProduksiPerPeriodeController::class],
        ['/reports/management/dashboard-ru', 'api.reports.management.dashboard-ru', DashboardRuController::class],
        ['/reports/management/produksi-semua-mesin', 'api.reports.management.produksi-semua-mesin', ProduksiSemuaMesinController::class],
        ['/reports/management/produksi-hulu-hilir', 'api.reports.management.produksi-hulu-hilir', ProduksiHuluHilirController::class],
        ['/reports/management/hasil-produksi-mesin-lembur-dan-non-lembur', 'api.reports.management.hasil-produksi-mesin-lembur-dan-non-lembur', HasilProduksiMesinLemburDanNonLemburController::class],
        ['/reports/proses-produksi/produksi-per-nomor-produksi', 'api.reports.proses-produksi.produksi-per-nomor-produksi', ProduksiPerNomorProduksiController::class],
        ['/reports/proses-produksi/produksi-fj-per-nomor-produksi', 'api.reports.proses-produksi.produksi-fj-per-nomor-produksi', ProduksiFjPerNomorProduksiController::class],
        ['/reports/proses-produksi/produksi-laminating-per-nomor-produksi', 'api.reports.proses-produksi.produksi-laminating-per-nomor-produksi', ProduksiLaminatingPerNomorProduksiController::class],
        ['/reports/proses-produksi/produksi-moulding-per-nomor-produksi', 'api.reports.proses-produksi.produksi-moulding-per-nomor-produksi', ProduksiMouldingPerNomorProduksiController::class],
        ['/reports/proses-produksi/produksi-packing-per-nomor-produksi', 'api.reports.proses-produksi.produksi-packing-per-nomor-produksi', ProduksiPackingPerNomorProduksiController::class],
        ['/reports/proses-produksi/produksi-s4s-per-nomor-produksi', 'api.reports.proses-produksi.produksi-s4s-per-nomor-produksi', ProduksiS4sPerNomorProduksiController::class],
        ['/reports/proses-produksi/produksi-sanding-per-nomor-produksi', 'api.reports.proses-produksi.produksi-sanding-per-nomor-produksi', ProduksiSandingPerNomorProduksiController::class],
        ['/reports/management/label-perhari', 'api.reports.management.label-perhari', LabelPerhariController::class],
        ['/reports/management/rekap-stock-on-hand', 'api.reports.management.rekap-stock-on-hand', RekapStockOnHandController::class],
        ['/reports/verifikasi/rangkuman-bongkar-susun', 'api.reports.verifikasi.rangkuman-bongkar-susun', RangkumanBongkarSusunController::class],
        ['/reports/verifikasi/bahan-yang-dihasilkan', 'api.reports.verifikasi.bahan-yang-dihasilkan', BahanYangDihasilkanController::class],
        ['/reports/verifikasi/kapasitas-racip-kayu-bulat-hidup', 'api.reports.verifikasi.kapasitas-racip-kayu-bulat-hidup', KapasitasRacipKayuBulatHidupController::class],
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
        ['/reports/rendemen-kayu/rekap-rendemen-rambung', 'api.reports.rendemen-kayu.rekap-rendemen-rambung', RekapRendemenRambungController::class],
        ['/reports/rendemen-kayu/rendemen-semua-proses', 'api.reports.rendemen-kayu.rendemen-semua-proses', RendemenSemuaProsesController::class],
        ['/reports/rendemen-kayu/produksi-per-spk', 'api.reports.rendemen-kayu.produksi-per-spk', ProduksiPerSpkController::class],
        ['/reports/penjualan/penjualan-barang-jadi-m3', 'api.reports.penjualan.penjualan-barang-jadi-m3', PenjualanBarangJadiM3Controller::class],
        ['/reports/penjualan/surat-jalan', 'api.reports.penjualan.surat-jalan', SuratJalanController::class],
        ['/reports/penjualan-kayu/penjualan-lokal', 'api.reports.penjualan-kayu.penjualan-lokal', PenjualanLokalController::class],
        ['/reports/penjualan-kayu/koordinat-tanah', 'api.reports.penjualan-kayu.koordinat-tanah', KoordinatTanahController::class],
        ['/reports/penjualan-kayu/rekap-penjualan-per-produk', 'api.reports.penjualan-kayu.rekap-penjualan-per-produk', RekapPenjualanPerProdukController::class],
        ['/reports/penjualan-kayu/rekap-penjualan-ekspor-per-produk-per-buyer', 'api.reports.penjualan-kayu.rekap-penjualan-ekspor-per-produk-per-buyer', RekapPenjualanEksporPerProdukPerBuyerController::class],
        ['/reports/penjualan-kayu/rekap-penjualan-ekspor-per-buyer-per-produk', 'api.reports.penjualan-kayu.rekap-penjualan-ekspor-per-buyer-per-produk', RekapPenjualanEksporPerBuyerPerProdukController::class],
        ['/reports/penjualan-kayu/timeline-rekap-penjualan-per-produk', 'api.reports.penjualan-kayu.timeline-rekap-penjualan-per-produk', TimelineRekapPenjualanPerProdukController::class],
        ['/reports/pps/rekap-produksi/inject', 'api.reports.pps.rekap-produksi.inject', RekapProduksiInjectController::class],
        ['/reports/pps/rekap-produksi/inject-bj', 'api.reports.pps.rekap-produksi.inject-bj', RekapProduksiInjectBjController::class],
        ['/reports/pps/rekap-produksi/hot-stamping-fwip', 'api.reports.pps.rekap-produksi.hot-stamping-fwip', RekapProduksiHotStampingFwipController::class],
        ['/reports/pps/rekap-produksi/packing-bj', 'api.reports.pps.rekap-produksi.packing-bj', RekapProduksiPackingBjController::class],
        ['/reports/pps/rekap-produksi/pasang-kunci-fwip', 'api.reports.pps.rekap-produksi.pasang-kunci-fwip', RekapProduksiPasangKunciFwipController::class],
        ['/reports/pps/rekap-produksi/spanner-fwip', 'api.reports.pps.rekap-produksi.spanner-fwip', RekapProduksiSpannerFwipController::class],
        ['/reports/pps/rekap-produksi/broker', 'api.reports.pps.rekap-produksi.broker', RekapProduksiBrokerController::class],
        ['/reports/pps/rekap-produksi/washing', 'api.reports.pps.rekap-produksi.washing', RekapProduksiWashingController::class],
        ['/reports/pps/washing/washing-produksi', 'api.reports.pps.washing.washing-produksi', HasilProduksiHarianWashingProduksiController::class],
        ['/reports/pps/broker/broker-produksi', 'api.reports.pps.broker.broker-produksi', HasilProduksiHarianBrokerProduksiController::class],
        ['/reports/pps/crusher/crusher-produksi', 'api.reports.pps.crusher.crusher-produksi', HasilProduksiHarianCrusherProduksiController::class],
        ['/reports/pps/gilingan/gilingan-produksi', 'api.reports.pps.gilingan.gilingan-produksi', HasilProduksiHarianGilinganProduksiController::class],
        ['/reports/pps/inject/hot-stamping/hot-stamping-produksi', 'api.reports.pps.inject.hot-stamping.hot-stamping-produksi', HasilProduksiHarianHotStampingProduksiController::class],
        ['/reports/pps/inject/inject-produksi', 'api.reports.pps.inject.inject-produksi', HasilProduksiHarianInjectProduksiController::class],
        ['/reports/pps/inject/pasang-kunci/pasang-kunci-produksi', 'api.reports.pps.inject.pasang-kunci.pasang-kunci-produksi', HasilProduksiHarianPasangKunciProduksiController::class],
        ['/reports/pps/rekap-produksi/mixer', 'api.reports.pps.rekap-produksi.mixer', RekapProduksiMixerController::class],
        ['/reports/pps/rekap-produksi/gilingan', 'api.reports.pps.rekap-produksi.gilingan', RekapProduksiGilinganController::class],
        ['/reports/pps/rekap-produksi/crusher', 'api.reports.pps.rekap-produksi.crusher', RekapProduksiCrusherController::class],
        ['/reports/pps/semua-label', 'api.reports.pps.semua-label', SemuaLabelController::class],
        ['/reports/pps/bahan-baku/mutasi-bahan-baku', 'api.reports.pps.bahan-baku.mutasi-bahan-baku', MutasiBahanBakuController::class],
        ['/reports/pps/bahan-baku/stock-bahan-baku-v2', 'api.reports.pps.bahan-baku.stock-bahan-baku-v2', StockBahanBakuV2Controller::class],
        ['/reports/pps/barang-jadi/mutasi-barang-jadi', 'api.reports.pps.barang-jadi.mutasi-barang-jadi', MutasiBarangJadiPpsController::class],
        ['/reports/pps/barang-jadi/stock-label-barang-jadi-v2', 'api.reports.pps.barang-jadi.stock-label-barang-jadi-v2', StockLabelBarangJadiV2Controller::class],
        ['/reports/pps/broker/mutasi-broker', 'api.reports.pps.broker.mutasi-broker', MutasiBrokerController::class],
        ['/reports/pps/broker/stock-broker', 'api.reports.pps.broker.stock-broker', StockBrokerController::class],
        ['/reports/pps/broker/stock-broker-v2', 'api.reports.pps.broker.stock-broker-v2', StockBrokerV2Controller::class],
        ['/reports/pps/bonggolan/mutasi-bonggolan', 'api.reports.pps.bonggolan.mutasi-bonggolan', MutasiBonggolanController::class],
        ['/reports/pps/bonggolan/stock-bonggolan', 'api.reports.pps.bonggolan.stock-bonggolan', StockBonggolanController::class],
        ['/reports/pps/bonggolan/stock-bonggolan-v2', 'api.reports.pps.bonggolan.stock-bonggolan-v2', StockBonggolanV2Controller::class],
        ['/reports/pps/crusher/mutasi-crusher', 'api.reports.pps.crusher.mutasi-crusher', MutasiCrusherController::class],
        ['/reports/pps/crusher/stock-crusher', 'api.reports.pps.crusher.stock-crusher', StockCrusherController::class],
        ['/reports/pps/crusher/stock-crusher-v2', 'api.reports.pps.crusher.stock-crusher-v2', StockCrusherV2Controller::class],
        ['/reports/pps/gilingan/mutasi-gilingan', 'api.reports.pps.gilingan.mutasi-gilingan', MutasiGilinganController::class],
        ['/reports/pps/gilingan/stock-gilingan', 'api.reports.pps.gilingan.stock-gilingan', StockGilinganController::class],
        ['/reports/pps/gilingan/stock-gilingan-v2', 'api.reports.pps.gilingan.stock-gilingan-v2', StockGilinganV2Controller::class],
        ['/reports/pps/mixer/mutasi-mixer', 'api.reports.pps.mixer.mutasi-mixer', MutasiMixerController::class],
        ['/reports/pps/mixer/stock-mixer', 'api.reports.pps.mixer.stock-mixer', StockMixerController::class],
        ['/reports/pps/mixer/stock-mixer-v2', 'api.reports.pps.mixer.stock-mixer-v2', StockMixerV2Controller::class],
        ['/reports/pps/reject/stock-reject', 'api.reports.pps.reject.stock-reject', StockRejectController::class],
        ['/reports/pps/washing/stock-washing', 'api.reports.pps.washing.stock-washing', StockWashingController::class],
        ['/reports/pps/washing/stock-washing-v2', 'api.reports.pps.washing.stock-washing-v2', StockWashingV2Controller::class],
        ['/reports/pps/qc/qc-harian-bahan-baku', 'api.reports.pps.qc.qc-harian-bahan-baku', QcHarianBahanBakuController::class],
        ['/reports/pps/qc/qc-harian-broker', 'api.reports.pps.qc.qc-harian-broker', QcHarianBrokerController::class],
        ['/reports/pps/qc/qc-harian-mixer', 'api.reports.pps.qc.qc-harian-mixer', QcHarianMixerController::class],
        ['/reports/pps/qc/qc-harian-washing', 'api.reports.pps.qc.qc-harian-washing', QcHarianWashingController::class],
        ['/reports/pps/furniture-wip/mutasi-furniture-wip', 'api.reports.pps.furniture-wip.mutasi-furniture-wip', MutasiFurnitureWipController::class],
        ['/reports/pps/furniture-wip/stock-furniture-wip-v2', 'api.reports.pps.furniture-wip.stock-furniture-wip-v2', StockFurnitureWipV2Controller::class],
        ['/reports/dashboard-barang-jadi', 'api.reports.dashboard-barang-jadi', DashboardBarangJadiController::class],
        ['/reports/dashboard-cross-cut-akhir', 'api.reports.dashboard-cross-cut-akhir', DashboardCrossCutAkhirController::class],
        ['/reports/dashboard-finger-joint', 'api.reports.dashboard-finger-joint', DashboardFingerJointController::class],
        ['/reports/dashboard-laminating', 'api.reports.dashboard-laminating', DashboardLaminatingController::class],
        ['/reports/dashboard-moulding', 'api.reports.dashboard-moulding', DashboardMouldingController::class],
        ['/reports/dashboard-reproses', 'api.reports.dashboard-reproses', DashboardReprosesController::class],
        ['/reports/dashboard-sanding', 'api.reports.dashboard-sanding', DashboardSandingController::class],
        ['/reports/dashboard-sawn-timber', 'api.reports.dashboard-sawn-timber', DashboardSawnTimberController::class],
        ['/reports/dashboard-s4s', 'api.reports.dashboard-s4s', DashboardS4SController::class],
        ['/reports/dashboard-s4s-v2', 'api.reports.dashboard-s4s-v2', DashboardS4SV2Controller::class],
        ['/reports/pps/mixer/mixer-produksi', 'api.reports.pps.mixer.mixer-produksi', HasilProduksiHarianMixerProduksiController::class],
        ['/reports/pps/packing/packing-produksi', 'api.reports.pps.packing.packing-produksi', HasilProduksiHarianPackingProduksiController::class],
        ['/reports/pps/spanner/spanner-produksi', 'api.reports.pps.spanner.spanner-produksi', HasilProduksiHarianSpannerProduksiController::class],
        ['/reports/pps/reject/mutasi-reject', 'api.reports.pps.reject.mutasi-reject', MutasiRejectController::class],
        ['/reports/finger-joint/rekap-produksi-finger-joint-per-jenis-per-grade', 'api.reports.finger-joint.rekap-produksi-finger-joint-per-jenis-per-grade', RekapProduksiFingerJointPerJenisPerGradeController::class],
        ['/reports/finger-joint/finger-joint-hidup-detail', 'api.reports.finger-joint.finger-joint-hidup-detail', FingerJointHidupDetailController::class],
        ['/reports/s4s/s4s-hidup-detail', 'api.reports.s4s.s4s-hidup-detail', S4SHidupDetailController::class],
        ['/reports/s4s/label-s4s-hidup-per-jenis-kayu', 'api.reports.s4s.label-s4s-hidup-per-jenis-kayu', LabelS4SHidupPerJenisKayuController::class],
        ['/reports/s4s/label-s4s-hidup-per-produk-per-jenis-kayu', 'api.reports.s4s.label-s4s-hidup-per-produk-per-jenis-kayu', LabelS4SHidupPerProdukPerJenisKayuController::class],
        ['/reports/s4s/rekap-produksi-s4s-per-jenis-per-grade', 'api.reports.s4s.rekap-produksi-s4s-per-jenis-per-grade', RekapProduksiS4SPerJenisPerGradeController::class],
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

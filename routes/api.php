<?php

use App\Http\Controllers\BahanTerpakaiController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OpenApiController;
use App\Http\Controllers\BalokSudahSemprotController;
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
use App\Http\Controllers\PenerimaanStSawmillKgController;
use App\Http\Controllers\MutasiS4SController;
use App\Http\Controllers\PerbandinganKbMasukPeriode1Dan2Controller;
use App\Http\Controllers\RangkumanJlhLabelInputController;
use App\Http\Controllers\SaldoKayuBulatController;
use App\Http\Controllers\StockSTBasahController;
use App\Http\Controllers\StockOpnameKayuBulatController;
use Illuminate\Support\Facades\Route;

Route::get('/openapi.json', [OpenApiController::class, 'index'])->name('api.openapi');

// Group route autentikasi API publik.
Route::prefix('auth')->group(function (): void {
    Route::post('/register', [AuthController::class, 'register'])->name('api.auth.register');
    Route::post('/login', [AuthController::class, 'login'])->name('api.auth.login');
    Route::post('/logout', [AuthController::class, 'logout'])->name('api.auth.logout');
    Route::post('/refresh', [AuthController::class, 'refresh'])->name('api.auth.refresh');

    // Group route autentikasi API yang memerlukan token JWT.
    Route::middleware('report.jwt.claims')->group(function (): void {
        Route::get('/me', [AuthController::class, 'me'])->name('api.auth.me');
    });
});

// Group route laporan yang hanya bisa diakses user terautentikasi.
Route::middleware('report.jwt.claims')->group(function (): void {
    /**
     * @param class-string $controller
     */
    $registerReportRoutes = static function (string $path, string $namePrefix, string $controller): void {
        Route::post($path, [$controller, 'preview'])->name("{$namePrefix}.preview");
        Route::match(['get', 'post'], "{$path}/pdf", [$controller, 'download'])->name("{$namePrefix}.pdf");
        Route::post("{$path}/health", [$controller, 'health'])->name("{$namePrefix}.health");
    };

    $reportRouteDefinitions = [
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
        ['/reports/kayu-bulat/saldo', 'api.reports.kayu-bulat.saldo', SaldoKayuBulatController::class],
        ['/reports/kayu-bulat/penerimaan-bulanan-per-supplier', 'api.reports.kayu-bulat.penerimaan-bulanan-per-supplier', PenerimaanKayuBulatBulananPerSupplierController::class],
        ['/reports/kayu-bulat/stock-opname', 'api.reports.kayu-bulat.stock-opname', StockOpnameKayuBulatController::class],
        ['/reports/sawn-timber/stock-st-basah', 'api.reports.sawn-timber.stock-st-basah', StockSTBasahController::class],
        ['/reports/sawn-timber/penerimaan-st-dari-sawmill-kg', 'api.reports.sawn-timber.penerimaan-st-dari-sawmill-kg', PenerimaanStSawmillKgController::class],
        ['/reports/sawn-timber/lembar-tally-hasil-sawmill', 'api.reports.sawn-timber.lembar-tally-hasil-sawmill', LembarTallyHasilSawmillController::class],
        ['/reports/kayu-bulat/hidup-per-group', 'api.reports.kayu-bulat.hidup-per-group', HidupKBPerGroupController::class],
        ['/reports/kayu-bulat/hidup', 'api.reports.kayu-bulat.hidup', KayuBulatHidupController::class],
        ['/reports/kayu-bulat/perbandingan-kb-masuk-periode-1-dan-2', 'api.reports.kayu-bulat.perbandingan-kb-masuk-periode-1-dan-2', PerbandinganKbMasukPeriode1Dan2Controller::class],
        ['/reports/kayu-bulat/kb-khusus-bangkang', 'api.reports.kayu-bulat.kb-khusus-bangkang', KbKhususBangkangController::class],
        ['/reports/kayu-bulat/balok-sudah-semprot', 'api.reports.kayu-bulat.balok-sudah-semprot', BalokSudahSemprotController::class],
        ['/reports/rangkuman-label-input', 'api.reports.rangkuman-label-input', RangkumanJlhLabelInputController::class],
        ['/reports/mutasi-hasil-racip', 'api.reports.mutasi-hasil-racip', MutasiHasilRacipController::class],
        ['/reports/label-nyangkut', 'api.reports.label-nyangkut', LabelNyangkutController::class],
        ['/reports/bahan-terpakai', 'api.reports.bahan-terpakai', BahanTerpakaiController::class],
    ];

    foreach ($reportRouteDefinitions as [$path, $namePrefix, $controller]) {
        $registerReportRoutes($path, $namePrefix, $controller);
    }
});

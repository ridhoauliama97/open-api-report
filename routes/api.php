<?php

use App\Http\Controllers\BahanTerpakaiController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OpenApiController;
use App\Http\Controllers\LabelNyangkutController;
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
use App\Http\Controllers\MutasiS4SController;
use App\Http\Controllers\RangkumanJlhLabelInputController;
use App\Http\Controllers\SaldoKayuBulatController;
use Illuminate\Support\Facades\Route;

Route::get('/openapi.json', [OpenApiController::class, 'index'])->name('api.openapi');

// Group route autentikasi API publik.
Route::prefix('auth')->group(function (): void {
    Route::post('/register', [AuthController::class, 'register'])->name('api.auth.register');
    Route::post('/login', [AuthController::class, 'login'])->name('api.auth.login');
    Route::post('/logout', [AuthController::class, 'logout'])->name('api.auth.logout');
    Route::post('/refresh', [AuthController::class, 'refresh'])->name('api.auth.refresh');

    // Group route autentikasi API yang memerlukan token JWT.
    Route::middleware('auth:api')->group(function (): void {
        Route::get('/me', [AuthController::class, 'me'])->name('api.auth.me');
    });
});

// Group route laporan yang hanya bisa diakses user terautentikasi.
Route::middleware('report.jwt.claims')->group(function (): void {
    //Mutasi Barang Jadi
    Route::post('/reports/mutasi-barang-jadi', [MutasiBarangJadiController::class, 'preview'])->name('api.reports.mutasi-barang-jadi.preview');
    Route::match(['get', 'post'], '/reports/mutasi-barang-jadi/pdf', [MutasiBarangJadiController::class, 'download'])->name('api.reports.mutasi-barang-jadi.pdf');
    Route::post('/reports/mutasi-barang-jadi/health', [MutasiBarangJadiController::class, 'health'])->name('api.reports.mutasi-barang-jadi.health');
    Route::post('/reports/mutasi-finger-joint', [MutasiFingerJointController::class, 'preview'])->name('api.reports.mutasi-finger-joint.preview');
    Route::match(['get', 'post'], '/reports/mutasi-finger-joint/pdf', [MutasiFingerJointController::class, 'download'])->name('api.reports.mutasi-finger-joint.pdf');
    Route::post('/reports/mutasi-finger-joint/health', [MutasiFingerJointController::class, 'health'])->name('api.reports.mutasi-finger-joint.health');
    Route::post('/reports/mutasi-moulding', [MutasiMouldingController::class, 'preview'])->name('api.reports.mutasi-moulding.preview');
    Route::match(['get', 'post'], '/reports/mutasi-moulding/pdf', [MutasiMouldingController::class, 'download'])->name('api.reports.mutasi-moulding.pdf');
    Route::post('/reports/mutasi-moulding/health', [MutasiMouldingController::class, 'health'])->name('api.reports.mutasi-moulding.health');
    Route::post('/reports/mutasi-laminating', [MutasiLaminatingController::class, 'preview'])->name('api.reports.mutasi-laminating.preview');
    Route::match(['get', 'post'], '/reports/mutasi-laminating/pdf', [MutasiLaminatingController::class, 'download'])->name('api.reports.mutasi-laminating.pdf');
    Route::post('/reports/mutasi-laminating/health', [MutasiLaminatingController::class, 'health'])->name('api.reports.mutasi-laminating.health');
    Route::post('/reports/mutasi-sanding', [MutasiSandingController::class, 'preview'])->name('api.reports.mutasi-sanding.preview');
    Route::match(['get', 'post'], '/reports/mutasi-sanding/pdf', [MutasiSandingController::class, 'download'])->name('api.reports.mutasi-sanding.pdf');
    Route::post('/reports/mutasi-sanding/health', [MutasiSandingController::class, 'health'])->name('api.reports.mutasi-sanding.health');
    Route::post('/reports/mutasi-s4s', [MutasiS4SController::class, 'preview'])->name('api.reports.mutasi-s4s.preview');
    Route::match(['get', 'post'], '/reports/mutasi-s4s/pdf', [MutasiS4SController::class, 'download'])->name('api.reports.mutasi-s4s.pdf');
    Route::post('/reports/mutasi-s4s/health', [MutasiS4SController::class, 'health'])->name('api.reports.mutasi-s4s.health');
    Route::post('/reports/mutasi-st', [MutasiSTController::class, 'preview'])->name('api.reports.mutasi-st.preview');
    Route::match(['get', 'post'], '/reports/mutasi-st/pdf', [MutasiSTController::class, 'download'])->name('api.reports.mutasi-st.pdf');
    Route::post('/reports/mutasi-st/health', [MutasiSTController::class, 'health'])->name('api.reports.mutasi-st.health');
    Route::post('/reports/mutasi-cca-akhir', [MutasiCCAkhirController::class, 'preview'])->name('api.reports.mutasi-cca-akhir.preview');
    Route::match(['get', 'post'], '/reports/mutasi-cca-akhir/pdf', [MutasiCCAkhirController::class, 'download'])->name('api.reports.mutasi-cca-akhir.pdf');
    Route::post('/reports/mutasi-cca-akhir/health', [MutasiCCAkhirController::class, 'health'])->name('api.reports.mutasi-cca-akhir.health');
    Route::post('/reports/mutasi-reproses', [MutasiReprosesController::class, 'preview'])->name('api.reports.mutasi-reproses.preview');
    Route::match(['get', 'post'], '/reports/mutasi-reproses/pdf', [MutasiReprosesController::class, 'download'])->name('api.reports.mutasi-reproses.pdf');
    Route::post('/reports/mutasi-reproses/health', [MutasiReprosesController::class, 'health'])->name('api.reports.mutasi-reproses.health');
    Route::post('/reports/mutasi-kayu-bulat', [MutasiKayuBulatController::class, 'preview'])->name('api.reports.mutasi-kayu-bulat.preview');
    Route::match(['get', 'post'], '/reports/mutasi-kayu-bulat/pdf', [MutasiKayuBulatController::class, 'download'])->name('api.reports.mutasi-kayu-bulat.pdf');
    Route::post('/reports/mutasi-kayu-bulat/health', [MutasiKayuBulatController::class, 'health'])->name('api.reports.mutasi-kayu-bulat.health');
    Route::post('/reports/mutasi-kayu-bulat-v2', [MutasiKayuBulatV2Controller::class, 'preview'])->name('api.reports.mutasi-kayu-bulat-v2.preview');
    Route::match(['get', 'post'], '/reports/mutasi-kayu-bulat-v2/pdf', [MutasiKayuBulatV2Controller::class, 'download'])->name('api.reports.mutasi-kayu-bulat-v2.pdf');
    Route::post('/reports/mutasi-kayu-bulat-v2/health', [MutasiKayuBulatV2Controller::class, 'health'])->name('api.reports.mutasi-kayu-bulat-v2.health');
    Route::post('/reports/mutasi-kayu-bulat-kgv2', [MutasiKayuBulatKGV2Controller::class, 'preview'])->name('api.reports.mutasi-kayu-bulat-kgv2.preview');
    Route::match(['get', 'post'], '/reports/mutasi-kayu-bulat-kgv2/pdf', [MutasiKayuBulatKGV2Controller::class, 'download'])->name('api.reports.mutasi-kayu-bulat-kgv2.pdf');
    Route::post('/reports/mutasi-kayu-bulat-kgv2/health', [MutasiKayuBulatKGV2Controller::class, 'health'])->name('api.reports.mutasi-kayu-bulat-kgv2.health');
    Route::post('/reports/mutasi-kayu-bulat-kg', [MutasiKayuBulatKGController::class, 'preview'])->name('api.reports.mutasi-kayu-bulat-kg.preview');
    Route::match(['get', 'post'], '/reports/mutasi-kayu-bulat-kg/pdf', [MutasiKayuBulatKGController::class, 'download'])->name('api.reports.mutasi-kayu-bulat-kg.pdf');
    Route::post('/reports/mutasi-kayu-bulat-kg/health', [MutasiKayuBulatKGController::class, 'health'])->name('api.reports.mutasi-kayu-bulat-kg.health');
    Route::post('/reports/kayu-bulat/saldo', [SaldoKayuBulatController::class, 'preview'])->name('api.reports.kayu-bulat.saldo.preview');
    Route::match(['get', 'post'], '/reports/kayu-bulat/saldo/pdf', [SaldoKayuBulatController::class, 'download'])->name('api.reports.kayu-bulat.saldo.pdf');
    Route::post('/reports/kayu-bulat/saldo/health', [SaldoKayuBulatController::class, 'health'])->name('api.reports.kayu-bulat.saldo.health');
    Route::post('/reports/rangkuman-label-input', [RangkumanJlhLabelInputController::class, 'preview'])->name('api.reports.rangkuman-label-input.preview');
    Route::match(['get', 'post'], '/reports/rangkuman-label-input/pdf', [RangkumanJlhLabelInputController::class, 'download'])->name('api.reports.rangkuman-label-input.pdf');
    Route::post('/reports/rangkuman-label-input/health', [RangkumanJlhLabelInputController::class, 'health'])->name('api.reports.rangkuman-label-input.health');
    Route::post('/reports/mutasi-hasil-racip', [MutasiHasilRacipController::class, 'preview'])->name('api.reports.mutasi-hasil-racip.preview');
    Route::match(['get', 'post'], '/reports/mutasi-hasil-racip/pdf', [MutasiHasilRacipController::class, 'download'])->name('api.reports.mutasi-hasil-racip.pdf');
    Route::post('/reports/mutasi-hasil-racip/health', [MutasiHasilRacipController::class, 'health'])->name('api.reports.mutasi-hasil-racip.health');
    Route::post('/reports/label-nyangkut', [LabelNyangkutController::class, 'preview'])->name('api.reports.label-nyangkut.preview');
    Route::match(['get', 'post'], '/reports/label-nyangkut/pdf', [LabelNyangkutController::class, 'download'])->name('api.reports.label-nyangkut.pdf');
    Route::post('/reports/label-nyangkut/health', [LabelNyangkutController::class, 'health'])->name('api.reports.label-nyangkut.health');
    Route::post('/reports/bahan-terpakai', [BahanTerpakaiController::class, 'preview'])->name('api.reports.bahan-terpakai.preview');
    Route::match(['get', 'post'], '/reports/bahan-terpakai/pdf', [BahanTerpakaiController::class, 'download'])->name('api.reports.bahan-terpakai.pdf');
    Route::post('/reports/bahan-terpakai/health', [BahanTerpakaiController::class, 'health'])->name('api.reports.bahan-terpakai.health');
});


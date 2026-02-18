<?php

use App\Http\Controllers\BahanTerpakaiController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OpenApiController;
use App\Http\Controllers\MutasiBarangJadiController;
use App\Http\Controllers\MutasiFingerJointController;
use App\Http\Controllers\MutasiMouldingController;
use App\Http\Controllers\MutasiS4SController;
use App\Http\Controllers\RangkumanJlhLabelInputController;
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
    Route::post('/reports/mutasi-s4s', [MutasiS4SController::class, 'preview'])->name('api.reports.mutasi-s4s.preview');
    Route::match(['get', 'post'], '/reports/mutasi-s4s/pdf', [MutasiS4SController::class, 'download'])->name('api.reports.mutasi-s4s.pdf');
    Route::post('/reports/mutasi-s4s/health', [MutasiS4SController::class, 'health'])->name('api.reports.mutasi-s4s.health');
    Route::post('/reports/rangkuman-label-input', [RangkumanJlhLabelInputController::class, 'preview'])->name('api.reports.rangkuman-label-input.preview');
    Route::match(['get', 'post'], '/reports/rangkuman-label-input/pdf', [RangkumanJlhLabelInputController::class, 'download'])->name('api.reports.rangkuman-label-input.pdf');
    Route::post('/reports/rangkuman-label-input/health', [RangkumanJlhLabelInputController::class, 'health'])->name('api.reports.rangkuman-label-input.health');
    Route::post('/reports/bahan-terpakai', [BahanTerpakaiController::class, 'preview'])->name('api.reports.bahan-terpakai.preview');
    Route::match(['get', 'post'], '/reports/bahan-terpakai/pdf', [BahanTerpakaiController::class, 'download'])->name('api.reports.bahan-terpakai.pdf');
    Route::post('/reports/bahan-terpakai/health', [BahanTerpakaiController::class, 'health'])->name('api.reports.bahan-terpakai.health');
});

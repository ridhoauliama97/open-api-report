<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OpenApiController;
use App\Http\Controllers\MutasiBarangJadiController;
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
});

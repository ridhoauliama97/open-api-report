<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OpenApiController;
use App\Http\Controllers\MutasiBarangJadiController;
use App\Http\Controllers\MutasiCrossCutController;
use App\Http\Controllers\SalesReportController;
use Illuminate\Support\Facades\Route;

Route::get('/openapi.json', [OpenApiController::class, 'index'])->name('api.openapi');

// Group endpoint autentikasi API publik.
// Group route autentikasi API publik.
Route::prefix('auth')->group(function (): void {
    Route::post('/register', [AuthController::class, 'register'])->name('api.auth.register');
    Route::post('/login', [AuthController::class, 'login'])->name('api.auth.login');
    Route::post('/logout', [AuthController::class, 'logout'])->name('api.auth.logout');
    Route::post('/refresh', [AuthController::class, 'refresh'])->name('api.auth.refresh');

    // Group endpoint autentikasi yang butuh token aktif.
    // Group seluruh endpoint laporan yang membutuhkan autentikasi API.
// Group route autentikasi API yang memerlukan token JWT.
Route::middleware('auth:api')->group(function (): void {
        Route::get('/me', [AuthController::class, 'me'])->name('api.auth.me');
    });
});

// Group seluruh endpoint laporan yang membutuhkan autentikasi API.
// Group route laporan yang hanya bisa diakses user terautentikasi.
Route::middleware('auth:api')->group(function (): void {
    Route::post('/reports/sales', [SalesReportController::class, 'preview'])->name('api.reports.sales.preview');
    Route::post('/reports/sales/pdf', [SalesReportController::class, 'download'])->name('api.reports.sales.pdf');

    //Mutasi Cross Cut
    Route::post('/reports/mutasi-cross-cut', [MutasiCrossCutController::class, 'preview'])->name('api.reports.mutasi-cross-cut.preview');
    Route::post('/reports/mutasi-cross-cut/pdf', [MutasiCrossCutController::class, 'download'])->name('api.reports.mutasi-cross-cut.pdf');

    //Mutasi Barang Jadi
    Route::post('/reports/mutasi-barang-jadi', [MutasiBarangJadiController::class, 'preview'])->name('api.reports.mutasi-barang-jadi.preview');
    Route::match(['get', 'post'], '/reports/mutasi-barang-jadi/pdf', [MutasiBarangJadiController::class, 'download'])->name('api.reports.mutasi-barang-jadi.pdf');
    Route::post('/reports/mutasi-barang-jadi/health', [MutasiBarangJadiController::class, 'health'])->name('api.reports.mutasi-barang-jadi.health');
});


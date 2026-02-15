<?php

use App\Http\Controllers\MutasiCrossCutController;
use App\Http\Controllers\SalesReportController;
use App\Http\Controllers\WebAuthController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');
Route::get('/reports/sales', [SalesReportController::class, 'index'])->name('reports.sales.index');
Route::post('/reports/sales/download', [SalesReportController::class, 'download'])->name('reports.sales.download');
Route::get('/reports/mutasi/cross-cut', [MutasiCrossCutController::class, 'index'])->name('reports.mutasi.cross-cut.index');
Route::post('/reports/mutasi/cross-cut/download', [MutasiCrossCutController::class, 'download'])->name('reports.mutasi.cross-cut.download');
Route::middleware('guest')->post('/login', [WebAuthController::class, 'login'])->name('web.login');
Route::middleware('auth')->post('/logout', [WebAuthController::class, 'logout'])->name('web.logout');

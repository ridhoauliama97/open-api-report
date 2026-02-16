<?php

use App\Http\Controllers\MutasiBarangJadiController;
use App\Http\Controllers\WebAuthController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::get('/reports/mutasi/barang-jadi', [MutasiBarangJadiController::class, 'index'])->name('reports.mutasi.barang-jadi.index');
Route::post('/reports/mutasi/barang-jadi/download', [MutasiBarangJadiController::class, 'download'])->name('reports.mutasi.barang-jadi.download');
Route::post('/reports/mutasi/barang-jadi/preview', [MutasiBarangJadiController::class, 'preview'])->name('reports.mutasi.barang-jadi.preview');
Route::middleware('guest')->post('/login', [WebAuthController::class, 'login'])->name('web.login');
Route::middleware('auth')->post('/logout', [WebAuthController::class, 'logout'])->name('web.logout');

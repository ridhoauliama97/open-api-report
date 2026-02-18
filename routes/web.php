<?php

use App\Http\Controllers\BahanTerpakaiController;
use App\Http\Controllers\MutasiBarangJadiController;
use App\Http\Controllers\MutasiFingerJointController;
use App\Http\Controllers\MutasiMouldingController;
use App\Http\Controllers\MutasiS4SController;
use App\Http\Controllers\RangkumanJlhLabelInputController;
use App\Http\Controllers\WebAuthController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::get('/reports/mutasi/barang-jadi', [MutasiBarangJadiController::class, 'index'])->name('reports.mutasi.barang-jadi.index');
Route::post('/reports/mutasi/barang-jadi/download', [MutasiBarangJadiController::class, 'download'])->name('reports.mutasi.barang-jadi.download');
Route::post('/reports/mutasi/barang-jadi/preview', [MutasiBarangJadiController::class, 'preview'])->name('reports.mutasi.barang-jadi.preview');
Route::get('/reports/mutasi/finger-joint', [MutasiFingerJointController::class, 'index'])->name('reports.mutasi.finger-joint.index');
Route::post('/reports/mutasi/finger-joint/download', [MutasiFingerJointController::class, 'download'])->name('reports.mutasi.finger-joint.download');
Route::post('/reports/mutasi/finger-joint/preview', [MutasiFingerJointController::class, 'preview'])->name('reports.mutasi.finger-joint.preview');
Route::get('/reports/mutasi/moulding', [MutasiMouldingController::class, 'index'])->name('reports.mutasi.moulding.index');
Route::post('/reports/mutasi/moulding/download', [MutasiMouldingController::class, 'download'])->name('reports.mutasi.moulding.download');
Route::post('/reports/mutasi/moulding/preview', [MutasiMouldingController::class, 'preview'])->name('reports.mutasi.moulding.preview');
Route::get('/reports/mutasi/s4s', [MutasiS4SController::class, 'index'])->name('reports.mutasi.s4s.index');
Route::post('/reports/mutasi/s4s/download', [MutasiS4SController::class, 'download'])->name('reports.mutasi.s4s.download');
Route::post('/reports/mutasi/s4s/preview', [MutasiS4SController::class, 'preview'])->name('reports.mutasi.s4s.preview');
Route::get('/reports/rangkuman-label-input', [RangkumanJlhLabelInputController::class, 'index'])->name('reports.rangkuman-label-input.index');
Route::post('/reports/rangkuman-label-input/download', [RangkumanJlhLabelInputController::class, 'download'])->name('reports.rangkuman-label-input.download');
Route::post('/reports/rangkuman-label-input/preview', [RangkumanJlhLabelInputController::class, 'preview'])->name('reports.rangkuman-label-input.preview');
Route::get('/reports/bahan-terpakai', [BahanTerpakaiController::class, 'index'])->name('reports.bahan-terpakai.index');
Route::post('/reports/bahan-terpakai/download', [BahanTerpakaiController::class, 'download'])->name('reports.bahan-terpakai.download');
Route::post('/reports/bahan-terpakai/preview', [BahanTerpakaiController::class, 'preview'])->name('reports.bahan-terpakai.preview');
Route::middleware('guest')->post('/login', [WebAuthController::class, 'login'])->name('web.login');
Route::middleware('auth')->post('/logout', [WebAuthController::class, 'logout'])->name('web.logout');

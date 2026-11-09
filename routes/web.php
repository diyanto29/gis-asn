<?php

use App\Http\Controllers\AsnController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SsoController;
use App\Http\Controllers\TopsisController;
use App\Http\Controllers\UnitKerjaController;
use Illuminate\Support\Facades\Route;

// Redirect root to dashboard
Route::get('/', function () {
    return redirect()->route('dashboard');
});

// Single Sign-On (SSO) Endpoint
Route::get('/auth/sso', [SsoController::class, 'handleSso'])->name('auth.sso');

// Dashboard Peta GIS & Statistik
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// Modul ASN & Pembaruan Domisili Spasial
Route::prefix('asn')->name('asn.')->group(function () {
    Route::get('/', [AsnController::class, 'index'])->name('index');
    Route::get('/{id}', [AsnController::class, 'show'])->name('show');
    Route::get('/{id}/edit-lokasi', [AsnController::class, 'editLokasi'])->name('edit-lokasi');
    Route::put('/{id}/update-lokasi', [AsnController::class, 'updateLokasi'])->name('update-lokasi');
});

// Modul Unit Kerja & Kuota Formasi
Route::prefix('unit-kerja')->name('unit-kerja.')->group(function () {
    Route::get('/', [UnitKerjaController::class, 'index'])->name('index');
    Route::get('/{id}/edit', [UnitKerjaController::class, 'edit'])->name('edit');
    Route::put('/{id}', [UnitKerjaController::class, 'update'])->name('update');
});

// Modul SPK Rekomendasi Mutasi (TOPSIS)
Route::prefix('topsis')->name('topsis.')->group(function () {
    Route::get('/', [TopsisController::class, 'index'])->name('index');
    Route::post('/bobot', [TopsisController::class, 'updateBobot'])->name('update-bobot');
    Route::post('/proses', [TopsisController::class, 'proses'])->name('proses');
    Route::post('/simpan', [TopsisController::class, 'simpanRekomendasi'])->name('simpan');
    Route::get('/riwayat', [TopsisController::class, 'riwayat'])->name('riwayat');
});

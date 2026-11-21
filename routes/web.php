<?php

use App\Http\Controllers\AsnController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FormasiJabatanController;
use App\Http\Controllers\KriteriaController;
use App\Http\Controllers\MasterLokasiController;
use App\Http\Controllers\SimulasiMandiriController;
use App\Http\Controllers\SsoController;
use App\Http\Controllers\SwapMatchController;
use App\Http\Controllers\TopsisController;
use App\Http\Controllers\UnitKerjaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - SIMUTASI-GIS (Sistem Independen Standalone)
|--------------------------------------------------------------------------
*/

// =========================================================================
// 1. AUTENTIKASI MANDIRI (Guest Only)
// =========================================================================
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

// Single Sign-On (SSO) Endpoint (Fallback / Backward-Compatibility)
Route::get('/auth/sso', [SsoController::class, 'handleSso'])->name('auth.sso');

// =========================================================================
// 2. MODUL TERPROTEKSI (Wajib Login - Authenticated Users)
// =========================================================================
Route::middleware('auth')->group(function () {
    // Logout
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Root Redirect
    Route::get('/', function () {
        return redirect()->route('dashboard');
    });

    // Dashboard Peta Spasial GIS & Statistik
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Smart Swap Match (Tukar Guling Simetris Zero-ABK)
    Route::get('/swap-match', [SwapMatchController::class, 'index'])->name('swap-match.index');
    Route::post('/swap-match/ajukan', [SwapMatchController::class, 'ajukanSwap'])->name('swap-match.ajukan');

    // Simulasi Rekomendasi Penempatan Mandiri (Tahap 2 TOPSIS & OSRM)
    Route::get('/simulasi-mandiri', [SimulasiMandiriController::class, 'index'])->name('asn.simulasi-mandiri');
    Route::post('/simulasi-mandiri/ajukan', [SimulasiMandiriController::class, 'ajukan'])->name('asn.simulasi-mandiri.ajukan');

    // Modul Data ASN
    Route::prefix('asn')->name('asn.')->group(function () {
        Route::get('/', [AsnController::class, 'index'])->name('index');

        // Manajemen ASN Khusus Role: Admin BKPSDM
        Route::middleware('role:admin')->group(function () {
            Route::get('/create', [AsnController::class, 'create'])->name('create');
            Route::post('/', [AsnController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [AsnController::class, 'edit'])->name('edit');
            Route::put('/{id}', [AsnController::class, 'update'])->name('update');
            Route::delete('/{id}', [AsnController::class, 'destroy'])->name('destroy');
        });

        Route::get('/{id}', [AsnController::class, 'show'])->name('show');
        
        // Pembaruan Titik Domisili (Admin atau ASN yang bersangkutan)
        Route::get('/{id}/edit-lokasi', [AsnController::class, 'editLokasi'])->name('edit-lokasi');
        Route::put('/{id}/update-lokasi', [AsnController::class, 'updateLokasi'])->name('update-lokasi');
    });

    // Modul Unit Kerja & Kuota Formasi
    Route::prefix('unit-kerja')->name('unit-kerja.')->group(function () {
        Route::get('/', [UnitKerjaController::class, 'index'])->name('index');
        
        // Manajemen Unit Kerja Khusus Role: Admin BKPSDM
        Route::middleware('role:admin')->group(function () {
            Route::get('/create', [UnitKerjaController::class, 'create'])->name('create');
            Route::post('/', [UnitKerjaController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [UnitKerjaController::class, 'edit'])->name('edit');
            Route::put('/{id}', [UnitKerjaController::class, 'update'])->name('update');
            Route::delete('/{id}', [UnitKerjaController::class, 'destroy'])->name('destroy');
        });
    });

    // Modul Master Formasi Jabatan & Tracing Riwayat ABK (Khusus Role: Admin BKPSDM)
    Route::prefix('formasi-jabatan')->name('formasi-jabatan.')->middleware('role:admin')->group(function () {
        Route::get('/', [FormasiJabatanController::class, 'index'])->name('index');
        Route::get('/create', [FormasiJabatanController::class, 'create'])->name('create');
        Route::post('/', [FormasiJabatanController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [FormasiJabatanController::class, 'edit'])->name('edit');
        Route::put('/{id}', [FormasiJabatanController::class, 'update'])->name('update');
        Route::delete('/{id}', [FormasiJabatanController::class, 'destroy'])->name('destroy');
    });

    // Modul Master Lokasi / Wilayah Spasial (Khusus Role: Admin BKPSDM)
    Route::prefix('master-lokasi')->name('master-lokasi.')->middleware('role:admin')->group(function () {
        Route::get('/', [MasterLokasiController::class, 'index'])->name('index');
        Route::get('/create', [MasterLokasiController::class, 'create'])->name('create');
        Route::post('/', [MasterLokasiController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [MasterLokasiController::class, 'edit'])->name('edit');
        Route::put('/{id}', [MasterLokasiController::class, 'update'])->name('update');
        Route::delete('/{id}', [MasterLokasiController::class, 'destroy'])->name('destroy');
    });

    // Modul Master Kriteria TOPSIS & Dynamic Weighting (Khusus Role: Admin BKPSDM)
    Route::prefix('master-kriteria')->name('master-kriteria.')->middleware('role:admin')->group(function () {
        Route::get('/', [KriteriaController::class, 'index'])->name('index');
        Route::post('/', [KriteriaController::class, 'store'])->name('store');
        Route::post('/bobot', [KriteriaController::class, 'updateBobot'])->name('update-bobot');
        Route::post('/{id}/toggle', [KriteriaController::class, 'toggle'])->name('toggle');
        Route::delete('/{id}', [KriteriaController::class, 'destroy'])->name('destroy');
    });

    // =====================================================================
    // 3. MODUL SPK TOPSIS (Khusus Role: Admin BKPSDM)
    // =====================================================================
    Route::prefix('topsis')->name('topsis.')->middleware('role:admin')->group(function () {
        Route::get('/', [TopsisController::class, 'index'])->name('index');
        Route::get('/pns-to-dinas', [TopsisController::class, 'pnsToDinas'])->name('pns-to-dinas');
        Route::get('/dinas-to-pns', [TopsisController::class, 'dinasToPns'])->name('dinas-to-pns');
        Route::post('/simpan', [TopsisController::class, 'simpanRekomendasi'])->name('simpan');
        Route::get('/riwayat', [TopsisController::class, 'riwayat'])->name('riwayat');
        Route::post('/riwayat/{id}/approve', [TopsisController::class, 'approveUsulan'])->name('approve');
        Route::post('/riwayat/{id}/reject', [TopsisController::class, 'rejectUsulan'])->name('reject');
    });
});

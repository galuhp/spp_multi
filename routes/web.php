<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\JurusanController;
use App\Http\Controllers\KelasController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\LogAktivitasController;
use App\Http\Controllers\PembayaranController;
use App\Http\Controllers\SiswaController;
use App\Http\Controllers\SiswaPortalController;
use App\Http\Controllers\SppController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Redirect root to dashboard or login
Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
});

// Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Protected Routes
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Admin & Petugas (Staff) Routes
    Route::middleware('role:admin,petugas')->group(function () {
        // Data Master Siswa
        Route::resource('siswa', SiswaController::class);

        // Transaksi Pembayaran SPP
        Route::get('/pembayaran', [PembayaranController::class, 'index'])->name('pembayaran.index');
        Route::get('/pembayaran/create', [PembayaranController::class, 'create'])->name('pembayaran.create');
        Route::post('/pembayaran', [PembayaranController::class, 'store'])->name('pembayaran.store');
        Route::get('/pembayaran/{pembayaran}', [PembayaranController::class, 'show'])->name('pembayaran.show');
        Route::get('/pembayaran/{pembayaran}/cetak', [PembayaranController::class, 'cetak'])->name('pembayaran.cetak');

        // Laporan & Rekap Pembayaran
        Route::get('/laporan', [LaporanController::class, 'index'])->name('laporan.index');
        Route::get('/laporan/cetak', [LaporanController::class, 'cetak'])->name('laporan.cetak');
    });

    // Admin Only Routes
    Route::middleware('role:admin')->group(function () {
        // Transaksi Deletion
        Route::delete('/pembayaran/{pembayaran}', [PembayaranController::class, 'destroy'])->name('pembayaran.destroy');

        // Data Master Jurusan
        Route::resource('jurusan', JurusanController::class)->except(['show']);

        // Data Master Kelas
        Route::resource('kelas', KelasController::class)->except(['show']);

        // Data Tarif SPP
        Route::resource('spp', SppController::class)->except(['show']);

        // Manajemen Pengguna & Hak Akses
        Route::resource('user', UserController::class)->except(['show']);

        // Log Aktivitas
        Route::get('/log-aktivitas', [LogAktivitasController::class, 'index'])->name('log.index');
    });

    // Siswa Only Portal Routes
    Route::middleware('role:siswa')->prefix('portal')->name('portal.')->group(function () {
        Route::get('/riwayat', [SiswaPortalController::class, 'riwayat'])->name('riwayat');
        Route::get('/tagihan', [SiswaPortalController::class, 'tagihan'])->name('tagihan');
        Route::get('/kuitansi/{pembayaran}', [SiswaPortalController::class, 'cetakKuitansi'])->name('kuitansi');
    });
});

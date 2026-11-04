<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BuktiPembayaranController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImporSiswaController;
use App\Http\Controllers\JurusanController;
use App\Http\Controllers\KelasController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\LogAktivitasController;
use App\Http\Controllers\PembayaranController;
use App\Http\Controllers\ProfilController;
use App\Http\Controllers\ProfilSekolahController;
use App\Http\Controllers\SiswaController;
use App\Http\Controllers\SiswaPortalController;
use App\Http\Controllers\SppController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Redirect root to Filament panel
Route::get('/', function () {
    return redirect('/admin');
});

// Demo UI kit (sementara, untuk evaluasi)
Route::prefix('demo')->name('demo.')->group(function () {
    Route::view('/preline', 'demo.preline')->name('preline');
    Route::view('/mary', 'demo.mary')->name('mary');
    Route::view('/flux', 'demo.flux')->name('flux');
    Route::view('/wireui', 'demo.wireui')->name('wireui');
    Route::view('/wirekit', 'demo.wirekit')->name('wirekit');
    Route::view('/sheaf', 'demo.sheaf')->name('sheaf');
});

// Public Digital Receipt Verification (QR Code Scan)
Route::get('/bukti/{kode}/{token}', [BuktiPembayaranController::class, 'show'])
    ->name('bukti.pembayaran');

// Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Unduhan pendukung impor siswa massal (admin).
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/impor-siswa/template', [ImporSiswaController::class, 'template'])->name('impor-siswa.template');
    Route::get('/impor-siswa/{batchId}/error', [ImporSiswaController::class, 'laporanError'])->name('impor-siswa.error');
});

// Protected Routes
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profil Pengguna (Semua Role)
    Route::get('/profil', [ProfilController::class, 'index'])->name('profil.index');
    Route::put('/profil', [ProfilController::class, 'update'])->name('profil.update');
    Route::put('/profil/password', [ProfilController::class, 'updatePassword'])->name('profil.password');

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

        // Pengaturan Profil Sekolah
        Route::get('/profil-sekolah', [ProfilSekolahController::class, 'edit'])->name('sekolah.edit');
        Route::put('/profil-sekolah', [ProfilSekolahController::class, 'update'])->name('sekolah.update');
    });

    // Siswa Only Portal Routes
    Route::middleware('role:siswa')->prefix('portal')->name('portal.')->group(function () {
        Route::get('/riwayat', [SiswaPortalController::class, 'riwayat'])->name('riwayat');
        Route::get('/tagihan', [SiswaPortalController::class, 'tagihan'])->name('tagihan');
        Route::get('/kuitansi/{pembayaran}', [SiswaPortalController::class, 'cetakKuitansi'])->name('kuitansi');
    });
});

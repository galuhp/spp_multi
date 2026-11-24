<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BuktiPembayaranController;
use App\Http\Controllers\ImporSiswaController;
use App\Http\Controllers\JurusanController;
use App\Http\Controllers\KelasController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\LegacyRedirectController;
use App\Http\Controllers\PembayaranController;
use App\Http\Controllers\PembayaranGabunganController;
use App\Http\Controllers\ProfilController;
use App\Http\Controllers\ProfilSekolahController;
use App\Http\Controllers\SiswaController;
use App\Http\Controllers\SiswaPortalController;
use App\Http\Controllers\SppController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Redirect root to Filament panel
Route::get('/', [LegacyRedirectController::class, 'root']);

// Public Digital Receipt Verification (QR Code Scan)
Route::get('/bukti/{kode}/{token}', [BuktiPembayaranController::class, 'show'])
    ->name('bukti.pembayaran');

// Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LegacyRedirectController::class, 'login'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('login.post');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Unduhan pendukung impor siswa massal (admin).
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/impor-siswa/template', [ImporSiswaController::class, 'template'])->name('impor-siswa.template');
    Route::get('/impor-siswa/{batchId}/error', [ImporSiswaController::class, 'laporanError'])->name('impor-siswa.error');

    // Unduhan template impor data master (admin).
    Route::get('/impor-master/template/jurusan', [\App\Http\Controllers\ImporMasterController::class, 'templateJurusan'])->name('impor.template.jurusan');
    Route::get('/impor-master/template/kelas', [\App\Http\Controllers\ImporMasterController::class, 'templateKelas'])->name('impor.template.kelas');
    Route::get('/impor-master/template/tarif', [\App\Http\Controllers\ImporMasterController::class, 'templateTarif'])->name('impor.template.tarif');
});

// Protected Routes
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [LegacyRedirectController::class, 'dashboard'])->name('dashboard');

    // Profil Pengguna (Semua Role)
    Route::get('/profil', [LegacyRedirectController::class, 'profil'])->name('profil.index');
    Route::put('/profil', [ProfilController::class, 'update'])->name('profil.update');
    Route::put('/profil/password', [ProfilController::class, 'updatePassword'])->name('profil.password');

    // Unduh Dokumen Buku Panduan Penggunaan
    Route::get('/panduan/unduh', [\App\Http\Controllers\PanduanController::class, 'unduh'])
        ->name('panduan.unduh');

    // Cetak / Simpan PDF Invoice Tagihan Tenant
    Route::get('/tenant-invoices/{invoice}/cetak', \App\Http\Controllers\TenantInvoicePrintController::class)
        ->name('tenant-invoices.cetak');

    // Keluar dari mode impersonate tenant kembali ke SuperAdmin
    Route::post('/impersonate/leave', [\App\Http\Controllers\ImpersonationController::class, 'leave'])
        ->name('impersonate.leave');

    // Admin & Petugas (Staff) Routes
    Route::middleware('role:admin,petugas')->group(function () {
        // Data Master Siswa
        Route::get('/siswa', [LegacyRedirectController::class, 'siswaIndex'])->name('siswa.index');
        Route::get('/siswa/create', [LegacyRedirectController::class, 'siswaCreate'])->name('siswa.create');
        Route::get('/siswa/{siswa}/edit', [LegacyRedirectController::class, 'siswaEdit'])->name('siswa.edit');
        Route::get('/siswa/{siswa}', [LegacyRedirectController::class, 'siswaEdit'])->name('siswa.show');
        Route::resource('siswa', SiswaController::class)->except(['index', 'create', 'show', 'edit']);

        // Transaksi Pembayaran SPP
        Route::get('/pembayaran', [LegacyRedirectController::class, 'pembayaranIndex'])->name('pembayaran.index');
        Route::get('/pembayaran/create', [LegacyRedirectController::class, 'pembayaranCreate'])->name('pembayaran.create');
        Route::post('/pembayaran', [PembayaranController::class, 'store'])->name('pembayaran.store');

        // Cetak Pembayaran Gabungan (static routes must stay above the dynamic receipt route)
        Route::get('/pembayaran/gabungan/{gabungan}/kuitansi', [PembayaranGabunganController::class, 'kuitansi'])->name('pembayaran.gabungan.kuitansi');
        Route::get('/pembayaran/gabungan/{gabungan}/rekap', [PembayaranGabunganController::class, 'rekap'])->name('pembayaran.gabungan.rekap');
        Route::get('/pembayaran/gabungan-template', [PembayaranGabunganController::class, 'template'])->name('pembayaran.gabungan.template');
        Route::get('/pembayaran/gabungan/{gabungan}/error', [PembayaranGabunganController::class, 'unduhError'])->name('pembayaran.gabungan.error');

        Route::get('/pembayaran/{pembayaran}', [PembayaranController::class, 'show'])->name('pembayaran.show');
        Route::get('/pembayaran/{pembayaran}/cetak', [PembayaranController::class, 'cetak'])->name('pembayaran.cetak');

        // Cetak Rekap & Bukti Pengeluaran Kas
        Route::get('/pengeluaran/rekap/cetak', [\App\Http\Controllers\PengeluaranController::class, 'cetakRekap'])->name('pengeluaran.rekap.cetak');
        Route::get('/pengeluaran/{pengeluaran}/cetak', [\App\Http\Controllers\PengeluaranController::class, 'cetak'])->name('pengeluaran.cetak');

        // Laporan & Rekap Pembayaran
        Route::get('/laporan', [LegacyRedirectController::class, 'laporanIndex'])->name('laporan.index');
        Route::get('/laporan/cetak', [LaporanController::class, 'cetak'])->name('laporan.cetak');
        Route::get('/laporan/tunggakan/cetak', [LaporanController::class, 'cetakTunggakan'])->name('laporan.tunggakan.cetak');
        Route::get('/laporan/potensi/cetak', [LaporanController::class, 'cetakPotensi'])->name('laporan.potensi.cetak');
        Route::get('/laporan/kartu/cetak', [LaporanController::class, 'cetakKartu'])->name('laporan.kartu.cetak');
    });

    // Admin Only Routes
    Route::middleware('role:admin')->group(function () {
        // Transaksi Deletion
        Route::delete('/pembayaran/{pembayaran}', [PembayaranController::class, 'destroy'])->name('pembayaran.destroy');

        // Data Master Jurusan
        Route::get('/jurusan', [LegacyRedirectController::class, 'jurusanIndex'])->name('jurusan.index');
        Route::get('/jurusan/create', [LegacyRedirectController::class, 'jurusanCreate'])->name('jurusan.create');
        Route::get('/jurusan/{jurusan}/edit', [LegacyRedirectController::class, 'jurusanEdit'])->name('jurusan.edit');
        Route::resource('jurusan', JurusanController::class)->except(['index', 'create', 'edit', 'show']);

        // Data Master Kelas
        Route::get('/kelas', [LegacyRedirectController::class, 'kelasIndex'])->name('kelas.index');
        Route::get('/kelas/create', [LegacyRedirectController::class, 'kelasCreate'])->name('kelas.create');
        Route::get('/kelas/{kelas}/edit', [LegacyRedirectController::class, 'kelasEdit'])->name('kelas.edit');
        Route::resource('kelas', KelasController::class)->except(['index', 'create', 'edit', 'show']);

        // Data Tarif SPP
        Route::get('/spp', [LegacyRedirectController::class, 'sppIndex'])->name('spp.index');
        Route::get('/spp/create', [LegacyRedirectController::class, 'sppCreate'])->name('spp.create');
        Route::get('/spp/{spp}/edit', [LegacyRedirectController::class, 'sppEdit'])->name('spp.edit');
        Route::resource('spp', SppController::class)->except(['index', 'create', 'edit', 'show']);

        // Manajemen Pengguna & Hak Akses
        Route::get('/user', [LegacyRedirectController::class, 'userIndex'])->name('user.index');
        Route::get('/user/create', [LegacyRedirectController::class, 'userCreate'])->name('user.create');
        Route::get('/user/{user}/edit', [LegacyRedirectController::class, 'userEdit'])->name('user.edit');
        Route::resource('user', UserController::class)->except(['index', 'create', 'edit', 'show']);

        // Log Aktivitas
        Route::get('/log-aktivitas', [LegacyRedirectController::class, 'logIndex'])->name('log.index');

        // Pengaturan Profil Sekolah
        Route::get('/profil-sekolah', [LegacyRedirectController::class, 'sekolahEdit'])->name('sekolah.edit');
        Route::put('/profil-sekolah', [ProfilSekolahController::class, 'update'])->name('sekolah.update');
    });

    // Siswa Only Portal Routes
    Route::middleware('role:siswa')->prefix('portal')->name('portal.')->group(function () {
        Route::get('/riwayat', [LegacyRedirectController::class, 'portalRiwayat'])->name('riwayat');
        Route::get('/tagihan', [LegacyRedirectController::class, 'portalTagihan'])->name('tagihan');
        Route::get('/kuitansi/{pembayaran}', [SiswaPortalController::class, 'cetakKuitansi'])->name('kuitansi');
    });
});


Route::post('/webhooks/doku/tenant-invoice', [\App\Http\Controllers\DokuWebhookController::class, 'handle'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class])
    ->name('webhooks.doku.tenant-invoice');

Route::post('/webhooks/tenant-payment/{provider}/{tenant:slug}', [\App\Http\Controllers\TenantPaymentWebhookController::class, 'handle'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class])
    ->name('webhooks.tenant-payment');

Route::get('/register-sekolah', [\App\Http\Controllers\SchoolRegistrationController::class, 'showForm'])
    ->name('school.register.form');
Route::post('/register-sekolah', [\App\Http\Controllers\SchoolRegistrationController::class, 'register'])
    ->middleware('throttle:5,1')
    ->name('school.register.submit');
Route::get('/register-sekolah/verify/{user}/{tenant}', [\App\Http\Controllers\SchoolRegistrationController::class, 'verify'])
    ->middleware('signed')
    ->name('school.verify');

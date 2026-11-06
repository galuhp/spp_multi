<?php

use App\Filament\Pages\LaporanPembayaran;
use App\Filament\Pages\ProfilSekolahSettings;
use App\Filament\Pages\RiwayatPembayaranSiswa;
use App\Filament\Pages\TagihanSiswa;
use App\Filament\Resources\Jurusans\JurusanResource;
use App\Filament\Resources\Kelas\KelasResource;
use App\Filament\Resources\LogAktivitas\LogAktivitasResource;
use App\Filament\Resources\Pembayarans\Pages\CreatePembayaran;
use App\Filament\Resources\Pembayarans\PembayaranResource;
use App\Filament\Resources\Siswas\SiswaResource;
use App\Filament\Resources\Spps\SppResource;
use App\Filament\Resources\Users\UserResource;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BuktiPembayaranController;
use App\Http\Controllers\ImporSiswaController;
use App\Http\Controllers\JurusanController;
use App\Http\Controllers\KelasController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\PembayaranController;
use App\Http\Controllers\PembayaranGabunganController;
use App\Http\Controllers\ProfilController;
use App\Http\Controllers\ProfilSekolahController;
use App\Http\Controllers\SiswaController;
use App\Http\Controllers\SiswaPortalController;
use App\Http\Controllers\SppController;
use App\Http\Controllers\UserController;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\Spp;
use App\Models\User;
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
    Route::get('/login', fn () => redirect('/admin/login'))->name('login');
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
    Route::get('/dashboard', function () {
        return auth()->user()?->isSiswa()
            ? redirect(TagihanSiswa::getUrl())
            : redirect('/admin');
    })->name('dashboard');

    // Profil Pengguna (Semua Role)
    Route::get('/profil', fn () => redirect('/admin/profile'))->name('profil.index');
    Route::put('/profil', [ProfilController::class, 'update'])->name('profil.update');
    Route::put('/profil/password', [ProfilController::class, 'updatePassword'])->name('profil.password');

    // Admin & Petugas (Staff) Routes
    Route::middleware('role:admin,petugas')->group(function () {
        // Data Master Siswa
        Route::get('/siswa', fn () => redirect(SiswaResource::getUrl('index')))->name('siswa.index');
        Route::get('/siswa/create', fn () => redirect(SiswaResource::getUrl('create')))->name('siswa.create');
        Route::get('/siswa/{siswa}/edit', fn (Siswa $siswa) => redirect(SiswaResource::getUrl('edit', ['record' => $siswa])))->name('siswa.edit');
        Route::get('/siswa/{siswa}', fn (Siswa $siswa) => redirect(SiswaResource::getUrl('edit', ['record' => $siswa])))->name('siswa.show');
        Route::resource('siswa', SiswaController::class)->except(['index', 'create', 'show', 'edit']);

        // Transaksi Pembayaran SPP
        Route::get('/pembayaran', fn () => redirect(PembayaranResource::getUrl('index')))->name('pembayaran.index');
        Route::get('/pembayaran/create', function () {
            $url = CreatePembayaran::getUrl();
            $query = request()->getQueryString();

            return redirect($query ? $url.'?'.$query : $url);
        })->name('pembayaran.create');
        Route::post('/pembayaran', [PembayaranController::class, 'store'])->name('pembayaran.store');

        // Cetak Pembayaran Gabungan (static routes must stay above the dynamic receipt route)
        Route::get('/pembayaran/gabungan/{gabungan}/kuitansi', [PembayaranGabunganController::class, 'kuitansi'])->name('pembayaran.gabungan.kuitansi');
        Route::get('/pembayaran/gabungan/{gabungan}/rekap', [PembayaranGabunganController::class, 'rekap'])->name('pembayaran.gabungan.rekap');
        Route::get('/pembayaran/gabungan-template', [PembayaranGabunganController::class, 'template'])->name('pembayaran.gabungan.template');
        Route::get('/pembayaran/gabungan/{gabungan}/error', [PembayaranGabunganController::class, 'unduhError'])->name('pembayaran.gabungan.error');

        Route::get('/pembayaran/{pembayaran}', [PembayaranController::class, 'show'])->name('pembayaran.show');
        Route::get('/pembayaran/{pembayaran}/cetak', [PembayaranController::class, 'cetak'])->name('pembayaran.cetak');

        // Laporan & Rekap Pembayaran
        Route::get('/laporan', function () {
            $url = LaporanPembayaran::getUrl();
            $query = request()->getQueryString();

            return redirect($query ? $url.'?'.$query : $url);
        })->name('laporan.index');
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
        Route::get('/jurusan', fn () => redirect(JurusanResource::getUrl('index')))->name('jurusan.index');
        Route::get('/jurusan/create', fn () => redirect(JurusanResource::getUrl('create')))->name('jurusan.create');
        Route::get('/jurusan/{jurusan}/edit', fn (Jurusan $jurusan) => redirect(JurusanResource::getUrl('edit', ['record' => $jurusan])))->name('jurusan.edit');
        Route::resource('jurusan', JurusanController::class)->except(['index', 'create', 'edit', 'show']);

        // Data Master Kelas
        Route::get('/kelas', fn () => redirect(KelasResource::getUrl('index')))->name('kelas.index');
        Route::get('/kelas/create', fn () => redirect(KelasResource::getUrl('create')))->name('kelas.create');
        Route::get('/kelas/{kelas}/edit', fn (Kelas $kelas) => redirect(KelasResource::getUrl('edit', ['record' => $kelas])))->name('kelas.edit');
        Route::resource('kelas', KelasController::class)->except(['index', 'create', 'edit', 'show']);

        // Data Tarif SPP
        Route::get('/spp', function () {
            $parameters = [];

            if (request()->filled('kategori')) {
                $parameters['filters'] = [
                    'kategori' => ['value' => request()->query('kategori')],
                ];
            }

            return redirect(SppResource::getUrl('index', $parameters));
        })->name('spp.index');
        Route::get('/spp/create', fn () => redirect(SppResource::getUrl('create')))->name('spp.create');
        Route::get('/spp/{spp}/edit', fn (Spp $spp) => redirect(SppResource::getUrl('edit', ['record' => $spp])))->name('spp.edit');
        Route::resource('spp', SppController::class)->except(['index', 'create', 'edit', 'show']);

        // Manajemen Pengguna & Hak Akses
        Route::get('/user', fn () => redirect(UserResource::getUrl('index')))->name('user.index');
        Route::get('/user/create', fn () => redirect(UserResource::getUrl('create')))->name('user.create');
        Route::get('/user/{user}/edit', fn (User $user) => redirect(UserResource::getUrl('edit', ['record' => $user])))->name('user.edit');
        Route::resource('user', UserController::class)->except(['index', 'create', 'edit', 'show']);

        // Log Aktivitas
        Route::get('/log-aktivitas', fn () => redirect(LogAktivitasResource::getUrl('index')))->name('log.index');

        // Pengaturan Profil Sekolah
        Route::get('/profil-sekolah', fn () => redirect(ProfilSekolahSettings::getUrl()))->name('sekolah.edit');
        Route::put('/profil-sekolah', [ProfilSekolahController::class, 'update'])->name('sekolah.update');
    });

    // Siswa Only Portal Routes
    Route::middleware('role:siswa')->prefix('portal')->name('portal.')->group(function () {
        Route::get('/riwayat', fn () => redirect(RiwayatPembayaranSiswa::getUrl()))->name('riwayat');
        Route::get('/tagihan', fn () => redirect(TagihanSiswa::getUrl()))->name('tagihan');
        Route::get('/kuitansi/{pembayaran}', [SiswaPortalController::class, 'cetakKuitansi'])->name('kuitansi');
    });
});

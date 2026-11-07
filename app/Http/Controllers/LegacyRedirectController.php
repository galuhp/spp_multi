<?php

namespace App\Http\Controllers;

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
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\Spp;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LegacyRedirectController extends Controller
{
    public function root(): RedirectResponse
    {
        return redirect('/admin');
    }

    public function login(): RedirectResponse
    {
        return redirect('/admin/login');
    }

    public function dashboard(): RedirectResponse
    {
        return auth()->user()?->isSiswa()
            ? redirect(TagihanSiswa::getUrl())
            : redirect('/admin');
    }

    public function profil(): RedirectResponse
    {
        return redirect('/admin/profile');
    }

    public function siswaIndex(): RedirectResponse
    {
        return redirect(SiswaResource::getUrl('index'));
    }

    public function siswaCreate(): RedirectResponse
    {
        return redirect(SiswaResource::getUrl('create'));
    }

    public function siswaEdit(Siswa $siswa): RedirectResponse
    {
        return redirect(SiswaResource::getUrl('edit', ['record' => $siswa]));
    }

    public function pembayaranIndex(): RedirectResponse
    {
        return redirect(PembayaranResource::getUrl('index'));
    }

    public function pembayaranCreate(Request $request): RedirectResponse
    {
        $url = CreatePembayaran::getUrl();
        $query = $request->getQueryString();

        return redirect($query ? $url.'?'.$query : $url);
    }

    public function laporanIndex(Request $request): RedirectResponse
    {
        $url = LaporanPembayaran::getUrl();
        $query = $request->getQueryString();

        return redirect($query ? $url.'?'.$query : $url);
    }

    public function jurusanIndex(): RedirectResponse
    {
        return redirect(JurusanResource::getUrl('index'));
    }

    public function jurusanCreate(): RedirectResponse
    {
        return redirect(JurusanResource::getUrl('create'));
    }

    public function jurusanEdit(Jurusan $jurusan): RedirectResponse
    {
        return redirect(JurusanResource::getUrl('edit', ['record' => $jurusan]));
    }

    public function kelasIndex(): RedirectResponse
    {
        return redirect(KelasResource::getUrl('index'));
    }

    public function kelasCreate(): RedirectResponse
    {
        return redirect(KelasResource::getUrl('create'));
    }

    public function kelasEdit(Kelas $kelas): RedirectResponse
    {
        return redirect(KelasResource::getUrl('edit', ['record' => $kelas]));
    }

    public function sppIndex(Request $request): RedirectResponse
    {
        $parameters = [];

        if ($request->filled('kategori')) {
            $parameters['filters'] = [
                'kategori' => ['value' => $request->query('kategori')],
            ];
        }

        return redirect(SppResource::getUrl('index', $parameters));
    }

    public function sppCreate(): RedirectResponse
    {
        return redirect(SppResource::getUrl('create'));
    }

    public function sppEdit(Spp $spp): RedirectResponse
    {
        return redirect(SppResource::getUrl('edit', ['record' => $spp]));
    }

    public function userIndex(): RedirectResponse
    {
        return redirect(UserResource::getUrl('index'));
    }

    public function userCreate(): RedirectResponse
    {
        return redirect(UserResource::getUrl('create'));
    }

    public function userEdit(User $user): RedirectResponse
    {
        return redirect(UserResource::getUrl('edit', ['record' => $user]));
    }

    public function logIndex(): RedirectResponse
    {
        return redirect(LogAktivitasResource::getUrl('index'));
    }

    public function sekolahEdit(): RedirectResponse
    {
        return redirect(ProfilSekolahSettings::getUrl());
    }

    public function portalRiwayat(): RedirectResponse
    {
        return redirect(RiwayatPembayaranSiswa::getUrl());
    }

    public function portalTagihan(): RedirectResponse
    {
        return redirect(TagihanSiswa::getUrl());
    }
}

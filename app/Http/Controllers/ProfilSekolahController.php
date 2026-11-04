<?php

namespace App\Http\Controllers;

use App\Models\LogAktivitas;
use App\Models\ProfilSekolah;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfilSekolahController extends Controller
{
    /**
     * Display the school profile settings form.
     */
    public function edit(): View
    {
        $sekolah = ProfilSekolah::current();

        return view('profil_sekolah.edit', compact('sekolah'));
    }

    /**
     * Update school profile information.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama_sekolah' => ['required', 'string', 'max:150'],
            'npsn' => ['nullable', 'string', 'max:30'],
            'jenjang' => ['nullable', 'string', 'max:20'],
            'pemerintah_daerah' => ['nullable', 'string', 'max:150'],
            'dinas_pendidikan' => ['nullable', 'string', 'max:150'],
            'alamat' => ['nullable', 'string', 'max:500'],
            'kota' => ['nullable', 'string', 'max:60'],
            'kode_pos' => ['nullable', 'string', 'max:15'],
            'no_telp' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'string', 'max:100'],
            'website' => ['nullable', 'string', 'max:120'],
            'nama_kepala_sekolah' => ['nullable', 'string', 'max:120'],
            'nip_kepala_sekolah' => ['nullable', 'string', 'max:50'],
        ], [
            'nama_sekolah.required' => 'Nama sekolah wajib diisi.',
            'nama_sekolah.max' => 'Nama sekolah maksimal 150 karakter.',
        ]);

        $sekolah = ProfilSekolah::current();
        $sebelum = $sekolah->only(array_keys($validated));

        $sekolah->update($validated);
        ProfilSekolah::clearCache();

        LogAktivitas::catat(
            'sekolah.diubah',
            "Profil sekolah {$sekolah->nama_sekolah} diperbarui",
            $sekolah,
            [
                'sebelum' => $sebelum,
                'sesudah' => $validated,
            ]
        );

        return redirect()->route('sekolah.edit')->with('success', 'Profil dan identitas sekolah berhasil diperbarui.');
    }
}

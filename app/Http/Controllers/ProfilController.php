<?php

namespace App\Http\Controllers;

use App\Models\LogAktivitas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfilController extends Controller
{
    /**
     * Display the current user's profile page.
     */
    public function index(): View
    {
        $user = auth()->user();

        if ($user->isSiswa()) {
            $user->loadMissing(['siswa.kelas.jurusan', 'siswa.spp']);
        }

        return view('profil.index', compact('user'));
    }

    /**
     * Update basic user profile information.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => [
                'nullable',
                'email',
                'max:100',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'name.max' => 'Nama lengkap maksimal 100 karakter.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email tersebut sudah digunakan oleh akun lain.',
        ]);

        $sebelum = $user->only(['name', 'email']);
        $user->update($validated);

        LogAktivitas::catat(
            'user.profil_diubah',
            "Pengguna {$user->username} memperbarui data profilnya",
            $user,
            [
                'sebelum' => $sebelum,
                'sesudah' => $validated,
            ]
        );

        return redirect()->route('profil.index')->with('success', 'Profil Anda berhasil diperbarui.');
    }

    /**
     * Update user password.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'confirmed', Password::min(6)],
        ], [
            'current_password.required' => 'Kata sandi saat ini wajib diisi.',
            'current_password.current_password' => 'Kata sandi saat ini tidak sesuai.',
            'password.required' => 'Kata sandi baru wajib diisi.',
            'password.min' => 'Kata sandi baru minimal :min karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi baru tidak cocok.',
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        LogAktivitas::catat(
            'user.ganti_password',
            "Pengguna {$user->username} berhasil memperbarui kata sandinya",
            $user
        );

        return redirect()->route('profil.index')->with('success', 'Kata sandi akun Anda berhasil diperbarui.');
    }
}

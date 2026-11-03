@extends('layouts.app')

@section('title', 'Tambah Pengguna')
@section('header_title', 'Tambah Pengguna Baru')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="prism-card p-6 sm:p-8">
        <div class="flex items-center justify-between pb-5 mb-5 border-b border-slate-100 dark:border-white/10">
            <div>
                <h3 class="font-bold text-slate-900 dark:text-white text-lg">Form Tambah Pengguna</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Daftarkan akun staf administrasi, petugas kasir, atau siswa</p>
            </div>
            <a href="{{ route('user.index') }}" class="text-xs font-semibold text-slate-600 dark:text-slate-300 hover:text-prism-600 dark:hover:text-prism-300">
                &larr; Kembali
            </a>
        </div>

        <form action="{{ route('user.store') }}" method="POST" class="space-y-4">
            @csrf

            <div>
                <label for="name" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1">
                    Nama Lengkap <span class="text-rose-500">*</span>
                </label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" required
                    placeholder="Contoh: Budi Santoso"
                    class="w-full px-3.5 py-2.5 prism-input text-sm">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="username" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1">
                        Username / NISN <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" id="username" name="username" value="{{ old('username') }}" required
                        placeholder="Contoh: petugas1 atau NISN"
                        class="w-full px-3.5 py-2.5 prism-input text-sm">
                </div>

                <div>
                    <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1">
                        Alamat Email (Opsional)
                    </label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}"
                        placeholder="user@sekolah.sch.id"
                        class="w-full px-3.5 py-2.5 prism-input text-sm">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1">
                        Kata Sandi <span class="text-rose-500">*</span>
                    </label>
                    <input type="password" id="password" name="password" required
                        placeholder="Minimal 6 karakter"
                        class="w-full px-3.5 py-2.5 prism-input text-sm">
                </div>

                <div>
                    <label for="role" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1">
                        Hak Akses / Peran <span class="text-rose-500">*</span>
                    </label>
                    <select id="role" name="role" required
                        class="w-full px-3.5 py-2.5 prism-input text-sm">
                        <option value="">-- Pilih Hak Akses --</option>
                        @foreach($roles as $r)
                            <option value="{{ $r->value }}" {{ old('role') == $r->value ? 'selected' : '' }}>
                                {{ $r->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-100 dark:border-white/10">
                <a href="{{ route('user.index') }}" class="px-4 py-2.5 prism-btn-secondary text-xs">
                    Batal
                </a>
                <button type="submit" class="px-5 py-2.5 prism-btn-primary text-xs">
                    Simpan Pengguna
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

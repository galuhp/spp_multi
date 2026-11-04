@extends('layouts.app')

@section('title', 'Tambah Pengguna')
@section('header_title', 'Tambah Pengguna Baru')

@section('content')
<div class="max-w-2xl mx-auto">
    <x-ui.card class="!max-w-none !p-6 sm:!p-8">
        <div class="flex items-center justify-between pb-5 mb-5 border-b border-zinc-200">
            <div>
                <h3 class="font-bold text-zinc-900 text-lg">Form Tambah Pengguna</h3>
                <p class="text-xs text-zinc-500 mt-0.5">Daftarkan akun staf administrasi, petugas kasir, atau siswa</p>
            </div>
            <a href="{{ route('user.index') }}" class="text-xs font-semibold text-zinc-600 hover:text-zinc-900">
                &larr; Kembali
            </a>
        </div>

        <form action="{{ route('user.store') }}" method="POST" class="space-y-4">
            @csrf

            <div>
                <label for="name" class="block text-sm font-medium text-zinc-700 mb-1.5">
                    Nama Lengkap <span class="text-rose-500">*</span>
                </label>
                <x-ui.input id="name" name="name" value="{{ old('name') }}" required placeholder="Contoh: Budi Santoso" />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="username" class="block text-sm font-medium text-zinc-700 mb-1.5">
                        Username / NISN <span class="text-rose-500">*</span>
                    </label>
                    <x-ui.input id="username" name="username" value="{{ old('username') }}" required placeholder="Contoh: petugas1 atau NISN" />
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-zinc-700 mb-1.5">
                        Alamat Email (Opsional)
                    </label>
                    <x-ui.input id="email" name="email" type="email" value="{{ old('email') }}" placeholder="user@sekolah.sch.id" />
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="password" class="block text-sm font-medium text-zinc-700 mb-1.5">
                        Kata Sandi <span class="text-rose-500">*</span>
                    </label>
                    <x-ui.input id="password" name="password" type="password" required placeholder="Minimal 6 karakter" />
                </div>

                <div>
                    <label for="role" class="block text-sm font-medium text-zinc-700 mb-1.5">
                        Hak Akses / Peran <span class="text-rose-500">*</span>
                    </label>
                    <select id="role" name="role" required
                        class="w-full min-h-10 px-3 rounded-field border border-zinc-300 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-zinc-400">
                        <option value="">-- Pilih Hak Akses --</option>
                        @foreach($roles as $r)
                            <option value="{{ $r->value }}" {{ old('role') == $r->value ? 'selected' : '' }}>
                                {{ $r->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="pt-4 flex items-center justify-end gap-3 border-t border-zinc-200">
                <x-ui.button as="a" href="{{ route('user.index') }}" variant="secondary">Batal</x-ui.button>
                <x-ui.button type="submit" variant="primary">Simpan Pengguna</x-ui.button>
            </div>
        </form>
    </x-ui.card>
</div>
@endsection
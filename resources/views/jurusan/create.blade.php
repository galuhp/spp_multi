@extends('layouts.app')

@section('title', 'Tambah Jurusan')
@section('header_title', 'Tambah Kompetensi Keahlian')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="prism-card p-6 sm:p-8">
        <div class="flex items-center justify-between pb-5 mb-5 border-b border-slate-100 dark:border-white/10">
            <div>
                <h3 class="font-bold text-slate-900 dark:text-white text-lg">Form Tambah Jurusan</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Lengkapi data jurusan / keahlian baru</p>
            </div>
            <a href="{{ route('jurusan.index') }}" class="text-xs font-semibold text-slate-600 dark:text-slate-300 hover:text-prism-600 dark:hover:text-prism-300">
                &larr; Kembali
            </a>
        </div>

        <form action="{{ route('jurusan.store') }}" method="POST" class="space-y-4">
            @csrf

            <div>
                <label for="kode" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1">
                    Kode Jurusan <span class="text-rose-500">*</span>
                </label>
                <input type="text" id="kode" name="kode" value="{{ old('kode') }}" required
                    placeholder="Contoh: RPL, TKJ, AKL"
                    class="w-full px-3.5 py-2.5 prism-input text-sm font-mono uppercase">
            </div>

            <div>
                <label for="nama" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1">
                    Nama Kompetensi Keahlian <span class="text-rose-500">*</span>
                </label>
                <input type="text" id="nama" name="nama" value="{{ old('nama') }}" required
                    placeholder="Contoh: Rekayasa Perangkat Lunak"
                    class="w-full px-3.5 py-2.5 prism-input text-sm">
            </div>

            <div>
                <label for="deskripsi" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1">
                    Deskripsi / Keterangan
                </label>
                <textarea id="deskripsi" name="deskripsi" rows="3"
                    placeholder="Keterangan singkat mengenai keahlian atau program studi"
                    class="w-full px-3.5 py-2.5 prism-input text-sm">{{ old('deskripsi') }}</textarea>
            </div>

            <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-100 dark:border-white/10">
                <a href="{{ route('jurusan.index') }}" class="px-4 py-2.5 prism-btn-secondary text-xs">
                    Batal
                </a>
                <button type="submit" class="px-5 py-2.5 prism-btn-primary text-xs">
                    Simpan Data
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

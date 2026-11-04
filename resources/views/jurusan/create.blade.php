@extends('layouts.app')

@section('title', 'Tambah Jurusan')
@section('header_title', 'Tambah Kompetensi Keahlian')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 sm:p-8">
        <div class="flex items-center justify-between pb-5 mb-5 border-b border-slate-100">
            <div>
                <h3 class="font-bold text-slate-900 text-lg">Form Tambah Jurusan</h3>
                <p class="text-xs text-slate-500 mt-0.5">Lengkapi data jurusan / keahlian baru</p>
            </div>
            <a href="{{ route('jurusan.index') }}" class="text-xs font-semibold text-slate-600 hover:text-slate-900">
                &larr; Kembali
            </a>
        </div>

        <form action="{{ route('jurusan.store') }}" method="POST" class="space-y-4">
            @csrf

            <div>
                <label for="kode" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1">
                    Kode Jurusan <span class="text-rose-500">*</span>
                </label>
                <input type="text" id="kode" name="kode" value="{{ old('kode') }}" required
                    placeholder="Contoh: RPL, TKJ, AKL"
                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm font-mono uppercase">
            </div>

            <div>
                <label for="nama" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1">
                    Nama Kompetensi Keahlian <span class="text-rose-500">*</span>
                </label>
                <input type="text" id="nama" name="nama" value="{{ old('nama') }}" required
                    placeholder="Contoh: Rekayasa Perangkat Lunak"
                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
            </div>

            <div>
                <label for="deskripsi" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1">
                    Deskripsi / Keterangan
                </label>
                <textarea id="deskripsi" name="deskripsi" rows="3"
                    placeholder="Keterangan singkat mengenai keahlian atau program studi"
                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">{{ old('deskripsi') }}</textarea>
            </div>

            <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-100">
                <a href="{{ route('jurusan.index') }}" class="px-4 py-2.5 rounded-xl text-slate-700 bg-slate-100 hover:bg-slate-200 text-xs font-semibold transition">
                    Batal
                </a>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold transition shadow-sm">
                    Simpan Data
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

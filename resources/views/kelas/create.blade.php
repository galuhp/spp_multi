@extends('layouts.app')

@section('title', 'Tambah Kelas')
@section('header_title', 'Tambah Kelas Baru')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="prism-card p-6 sm:p-8">
        <div class="flex items-center justify-between pb-5 mb-5 border-b border-slate-100 dark:border-white/10">
            <div>
                <h3 class="font-bold text-slate-900 dark:text-white text-lg">Form Tambah Kelas</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Daftarkan kelas atau rombel baru</p>
            </div>
            <a href="{{ route('kelas.index') }}" class="text-xs font-semibold text-slate-600 dark:text-slate-300 hover:text-prism-600 dark:hover:text-prism-300">
                &larr; Kembali
            </a>
        </div>

        <form action="{{ route('kelas.store') }}" method="POST" class="space-y-4">
            @csrf

            <div>
                <label for="nama_kelas" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1">
                    Nama Kelas <span class="text-rose-500">*</span>
                </label>
                <input type="text" id="nama_kelas" name="nama_kelas" value="{{ old('nama_kelas') }}" required
                    placeholder="Contoh: X RPL 1, XI TKJ 2"
                    class="w-full px-3.5 py-2.5 prism-input text-sm">
            </div>

            <div>
                <label for="tingkat" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1">
                    Tingkat Kelas <span class="text-rose-500">*</span>
                </label>
                <select id="tingkat" name="tingkat" required
                    class="w-full px-3.5 py-2.5 prism-input text-sm">
                    <option value="">-- Pilih Tingkat Kelas --</option>
                    <option value="10" {{ old('tingkat') == '10' ? 'selected' : '' }}>Kelas 10 (Sepuluh)</option>
                    <option value="11" {{ old('tingkat') == '11' ? 'selected' : '' }}>Kelas 11 (Sebelas)</option>
                    <option value="12" {{ old('tingkat') == '12' ? 'selected' : '' }}>Kelas 12 (Dua Belas)</option>
                </select>
            </div>

            <div>
                <label for="jurusan_id" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1">
                    Kompetensi Keahlian (Jurusan) <span class="text-rose-500">*</span>
                </label>
                <select id="jurusan_id" name="jurusan_id" required
                    class="w-full px-3.5 py-2.5 prism-input text-sm">
                    <option value="">-- Pilih Jurusan --</option>
                    @foreach($jurusans as $j)
                        <option value="{{ $j->id }}" {{ old('jurusan_id') == $j->id ? 'selected' : '' }}>
                            {{ $j->kode }} - {{ $j->nama }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-100 dark:border-white/10">
                <a href="{{ route('kelas.index') }}" class="px-4 py-2.5 prism-btn-secondary text-xs">
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

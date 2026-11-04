@extends('layouts.app')

@section('title', 'Ubah Kelas')
@section('header_title', 'Ubah Data Kelas')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 sm:p-8">
        <div class="flex items-center justify-between pb-5 mb-5 border-b border-slate-100">
            <div>
                <h3 class="font-bold text-slate-900 text-lg">Form Ubah Data Kelas</h3>
                <p class="text-xs text-slate-500 mt-0.5">Perbarui informasi rombel kelas</p>
            </div>
            <a href="{{ route('kelas.index') }}" class="text-xs font-semibold text-slate-600 hover:text-slate-900">
                &larr; Kembali
            </a>
        </div>

        <form action="{{ route('kelas.update', $kelas->id) }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label for="tahun_ajaran_id" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1">
                    Tahun Ajaran <span class="text-rose-500">*</span>
                </label>
                <select id="tahun_ajaran_id" name="tahun_ajaran_id" required
                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm bg-white">
                    <option value="">-- Pilih Tahun Ajaran --</option>
                    @foreach($tahunAjarans as $tahunAjaran)
                        <option value="{{ $tahunAjaran->id }}"
                            {{ (string) old('tahun_ajaran_id', $kelas->tahun_ajaran_id) === (string) $tahunAjaran->id ? 'selected' : '' }}>
                            {{ $tahunAjaran->nama }}{{ $tahunAjaran->aktif ? ' (aktif)' : '' }}
                        </option>
                    @endforeach
                </select>
                @error('tahun_ajaran_id')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="nama_kelas" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1">
                    Nama Kelas <span class="text-rose-500">*</span>
                </label>
                <input type="text" id="nama_kelas" name="nama_kelas" value="{{ old('nama_kelas', $kelas->nama_kelas) }}" required
                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
            </div>

            <div>
                <label for="tingkat" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1">
                    Tingkat Kelas <span class="text-rose-500">*</span>
                </label>
                <select id="tingkat" name="tingkat" required
                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm bg-white">
                    <option value="10" {{ old('tingkat', $kelas->tingkat) == '10' ? 'selected' : '' }}>Kelas 10 (Sepuluh)</option>
                    <option value="11" {{ old('tingkat', $kelas->tingkat) == '11' ? 'selected' : '' }}>Kelas 11 (Sebelas)</option>
                    <option value="12" {{ old('tingkat', $kelas->tingkat) == '12' ? 'selected' : '' }}>Kelas 12 (Dua Belas)</option>
                </select>
            </div>

            <div>
                <label for="jurusan_id" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1">
                    Kompetensi Keahlian (Jurusan) <span class="text-rose-500">*</span>
                </label>
                <select id="jurusan_id" name="jurusan_id" required
                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm bg-white">
                    @foreach($jurusans as $j)
                        <option value="{{ $j->id }}" {{ old('jurusan_id', $kelas->jurusan_id) == $j->id ? 'selected' : '' }}>
                            {{ $j->kode }} - {{ $j->nama }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-100">
                <a href="{{ route('kelas.index') }}" class="px-4 py-2.5 rounded-xl text-slate-700 bg-slate-100 hover:bg-slate-200 text-xs font-semibold transition">
                    Batal
                </a>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold transition shadow-sm">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

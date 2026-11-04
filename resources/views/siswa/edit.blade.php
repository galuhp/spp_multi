@extends('layouts.app')

@section('title', 'Ubah Data Siswa')
@section('header_title', 'Ubah Data Siswa')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 sm:p-8">
        <div class="flex items-center justify-between pb-5 mb-5 border-b border-slate-100">
            <div>
                <h3 class="font-bold text-slate-900 text-lg">Form Ubah Data Siswa</h3>
                <p class="text-xs text-slate-500 mt-0.5">Perbarui profil siswa, kelas, dan tarif SPP</p>
            </div>
            <a href="{{ route('siswa.index') }}" class="text-xs font-semibold text-slate-600 hover:text-slate-900">
                &larr; Kembali
            </a>
        </div>

        <form action="{{ route('siswa.update', $siswa->id) }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="nisn" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1">
                        NISN <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" id="nisn" name="nisn" value="{{ old('nisn', $siswa->nisn) }}" required maxlength="15"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm font-mono">
                </div>

                <div>
                    <label for="nis" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1">
                        NIS <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" id="nis" name="nis" value="{{ old('nis', $siswa->nis) }}" required maxlength="20"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm font-mono">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="sm:col-span-2">
                    <label for="nama" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1">
                        Nama Lengkap Siswa <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" id="nama" name="nama" value="{{ old('nama', $siswa->nama) }}" required
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
                </div>

                <div>
                    <label for="jenis_kelamin" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1">
                        Jenis Kelamin <span class="text-rose-500">*</span>
                    </label>
                    <select id="jenis_kelamin" name="jenis_kelamin" required
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm bg-white">
                        <option value="L" {{ old('jenis_kelamin', $siswa->jenis_kelamin) == 'L' ? 'selected' : '' }}>Laki-laki (L)</option>
                        <option value="P" {{ old('jenis_kelamin', $siswa->jenis_kelamin) == 'P' ? 'selected' : '' }}>Perempuan (P)</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="kelas_id" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1">
                        Rombongan Belajar (Kelas) <span class="text-rose-500">*</span>
                    </label>
                    <select id="kelas_id" name="kelas_id" required
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm bg-white">
                        @foreach($kelasList as $k)
                            <option value="{{ $k->id }}" {{ old('kelas_id', $siswa->kelas_id) == $k->id ? 'selected' : '' }}>
                                {{ $k->nama_kelas }} ({{ $k->jurusan?->nama }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="spp_id" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1">
                        Tarif SPP <span class="text-rose-500">*</span>
                    </label>
                    <select id="spp_id" name="spp_id" required
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm bg-white">
                        @foreach($sppList as $s)
                            <option value="{{ $s->id }}" {{ old('spp_id', $siswa->spp_id) == $s->id ? 'selected' : '' }}>
                                TA {{ $s->tahun_ajaran }} - {{ $s->formattedNominal() }}/bulan
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="no_telp" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1">
                        No. Telepon / WhatsApp
                    </label>
                    <input type="text" id="no_telp" name="no_telp" value="{{ old('no_telp', $siswa->no_telp) }}"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
                </div>

                <div>
                    <label for="alamat" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1">
                        Alamat Tinggal
                    </label>
                    <textarea id="alamat" name="alamat" rows="2"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">{{ old('alamat', $siswa->alamat) }}</textarea>
                </div>
            </div>

            <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-100">
                <a href="{{ route('siswa.index') }}" class="px-4 py-2.5 rounded-xl text-slate-700 bg-slate-100 hover:bg-slate-200 text-xs font-semibold transition">
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

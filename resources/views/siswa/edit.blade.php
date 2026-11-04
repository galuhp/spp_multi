@extends('layouts.app')

@section('title', 'Ubah Data Siswa')
@section('header_title', 'Ubah Data Siswa')

@section('content')
<div class="max-w-3xl mx-auto">
    <x-ui.card class="!max-w-none !p-6 sm:!p-8">
        <div class="flex items-center justify-between pb-5 mb-5 border-b border-zinc-200">
            <div>
                <h3 class="font-bold text-zinc-900 text-lg">Form Ubah Data Siswa</h3>
                <p class="text-xs text-zinc-500 mt-0.5">Perbarui profil siswa, kelas, dan tarif SPP</p>
            </div>
            <a href="{{ route('siswa.index') }}" class="text-xs font-semibold text-zinc-600 hover:text-zinc-900">
                &larr; Kembali
            </a>
        </div>

        <form action="{{ route('siswa.update', $siswa->id) }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="nisn" class="block text-sm font-medium text-zinc-700 mb-1.5">
                        NISN <span class="text-rose-500">*</span>
                    </label>
                    <x-ui.input id="nisn" name="nisn" value="{{ old('nisn', $siswa->nisn) }}" required maxlength="15" class="font-mono" />
                </div>

                <div>
                    <label for="nis" class="block text-sm font-medium text-zinc-700 mb-1.5">
                        NIS <span class="text-rose-500">*</span>
                    </label>
                    <x-ui.input id="nis" name="nis" value="{{ old('nis', $siswa->nis) }}" required maxlength="20" class="font-mono" />
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="sm:col-span-2">
                    <label for="nama" class="block text-sm font-medium text-zinc-700 mb-1.5">
                        Nama Lengkap Siswa <span class="text-rose-500">*</span>
                    </label>
                    <x-ui.input id="nama" name="nama" value="{{ old('nama', $siswa->nama) }}" required />
                </div>

                <div>
                    <label for="jenis_kelamin" class="block text-sm font-medium text-zinc-700 mb-1.5">
                        Jenis Kelamin <span class="text-rose-500">*</span>
                    </label>
                    <select id="jenis_kelamin" name="jenis_kelamin" required
                        class="w-full min-h-10 px-3 rounded-field border border-zinc-300 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-zinc-400">
                        <option value="L" {{ old('jenis_kelamin', $siswa->jenis_kelamin) == 'L' ? 'selected' : '' }}>Laki-laki (L)</option>
                        <option value="P" {{ old('jenis_kelamin', $siswa->jenis_kelamin) == 'P' ? 'selected' : '' }}>Perempuan (P)</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="kelas_id" class="block text-sm font-medium text-zinc-700 mb-1.5">
                        Rombongan Belajar (Kelas) <span class="text-rose-500">*</span>
                    </label>
                    <select id="kelas_id" name="kelas_id" required
                        class="w-full min-h-10 px-3 rounded-field border border-zinc-300 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-zinc-400">
                        @foreach($kelasList as $k)
                            <option value="{{ $k->id }}" {{ old('kelas_id', $siswa->kelas_id) == $k->id ? 'selected' : '' }}>
                                {{ $k->nama_kelas }} ({{ $k->jurusan?->nama }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="spp_id" class="block text-sm font-medium text-zinc-700 mb-1.5">
                        Tarif SPP <span class="text-rose-500">*</span>
                    </label>
                    <select id="spp_id" name="spp_id" required
                        class="w-full min-h-10 px-3 rounded-field border border-zinc-300 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-zinc-400">
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
                    <label for="no_telp" class="block text-sm font-medium text-zinc-700 mb-1.5">
                        No. Telepon / WhatsApp
                    </label>
                    <x-ui.input id="no_telp" name="no_telp" value="{{ old('no_telp', $siswa->no_telp) }}" />
                </div>

                <div>
                    <label for="alamat" class="block text-sm font-medium text-zinc-700 mb-1.5">
                        Alamat Tinggal
                    </label>
                    <x-ui.textarea id="alamat" name="alamat" rows="2">{{ old('alamat', $siswa->alamat) }}</x-ui.textarea>
                </div>
            </div>

            <div class="pt-4 flex items-center justify-end gap-3 border-t border-zinc-200">
                <x-ui.button as="a" href="{{ route('siswa.index') }}" variant="secondary">Batal</x-ui.button>
                <x-ui.button type="submit" variant="primary">Simpan Perubahan</x-ui.button>
            </div>
        </form>
    </x-ui.card>
</div>
@endsection
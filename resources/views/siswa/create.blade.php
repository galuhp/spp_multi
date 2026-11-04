@extends('layouts.app')

@section('title', 'Tambah Siswa')
@section('header_title', 'Tambah Data Siswa')

@section('content')
<div class="max-w-3xl mx-auto">
    <x-ui.card class="!max-w-none !p-6 sm:!p-8">
        <div class="flex items-center justify-between pb-5 mb-5 border-b border-zinc-200">
            <div>
                <h3 class="font-bold text-zinc-900 text-lg">Form Tambah Siswa</h3>
                <p class="text-xs text-zinc-500 mt-0.5">Daftarkan data siswa beserta kelas dan penetapan tarif SPP</p>
            </div>
            <a href="{{ route('siswa.index') }}" class="text-xs font-semibold text-zinc-600 hover:text-zinc-900">
                &larr; Kembali
            </a>
        </div>

        <form action="{{ route('siswa.store') }}" method="POST" class="space-y-4">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="nisn" class="block text-sm font-medium text-zinc-700 mb-1.5">
                        NISN (Nomor Induk Siswa Nasional) <span class="text-rose-500">*</span>
                    </label>
                    <x-ui.input id="nisn" name="nisn" value="{{ old('nisn') }}" required maxlength="15" placeholder="10 digit nomor NISN" class="font-mono" />
                </div>

                <div>
                    <label for="nis" class="block text-sm font-medium text-zinc-700 mb-1.5">
                        NIS (Nomor Induk Sekolah) <span class="text-rose-500">*</span>
                    </label>
                    <x-ui.input id="nis" name="nis" value="{{ old('nis') }}" required maxlength="20" placeholder="Contoh: 2026001" class="font-mono" />
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="sm:col-span-2">
                    <label for="nama" class="block text-sm font-medium text-zinc-700 mb-1.5">
                        Nama Lengkap Siswa <span class="text-rose-500">*</span>
                    </label>
                    <x-ui.input id="nama" name="nama" value="{{ old('nama') }}" required placeholder="Nama lengkap sesuai ijazah/akta" />
                </div>

                <div>
                    <label for="jenis_kelamin" class="block text-sm font-medium text-zinc-700 mb-1.5">
                        Jenis Kelamin <span class="text-rose-500">*</span>
                    </label>
                    <select id="jenis_kelamin" name="jenis_kelamin" required
                        class="w-full min-h-10 px-3 rounded-field border border-zinc-300 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-zinc-400">
                        <option value="L" {{ old('jenis_kelamin') == 'L' ? 'selected' : '' }}>Laki-laki (L)</option>
                        <option value="P" {{ old('jenis_kelamin') == 'P' ? 'selected' : '' }}>Perempuan (P)</option>
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
                        <option value="">-- Pilih Kelas --</option>
                        @foreach($kelasList as $k)
                            <option value="{{ $k->id }}" {{ old('kelas_id') == $k->id ? 'selected' : '' }}>
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
                        <option value="">-- Pilih Tarif SPP --</option>
                        @foreach($sppList as $s)
                            <option value="{{ $s->id }}" {{ old('spp_id') == $s->id ? 'selected' : '' }}>
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
                    <x-ui.input id="no_telp" name="no_telp" value="{{ old('no_telp') }}" placeholder="Contoh: 08123456789" />
                </div>

                <div>
                    <label for="alamat" class="block text-sm font-medium text-zinc-700 mb-1.5">
                        Alamat Tinggal
                    </label>
                    <x-ui.textarea id="alamat" name="alamat" rows="2" placeholder="Alamat lengkap tempat tinggal siswa">{{ old('alamat') }}</x-ui.textarea>
                </div>
            </div>

            <div class="p-4 rounded-field bg-zinc-100 border border-zinc-200 flex items-start gap-3">
                <input type="checkbox" id="create_user_account" name="create_user_account" value="1" {{ old('create_user_account', true) ? 'checked' : '' }}
                    class="mt-1 size-4 rounded border-zinc-300 accent-zinc-900">
                <label for="create_user_account" class="text-xs text-zinc-700 cursor-pointer">
                    <strong class="font-semibold text-zinc-900 block">Buat Akun Portal Siswa Otomatis</strong>
                    Buatkan akun login siswa menggunakan NISN sebagai username dan kata sandi awal untuk mengecek tagihan dan riwayat pembayaran mandiri.
                </label>
            </div>

            <div class="pt-4 flex items-center justify-end gap-3 border-t border-zinc-200">
                <x-ui.button as="a" href="{{ route('siswa.index') }}" variant="secondary">Batal</x-ui.button>
                <x-ui.button type="submit" variant="primary">Simpan Data Siswa</x-ui.button>
            </div>
        </form>
    </x-ui.card>
</div>
@endsection
@extends('layouts.app')

@section('title', 'Tambah Siswa')
@section('header_title', 'Tambah Data Siswa')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 sm:p-8">
        <div class="flex items-center justify-between pb-5 mb-5 border-b border-slate-100">
            <div>
                <h3 class="font-bold text-slate-900 text-lg">Form Tambah Siswa</h3>
                <p class="text-xs text-slate-500 mt-0.5">Daftarkan data siswa beserta kelas dan penetapan tarif SPP</p>
            </div>
            <a href="{{ route('siswa.index') }}" class="text-xs font-semibold text-slate-600 hover:text-slate-900">
                &larr; Kembali
            </a>
        </div>

        <form action="{{ route('siswa.store') }}" method="POST" class="space-y-4">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="nisn" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1">
                        NISN (Nomor Induk Siswa Nasional) <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" id="nisn" name="nisn" value="{{ old('nisn') }}" required maxlength="15"
                        placeholder="10 digit nomor NISN"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm font-mono">
                </div>

                <div>
                    <label for="nis" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1">
                        NIS (Nomor Induk Sekolah) <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" id="nis" name="nis" value="{{ old('nis') }}" required maxlength="20"
                        placeholder="Contoh: 2026001"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm font-mono">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="sm:col-span-2">
                    <label for="nama" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1">
                        Nama Lengkap Siswa <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" id="nama" name="nama" value="{{ old('nama') }}" required
                        placeholder="Nama lengkap sesuai ijazah/akta"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
                </div>

                <div>
                    <label for="jenis_kelamin" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1">
                        Jenis Kelamin <span class="text-rose-500">*</span>
                    </label>
                    <select id="jenis_kelamin" name="jenis_kelamin" required
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm bg-white">
                        <option value="L" {{ old('jenis_kelamin') == 'L' ? 'selected' : '' }}>Laki-laki (L)</option>
                        <option value="P" {{ old('jenis_kelamin') == 'P' ? 'selected' : '' }}>Perempuan (P)</option>
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
                        <option value="">-- Pilih Kelas --</option>
                        @foreach($kelasList as $k)
                            <option value="{{ $k->id }}" {{ old('kelas_id') == $k->id ? 'selected' : '' }}>
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
                    <label for="no_telp" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1">
                        No. Telepon / WhatsApp
                    </label>
                    <input type="text" id="no_telp" name="no_telp" value="{{ old('no_telp') }}"
                        placeholder="Contoh: 08123456789"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
                </div>

                <div>
                    <label for="alamat" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1">
                        Alamat Tinggal
                    </label>
                    <textarea id="alamat" name="alamat" rows="2"
                        placeholder="Alamat lengkap tempat tinggal siswa"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">{{ old('alamat') }}</textarea>
                </div>
            </div>

            <!-- Auto User Account Checkbox -->
            <div class="p-4 rounded-xl bg-indigo-50/60 border border-indigo-100 flex items-start gap-3">
                <input type="checkbox" id="create_user_account" name="create_user_account" value="1" {{ old('create_user_account', true) ? 'checked' : '' }}
                    class="mt-1 w-4 h-4 rounded text-indigo-600 border-slate-300 focus:ring-indigo-500">
                <label for="create_user_account" class="text-xs text-slate-700 cursor-pointer">
                    <strong class="font-semibold text-indigo-900 block">Buat Akun Portal Siswa Otomatis</strong>
                    Buatkan akun login siswa menggunakan NISN sebagai username dan kata sandi awal untuk mengecek tagihan dan riwayat pembayaran mandiri.
                </label>
            </div>

            <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-100">
                <a href="{{ route('siswa.index') }}" class="px-4 py-2.5 rounded-xl text-slate-700 bg-slate-100 hover:bg-slate-200 text-xs font-semibold transition">
                    Batal
                </a>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold transition shadow-sm">
                    Simpan Data Siswa
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

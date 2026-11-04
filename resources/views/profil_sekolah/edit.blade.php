@extends('layouts.app')

@section('title', 'Profil & Identitas Sekolah')
@section('header_title', 'Pengaturan Profil Sekolah')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h3 class="text-xl font-bold text-slate-900">Pengaturan Profil Sekolah</h3>
            <p class="text-xs text-slate-500 mt-1">Identitas resmi yang akan tercetak otomatis pada kuitansi pembayaran, laporan rekapitulasi, dan hasil scan QR bukti bayar</p>
        </div>
    </div>

    <!-- Live Preview Card (Letterhead & Receipt Preview) -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 bg-gradient-to-r from-slate-50 to-white">
        <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-3 flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
            Pratinjau Kop Kuitansi & Dokumen
        </div>
        <div class="p-4 rounded-xl bg-white border border-slate-200 flex items-center justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <div class="w-12 h-12 rounded-xl bg-slate-900 text-white flex items-center justify-center font-black text-2xl shadow-sm shrink-0">
                    {{ strtoupper(substr($sekolah->nama_sekolah, 0, 1) ?: 'S') }}
                </div>
                <div>
                    <h4 class="font-black text-slate-900 text-base tracking-wide uppercase">{{ $sekolah->nama_sekolah }}</h4>
                    <p class="text-xs text-slate-600">{{ $sekolah->alamat }}, {{ $sekolah->kota }} &bull; Telp. {{ $sekolah->no_telp }}</p>
                    <p class="text-[11px] text-slate-400">Website: {{ $sekolah->website ?? '-' }} &bull; Email: {{ $sekolah->email ?? '-' }}</p>
                </div>
            </div>
            <div class="text-right hidden sm:block">
                <span class="px-2.5 py-1 rounded-md text-[10px] font-bold uppercase bg-slate-100 text-slate-600">
                    NPSN {{ $sekolah->npsn ?: '-' }}
                </span>
            </div>
        </div>
    </div>

    <!-- Form Setting Profil -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 sm:p-8">
        <form action="{{ route('sekolah.update') }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Section 1: Identitas Sekolah -->
            <div>
                <h4 class="text-xs font-bold uppercase tracking-wider text-indigo-700 pb-2 border-b border-slate-100 mb-4 flex items-center gap-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-indigo-600"></span>
                    1. Identitas & Jenjang Sekolah
                </h4>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="sm:col-span-2">
                        <label for="nama_sekolah" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1">
                            Nama Sekolah <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" id="nama_sekolah" name="nama_sekolah" value="{{ old('nama_sekolah', $sekolah->nama_sekolah) }}" required
                            placeholder="Contoh: SMK Negeri 1 Bandung"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm font-semibold">
                    </div>

                    <div>
                        <label for="jenjang" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1">
                            Jenjang Pendidikan
                        </label>
                        <select id="jenjang" name="jenjang" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm bg-white">
                            @foreach(['SMK', 'SMA', 'MA', 'SMP', 'MTs', 'SD', 'MI'] as $j)
                                <option value="{{ $j }}" {{ old('jenjang', $sekolah->jenjang) === $j ? 'selected' : '' }}>{{ $j }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="npsn" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1">
                            NPSN (Nomor Pokok Sekolah)
                        </label>
                        <input type="text" id="npsn" name="npsn" value="{{ old('npsn', $sekolah->npsn) }}"
                            placeholder="Contoh: 20100001"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm font-mono">
                    </div>

                    <div class="sm:col-span-2">
                        <label for="website" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1">
                            Website Resmi Sekolah
                        </label>
                        <input type="text" id="website" name="website" value="{{ old('website', $sekolah->website) }}"
                            placeholder="Contoh: www.smknegericontoh.sch.id"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
                    </div>
                </div>
            </div>

            <!-- Section 2: Instansi & Kop Kedinasan -->
            <div>
                <h4 class="text-xs font-bold uppercase tracking-wider text-indigo-700 pb-2 border-b border-slate-100 mb-4 flex items-center gap-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-indigo-600"></span>
                    2. Instansi Induk (Header Dokumen Laporan)
                </h4>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="pemerintah_daerah" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1">
                            Pemerintah Daerah (Header Baris 1)
                        </label>
                        <input type="text" id="pemerintah_daerah" name="pemerintah_daerah" value="{{ old('pemerintah_daerah', $sekolah->pemerintah_daerah) }}"
                            placeholder="Contoh: PEMERINTAH DAERAH PROVINSI JAWA BARAT"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
                    </div>

                    <div>
                        <label for="dinas_pendidikan" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1">
                            Dinas Pendidikan (Header Baris 2)
                        </label>
                        <input type="text" id="dinas_pendidikan" name="dinas_pendidikan" value="{{ old('dinas_pendidikan', $sekolah->dinas_pendidikan) }}"
                            placeholder="Contoh: DINAS PENDIDIKAN DAN KEBUDAYAAN"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
                    </div>
                </div>
            </div>

            <!-- Section 3: Alamat & Kontak -->
            <div>
                <h4 class="text-xs font-bold uppercase tracking-wider text-indigo-700 pb-2 border-b border-slate-100 mb-4 flex items-center gap-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-indigo-600"></span>
                    3. Alamat & Kontak Sekolah
                </h4>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="sm:col-span-3">
                        <label for="alamat" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1">
                            Alamat Jalan / Gedung
                        </label>
                        <input type="text" id="alamat" name="alamat" value="{{ old('alamat', $sekolah->alamat) }}"
                            placeholder="Contoh: Jl. Pendidikan No. 123"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
                    </div>

                    <div>
                        <label for="kota" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1">
                            Kota / Kabupaten
                        </label>
                        <input type="text" id="kota" name="kota" value="{{ old('kota', $sekolah->kota) }}"
                            placeholder="Contoh: Bandung"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
                    </div>

                    <div>
                        <label for="kode_pos" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1">
                            Kode Pos
                        </label>
                        <input type="text" id="kode_pos" name="kode_pos" value="{{ old('kode_pos', $sekolah->kode_pos) }}"
                            placeholder="Contoh: 40123"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm font-mono">
                    </div>

                    <div>
                        <label for="no_telp" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1">
                            No. Telepon / Fax / WA TU
                        </label>
                        <input type="text" id="no_telp" name="no_telp" value="{{ old('no_telp', $sekolah->no_telp) }}"
                            placeholder="Contoh: (022) 1234567"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
                    </div>

                    <div class="sm:col-span-3">
                        <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1">
                            Email Resmi Sekolah
                        </label>
                        <input type="email" id="email" name="email" value="{{ old('email', $sekolah->email) }}"
                            placeholder="Contoh: info@smknegericontoh.sch.id"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
                    </div>
                </div>
            </div>

            <!-- Section 4: Kepala Sekolah & Tanda Tangan Dokumen -->
            <div>
                <h4 class="text-xs font-bold uppercase tracking-wider text-indigo-700 pb-2 border-b border-slate-100 mb-4 flex items-center gap-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-indigo-600"></span>
                    4. Kepala Sekolah (Penandatangan Laporan Resmi)
                </h4>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="nama_kepala_sekolah" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1">
                            Nama Lengkap Kepala Sekolah & Gelar
                        </label>
                        <input type="text" id="nama_kepala_sekolah" name="nama_kepala_sekolah" value="{{ old('nama_kepala_sekolah', $sekolah->nama_kepala_sekolah) }}"
                            placeholder="Contoh: Drs. H. Rahmat Hidayat, M.M.Pd."
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
                    </div>

                    <div>
                        <label for="nip_kepala_sekolah" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1">
                            NIP Kepala Sekolah
                        </label>
                        <input type="text" id="nip_kepala_sekolah" name="nip_kepala_sekolah" value="{{ old('nip_kepala_sekolah', $sekolah->nip_kepala_sekolah) }}"
                            placeholder="Contoh: 19700101 199503 1 001"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm font-mono">
                    </div>
                </div>
            </div>

            <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-100">
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs transition shadow-sm">
                    Simpan Perubahan Profil Sekolah
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

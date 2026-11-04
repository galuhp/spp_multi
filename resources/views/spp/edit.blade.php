@extends('layouts.app')

@section('title', 'Ubah Tarif - ' . $spp->kategori)
@section('header_title', 'Ubah Data Tarif & Kategori')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 sm:p-8">
        <div class="flex items-center justify-between pb-5 mb-5 border-b border-slate-100">
            <div>
                <h3 class="font-bold text-slate-900 text-lg">Form Ubah Tarif</h3>
                <p class="text-xs text-slate-500 mt-0.5">Perbarui data kategori, tipe pembayaran, tahun ajaran, atau nominal</p>
            </div>
            <a href="{{ route('spp.index') }}" class="text-xs font-semibold text-slate-600 hover:text-indigo-600">
                &larr; Kembali
            </a>
        </div>

        <form action="{{ route('spp.update', $spp->id) }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            <!-- Kategori Tarif -->
            <div>
                <label for="kategori" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1">
                    Kategori / Pos Tarif <span class="text-rose-500">*</span>
                </label>
                <div class="flex flex-wrap gap-2 mb-2">
                    @foreach($availableKategori as $preset)
                        <button type="button" onclick="setKategori('{{ $preset }}')"
                            class="px-3 py-1 rounded-lg border border-slate-200 bg-slate-50 text-xs font-semibold hover:border-indigo-400 hover:bg-indigo-50 text-slate-700 cursor-pointer transition">
                            {{ $preset }}
                        </button>
                    @endforeach
                </div>
                <input type="text" id="kategori" name="kategori" value="{{ old('kategori', $spp->kategori) }}" required
                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
            </div>

            <!-- Tipe Pembayaran -->
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                    Tipe Pembayaran <span class="text-rose-500">*</span>
                </label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="p-3 rounded-xl border border-slate-200 bg-slate-50/50 flex items-start gap-2.5 cursor-pointer hover:border-indigo-400 transition">
                        <input type="radio" name="tipe" id="tipe-bulanan" value="bulanan" {{ old('tipe', $spp->tipe ?? 'bulanan') === 'bulanan' ? 'checked' : '' }}
                            class="mt-0.5 w-4 h-4 text-indigo-600">
                        <div>
                            <div class="font-bold text-xs text-slate-900">Bulanan (12 Bulan / TA)</div>
                            <div class="text-[11px] text-slate-500 mt-0.5">Iuran rutin bulanan seperti SPP sekolah</div>
                        </div>
                    </label>

                    <label class="p-3 rounded-xl border border-slate-200 bg-slate-50/50 flex items-start gap-2.5 cursor-pointer hover:border-indigo-400 transition">
                        <input type="radio" name="tipe" id="tipe-sekali-bayar" value="sekali_bayar" {{ old('tipe', $spp->tipe ?? 'bulanan') === 'sekali_bayar' ? 'checked' : '' }}
                            class="mt-0.5 w-4 h-4 text-indigo-600">
                        <div>
                            <div class="font-bold text-xs text-slate-900">Sekali Bayar / Bebas</div>
                            <div class="text-[11px] text-slate-500 mt-0.5">Uang gedung, uang seragam, ujian, dll.</div>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Nama Tarif Spesifik (Opsional) -->
            <div>
                <label for="nama" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1">
                    Nama / Label Khusus (Opsional)
                </label>
                <input type="text" id="nama" name="nama" value="{{ old('nama', $spp->nama) }}"
                    placeholder="Contoh: Uang Gedung Angkatan 2026 atau Seragam Lengkap 4 Stel"
                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="tahun" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1">
                        Tahun <span class="text-rose-500">*</span>
                    </label>
                    <input type="number" id="tahun" name="tahun" value="{{ old('tahun', $spp->tahun) }}" required min="2000" max="2100"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
                </div>

                <div>
                    <label for="tahun_ajaran" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1">
                        Tahun Ajaran <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" id="tahun_ajaran" name="tahun_ajaran" value="{{ old('tahun_ajaran', $spp->tahun_ajaran) }}" required
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
                </div>
            </div>

            <div>
                <label for="nominal" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1">
                    Besaran Nominal Tarif (Rp) <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-sm font-bold text-slate-400 pointer-events-none">Rp</span>
                    <input type="number" id="nominal" name="nominal" value="{{ old('nominal', $spp->nominal) }}" required min="1000" step="5000"
                        class="w-full pl-11 pr-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm font-semibold">
                </div>
            </div>

            <div>
                <label for="keterangan" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1">
                    Keterangan
                </label>
                <input type="text" id="keterangan" name="keterangan" value="{{ old('keterangan', $spp->keterangan) }}"
                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
            </div>

            <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-100">
                <a href="{{ route('spp.index') }}" class="px-4 py-2.5 rounded-xl border border-slate-300 hover:bg-slate-50 text-slate-700 font-semibold text-xs transition">
                    Batal
                </a>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs transition shadow-sm">
                    Perbarui Tarif
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function setKategori(nama) {
        document.getElementById('kategori').value = nama;
        if (nama === 'SPP') {
            document.getElementById('tipe-bulanan').checked = true;
        } else {
            document.getElementById('tipe-sekali-bayar').checked = true;
        }
    }
</script>
@endsection

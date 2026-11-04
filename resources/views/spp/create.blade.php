@extends('layouts.app')

@section('title', 'Tambah Tarif Pembayaran')
@section('header_title', 'Tambah Tarif & Kategori Baru')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 sm:p-8">
        <div class="flex items-center justify-between pb-5 mb-5 border-b border-slate-100">
            <div>
                <h3 class="font-bold text-slate-900 text-lg">Form Tambah Tarif</h3>
                <p class="text-xs text-slate-500 mt-0.5">Tentukan kategori (SPP, uang gedung, seragam, dll.), tipe pembayaran, dan nominal</p>
            </div>
            <a href="{{ route('spp.index') }}" class="text-xs font-semibold text-slate-600 hover:text-indigo-600">
                &larr; Kembali
            </a>
        </div>

        <form action="{{ route('spp.store') }}" method="POST" class="space-y-4">
            @csrf

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
                <input type="text" id="kategori" name="kategori" value="{{ old('kategori', 'SPP') }}" required
                    placeholder="Contoh: SPP, Uang Gedung, Uang Seragam, Biaya Praktik"
                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
            </div>

            <!-- Tipe Pembayaran -->
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                    Tipe Pembayaran <span class="text-rose-500">*</span>
                </label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="p-3 rounded-xl border border-slate-200 bg-slate-50/50 flex items-start gap-2.5 cursor-pointer hover:border-indigo-400 transition">
                        <input type="radio" name="tipe" id="tipe-bulanan" value="bulanan" {{ old('tipe', 'bulanan') === 'bulanan' ? 'checked' : '' }}
                            class="mt-0.5 w-4 h-4 text-indigo-600">
                        <div>
                            <div class="font-bold text-xs text-slate-900">Bulanan (12 Bulan / TA)</div>
                            <div class="text-[11px] text-slate-500 mt-0.5">Iuran rutin bulanan seperti SPP sekolah</div>
                        </div>
                    </label>

                    <label class="p-3 rounded-xl border border-slate-200 bg-slate-50/50 flex items-start gap-2.5 cursor-pointer hover:border-indigo-400 transition">
                        <input type="radio" name="tipe" id="tipe-sekali-bayar" value="sekali_bayar" {{ old('tipe') === 'sekali_bayar' ? 'checked' : '' }}
                            class="mt-0.5 w-4 h-4 text-indigo-600">
                        <div>
                            <div class="font-bold text-xs text-slate-900">Sekali Bayar / Bebas</div>
                            <div class="text-[11px] text-slate-500 mt-0.5">Uang gedung, uang seragam, uang ujian, praktik, dll.</div>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Nama Tarif Spesifik (Opsional) -->
            <div>
                <label for="nama" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1">
                    Nama / Label Khusus (Opsional)
                </label>
                <input type="text" id="nama" name="nama" value="{{ old('nama') }}"
                    placeholder="Contoh: Uang Gedung Angkatan 2026 atau Seragam Lengkap 4 Stel"
                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="tahun" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1">
                        Tahun <span class="text-rose-500">*</span>
                    </label>
                    <input type="number" id="tahun" name="tahun" value="{{ old('tahun', date('Y')) }}" required min="2000" max="2100"
                        placeholder="Contoh: 2026"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
                </div>

                <div>
                    <label for="tahun_ajaran" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1">
                        Tahun Ajaran <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" id="tahun_ajaran" name="tahun_ajaran" value="{{ old('tahun_ajaran', date('Y') . '/' . (date('Y') + 1)) }}" required
                        placeholder="Contoh: 2026/2027"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
                </div>
            </div>

            <div>
                <label for="nominal" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1">
                    Besaran Nominal Tarif (Rp) <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-sm font-bold text-slate-400 pointer-events-none">Rp</span>
                    <input type="number" id="nominal" name="nominal" value="{{ old('nominal') }}" required min="1000" step="5000"
                        placeholder="250000"
                        class="w-full pl-11 pr-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm font-semibold">
                </div>
                <p class="text-[11px] text-slate-400 mt-1">Untuk tipe bulanan: nominal per bulan. Untuk sekali bayar: nominal total tagihan.</p>
            </div>

            <div>
                <label for="keterangan" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1">
                    Keterangan
                </label>
                <input type="text" id="keterangan" name="keterangan" value="{{ old('keterangan') }}"
                    placeholder="Contoh: Sumbangan pengembangan sarana dan gedung angkatan 2026"
                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
            </div>

            <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-100">
                <a href="{{ route('spp.index') }}" class="px-4 py-2.5 rounded-xl border border-slate-300 hover:bg-slate-50 text-slate-700 font-semibold text-xs transition">
                    Batal
                </a>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs transition shadow-sm">
                    Simpan Tarif
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

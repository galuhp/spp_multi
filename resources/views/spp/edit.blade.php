@extends('layouts.app')

@section('title', 'Ubah Tarif - ' . $spp->kategori)
@section('header_title', 'Ubah Data Tarif & Kategori')

@section('content')
<div class="max-w-2xl mx-auto">
    <x-ui.card class="!max-w-none !p-6 sm:!p-8">
        <div class="flex items-center justify-between pb-5 mb-5 border-b border-zinc-200">
            <div>
                <h3 class="font-bold text-zinc-900 text-lg">Form Ubah Tarif</h3>
                <p class="text-xs text-zinc-500 mt-0.5">Perbarui kategori, tipe pembayaran, tahun ajaran, atau nominal</p>
            </div>
            <a href="{{ route('spp.index') }}" class="text-xs font-semibold text-zinc-600 hover:text-zinc-900">
                &larr; Kembali
            </a>
        </div>

        <form action="{{ route('spp.update', $spp->id) }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label for="kategori" class="block text-sm font-medium text-zinc-700 mb-1.5">
                    Kategori / Pos Tarif <span class="text-rose-500">*</span>
                </label>
                <div class="flex flex-wrap gap-2 mb-2">
                    @foreach($availableKategori as $preset)
                        <x-ui.button type="button" variant="outline" size="sm" onclick="setKategori('{{ $preset }}')">
                            {{ $preset }}
                        </x-ui.button>
                    @endforeach
                </div>
                <x-ui.input id="kategori" name="kategori" value="{{ old('kategori', $spp->kategori) }}" required />
            </div>

            <div>
                <label class="block text-sm font-medium text-zinc-700 mb-1.5">
                    Tipe Pembayaran <span class="text-rose-500">*</span>
                </label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="p-3 rounded-field border border-zinc-200 bg-zinc-50 flex items-start gap-2.5 cursor-pointer hover:border-zinc-400 transition">
                        <input type="radio" name="tipe" id="tipe-bulanan" value="bulanan" {{ old('tipe', $spp->tipe ?? 'bulanan') === 'bulanan' ? 'checked' : '' }}
                            class="mt-0.5 size-4 accent-zinc-900">
                        <div>
                            <div class="font-bold text-xs text-zinc-900">Bulanan (12 Bulan / TA)</div>
                            <div class="text-[11px] text-zinc-500 mt-0.5">Iuran rutin bulanan seperti SPP sekolah</div>
                        </div>
                    </label>

                    <label class="p-3 rounded-field border border-zinc-200 bg-zinc-50 flex items-start gap-2.5 cursor-pointer hover:border-zinc-400 transition">
                        <input type="radio" name="tipe" id="tipe-sekali-bayar" value="sekali_bayar" {{ old('tipe', $spp->tipe ?? 'bulanan') === 'sekali_bayar' ? 'checked' : '' }}
                            class="mt-0.5 size-4 accent-zinc-900">
                        <div>
                            <div class="font-bold text-xs text-zinc-900">Sekali Bayar / Bebas</div>
                            <div class="text-[11px] text-zinc-500 mt-0.5">Uang gedung, seragam, ujian, dll.</div>
                        </div>
                    </label>
                </div>
            </div>

            <div>
                <label for="nama" class="block text-sm font-medium text-zinc-700 mb-1.5">
                    Nama / Label Khusus (Opsional)
                </label>
                <x-ui.input id="nama" name="nama" value="{{ old('nama', $spp->nama) }}"
                    placeholder="Contoh: Uang Gedung Angkatan 2026" />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label for="tahun" class="block text-sm font-medium text-zinc-700 mb-1.5">
                        Tahun <span class="text-rose-500">*</span>
                    </label>
                    <x-ui.input id="tahun" name="tahun" type="number" value="{{ old('tahun', $spp->tahun) }}" required min="2000" max="2100" />
                </div>

                <div>
                    <label for="tahun_ajaran" class="block text-sm font-medium text-zinc-700 mb-1.5">
                        Tahun Ajaran <span class="text-rose-500">*</span>
                    </label>
                    <x-ui.input id="tahun_ajaran" name="tahun_ajaran" value="{{ old('tahun_ajaran', $spp->tahun_ajaran) }}" required />
                </div>

                <div>
                    <label for="tingkat" class="block text-sm font-medium text-zinc-700 mb-1.5">
                        Tingkat SPP
                    </label>
                    <select id="tingkat" name="tingkat"
                        class="w-full rounded-xl border border-zinc-300 bg-white px-3.5 py-2.5 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none focus:ring-2 focus:ring-zinc-200">
                        <option value="">Semua Tingkat / Non-SPP</option>
                        @foreach(\App\Models\Spp::TINGKAT_LIST as $nilai => $label)
                            <option value="{{ $nilai }}" @selected(old('tingkat', $spp->tingkat) === $nilai)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="text-[11px] text-zinc-400 mt-1">Wajib untuk tarif SPP.</p>
                </div>
            </div>

            <div>
                <label for="nominal" class="block text-sm font-medium text-zinc-700 mb-1.5">
                    Besaran Nominal Tarif (Rp) <span class="text-rose-500">*</span>
                </label>
                <x-ui.input id="nominal" name="nominal" type="number" value="{{ old('nominal', $spp->nominal) }}" required min="1000" step="5000" prefix="Rp" />
            </div>

            <div>
                <label for="keterangan" class="block text-sm font-medium text-zinc-700 mb-1.5">
                    Keterangan
                </label>
                <x-ui.input id="keterangan" name="keterangan" value="{{ old('keterangan', $spp->keterangan) }}" />
            </div>

            <div class="pt-4 flex items-center justify-end gap-3 border-t border-zinc-200">
                <x-ui.button as="a" href="{{ route('spp.index') }}" variant="secondary">Batal</x-ui.button>
                <x-ui.button type="submit" variant="primary">Perbarui Tarif</x-ui.button>
            </div>
        </form>
    </x-ui.card>
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
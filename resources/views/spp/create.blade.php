@extends('layouts.app')

@section('title', 'Tambah Tarif SPP')
@section('header_title', 'Tambah Tarif SPP Baru')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 sm:p-8">
        <div class="flex items-center justify-between pb-5 mb-5 border-b border-slate-100">
            <div>
                <h3 class="font-bold text-slate-900 text-lg">Form Tambah Tarif SPP</h3>
                <p class="text-xs text-slate-500 mt-0.5">Tentukan tahun, tahun ajaran, dan besaran nominal per bulan</p>
            </div>
            <a href="{{ route('spp.index') }}" class="text-xs font-semibold text-slate-600 hover:text-slate-900">
                &larr; Kembali
            </a>
        </div>

        <form action="{{ route('spp.store') }}" method="POST" class="space-y-4">
            @csrf

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
                    Nominal Iuran SPP per Bulan (Rp) <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-sm font-bold text-slate-400">Rp</span>
                    <input type="number" id="nominal" name="nominal" value="{{ old('nominal') }}" required min="1000" step="5000"
                        placeholder="250000"
                        class="w-full pl-11 pr-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm font-semibold text-slate-800">
                </div>
                <p class="text-[11px] text-slate-400 mt-1">Masukkan nominal angka murni tanpa titik atau koma.</p>
            </div>

            <div>
                <label for="keterangan" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1">
                    Keterangan
                </label>
                <input type="text" id="keterangan" name="keterangan" value="{{ old('keterangan') }}"
                    placeholder="Contoh: Tarif SPP Reguler Angkatan 2026/2027"
                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
            </div>

            <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-100">
                <a href="{{ route('spp.index') }}" class="px-4 py-2.5 rounded-xl text-slate-700 bg-slate-100 hover:bg-slate-200 text-xs font-semibold transition">
                    Batal
                </a>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold transition shadow-sm">
                    Simpan Tarif SPP
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@extends('layouts.app')

@section('title', 'Ubah Tarif SPP')
@section('header_title', 'Ubah Tarif SPP')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 sm:p-8">
        <div class="flex items-center justify-between pb-5 mb-5 border-b border-slate-100">
            <div>
                <h3 class="font-bold text-slate-900 text-lg">Form Ubah Tarif SPP</h3>
                <p class="text-xs text-slate-500 mt-0.5">Perbarui tahun dan besaran nominal tarif SPP</p>
            </div>
            <a href="{{ route('spp.index') }}" class="text-xs font-semibold text-slate-600 hover:text-slate-900">
                &larr; Kembali
            </a>
        </div>

        <form action="{{ route('spp.update', $spp->id) }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

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
                    Nominal Iuran SPP per Bulan (Rp) <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-sm font-bold text-slate-400">Rp</span>
                    <input type="number" id="nominal" name="nominal" value="{{ old('nominal', $spp->nominal) }}" required min="1000" step="5000"
                        class="w-full pl-11 pr-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm font-semibold text-slate-800">
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
                <a href="{{ route('spp.index') }}" class="px-4 py-2.5 rounded-xl text-slate-700 bg-slate-100 hover:bg-slate-200 text-xs font-semibold transition">
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

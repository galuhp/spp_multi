@extends('layouts.app')

@section('title', 'Tagihan SPP Siswa')
@section('header_title', 'Status Tagihan & Iuran SPP')

@section('content')
<div class="space-y-6">
    <!-- Student Header Summary -->
    <div class="p-6 rounded-2xl bg-white border border-slate-200 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <h3 class="text-xl font-bold text-slate-900">{{ $siswa->nama }}</h3>
            <div class="text-xs text-slate-500 mt-1 flex flex-wrap gap-x-4 gap-y-1">
                <span>NISN: <strong class="font-mono text-slate-700">{{ $siswa->nisn }}</strong></span>
                <span>Kelas: <strong class="text-slate-700">{{ $siswa->kelas?->nama_kelas }}</strong></span>
                <span>Kompetensi: <strong class="text-slate-700">{{ $siswa->kelas?->jurusan?->nama }}</strong></span>
            </div>
            <div class="text-xs text-slate-500 mt-1">
                Tahun Ajaran: <strong class="text-slate-800">{{ $siswa->spp?->tahun_ajaran }}</strong> &bull; Tarif: <strong class="text-indigo-700">{{ $siswa->spp?->formattedNominal() }}</strong> / bulan
            </div>
        </div>

        <div class="flex gap-4 border-t md:border-t-0 md:border-l border-slate-100 pt-3 md:pt-0 md:pl-6 shrink-0">
            <div>
                <div class="text-xs text-slate-500 uppercase font-semibold">Sudah Terbayar</div>
                <div class="text-xl font-bold text-emerald-600">Rp {{ number_format($totalLunas, 0, ',', '.') }}</div>
            </div>
            <div class="pl-4 border-l border-slate-100">
                <div class="text-xs text-slate-500 uppercase font-semibold">Sisa Tagihan</div>
                <div class="text-xl font-bold text-rose-600">Rp {{ number_format($totalBelumLunas, 0, ',', '.') }}</div>
            </div>
        </div>
    </div>

    <!-- Monthly Cards -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
        <h4 class="font-bold text-slate-900 text-base mb-1">Rincian Iuran SPP per Bulan (TA {{ $siswa->spp?->tahun_ajaran }})</h4>
        <p class="text-xs text-slate-500 mb-5">Untuk pelunasan tagihan yang belum dibayar, silakan melakukan pembayaran langsung melalui petugas kasir sekolah atau transfer bank.</p>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            @foreach($bulanStatus as $item)
                <div class="p-4 rounded-xl border {{ $item['lunas'] ? 'bg-emerald-50/60 border-emerald-200' : 'bg-slate-50 border-slate-200' }} flex flex-col justify-between">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-bold text-slate-800">{{ $item['bulan'] }} {{ $item['tahun'] }}</span>
                        @if($item['lunas'])
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-600 text-white">
                                LUNAS
                            </span>
                        @else
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-500 text-white">
                                BELUM
                            </span>
                        @endif
                    </div>

                    <div class="mt-2 text-xs">
                        <div class="text-slate-500">Nominal: <strong class="text-slate-800">Rp {{ number_format($item['nominal'], 0, ',', '.') }}</strong></div>
                        @if($item['lunas'])
                            <div class="text-[11px] text-emerald-700 mt-1">
                                Dibayar pada: {{ $item['pembayaran']->tgl_bayar->format('d/m/Y') }}
                            </div>
                            <div class="mt-3">
                                <a href="{{ route('portal.kuitansi', $item['pembayaran']->id) }}" target="_blank" class="w-full text-center block px-3 py-1.5 rounded-lg text-xs font-semibold bg-white border border-emerald-300 text-emerald-800 hover:bg-emerald-100 transition shadow-xs">
                                    Cetak Kuitansi
                                </a>
                            </div>
                        @else
                            <div class="text-[11px] text-rose-600 mt-1">
                                Menunggu pembayaran
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection

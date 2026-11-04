@extends('layouts.app')

@section('title', 'Tagihan SPP & Biaya Pendidikan')
@section('header_title', 'Status Tagihan & Iuran')

@section('content')
@php
    $grandTotalLunas = $totalLunas + ($totalNonSppLunas ?? 0);
    $grandTotalBelumLunas = $totalBelumLunas + ($totalNonSppBelumLunas ?? 0);
@endphp

<div class="space-y-6">
    <!-- Student Header Summary -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <h3 class="text-xl font-bold text-slate-900">{{ $siswa->nama }}</h3>
            <div class="text-xs text-slate-500 mt-1 flex flex-wrap gap-x-4 gap-y-1">
                <span>NISN: <strong class="font-mono text-slate-700">{{ $siswa->nisn }}</strong></span>
                <span>Kelas: <strong class="text-slate-700">{{ $siswa->kelas?->nama_kelas }}</strong></span>
                <span>Kompetensi: <strong class="text-slate-700">{{ $siswa->kelas?->jurusan?->nama }}</strong></span>
            </div>
            <div class="text-xs text-slate-500 mt-1">
                Tahun Ajaran: <strong class="text-slate-800">{{ $siswa->spp?->tahun_ajaran }}</strong> &bull; Tarif SPP: <strong class="text-indigo-700">{{ $siswa->spp?->formattedNominal() }}</strong>
            </div>
        </div>

        <div class="flex gap-4 border-t md:border-t-0 md:border-l border-slate-100 pt-3 md:pt-0 md:pl-6 shrink-0">
            <div>
                <div class="text-xs text-slate-500 uppercase font-semibold">Total Terbayar</div>
                <div class="text-xl font-bold text-emerald-600">Rp {{ number_format($grandTotalLunas, 0, ',', '.') }}</div>
            </div>
            <div class="pl-4 border-l border-slate-100">
                <div class="text-xs text-slate-500 uppercase font-semibold">Sisa Tagihan</div>
                <div class="text-xl font-bold text-rose-600">Rp {{ number_format($grandTotalBelumLunas, 0, ',', '.') }}</div>
            </div>
        </div>
    </div>

    <!-- Non-SPP Fees Cards (Uang Gedung, Uang Seragam, dll.) -->
    @if(!empty($nonSppStatus))
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6">
            <h4 class="font-bold text-slate-900 text-base mb-1">Tagihan Biaya Lainnya (Uang Gedung, Seragam, dll.)</h4>
            <p class="text-xs text-slate-500 mb-5">Daftar kewajiban biaya pendidikan di luar iuran bulanan untuk tahun ajaran {{ $siswa->spp?->tahun_ajaran }}.</p>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($nonSppStatus as $item)
                    @php
                        $tarif = $item['tarif'];
                        $isLunas = $item['lunas'];
                    @endphp
                    <div class="p-4 rounded-xl border flex flex-col justify-between
                        {{ $isLunas ? 'bg-emerald-50/70 border-emerald-200' : 'bg-slate-50/70 border-slate-200' }}">
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold border {{ $tarif->badgeClass() }}">
                                    {{ $tarif->kategori }}
                                </span>
                                @if($isLunas)
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-600 text-white">
                                        LUNAS
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-500 text-white">
                                        BELUM LUNAS
                                    </span>
                                @endif
                            </div>

                            <div class="font-bold text-sm text-slate-900 mt-1">
                                {{ $tarif->nama ?: $tarif->kategori }}
                            </div>
                            <div class="text-xs text-slate-500 mt-2 space-y-1">
                                <div class="flex justify-between">
                                    <span>Total Biaya:</span>
                                    <strong class="text-slate-800">Rp {{ number_format($item['nominal'], 0, ',', '.') }}</strong>
                                </div>
                                <div class="flex justify-between">
                                    <span>Sudah Dibayar:</span>
                                    <strong class="text-emerald-700">Rp {{ number_format($item['dibayar'], 0, ',', '.') }}</strong>
                                </div>
                                <div class="flex justify-between pt-1 border-t border-slate-200 font-bold">
                                    <span>Sisa Tagihan:</span>
                                    <span class="{{ $item['sisa'] > 0 ? 'text-rose-600' : 'text-slate-400' }}">Rp {{ number_format($item['sisa'], 0, ',', '.') }}</span>
                                </div>
                            </div>
                        </div>

                        @if($item['pembayarans']->isNotEmpty())
                            <div class="mt-4 pt-3 border-t border-slate-200 space-y-1.5">
                                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Bukti Pembayaran:</div>
                                @foreach($item['pembayarans'] as $p)
                                    <div class="flex items-center justify-between text-xs">
                                        <span class="font-mono text-[11px] text-slate-600">{{ $p->tgl_bayar->format('d/m/Y') }} (Rp {{ number_format($p->jumlah_bayar, 0, ',', '.') }})</span>
                                        <a href="{{ route('portal.kuitansi', $p->id) }}" target="_blank" class="text-indigo-600 hover:underline font-semibold text-[11px]">
                                            Kuitansi &rarr;
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Monthly Cards -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6">
        <h4 class="font-bold text-slate-900 text-base mb-1">Rincian Iuran SPP per Bulan (TA {{ $siswa->spp?->tahun_ajaran }})</h4>
        <p class="text-xs text-slate-500 mb-5">Untuk pelunasan tagihan yang belum dibayar, silakan melakukan pembayaran langsung melalui kasir sekolah.</p>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            @foreach($bulanStatus as $item)
                <div class="p-4 rounded-xl border {{ $item['lunas'] ? 'bg-emerald-50/70 border-emerald-200' : 'bg-slate-50/70 border-slate-200' }} flex flex-col justify-between">
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
                                <a href="{{ route('portal.kuitansi', $item['pembayaran']->id) }}" target="_blank" class="w-full text-center block px-3 py-1.5 rounded-lg text-xs font-semibold bg-white border border-emerald-300 text-emerald-800 hover:bg-emerald-50 transition shadow-xs">
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

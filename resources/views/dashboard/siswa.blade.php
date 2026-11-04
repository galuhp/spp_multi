@extends('layouts.app')

@section('title', 'Portal Siswa')
@section('header_title', 'Portal Informasi SPP Siswa')

@section('content')
<div class="space-y-6">
    @if(! $siswa)
        <x-ui.alerts variant="warning" icon="exclamation-triangle">
            <h4 class="font-bold text-base">Profil Siswa Belum Tertaut</h4>
            <p class="text-sm mt-1">Akun Anda belum ditautkan ke data siswa sekolah. Silakan hubungi bagian tata usaha / petugas SPP.</p>
        </x-ui.alerts>
    @else
        <!-- Siswa Profile Card -->
        <x-ui.card class="!max-w-none !p-6 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 rounded-2xl bg-zinc-900 text-white flex items-center justify-center font-black text-2xl shadow-md">
                    {{ strtoupper(substr($siswa->nama, 0, 2)) }}
                </div>
                <div>
                    <h3 class="text-xl font-bold text-slate-900">{{ $siswa->nama }}</h3>
                    <div class="text-xs text-slate-500 mt-1 flex flex-wrap gap-x-3 gap-y-1">
                        <span>NISN: <strong class="font-mono text-slate-700">{{ $siswa->nisn }}</strong></span>
                        <span>NIS: <strong class="font-mono text-slate-700">{{ $siswa->nis }}</strong></span>
                        <span>Kelas: <strong class="text-slate-700">{{ $siswa->kelas?->nama_kelas }}</strong></span>
                        <span>Jurusan: <strong class="text-slate-700">{{ $siswa->kelas?->jurusan?->nama }}</strong></span>
                    </div>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row gap-3 border-t md:border-t-0 md:border-l border-slate-200 pt-4 md:pt-0 md:pl-6">
                <div>
                    <div class="text-xs text-slate-500 uppercase font-semibold">Tarif SPP Bulanan</div>
                    <div class="text-lg font-bold text-indigo-700">{{ $siswa->spp?->formattedNominal() ?? '-' }}</div>
                    <div class="text-[11px] text-slate-400">Tahun Ajaran {{ $siswa->spp?->tahun_ajaran }}</div>
                </div>
                <div class="sm:ml-6">
                    <div class="text-xs text-slate-500 uppercase font-semibold">Total Pembayaran</div>
                    <div class="text-lg font-bold text-emerald-600">Rp {{ number_format($totalDibayar, 0, ',', '.') }}</div>
                    <div class="text-[11px] text-slate-400">{{ $riwayatPembayaran->count() }} bulan terbayar</div>
                </div>
            </div>
        </x-ui.card>

        <!-- Monthly Payment Status Grid -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
            <div class="mb-4">
                <h4 class="font-bold text-slate-900 text-base">Status Pembayaran SPP Tahun Ajaran {{ $siswa->spp?->tahun_ajaran }}</h4>
                <p class="text-xs text-slate-500 mt-0.5">Pantau status pelunasan SPP Anda setiap bulan</p>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
                @foreach($statusBulan as $item)
                    <div class="p-3.5 rounded-xl border {{ $item['lunas'] ? 'bg-emerald-50/60 border-emerald-200' : 'bg-rose-50/50 border-rose-200' }} flex flex-col justify-between">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs font-bold text-slate-800">{{ $item['bulan'] }}</span>
                            @if($item['lunas'])
                                <span class="w-5 h-5 rounded-full bg-emerald-500 text-white flex items-center justify-center text-[10px]">
                                    ✓
                                </span>
                            @else
                                <span class="w-5 h-5 rounded-full bg-rose-400 text-white flex items-center justify-center text-[10px] font-bold">
                                    !
                                </span>
                            @endif
                        </div>

                        <div class="mt-2">
                            @if($item['lunas'])
                                <span class="inline-block text-[11px] font-bold text-emerald-700">
                                    LUNAS
                                </span>
                                <div class="text-[10px] text-slate-500 mt-0.5">
                                    {{ $item['pembayaran']->tgl_bayar->format('d/m/Y') }}
                                </div>
                                <a href="{{ route('portal.kuitansi', $item['pembayaran']->id) }}" target="_blank" class="mt-2 inline-block text-[10px] font-semibold text-indigo-600 hover:text-indigo-800">
                                    Cetak Bukti &rarr;
                                </a>
                            @else
                                <span class="inline-block text-[11px] font-bold text-rose-600">
                                    BELUM BAYAR
                                </span>
                                <div class="text-[10px] text-slate-400 mt-0.5">
                                    {{ $siswa->spp?->formattedNominal() }}
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Personal Transaction History Table -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="p-5 border-b border-slate-200">
                <h4 class="font-bold text-slate-900 text-base">Riwayat Transaksi Pembayaran</h4>
                <p class="text-xs text-slate-500 mt-0.5">Semua riwayat transaksi pembayaran SPP yang tercatat</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50 text-slate-600 text-xs font-semibold uppercase">
                            <th class="py-3 px-4">No. Transaksi</th>
                            <th class="py-3 px-4">Tgl. Bayar</th>
                            <th class="py-3 px-4">Bulan Dibayar</th>
                            <th class="py-3 px-4">Jumlah Bayar</th>
                            <th class="py-3 px-4">Metode</th>
                            <th class="py-3 px-4">Petugas Penerima</th>
                            <th class="py-3 px-4 text-center">Bukti Bayar</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @forelse($riwayatPembayaran as $trx)
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="py-3.5 px-4 font-mono font-medium text-xs text-indigo-700">{{ $trx->kode_transaksi }}</td>
                                <td class="py-3.5 px-4 text-xs">{{ $trx->tgl_bayar->format('d/m/Y') }}</td>
                                <td class="py-3.5 px-4 font-semibold text-slate-900">{{ $trx->bulan_dibayar }} {{ $trx->tahun_dibayar }}</td>
                                <td class="py-3.5 px-4 font-bold text-emerald-700">{{ $trx->formattedJumlahBayar() }}</td>
                                <td class="py-3.5 px-4 text-xs">{{ $trx->metode_pembayaran }}</td>
                                <td class="py-3.5 px-4 text-xs text-slate-600">{{ $trx->user?->name ?? '-' }}</td>
                                <td class="py-3.5 px-4 text-center">
                                    <x-ui.button as="a" href="{{ route('portal.kuitansi', $trx->id) }}" target="_blank" variant="outline" size="sm">
                                        Cetak Kuitansi
                                    </x-ui.button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-8 text-center text-slate-400 text-sm">
                                    Belum ada transaksi pembayaran yang tercatat.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection

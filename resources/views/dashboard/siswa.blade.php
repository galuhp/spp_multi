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
                <div class="w-16 h-16 rounded-xl bg-zinc-900 text-white flex items-center justify-center font-black text-2xl shadow-md">
                    {{ strtoupper(substr($siswa->nama, 0, 2)) }}
                </div>
                <div>
                    <h3 class="text-xl font-bold text-zinc-900">{{ $siswa->nama }}</h3>
                    <div class="text-xs text-zinc-500 mt-1 flex flex-wrap gap-x-3 gap-y-1">
                        <span>NISN: <strong class="font-mono text-zinc-700">{{ $siswa->nisn }}</strong></span>
                        <span>NIS: <strong class="font-mono text-zinc-700">{{ $siswa->nis }}</strong></span>
                        <span>Kelas: <strong class="text-zinc-700">{{ $siswa->kelas?->nama_kelas }}</strong></span>
                        <span>Jurusan: <strong class="text-zinc-700">{{ $siswa->kelas?->jurusan?->nama }}</strong></span>
                    </div>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row gap-3 border-t md:border-t-0 md:border-l border-zinc-200 pt-4 md:pt-0 md:pl-6">
                <div>
                    <div class="text-xs text-zinc-500 uppercase font-semibold">Tarif SPP Bulanan</div>
                    <div class="text-lg font-bold text-zinc-900">{{ $siswa->spp?->formattedNominal() ?? '-' }}</div>
                    <div class="text-[11px] text-zinc-400">Tahun Ajaran {{ $siswa->spp?->tahun_ajaran }}</div>
                </div>
                <div class="sm:ml-6">
                    <div class="text-xs text-zinc-500 uppercase font-semibold">Total Pembayaran</div>
                    <div class="text-lg font-bold text-emerald-600">Rp {{ number_format($totalDibayar, 0, ',', '.') }}</div>
                    <div class="text-[11px] text-zinc-400">{{ $riwayatPembayaran->count() }} bulan terbayar</div>
                </div>
            </div>
        </x-ui.card>

        <!-- Monthly Payment Status Grid -->
        <div class="bg-white rounded-xl border border-zinc-200 shadow-xs p-6">
            <div class="mb-4">
                <h4 class="font-bold text-zinc-900 text-base">Status Pembayaran SPP Tahun Ajaran {{ $siswa->spp?->tahun_ajaran }}</h4>
                <p class="text-xs text-zinc-500 mt-0.5">Pantau status pelunasan SPP Anda setiap bulan</p>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
                @foreach($statusBulan as $item)
                    <div class="p-3.5 rounded-xl border {{ $item['lunas'] ? 'bg-emerald-50/35 border-emerald-200/80' : 'bg-white border-slate-200' }} flex flex-col justify-between">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs font-bold text-zinc-800">{{ $item['bulan'] }}</span>
                            @if($item['lunas'])
                                <span class="w-5 h-5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200/80 flex items-center justify-center text-[10px] font-bold">
                                    ✓
                                </span>
                            @else
                                <span class="w-5 h-5 rounded-full bg-rose-50 text-rose-700 border border-rose-200/80 flex items-center justify-center text-[10px] font-bold">
                                    !
                                </span>
                            @endif
                        </div>

                        <div class="mt-2">
                            @if($item['lunas'])
                                <span class="inline-block px-2 py-0.5 rounded-md text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/80">
                                    Lunas
                                </span>
                                <div class="text-[10px] text-zinc-500 mt-1">
                                    {{ $item['pembayaran']->tgl_bayar->format('d/m/Y') }}
                                </div>
                                <a href="{{ route('portal.kuitansi', $item['pembayaran']->id) }}" target="_blank" class="mt-2 inline-block text-[10px] font-semibold text-zinc-900 hover:text-zinc-900">
                                    Cetak Bukti &rarr;
                                </a>
                            @else
                                <span class="inline-block px-2 py-0.5 rounded-md text-[10px] font-semibold bg-rose-50 text-rose-700 border border-rose-200/80">
                                    Belum Lunas
                                </span>
                                <div class="text-[10px] text-zinc-400 mt-1">
                                    {{ $siswa->spp?->formattedNominal() }}
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Personal Transaction History Table -->
        <div class="bg-white rounded-xl border border-zinc-200 shadow-xs overflow-hidden">
            <div class="p-5 border-b border-zinc-200">
                <h4 class="font-bold text-zinc-900 text-base">Riwayat Transaksi Pembayaran</h4>
                <p class="text-xs text-zinc-500 mt-0.5">Semua riwayat transaksi pembayaran SPP yang tercatat</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="border-b border-zinc-200 bg-zinc-50 text-zinc-600 text-xs font-semibold uppercase">
                            <th class="py-3 px-4">No. Transaksi</th>
                            <th class="py-3 px-4">Tgl. Bayar</th>
                            <th class="py-3 px-4">Bulan Dibayar</th>
                            <th class="py-3 px-4">Jumlah Bayar</th>
                            <th class="py-3 px-4">Metode</th>
                            <th class="py-3 px-4">Petugas Penerima</th>
                            <th class="py-3 px-4 text-center">Bukti Bayar</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 text-zinc-700">
                        @forelse($riwayatPembayaran as $trx)
                            <tr class="hover:bg-zinc-50 transition">
                                <td class="py-3.5 px-4 font-mono font-medium text-xs text-zinc-900">{{ $trx->kode_transaksi }}</td>
                                <td class="py-3.5 px-4 text-xs">{{ $trx->tgl_bayar->format('d/m/Y') }}</td>
                                <td class="py-3.5 px-4 font-semibold text-zinc-900">{{ $trx->bulan_dibayar }} {{ $trx->tahun_dibayar }}</td>
                                <td class="py-3.5 px-4 font-bold text-emerald-700">{{ $trx->formattedJumlahBayar() }}</td>
                                <td class="py-3.5 px-4 text-xs">{{ $trx->metode_pembayaran }}</td>
                                <td class="py-3.5 px-4 text-xs text-zinc-600">{{ $trx->user?->name ?? '-' }}</td>
                                <td class="py-3.5 px-4 text-center">
                                    <x-ui.button as="a" href="{{ route('portal.kuitansi', $trx->id) }}" target="_blank" variant="outline" size="sm">
                                        Cetak Kuitansi
                                    </x-ui.button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-8 text-center text-zinc-400 text-sm">
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

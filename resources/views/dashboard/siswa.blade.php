@extends('layouts.app')

@section('title', 'Portal Siswa')
@section('header_title', 'Portal Informasi SPP Siswa')

@section('content')
<div class="space-y-6">
    @if(! $siswa)
        <div class="p-6 rounded-2xl bg-amber-50/90 dark:bg-amber-500/12 border border-amber-200/80 dark:border-amber-400/25 text-amber-800 dark:text-amber-200">
            <h4 class="font-bold text-base">Profil Siswa Belum Tertaut</h4>
            <p class="text-sm mt-1">Akun Anda belum ditautkan ke data siswa sekolah. Silakan hubungi bagian tata usaha / petugas SPP.</p>
        </div>
    @else
        <!-- Siswa Profile Card -->
        <div class="prism-card p-6 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 rounded-2xl bg-linear-to-tr from-emerald-500 via-teal-400 to-cyan-400 text-white flex items-center justify-center font-black text-2xl shadow-md shrink-0">
                    {{ strtoupper(substr($siswa->nama, 0, 2)) }}
                </div>
                <div>
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white">{{ $siswa->nama }}</h3>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-1 flex flex-wrap gap-x-3 gap-y-1">
                        <span>NISN: <strong class="font-mono text-slate-700 dark:text-slate-200">{{ $siswa->nisn }}</strong></span>
                        <span>NIS: <strong class="font-mono text-slate-700 dark:text-slate-200">{{ $siswa->nis }}</strong></span>
                        <span>Kelas: <strong class="text-slate-700 dark:text-slate-200">{{ $siswa->kelas?->nama_kelas }}</strong></span>
                        <span>Jurusan: <strong class="text-slate-700 dark:text-slate-200">{{ $siswa->kelas?->jurusan?->nama }}</strong></span>
                    </div>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row gap-3 border-t md:border-t-0 md:border-l border-slate-200/80 dark:border-white/10 pt-4 md:pt-0 md:pl-6">
                <div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 uppercase font-semibold">Tarif SPP Bulanan</div>
                    <div class="text-lg font-bold text-prism-700 dark:text-prism-300">{{ $siswa->spp?->formattedNominal() ?? '-' }}</div>
                    <div class="text-[11px] text-slate-400 dark:text-slate-500">Tahun Ajaran {{ $siswa->spp?->tahun_ajaran }}</div>
                </div>
                <div class="sm:ml-6">
                    <div class="text-xs text-slate-500 dark:text-slate-400 uppercase font-semibold">Total Pembayaran</div>
                    <div class="text-lg font-bold text-emerald-600 dark:text-emerald-400">Rp {{ number_format($totalDibayar, 0, ',', '.') }}</div>
                    <div class="text-[11px] text-slate-400 dark:text-slate-500">{{ $riwayatPembayaran->count() }} bulan terbayar</div>
                </div>
            </div>
        </div>

        <!-- Monthly Payment Status Grid -->
        <div class="prism-card p-6">
            <div class="mb-4">
                <h4 class="font-bold text-slate-900 dark:text-white text-base">Status Pembayaran SPP Tahun Ajaran {{ $siswa->spp?->tahun_ajaran }}</h4>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Pantau status pelunasan SPP Anda setiap bulan</p>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
                @foreach($statusBulan as $item)
                    <div class="p-3.5 rounded-xl border {{ $item['lunas'] ? 'bg-emerald-50/70 dark:bg-emerald-500/10 border-emerald-200/80 dark:border-emerald-400/25' : 'bg-rose-50/50 dark:bg-rose-500/10 border-rose-200/80 dark:border-rose-400/25' }} flex flex-col justify-between">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs font-bold text-slate-800 dark:text-slate-100">{{ $item['bulan'] }}</span>
                            @if($item['lunas'])
                                <span class="w-5 h-5 rounded-full bg-emerald-500 text-white flex items-center justify-center text-[10px]">
                                    ✓
                                </span>
                            @else
                                <span class="w-5 h-5 rounded-full bg-rose-500 text-white flex items-center justify-center text-[10px] font-bold">
                                    !
                                </span>
                            @endif
                        </div>

                        <div class="mt-2">
                            @if($item['lunas'])
                                <span class="inline-block text-[11px] font-bold text-emerald-700 dark:text-emerald-300">
                                    LUNAS
                                </span>
                                <div class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">
                                    {{ $item['pembayaran']->tgl_bayar->format('d/m/Y') }}
                                </div>
                                <a href="{{ route('portal.kuitansi', $item['pembayaran']->id) }}" target="_blank" class="mt-2 inline-block text-[10px] font-semibold text-prism-600 dark:text-prism-300 hover:underline">
                                    Cetak Bukti &rarr;
                                </a>
                            @else
                                <span class="inline-block text-[11px] font-bold text-rose-600 dark:text-rose-400">
                                    BELUM BAYAR
                                </span>
                                <div class="text-[10px] text-slate-400 dark:text-slate-500 mt-0.5">
                                    {{ $siswa->spp?->formattedNominal() }}
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Personal Transaction History Table -->
        <div class="prism-card overflow-hidden">
            <div class="p-5 border-b border-slate-200/80 dark:border-white/10">
                <h4 class="font-bold text-slate-900 dark:text-white text-base">Riwayat Transaksi Pembayaran</h4>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Semua riwayat transaksi pembayaran SPP yang tercatat</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="prism-thead">
                            <th class="py-3 px-4">No. Transaksi</th>
                            <th class="py-3 px-4">Tgl. Bayar</th>
                            <th class="py-3 px-4">Bulan Dibayar</th>
                            <th class="py-3 px-4">Jumlah Bayar</th>
                            <th class="py-3 px-4">Metode</th>
                            <th class="py-3 px-4">Petugas Penerima</th>
                            <th class="py-3 px-4 text-center">Bukti Bayar</th>
                        </tr>
                    </thead>
                    <tbody class="prism-tbody">
                        @forelse($riwayatPembayaran as $trx)
                            <tr class="prism-tr">
                                <td class="py-3.5 px-4 font-mono font-medium text-xs text-prism-700 dark:text-prism-300">{{ $trx->kode_transaksi }}</td>
                                <td class="py-3.5 px-4 text-xs">{{ $trx->tgl_bayar->format('d/m/Y') }}</td>
                                <td class="py-3.5 px-4 font-semibold text-slate-900 dark:text-slate-100">{{ $trx->bulan_dibayar }} {{ $trx->tahun_dibayar }}</td>
                                <td class="py-3.5 px-4 font-bold text-emerald-700 dark:text-emerald-400">{{ $trx->formattedJumlahBayar() }}</td>
                                <td class="py-3.5 px-4 text-xs">{{ $trx->metode_pembayaran }}</td>
                                <td class="py-3.5 px-4 text-xs text-slate-600 dark:text-slate-300">{{ $trx->user?->name ?? '-' }}</td>
                                <td class="py-3.5 px-4 text-center">
                                    <a href="{{ route('portal.kuitansi', $trx->id) }}" target="_blank" class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-prism-50 dark:bg-prism-500/15 hover:bg-prism-100 dark:hover:bg-prism-500/25 text-prism-700 dark:text-prism-300 border border-prism-200/80 dark:border-prism-400/25 transition">
                                        Cetak Kuitansi
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-8 text-center text-slate-400 dark:text-slate-500 text-sm">
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

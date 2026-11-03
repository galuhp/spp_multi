@extends('layouts.app')

@section('title', 'Riwayat Pembayaran Siswa')
@section('header_title', 'Riwayat Pembayaran SPP')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h3 class="text-xl font-bold text-slate-900 dark:text-white">Riwayat Pembayaran SPP Saya</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Daftar seluruh kuitansi pembayaran SPP yang tercatat atas nama Anda</p>
        </div>
        <div class="text-right">
            <div class="text-xs text-slate-500 dark:text-slate-400 uppercase font-semibold">Total Pembayaran Berhasil</div>
            <div class="text-xl font-bold text-emerald-600 dark:text-emerald-400">Rp {{ number_format($totalDibayar, 0, ',', '.') }}</div>
        </div>
    </div>

    <div class="prism-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="prism-thead">
                        <th class="py-3 px-4 w-14 text-center">No</th>
                        <th class="py-3 px-4">No. Kuitansi</th>
                        <th class="py-3 px-4">Tgl. Bayar</th>
                        <th class="py-3 px-4">Bulan Dibayar</th>
                        <th class="py-3 px-4">Jumlah Bayar</th>
                        <th class="py-3 px-4">Metode Bayar</th>
                        <th class="py-3 px-4">Petugas Kasir</th>
                        <th class="py-3 px-4 text-center w-36">Aksi</th>
                    </tr>
                </thead>
                <tbody class="prism-tbody">
                    @forelse($pembayarans as $index => $item)
                        <tr class="prism-tr">
                            <td class="py-3.5 px-4 text-center text-xs text-slate-400 dark:text-slate-500 font-medium">
                                {{ $pembayarans->firstItem() + $index }}
                            </td>
                            <td class="py-3.5 px-4 font-mono font-medium text-xs text-prism-700 dark:text-prism-300">
                                {{ $item->kode_transaksi }}
                            </td>
                            <td class="py-3.5 px-4 text-xs text-slate-600 dark:text-slate-400">
                                {{ $item->tgl_bayar->format('d/m/Y') }}
                            </td>
                            <td class="py-3.5 px-4 font-semibold text-slate-900 dark:text-white">
                                {{ $item->bulan_dibayar }} {{ $item->tahun_dibayar }}
                            </td>
                            <td class="py-3.5 px-4 font-bold text-emerald-700 dark:text-emerald-400">
                                {{ $item->formattedJumlahBayar() }}
                            </td>
                            <td class="py-3.5 px-4 text-xs text-slate-600 dark:text-slate-300">
                                {{ $item->metode_pembayaran }}
                            </td>
                            <td class="py-3.5 px-4 text-xs text-slate-600 dark:text-slate-300">
                                {{ $item->user?->name ?? '-' }}
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <a href="{{ route('portal.kuitansi', $item->id) }}" target="_blank" class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-prism-50 dark:bg-prism-500/15 hover:bg-prism-100 dark:hover:bg-prism-500/25 text-prism-700 dark:text-prism-300 border border-prism-200/80 dark:border-prism-400/25 transition">
                                    Cetak Kuitansi
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 text-center text-slate-400 dark:text-slate-500 text-sm">
                                Belum ada riwayat transaksi pembayaran SPP yang tercatat.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($pembayarans->hasPages())
            <div class="p-4 border-t border-slate-100 dark:border-white/10">
                {{ $pembayarans->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

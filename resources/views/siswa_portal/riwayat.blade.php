@extends('layouts.app')

@section('title', 'Riwayat Pembayaran Siswa')
@section('header_title', 'Riwayat Pembayaran SPP')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h3 class="text-xl font-bold text-slate-900">Riwayat Pembayaran SPP Saya</h3>
            <p class="text-xs text-slate-500 mt-1">Daftar seluruh kuitansi pembayaran SPP yang tercatat atas nama Anda</p>
        </div>
        <div class="text-right">
            <div class="text-xs text-slate-500 uppercase font-semibold">Total Pembayaran Berhasil</div>
            <div class="text-xl font-bold text-emerald-600">Rp {{ number_format($totalDibayar, 0, ',', '.') }}</div>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-slate-50/80 border-b border-slate-200 text-slate-700 font-semibold text-xs uppercase tracking-wider">
                        <th class="py-3 px-4 w-14 text-center">No</th>
                        <th class="py-3 px-4">No. Kuitansi</th>
                        <th class="py-3 px-4">Tgl. Bayar</th>
                        <th class="py-3 px-4">Pos Pembayaran</th>
                        <th class="py-3 px-4">Jumlah Bayar</th>
                        <th class="py-3 px-4">Metode Bayar</th>
                        <th class="py-3 px-4">Petugas Kasir</th>
                        <th class="py-3 px-4 text-center w-36">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($pembayarans as $index => $item)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="py-3.5 px-4 text-center text-xs text-slate-400 font-medium">
                                {{ $pembayarans->firstItem() + $index }}
                            </td>
                            <td class="py-3.5 px-4 font-mono font-medium text-xs text-indigo-700">
                                {{ $item->kode_transaksi }}
                            </td>
                            <td class="py-3.5 px-4 text-xs text-slate-600">
                                {{ $item->tgl_bayar->format('d/m/Y') }}
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-semibold border {{ $item->spp?->badgeClass() }}">
                                        {{ $item->spp?->kategori ?? 'SPP' }}
                                    </span>
                                    <span class="text-xs font-semibold text-slate-900">
                                        {{ $item->namaPos() }}
                                    </span>
                                </div>
                            </td>
                            <td class="py-3.5 px-4 font-bold text-emerald-700">
                                {{ $item->formattedJumlahBayar() }}
                            </td>
                            <td class="py-3.5 px-4 text-xs text-slate-600">
                                {{ $item->metode_pembayaran }}
                            </td>
                            <td class="py-3.5 px-4 text-xs text-slate-600">
                                {{ $item->user?->name ?? '-' }}
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <a href="{{ route('portal.kuitansi', $item->id) }}" target="_blank" class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 transition">
                                    Cetak Kuitansi
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 text-center text-slate-400 text-sm">
                                Belum ada riwayat transaksi pembayaran SPP yang tercatat.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($pembayarans->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $pembayarans->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

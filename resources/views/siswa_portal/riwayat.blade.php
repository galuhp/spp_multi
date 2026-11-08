@extends('layouts.app')

@section('title', 'Riwayat Pembayaran Siswa')
@section('header_title', 'Riwayat Pembayaran SPP')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h3 class="text-xl font-bold text-zinc-900">Riwayat Pembayaran SPP Saya</h3>
            <p class="text-xs text-zinc-500 mt-1">Daftar seluruh kuitansi pembayaran SPP yang tercatat atas nama Anda</p>
        </div>
        <div class="text-right">
            <div class="text-xs text-zinc-500 uppercase font-semibold">Total Pembayaran Berhasil</div>
            <div class="text-xl font-bold text-emerald-600">Rp {{ number_format($totalDibayar, 0, ',', '.') }}</div>
        </div>
    </div>

    <x-ui.card class="!max-w-none !p-0">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-zinc-50 border-b border-zinc-200 text-zinc-700 font-semibold text-xs uppercase tracking-wider">
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
                <tbody class="divide-y divide-zinc-100 text-zinc-700">
                    @forelse($pembayarans as $index => $item)
                        <tr class="hover:bg-zinc-50 transition">
                            <td class="py-3.5 px-4 text-center text-xs text-zinc-400 font-medium">
                                {{ $pembayarans->firstItem() + $index }}
                            </td>
                            <td class="py-3.5 px-4 font-mono font-medium text-xs text-zinc-900">
                                {{ $item->kode_transaksi }}
                            </td>
                            <td class="py-3.5 px-4 text-xs text-zinc-600">
                                {{ $item->tgl_bayar->format('d/m/Y') }}
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-semibold border {{ $item->spp?->badgeClass() }}">
                                        {{ $item->labelKategori() }}
                                    </span>
                                    <span class="text-xs font-semibold text-zinc-900">
                                        {{ $item->namaPos() }}
                                    </span>
                                </div>
                            </td>
                            <td class="py-3.5 px-4 font-bold text-emerald-700">
                                {{ $item->formattedJumlahBayar() }}
                            </td>
                            <td class="py-3.5 px-4 text-xs text-zinc-600">
                                {{ $item->metode_pembayaran }}
                            </td>
                            <td class="py-3.5 px-4 text-xs text-zinc-600">
                                {{ $item->user?->name ?? '-' }}
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <x-ui.button as="a" href="{{ route('portal.kuitansi', $item->id) }}" target="_blank" variant="outline" size="sm">
                                    Cetak Kuitansi
                                </x-ui.button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 text-center text-zinc-400 text-sm">
                                Belum ada riwayat transaksi pembayaran SPP yang tercatat.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($pembayarans->hasPages())
            <div class="p-4 border-t border-zinc-200">
                {{ $pembayarans->links() }}
            </div>
        @endif
    </x-ui.card>
</div>
@endsection

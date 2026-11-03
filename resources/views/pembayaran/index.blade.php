@extends('layouts.app')

@section('title', 'Transaksi Pembayaran SPP')
@section('header_title', 'Transaksi Pembayaran SPP')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h3 class="text-xl font-bold text-slate-900 dark:text-white">Riwayat Transaksi SPP</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Daftar seluruh penerimaan pembayaran SPP siswa sekolah</p>
        </div>
        <a href="{{ route('pembayaran.create') }}" class="px-4 py-2.5 prism-btn-emerald text-sm self-start">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            + Entri Pembayaran Baru
        </a>
    </div>

    <!-- Filter Card -->
    <div class="prism-card p-4">
        <form action="{{ route('pembayaran.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <div class="sm:col-span-2 relative">
                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 dark:text-slate-500 pointer-events-none">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </span>
                <input type="text" name="search" value="{{ $search }}" placeholder="Cari No. Transaksi, Nama Siswa, NISN..."
                    class="w-full pl-10 pr-3.5 py-2 prism-input text-sm">
            </div>

            <div>
                <input type="date" name="tgl_awal" value="{{ $tglAwal }}" title="Tanggal Awal"
                    class="w-full px-3.5 py-2 prism-input text-sm">
            </div>

            <div class="flex items-center gap-2">
                <input type="date" name="tgl_akhir" value="{{ $tglAkhir }}" title="Tanggal Akhir"
                    class="w-full px-3.5 py-2 prism-input text-sm">

                <button type="submit" class="px-3.5 py-2 prism-btn-primary text-xs shrink-0">
                    Filter
                </button>

                @if($search || $tglAwal || $tglAkhir)
                    <a href="{{ route('pembayaran.index') }}" class="px-2.5 py-2 prism-btn-secondary text-xs shrink-0">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Summary Box -->
    <div class="p-4 rounded-2xl bg-emerald-50/90 dark:bg-emerald-500/12 border border-emerald-200/80 dark:border-emerald-400/25 flex items-center justify-between">
        <span class="text-xs font-semibold uppercase tracking-wider text-emerald-800 dark:text-emerald-200">Total Penerimaan Berdasarkan Filter:</span>
        <span class="text-lg font-bold text-emerald-700 dark:text-emerald-300">Rp {{ number_format($totalPenerimaan, 0, ',', '.') }}</span>
    </div>

    <!-- Transactions Table -->
    <div class="prism-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="prism-thead">
                        <th class="py-3 px-4 w-14 text-center">No</th>
                        <th class="py-3 px-4">No. Transaksi</th>
                        <th class="py-3 px-4">Tgl. Bayar</th>
                        <th class="py-3 px-4">Siswa</th>
                        <th class="py-3 px-4">Kelas</th>
                        <th class="py-3 px-4">Bulan Dibayar</th>
                        <th class="py-3 px-4">Jumlah Bayar</th>
                        <th class="py-3 px-4">Metode</th>
                        <th class="py-3 px-4">Petugas</th>
                        <th class="py-3 px-4 text-center w-36">Aksi</th>
                    </tr>
                </thead>
                <tbody class="prism-tbody">
                    @forelse($pembayarans as $index => $item)
                        <tr class="prism-tr">
                            <td class="py-3.5 px-4 text-center text-xs text-slate-400 dark:text-slate-500 font-medium">
                                {{ $pembayarans->firstItem() + $index }}
                            </td>
                            <td class="py-3.5 px-4 font-mono font-bold text-xs text-prism-700 dark:text-prism-300">
                                {{ $item->kode_transaksi }}
                            </td>
                            <td class="py-3.5 px-4 text-xs text-slate-600 dark:text-slate-400">
                                {{ $item->tgl_bayar->format('d/m/Y') }}
                            </td>
                            <td class="py-3.5 px-4">
                                <a href="{{ route('siswa.show', $item->siswa_id) }}" class="font-bold text-slate-900 dark:text-slate-100 hover:text-prism-600 dark:hover:text-prism-300 transition block">
                                    {{ $item->siswa?->nama ?? '-' }}
                                </a>
                                <div class="text-xs text-slate-400 dark:text-slate-500 font-mono">NISN: {{ $item->siswa?->nisn ?? '-' }}</div>
                            </td>
                            <td class="py-3.5 px-4 text-xs font-medium text-slate-600 dark:text-slate-300">
                                {{ $item->siswa?->kelas?->nama_kelas ?? '-' }}
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold bg-sky-50 dark:bg-sky-500/15 text-sky-700 dark:text-sky-300 border border-sky-200/80 dark:border-sky-400/25">
                                    {{ $item->bulan_dibayar }} {{ $item->tahun_dibayar }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 font-bold text-slate-900 dark:text-white">
                                {{ $item->formattedJumlahBayar() }}
                            </td>
                            <td class="py-3.5 px-4 text-xs text-slate-600 dark:text-slate-300">
                                {{ $item->metode_pembayaran }}
                            </td>
                            <td class="py-3.5 px-4 text-xs text-slate-600 dark:text-slate-300">
                                {{ $item->user?->name ?? '-' }}
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <a href="{{ route('pembayaran.show', $item->id) }}" class="px-2 py-1 rounded-lg text-xs font-semibold bg-prism-50 dark:bg-prism-500/15 hover:bg-prism-100 dark:hover:bg-prism-500/25 text-prism-700 dark:text-prism-300 border border-prism-200/80 dark:border-prism-400/25 transition" title="Lihat & Cetak Kuitansi">
                                        Kuitansi
                                    </a>
                                    @if(auth()->user()->isAdmin())
                                        <form action="{{ route('pembayaran.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan/menghapus transaksi ini?');" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1 text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-500/15 rounded-lg transition cursor-pointer" title="Hapus Transaksi">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="py-8 text-center text-slate-400 dark:text-slate-500 text-sm">
                                Tidak ada data transaksi pembayaran yang ditemukan.
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

@extends('layouts.app')

@section('title', 'Laporan Pembayaran SPP')
@section('header_title', 'Laporan & Rekapitulasi Pembayaran SPP')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h3 class="text-xl font-bold text-slate-900 dark:text-white">Rekapitulasi Pembayaran SPP</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Saring dan cetak laporan penerimaan iuran SPP berdasarkan periode dan kelas</p>
        </div>
        <a href="{{ route('laporan.cetak', request()->all()) }}" target="_blank" class="px-4 py-2.5 prism-btn-primary text-sm self-start">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Cetak Laporan / PDF
        </a>
    </div>

    <!-- Filter Card -->
    <div class="prism-card p-5">
        <form action="{{ route('laporan.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <label for="tgl_awal" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1">
                    Tanggal Awal
                </label>
                <input type="date" id="tgl_awal" name="tgl_awal" value="{{ $tglAwal }}"
                    class="w-full px-3.5 py-2 prism-input text-sm">
            </div>

            <div>
                <label for="tgl_akhir" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1">
                    Tanggal Akhir
                </label>
                <input type="date" id="tgl_akhir" name="tgl_akhir" value="{{ $tglAkhir }}"
                    class="w-full px-3.5 py-2 prism-input text-sm">
            </div>

            <div>
                <label for="kelas_id" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1">
                    Filter Rombel Kelas
                </label>
                <select id="kelas_id" name="kelas_id"
                    class="w-full px-3.5 py-2 prism-input text-sm">
                    <option value="">-- Semua Kelas --</option>
                    @foreach($kelasList as $k)
                        <option value="{{ $k->id }}" {{ $kelasId == $k->id ? 'selected' : '' }}>
                            {{ $k->nama_kelas }} ({{ $k->jurusan?->kode }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="metode_pembayaran" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1">
                    Metode Pembayaran
                </label>
                <select id="metode_pembayaran" name="metode_pembayaran"
                    class="w-full px-3.5 py-2 prism-input text-sm">
                    <option value="">-- Semua Metode --</option>
                    <option value="Tunai" {{ $metode == 'Tunai' ? 'selected' : '' }}>Tunai</option>
                    <option value="Transfer Bank" {{ $metode == 'Transfer Bank' ? 'selected' : '' }}>Transfer Bank</option>
                    <option value="QRIS" {{ $metode == 'QRIS' ? 'selected' : '' }}>QRIS</option>
                </select>
            </div>

            <div class="sm:col-span-2 lg:col-span-4 flex items-center justify-end gap-2 pt-2 border-t border-slate-100 dark:border-white/10">
                <a href="{{ route('laporan.index') }}" class="px-4 py-2 prism-btn-secondary text-xs">
                    Reset Filter
                </a>
                <button type="submit" class="px-5 py-2 prism-btn-primary text-xs">
                    Tampilkan Laporan
                </button>
            </div>
        </form>
    </div>

    <!-- Summary Banner -->
    <div class="p-5 sm:p-6 rounded-2xl prism-hero text-white flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="text-xs font-semibold uppercase tracking-wider text-prism-100">Periode Laporan</div>
            <div class="text-base font-bold mt-0.5">
                {{ \Carbon\Carbon::parse($tglAwal)->translatedFormat('d F Y') }} s/d {{ \Carbon\Carbon::parse($tglAkhir)->translatedFormat('d F Y') }}
            </div>
            <div class="text-xs text-prism-100 mt-1">
                Total {{ $pembayarans->count() }} transaksi penerimaan SPP
            </div>
        </div>
        <div class="text-left sm:text-right border-t sm:border-t-0 sm:border-l border-white/20 pt-3 sm:pt-0 sm:pl-6">
            <div class="text-xs uppercase font-semibold text-prism-100">Total Nilai Penerimaan</div>
            <div class="text-2xl sm:text-3xl font-black mt-0.5">
                Rp {{ number_format($totalPenerimaan, 0, ',', '.') }}
            </div>
        </div>
    </div>

    <!-- Table of Report Records -->
    <div class="prism-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="prism-thead">
                        <th class="py-3 px-4 w-12 text-center">No</th>
                        <th class="py-3 px-4">No. Transaksi</th>
                        <th class="py-3 px-4">Tgl. Bayar</th>
                        <th class="py-3 px-4">Nama Siswa</th>
                        <th class="py-3 px-4">NISN</th>
                        <th class="py-3 px-4">Kelas</th>
                        <th class="py-3 px-4">Bulan Dibayar</th>
                        <th class="py-3 px-4 text-right">Jumlah Bayar</th>
                        <th class="py-3 px-4">Metode</th>
                        <th class="py-3 px-4">Petugas</th>
                    </tr>
                </thead>
                <tbody class="prism-tbody">
                    @forelse($pembayarans as $index => $item)
                        <tr class="prism-tr">
                            <td class="py-3 px-4 text-center text-xs text-slate-400 dark:text-slate-500 font-medium">
                                {{ $index + 1 }}
                            </td>
                            <td class="py-3 px-4 font-mono font-medium text-xs text-prism-700 dark:text-prism-300">
                                {{ $item->kode_transaksi }}
                            </td>
                            <td class="py-3 px-4 text-xs text-slate-600 dark:text-slate-400">
                                {{ $item->tgl_bayar->format('d/m/Y') }}
                            </td>
                            <td class="py-3 px-4 font-semibold text-slate-900 dark:text-slate-100">
                                {{ $item->siswa?->nama ?? '-' }}
                            </td>
                            <td class="py-3 px-4 font-mono text-xs text-slate-600 dark:text-slate-400">
                                {{ $item->siswa?->nisn ?? '-' }}
                            </td>
                            <td class="py-3 px-4 text-xs">
                                {{ $item->siswa?->kelas?->nama_kelas ?? '-' }}
                            </td>
                            <td class="py-3 px-4 text-xs font-medium text-slate-800 dark:text-slate-200">
                                {{ $item->bulan_dibayar }} {{ $item->tahun_dibayar }}
                            </td>
                            <td class="py-3 px-4 font-bold text-right text-slate-900 dark:text-white">
                                {{ $item->formattedJumlahBayar() }}
                            </td>
                            <td class="py-3 px-4 text-xs text-slate-600 dark:text-slate-400">
                                {{ $item->metode_pembayaran }}
                            </td>
                            <td class="py-3 px-4 text-xs text-slate-600 dark:text-slate-400">
                                {{ $item->user?->name ?? '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="py-8 text-center text-slate-400 dark:text-slate-500 text-sm">
                                Tidak ada data penerimaan pembayaran pada periode dan filter yang dipilih.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if($pembayarans->isNotEmpty())
                    <tfoot>
                        <tr class="border-t-2 border-slate-900 dark:border-white/20 bg-slate-50/80 dark:bg-slate-800/60 font-bold text-sm">
                            <td colspan="7" class="py-3.5 px-4 text-right uppercase tracking-wider text-slate-800 dark:text-slate-200">
                                Total Penerimaan SPP:
                            </td>
                            <td class="py-3.5 px-4 text-right text-emerald-700 dark:text-emerald-400">
                                Rp {{ number_format($totalPenerimaan, 0, ',', '.') }}
                            </td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
@endsection

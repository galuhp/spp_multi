@extends('layouts.app')

@section('title', 'Kuitansi Pembayaran ' . $pembayaran->kode_transaksi)
@section('header_title', 'Rincian Kuitansi Pembayaran')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <!-- Top Action Bar -->
    <div class="flex items-center justify-between no-print">
        <a href="{{ route('pembayaran.index') }}" class="text-xs font-semibold text-slate-600 dark:text-slate-300 hover:text-prism-600 dark:hover:text-prism-300 inline-flex items-center gap-1.5">
            &larr; Kembali ke Riwayat Transaksi
        </a>
        <div class="flex items-center gap-2">
            <a href="{{ route('pembayaran.cetak', $pembayaran->id) }}" target="_blank" class="px-4 py-2 prism-btn-primary text-xs">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Cetak Lembar Kuitansi
            </a>
            <a href="{{ route('pembayaran.create', ['siswa_id' => $pembayaran->siswa_id]) }}" class="px-3.5 py-2 prism-btn-secondary text-xs">
                + Bayar Lagi
            </a>
        </div>
    </div>

    <!-- Receipt Card Preview -->
    <div class="prism-card overflow-hidden">
        <div class="h-1 w-full prism-spectrum-bar no-print"></div>
        <div class="p-6 sm:p-10 space-y-6">
            <!-- School Header -->
            <div class="flex items-center justify-between border-b-2 border-slate-900 dark:border-white/25 pb-5">
                <div class="flex items-center gap-3">
                    <x-prism-mark class="w-12 h-12" />
                    <div>
                        <h2 class="font-black text-slate-900 dark:text-white text-lg uppercase tracking-wide">SMK NEGERI CONTOH</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Jl. Pendidikan No. 123, Bandung &bull; Telp: (022) 1234567</p>
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Bukti Pembayaran SPP</div>
                    <div class="font-mono font-bold text-sm text-prism-700 dark:text-prism-300 mt-0.5">{{ $pembayaran->kode_transaksi }}</div>
                </div>
            </div>

            <!-- Student & Transaction Info -->
            <div class="grid grid-cols-2 gap-6 text-xs">
                <div class="space-y-1.5">
                    <div><span class="text-slate-400 dark:text-slate-500">Nama Siswa:</span> <strong class="text-slate-900 dark:text-white text-sm ml-1">{{ $pembayaran->siswa?->nama }}</strong></div>
                    <div><span class="text-slate-400 dark:text-slate-500">NISN / NIS:</span> <strong class="font-mono text-slate-800 dark:text-slate-200 ml-1">{{ $pembayaran->siswa?->nisn }} / {{ $pembayaran->siswa?->nis }}</strong></div>
                    <div><span class="text-slate-400 dark:text-slate-500">Kelas / Jurusan:</span> <span class="font-medium text-slate-800 dark:text-slate-200 ml-1">{{ $pembayaran->siswa?->kelas?->nama_kelas }} ({{ $pembayaran->siswa?->kelas?->jurusan?->nama }})</span></div>
                </div>
                <div class="space-y-1.5 text-right">
                    <div><span class="text-slate-400 dark:text-slate-500">Tgl. Transaksi:</span> <strong class="text-slate-800 dark:text-slate-200 ml-1">{{ $pembayaran->tgl_bayar->translatedFormat('d F Y') }}</strong></div>
                    <div><span class="text-slate-400 dark:text-slate-500">Metode Bayar:</span> <strong class="text-slate-800 dark:text-slate-200 ml-1">{{ $pembayaran->metode_pembayaran }}</strong></div>
                    <div><span class="text-slate-400 dark:text-slate-500">Petugas Kasir:</span> <strong class="text-slate-800 dark:text-slate-200 ml-1">{{ $pembayaran->user?->name }}</strong></div>
                </div>
            </div>

            <!-- Itemized Table -->
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-y border-slate-200 dark:border-white/12 bg-slate-50/80 dark:bg-slate-800/50 text-slate-600 dark:text-slate-300 font-semibold uppercase">
                        <th class="py-2.5 px-3">Deskripsi Pembayaran</th>
                        <th class="py-2.5 px-3">Tahun Ajaran</th>
                        <th class="py-2.5 px-3 text-right">Jumlah</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-white/8 text-slate-800 dark:text-slate-200">
                    @php $totalSemua = 0; @endphp
                    @foreach($relatedPayments as $trx)
                        @php $totalSemua += $trx->jumlah_bayar; @endphp
                        <tr>
                            <td class="py-3 px-3 font-medium">
                                Iuran SPP Bulan <strong>{{ $trx->bulan_dibayar }} {{ $trx->tahun_dibayar }}</strong>
                            </td>
                            <td class="py-3 px-3 text-slate-500 dark:text-slate-400">
                                {{ $trx->spp?->tahun_ajaran }}
                            </td>
                            <td class="py-3 px-3 font-bold text-right text-slate-900 dark:text-white">
                                {{ $trx->formattedJumlahBayar() }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t-2 border-slate-900 dark:border-white/25 font-bold text-sm">
                        <td colspan="2" class="py-3 px-3 uppercase tracking-wider text-slate-900 dark:text-white">
                            Total Pembayaran
                        </td>
                        <td class="py-3 px-3 text-right text-prism-700 dark:text-prism-300">
                            Rp {{ number_format($totalSemua, 0, ',', '.') }}
                        </td>
                    </tr>
                </tfoot>
            </table>

            <!-- Signatures, QR Verification & Status -->
            <div class="pt-6 grid grid-cols-1 sm:grid-cols-3 gap-6 items-end text-xs text-center border-t border-slate-100 dark:border-white/10">
                <div>
                    <p class="text-slate-500 dark:text-slate-400 mb-14">Siswa / Pembayar,</p>
                    <p class="font-bold text-slate-900 dark:text-white underline">{{ $pembayaran->siswa?->nama }}</p>
                </div>
                <div class="flex flex-col items-center justify-center">
                    <a href="{{ $pembayaran->buktiUrl() }}" target="_blank" title="Buka halaman verifikasi bukti pembayaran publik" class="p-2 bg-white rounded-xl border border-slate-200 dark:border-white/20 shadow-xs hover:scale-[1.03] transition">
                        {!! $pembayaran->qrCodeSvg(92) !!}
                    </a>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 mt-2">QR Bukti Pembayaran</span>
                    <a href="{{ $pembayaran->buktiUrl() }}" target="_blank" class="text-[11px] text-prism-600 dark:text-prism-300 hover:underline mt-0.5">
                        Buka Link Verifikasi &rarr;
                    </a>
                </div>
                <div>
                    <p class="text-slate-500 dark:text-slate-400 mb-14">Petugas Kasir Penerima,</p>
                    <p class="font-bold text-slate-900 dark:text-white underline">{{ $pembayaran->user?->name }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

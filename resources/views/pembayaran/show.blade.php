@extends('layouts.app')

@section('title', 'Kuitansi Pembayaran ' . $pembayaran->kode_transaksi)
@section('header_title', 'Rincian Kuitansi Pembayaran')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <!-- Top Action Bar -->
    <div class="flex items-center justify-between no-print">
        <a href="{{ route('pembayaran.index') }}" class="text-xs font-semibold text-zinc-600 hover:text-zinc-900 inline-flex items-center gap-1.5">
            &larr; Kembali ke Riwayat Transaksi
        </a>
        <div class="flex items-center gap-2">
            <a href="{{ route('pembayaran.cetak', $pembayaran->id) }}" target="_blank" class="px-4 py-2 rounded-xl bg-zinc-900 hover:bg-zinc-800 text-white font-semibold text-xs transition shadow-sm inline-flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Cetak Lembar Kuitansi
            </a>
            <a href="{{ route('pembayaran.create', ['siswa_id' => $pembayaran->siswa_id]) }}" class="px-3.5 py-2 rounded-xl border border-zinc-300 hover:bg-zinc-50 text-zinc-700 font-semibold text-xs transition">
                + Bayar Lagi
            </a>
        </div>
    </div>

    <!-- Receipt Card Preview -->
    @php $sekolah = \App\Models\ProfilSekolah::current(); @endphp
    <div class="bg-white rounded-xl border border-zinc-200 shadow-xs overflow-hidden">
        <div class="p-6 sm:p-10 space-y-6">
            <!-- School Header -->
            <div class="flex items-center justify-between border-b-2 border-slate-900 pb-5">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-tr from-indigo-600 to-blue-500 flex items-center justify-center text-white font-black text-2xl shadow-md">
                        S
                    </div>
                    <div>
                        <h2 class="font-black text-zinc-900 text-lg uppercase tracking-wide">{{ $sekolah->nama_sekolah }}</h2>
                        <p class="text-xs text-zinc-500">{{ $sekolah->alamatLengkap() }} &bull; Telp: {{ $sekolah->no_telp }}</p>
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-xs font-bold uppercase tracking-wider text-zinc-400">Bukti Pembayaran SPP</div>
                    <div class="font-mono font-bold text-sm text-zinc-900 mt-0.5">{{ $pembayaran->kode_transaksi }}</div>
                </div>
            </div>

            <!-- Student & Transaction Info -->
            <div class="grid grid-cols-2 gap-6 text-xs">
                <div class="space-y-1.5">
                    <div><span class="text-zinc-400">Nama Siswa:</span> <strong class="text-zinc-900 text-sm ml-1">{{ $pembayaran->siswa?->nama }}</strong></div>
                    <div><span class="text-zinc-400">NISN / NIS:</span> <strong class="font-mono text-zinc-800 ml-1">{{ $pembayaran->siswa?->nisn }} / {{ $pembayaran->siswa?->nis }}</strong></div>
                    <div><span class="text-zinc-400">Kelas / Jurusan:</span> <span class="font-medium text-zinc-800 ml-1">{{ $pembayaran->siswa?->kelas?->nama_kelas }} ({{ $pembayaran->siswa?->kelas?->jurusan?->nama }})</span></div>
                </div>
                <div class="space-y-1.5 text-right">
                    <div><span class="text-zinc-400">Tgl. Transaksi:</span> <strong class="text-zinc-800 ml-1">{{ $pembayaran->tgl_bayar->translatedFormat('d F Y') }}</strong></div>
                    <div><span class="text-zinc-400">Metode Bayar:</span> <strong class="text-zinc-800 ml-1">{{ $pembayaran->metode_pembayaran }}</strong></div>
                    <div><span class="text-zinc-400">Petugas Kasir:</span> <strong class="text-zinc-800 ml-1">{{ $pembayaran->user?->name }}</strong></div>
                </div>
            </div>

            <!-- Itemized Table -->
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-y border-zinc-200 bg-zinc-50 text-zinc-600 font-semibold uppercase">
                        <th class="py-2.5 px-3">Deskripsi Pembayaran</th>
                        <th class="py-2.5 px-3">Tahun Ajaran</th>
                        <th class="py-2.5 px-3 text-right">Jumlah</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 text-zinc-800">
                    @php $totalSemua = 0; @endphp
                    @foreach($relatedPayments as $trx)
                        @php $totalSemua += $trx->jumlah_bayar; @endphp
                        <tr>
                            <td class="py-3 px-3 font-medium">
                                {{ $trx->namaPos() }}
                            </td>
                            <td class="py-3 px-3 text-zinc-500">
                                {{ $trx->spp?->tahun_ajaran }}
                            </td>
                            <td class="py-3 px-3 font-bold text-right text-zinc-900">
                                {{ $trx->formattedJumlahBayar() }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t-2 border-slate-900 font-bold text-sm">
                        <td colspan="2" class="py-3 px-3 uppercase tracking-wider text-zinc-900">
                            Total Pembayaran
                        </td>
                        <td class="py-3 px-3 text-right text-zinc-900">
                            Rp {{ number_format($totalSemua, 0, ',', '.') }}
                        </td>
                    </tr>
                </tfoot>
            </table>

            <!-- Signatures, QR Verification & Status -->
            <div class="pt-6 grid grid-cols-1 sm:grid-cols-3 gap-6 items-end text-xs text-center border-t border-zinc-200">
                <div>
                    <p class="text-zinc-500 mb-14">Siswa / Pembayar,</p>
                    <p class="font-bold text-zinc-900 underline">{{ $pembayaran->siswa?->nama }}</p>
                </div>
                <div class="flex flex-col items-center justify-center">
                    <a href="{{ $pembayaran->buktiUrl() }}" target="_blank" title="Buka halaman verifikasi bukti pembayaran publik" class="p-2 bg-white rounded-xl border border-zinc-200 shadow-xs hover:scale-[1.03] transition">
                        {!! $pembayaran->qrCodeSvg(92) !!}
                    </a>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-zinc-600 mt-2">QR Bukti Pembayaran</span>
                    <a href="{{ $pembayaran->buktiUrl() }}" target="_blank" class="text-[11px] text-zinc-900 hover:underline mt-0.5">
                        Buka Link Verifikasi &rarr;
                    </a>
                </div>
                <div>
                    <p class="text-zinc-500 mb-14">Petugas Kasir Penerima,</p>
                    <p class="font-bold text-zinc-900 underline">{{ $pembayaran->user?->name }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

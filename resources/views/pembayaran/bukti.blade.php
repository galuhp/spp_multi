<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50 dark:bg-slate-950">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light dark">
    <meta name="robots" content="noindex, nofollow">
    <script>
        try {
            document.querySelector('meta[name="color-scheme"]').content = localStorage.getItem('color-scheme') ?? 'light dark';
        } catch {}
    </script>
    <title>{{ $valid ? 'Bukti Pembayaran SPP - ' . $pembayaran->kode_transaksi : 'Verifikasi Bukti Pembayaran SPP' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: #fff !important; color: #0f172a !important; }
            .prism-card { box-shadow: none !important; border: 1px solid #cbd5e1 !important; background: #fff !important; }
            @page { margin: 1cm; size: auto; }
        }
    </style>
</head>
<body class="prism-canvas min-h-full flex flex-col justify-center items-center p-4 sm:p-6 antialiased text-slate-800 dark:text-slate-100 relative">
    <!-- Top Spectrum Accent -->
    <div class="fixed top-0 inset-x-0 h-1 prism-spectrum-bar z-20 no-print"></div>

    <!-- Floating Theme Switcher -->
    <div class="fixed top-4 right-4 z-20 no-print">
        <x-theme-toggle :show-label="true" />
    </div>

    <div class="w-full max-w-xl my-8 space-y-5">
        <!-- School Brand Header -->
        <div class="text-center">
            <x-prism-mark size="lg" class="mx-auto mb-3" />
            <h1 class="text-lg sm:text-xl font-black text-slate-900 dark:text-white uppercase tracking-wide">SMK NEGERI CONTOH</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Portal Verifikasi Keaslian Bukti Pembayaran SPP</p>
        </div>

        @if($valid)
            @php
                $totalBayar = $relatedPayments->sum('jumlah_bayar');
            @endphp

            <!-- Receipt Verification Card -->
            <div class="prism-card overflow-hidden">
                <div class="h-1.5 w-full bg-gradient-to-r from-emerald-500 via-teal-500 to-cyan-500 no-print"></div>

                <div class="p-5 sm:p-7 space-y-6">
                    <!-- Verified Banner -->
                    <div class="flex items-start gap-3.5 p-4 rounded-2xl bg-emerald-50/90 dark:bg-emerald-500/12 border border-emerald-200/90 dark:border-emerald-400/25">
                        <div class="w-10 h-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center shrink-0 shadow-sm">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-xs font-extrabold uppercase tracking-wider text-emerald-800 dark:text-emerald-300">
                                    Bukti Pembayaran Terverifikasi Sah
                                </span>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-emerald-600 text-white">
                                    Lunas
                                </span>
                            </div>
                            <p class="text-xs text-emerald-700/90 dark:text-emerald-200/80 mt-1 leading-relaxed">
                                Data kuitansi ini asli dan tercatat resmi pada sistem penerimaan SPP sekolah.
                            </p>
                        </div>
                    </div>

                    <!-- Highlighted Amount & Receipt Number -->
                    <div class="text-center py-3 px-4 rounded-2xl bg-slate-50/90 dark:bg-slate-800/50 border border-slate-200/70 dark:border-white/10">
                        <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Pembayaran Diterima</div>
                        <div class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white mt-1">
                            Rp {{ number_format($totalBayar, 0, ',', '.') }}
                        </div>
                        <div class="mt-2 inline-flex items-center gap-1.5 text-xs font-mono font-semibold text-prism-700 dark:text-prism-300">
                            <span>No. Kuitansi:</span>
                            <span>{{ $pembayaran->kode_transaksi }}</span>
                        </div>
                    </div>

                    <!-- Student & Payment Metadata -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                        <div class="p-3.5 rounded-xl bg-white/60 dark:bg-slate-900/40 border border-slate-200/70 dark:border-white/10 space-y-2">
                            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Identitas Siswa</div>
                            <div>
                                <div class="text-slate-500 dark:text-slate-400">Nama Lengkap</div>
                                <div class="font-bold text-sm text-slate-900 dark:text-white">{{ $pembayaran->siswa?->nama }}</div>
                            </div>
                            <div class="flex justify-between gap-2 pt-1 border-t border-slate-100 dark:border-white/8">
                                <span class="text-slate-500 dark:text-slate-400">NISN / NIS</span>
                                <span class="font-mono font-semibold text-slate-800 dark:text-slate-200">{{ $pembayaran->siswa?->nisn }} / {{ $pembayaran->siswa?->nis }}</span>
                            </div>
                            <div class="flex justify-between gap-2">
                                <span class="text-slate-500 dark:text-slate-400">Kelas</span>
                                <span class="font-semibold text-slate-800 dark:text-slate-200 text-right">{{ $pembayaran->siswa?->kelas?->nama_kelas }} ({{ $pembayaran->siswa?->kelas?->jurusan?->kode }})</span>
                            </div>
                        </div>

                        <div class="p-3.5 rounded-xl bg-white/60 dark:bg-slate-900/40 border border-slate-200/70 dark:border-white/10 space-y-2">
                            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Detail Transaksi</div>
                            <div class="flex justify-between gap-2">
                                <span class="text-slate-500 dark:text-slate-400">Tanggal Bayar</span>
                                <span class="font-bold text-slate-900 dark:text-white">{{ $pembayaran->tgl_bayar->translatedFormat('d F Y') }}</span>
                            </div>
                            @if($pembayaran->created_at)
                                <div class="flex justify-between gap-2">
                                    <span class="text-slate-500 dark:text-slate-400">Waktu Input</span>
                                    <span class="font-medium text-slate-800 dark:text-slate-200">{{ $pembayaran->created_at->format('d/m/Y H:i') }}</span>
                                </div>
                            @endif
                            <div class="flex justify-between gap-2 pt-1 border-t border-slate-100 dark:border-white/8">
                                <span class="text-slate-500 dark:text-slate-400">Metode Bayar</span>
                                <span class="font-semibold text-slate-800 dark:text-slate-200">{{ $pembayaran->metode_pembayaran }}</span>
                            </div>
                            <div class="flex justify-between gap-2">
                                <span class="text-slate-500 dark:text-slate-400">Petugas Kasir</span>
                                <span class="font-semibold text-slate-800 dark:text-slate-200 text-right">{{ $pembayaran->user?->name }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Paid Months Breakdown -->
                    <div>
                        <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">
                            Rincian Bulan SPP Dibayarkan ({{ $relatedPayments->count() }} Bulan)
                        </div>
                        <div class="rounded-xl border border-slate-200/80 dark:border-white/12 overflow-hidden">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr class="bg-slate-50/90 dark:bg-slate-800/60 border-b border-slate-200/80 dark:border-white/10 text-slate-600 dark:text-slate-300 font-semibold uppercase">
                                        <th class="py-2.5 px-3">Bulan Tagihan</th>
                                        <th class="py-2.5 px-3 text-center">Tahun Ajaran</th>
                                        <th class="py-2.5 px-3 text-right">Nominal</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-white/8 text-slate-800 dark:text-slate-200">
                                    @foreach($relatedPayments as $trx)
                                        <tr>
                                            <td class="py-2.5 px-3 font-medium">
                                                SPP Bulan <strong>{{ $trx->bulan_dibayar }} {{ $trx->tahun_dibayar }}</strong>
                                            </td>
                                            <td class="py-2.5 px-3 text-center text-slate-500 dark:text-slate-400">
                                                {{ $trx->spp?->tahun_ajaran }}
                                            </td>
                                            <td class="py-2.5 px-3 text-right font-bold text-slate-900 dark:text-white">
                                                {{ $trx->formattedJumlahBayar() }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="bg-slate-50/90 dark:bg-slate-800/50 border-t border-slate-200/90 dark:border-white/15 font-bold">
                                        <td colspan="2" class="py-2.5 px-3 uppercase text-slate-700 dark:text-slate-200">Total Diterima</td>
                                        <td class="py-2.5 px-3 text-right text-emerald-700 dark:text-emerald-300 text-sm">
                                            Rp {{ number_format($totalBayar, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <!-- Print / Save PDF Action for Parents -->
                    <div class="pt-2 flex flex-col sm:flex-row items-center justify-between gap-3 border-t border-slate-200/70 dark:border-white/10 no-print">
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 text-center sm:text-left">
                            Simpan halaman ini atau cetak sebagai arsip bukti pembayaran orang tua/wali.
                        </p>
                        <button type="button" onclick="window.print()" class="w-full sm:w-auto shrink-0 px-4 py-2.5 prism-btn-primary text-xs cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                            </svg>
                            Simpan PDF / Cetak
                        </button>
                    </div>
                </div>
            </div>
        @else
            <!-- Invalid / Unverified Receipt Card -->
            <div class="prism-card overflow-hidden">
                <div class="h-1.5 w-full bg-gradient-to-r from-rose-500 via-red-500 to-amber-500"></div>

                <div class="p-6 sm:p-8 text-center space-y-4">
                    <div class="w-14 h-14 rounded-2xl bg-rose-100 dark:bg-rose-500/20 text-rose-600 dark:text-rose-300 flex items-center justify-center mx-auto">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-base sm:text-lg font-extrabold text-rose-700 dark:text-rose-300 uppercase tracking-wide">
                            Bukti Pembayaran Tidak Valid
                        </h2>
                        <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-300 mt-2 leading-relaxed">
                            Maaf, kode transaksi <span class="font-mono font-bold text-slate-900 dark:text-white">{{ $kode }}</span> tidak ditemukan atau tanda tangan digital pada tautan QR Code tidak sesuai dengan data sistem sekolah.
                        </p>
                    </div>
                    <div class="p-3.5 rounded-xl bg-amber-50/90 dark:bg-amber-500/12 border border-amber-200/80 dark:border-amber-400/25 text-xs text-amber-800 dark:text-amber-200">
                        Pastikan Anda memindai QR Code langsung dari lembar kuitansi asli atau hubungi bagian Tata Usaha sekolah untuk konfirmasi.
                    </div>
                </div>
            </div>
        @endif

        <p class="text-center text-[11px] text-slate-500 dark:text-slate-400">
            &copy; {{ date('Y') }} Sistem Informasi Manajemen SPP &bull; SMK Negeri Contoh
        </p>
    </div>
</body>
</html>

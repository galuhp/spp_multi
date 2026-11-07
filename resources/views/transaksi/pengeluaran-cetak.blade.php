<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bukti Pengeluaran Kas - {{ $pengeluaran->nomor_transaksi }} - {{ $sekolah->nama_sekolah }}</title>
    @include('partials.app-icon')
    @vite(['resources/css/app.css'])
    <style>
        @media print {
            .no-print { display: none !important; }
            body { font-size: 11px; background: #fff !important; padding: 0 !important; }
            @page { margin: 1cm; size: A5 landscape; }
            .voucher-card {
                box-shadow: none !important;
                border: 1px solid #18181b !important;
                border-radius: 0 !important;
                margin: 0 !important;
                width: 100% !important;
                max-width: none !important;
            }
        }
    </style>
</head>
<body class="bg-zinc-100 p-4 sm:p-8 text-zinc-900 antialiased font-print">
    <div class="no-print max-w-3xl mx-auto mb-4 flex items-center justify-between">
        <a href="{{ route('filament.admin.resources.pengeluaran-sekolahs.index') }}" class="text-xs font-semibold text-zinc-600 hover:text-zinc-900">
            &larr; Kembali ke Daftar Pengeluaran
        </a>
        <button onclick="window.print()" class="px-4 py-2 bg-zinc-900 hover:bg-zinc-800 text-white rounded-xl text-xs font-bold transition shadow-sm inline-flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Cetak Voucher Sekarang
        </button>
    </div>

    <div class="voucher-card max-w-3xl mx-auto bg-white p-6 sm:p-8 rounded-xl shadow-sm border border-zinc-200">
        <!-- Kop Surat -->
        <div class="mb-4 flex items-center gap-4 border-b-2 border-zinc-900 pb-3">
            @if ($sekolah->logoUrl())
                <img src="{{ $sekolah->logoUrl() }}" alt="Logo {{ $sekolah->nama_sekolah }}" class="h-12 w-12 shrink-0 object-contain">
            @endif
            <div class="min-w-0 flex-1">
                <h1 class="text-base font-bold uppercase leading-tight tracking-wide">
                    {{ $sekolah->nama_sekolah }}{{ $sekolah->kota ? ' — ' . strtoupper($sekolah->kota) : '' }}
                </h1>
                <p class="text-xs leading-tight text-zinc-600">
                    {{ $sekolah->alamatLengkap() }} &bull; Telp. {{ $sekolah->no_telp }} &bull; Email: {{ $sekolah->email }}
                </p>
            </div>
            <div class="text-right">
                <span class="inline-block border border-zinc-900 bg-zinc-50 px-3 py-1 font-mono text-xs font-bold text-zinc-900">
                    {{ $pengeluaran->nomor_transaksi }}
                </span>
            </div>
        </div>

        <div class="mb-4 text-center">
            <h2 class="text-sm font-bold uppercase underline tracking-wider">BUKTI PENGELUARAN KAS (KAS KELUAR)</h2>
            <p class="text-[11px] text-zinc-600">Tahun Pelajaran: {{ $pengeluaran->tahunAjaran?->nama ?? '-' }}</p>
        </div>

        <!-- Rincian -->
        <table class="w-full text-xs border-collapse mb-5">
            <tbody>
                <tr class="border-b border-zinc-200">
                    <td class="py-2 pr-4 font-semibold text-zinc-600 w-40">Tanggal Pengeluaran</td>
                    <td class="py-2 font-medium text-zinc-900">: {{ $pengeluaran->tanggal->translatedFormat('d F Y') }}</td>
                </tr>
                <tr class="border-b border-zinc-200">
                    <td class="py-2 pr-4 font-semibold text-zinc-600">Kategori Biaya</td>
                    <td class="py-2 font-medium text-zinc-900">: <span class="font-bold">{{ $pengeluaran->kategori }}</span></td>
                </tr>
                <tr class="border-b border-zinc-200">
                    <td class="py-2 pr-4 font-semibold text-zinc-600">Dibayarkan Kepada</td>
                    <td class="py-2 font-medium text-zinc-900">: {{ $pengeluaran->penerima ?: '—' }}</td>
                </tr>
                <tr class="border-b border-zinc-200">
                    <td class="py-2 pr-4 font-semibold text-zinc-600">Uraian / Keperluan</td>
                    <td class="py-2 font-medium text-zinc-900 leading-relaxed">: {{ $pengeluaran->keperluan }}</td>
                </tr>
                @if ($pengeluaran->catatan)
                    <tr class="border-b border-zinc-200">
                        <td class="py-2 pr-4 font-semibold text-zinc-600">Catatan Tambahan</td>
                        <td class="py-2 text-zinc-700">: {{ $pengeluaran->catatan }}</td>
                    </tr>
                @endif
                <tr class="border-b border-zinc-200">
                    <td class="py-2 pr-4 font-semibold text-zinc-600">Metode Pembayaran</td>
                    <td class="py-2 font-medium text-zinc-900">: {{ $pengeluaran->metode_pembayaran }}</td>
                </tr>
                <tr class="bg-zinc-50 border-b-2 border-zinc-900">
                    <td class="py-3 pr-4 font-bold text-sm text-zinc-900 uppercase">Jumlah Uang (Nominal)</td>
                    <td class="py-3 font-bold text-base text-zinc-900 tabular-nums">
                        : Rp {{ number_format($pengeluaran->nominal, 0, ',', '.') }}
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Tanda Tangan -->
        <div class="mt-8 grid grid-cols-3 gap-6 text-center text-xs">
            <div>
                <p class="text-zinc-600 mb-16">
                    Penerima Uang,<br>
                    <strong>(Vendor / Petugas)</strong>
                </p>
                <p class="font-bold underline text-xs">{{ $pengeluaran->penerima ?: '( ................................... )' }}</p>
            </div>
            <div>
                <p class="text-zinc-600 mb-16">
                    Mengetahui,<br>
                    <strong>Kepala Sekolah</strong>
                </p>
                <p class="font-bold underline text-xs">{{ $sekolah->nama_kepala_sekolah }}</p>
                <p class="text-[10px] text-zinc-500">NIP. {{ $sekolah->nip_kepala_sekolah ?? '-' }}</p>
            </div>
            <div>
                <p class="text-zinc-600 mb-16">
                    {{ $sekolah->kota ?? 'Bandung' }}, {{ $pengeluaran->tanggal->translatedFormat('d F Y') }}<br>
                    <strong>Bendahara / Kasir</strong>
                </p>
                <p class="font-bold underline text-xs">{{ $pengeluaran->user?->name ?? auth()->user()->name }}</p>
                <p class="text-[10px] text-zinc-500">NIP / ID: {{ $pengeluaran->user?->username ?? auth()->user()->username }}</p>
            </div>
        </div>

        <div class="mt-8 flex items-center justify-between border-t border-zinc-200 pt-2 text-[9px] text-zinc-400">
            <span>Dicetak secara elektronik pada {{ now()->translatedFormat('d F Y H:i:s') }}</span>
            <span>Bukti Pengeluaran Kas Resmi — {{ $sekolah->nama_sekolah }}</span>
        </div>
    </div>
</body>
</html>

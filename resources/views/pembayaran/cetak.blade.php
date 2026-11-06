<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php $sekolah = \App\Models\ProfilSekolah::current(); @endphp
    <title>Kuitansi SPP - {{ $pembayaran->kode_transaksi }} - {{ $sekolah->nama_sekolah }}</title>
    @include('partials.app-icon')
    @vite(['resources/css/app.css'])
    <style>
        @media print {
            .no-print { display: none !important; }
            body { font-size: 12px; background: #fff !important; }
            @page { margin: 1cm; size: auto; }
        }
    </style>
</head>
<body class="bg-zinc-100 p-4 sm:p-8 text-zinc-900 antialiased font-print">
    <div class="no-print max-w-2xl mx-auto mb-4 flex items-center justify-between">
        <a href="{{ auth()->user()?->isSiswa() ? route('portal.riwayat') : \App\Filament\Resources\Pembayarans\PembayaranResource::getUrl('index') }}" class="text-xs font-semibold text-zinc-600 hover:text-zinc-900">
            &larr; Kembali ke Aplikasi
        </a>
        <div class="flex items-center gap-2">
            <x-print-button variant="secondary" href="{{ $pembayaran->buktiUrl() }}" target="_blank" icon="arrow-top-right-on-square">
                Cek Link Bukti Digital
            </x-print-button>
            <x-print-button onclick="window.print()">
                Cetak Kuitansi
            </x-print-button>
        </div>
    </div>

    <!-- Printable Receipt Container -->
    <div class="max-w-2xl mx-auto bg-white p-8 rounded-xl shadow-sm border border-zinc-200">
        <!-- School Header -->
        <div class="flex items-center justify-between border-b-2 border-black pb-4">
            <div class="flex items-center gap-3">
                @if ($sekolah->logoUrl())
                    <img src="{{ $sekolah->logoUrl() }}" alt="Logo {{ $sekolah->nama_sekolah }}"
                        class="w-12 h-12 shrink-0 rounded-xl border border-zinc-200 bg-white object-contain p-1">
                @else
                    <div class="w-12 h-12 rounded-xl bg-zinc-900 text-white flex items-center justify-center font-black text-2xl uppercase">
                        {{ substr($sekolah->nama_sekolah, 0, 1) }}
                    </div>
                @endif
                <div>
                    <h1 class="text-base font-black tracking-wide uppercase">{{ $sekolah->nama_sekolah }}</h1>
                    <p class="text-xs text-zinc-600">{{ $sekolah->alamatLengkap() }} &bull; Telp. {{ $sekolah->no_telp }}</p>
                    <p class="text-[10px] text-zinc-500">Website: {{ $sekolah->website ?? '-' }} &bull; NPSN: {{ $sekolah->npsn ?? '-' }}</p>
                </div>
            </div>
            <div class="text-right">
                <div class="text-[10px] font-bold uppercase tracking-wider text-zinc-500">KUITANSI PEMBAYARAN SPP</div>
                <div class="font-mono font-bold text-sm text-zinc-900 mt-0.5">{{ $pembayaran->kode_transaksi }}</div>
            </div>
        </div>

        @if($pembayaran->isVoid())
            <div class="my-4 rounded-xl border border-rose-300 bg-rose-50 px-4 py-3 text-xs text-rose-800">
                <strong class="uppercase">Transaksi dibatalkan (VOID)</strong>
                @if($pembayaran->void_at)
                    &bull; {{ $pembayaran->void_at->translatedFormat('d F Y H:i') }}
                @endif
                @if($pembayaran->void_alasan)
                    <div class="mt-1">Alasan: {{ $pembayaran->void_alasan }}</div>
                @endif
            </div>
        @endif

        <!-- Student & Date Info -->
        <div class="grid grid-cols-2 gap-4 py-4 text-xs border-b border-zinc-200">
            <div class="space-y-1">
                <div><span class="text-zinc-500 inline-block w-24">Nama Siswa</span>: <strong class="text-zinc-900">{{ $pembayaran->siswa?->nama }}</strong></div>
                <div><span class="text-zinc-500 inline-block w-24">NISN / NIS</span>: <span class="font-mono text-zinc-800">{{ $pembayaran->siswa?->nisn }} / {{ $pembayaran->siswa?->nis }}</span></div>
                <div><span class="text-zinc-500 inline-block w-24">Kelas / Jurusan</span>: <span>{{ $pembayaran->siswa?->kelas?->nama_kelas }} ({{ $pembayaran->siswa?->kelas?->jurusan?->nama }})</span></div>
            </div>
            <div class="space-y-1 text-right">
                <div><span class="text-zinc-500">Tanggal Bayar:</span> <strong class="text-zinc-800">{{ $pembayaran->tgl_bayar->translatedFormat('d F Y') }}</strong></div>
                <div><span class="text-zinc-500">Metode:</span> <span class="text-zinc-800">{{ $pembayaran->metode_pembayaran }}</span></div>
                <div><span class="text-zinc-500">Petugas Kasir:</span> <span class="text-zinc-800">{{ $pembayaran->user?->name }}</span></div>
            </div>
        </div>

        <!-- Table of Items -->
        <table class="w-full text-left border-collapse text-xs my-4">
            <thead>
                <tr class="border-b border-zinc-300 bg-zinc-50 font-bold uppercase text-zinc-700">
                    <th class="py-2 px-2 w-12 text-center">No</th>
                    <th class="py-2 px-2">Keterangan / Pos Pembayaran</th>
                    <th class="py-2 px-2 text-center">Tahun Ajaran</th>
                    <th class="py-2 px-2 text-right">Jumlah Bayar</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @php $total = 0; @endphp
                @foreach($relatedPayments as $index => $trx)
                    @php $total += $trx->jumlah_bayar; @endphp
                    <tr>
                        <td class="py-2 px-2 text-center">{{ $index + 1 }}</td>
                        <td class="py-2 px-2 font-medium">
                            {{ $trx->namaPos() }}
                        </td>
                        <td class="py-2 px-2 text-center">{{ $trx->spp?->tahun_ajaran }}</td>
                        <td class="py-2 px-2 text-right font-semibold">{{ $trx->formattedJumlahBayar() }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="border-t-2 border-black font-bold">
                    <td colspan="3" class="py-2 px-2 uppercase text-right">Total Penerimaan:</td>
                    <td class="py-2 px-2 text-right text-sm">Rp {{ number_format($total, 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>

        <!-- QR Verification & Signatures -->
        <div class="grid grid-cols-3 gap-4 items-end text-xs pt-6 mt-6 border-t border-zinc-200">
            <div class="text-center">
                <p class="text-zinc-600 mb-16">Siswa / Orang Tua,</p>
                <p class="font-bold underline">{{ $pembayaran->siswa?->nama }}</p>
            </div>
            <div class="flex flex-col items-center justify-center text-center">
                <div class="p-1.5 bg-white border border-zinc-300 rounded-lg inline-block shadow-2xs">
                    {!! $pembayaran->qrCodeSvg(96) !!}
                </div>
                <p class="text-[10px] font-bold uppercase tracking-wider text-zinc-700 mt-1.5">Scan Cek Bukti Asli</p>
                <p class="text-[9px] text-zinc-500 leading-tight mt-0.5">Verifikasi online orang tua/wali</p>
            </div>
            <div class="text-center">
                <p class="text-zinc-600 mb-16">{{ $sekolah->kota ?? 'Bandung' }}, {{ $pembayaran->tgl_bayar->translatedFormat('d F Y') }}<br>Petugas Kasir,</p>
                <p class="font-bold underline">{{ $pembayaran->user?->name }}</p>
            </div>
        </div>

        <p class="text-center text-[10px] text-zinc-500 mt-6 pt-3 border-t border-dashed border-zinc-200">
            * Kuitansi ini sah diterbitkan oleh sistem sekolah. Orang tua dapat memindai (scan) QR Code di atas menggunakan kamera HP untuk melihat bukti pembayaran digital resmi.
        </p>
    </div>
</body>
</html>

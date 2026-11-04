<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php $sekolah = \App\Models\ProfilSekolah::current(); @endphp
    <title>Kuitansi SPP - {{ $pembayaran->kode_transaksi }} - {{ $sekolah->nama_sekolah }}</title>
    @vite(['resources/css/app.css'])
    <style>
        @media print {
            .no-print { display: none !important; }
            body { font-size: 12px; background: #fff !important; }
            @page { margin: 1cm; size: auto; }
        }
    </style>
</head>
<body class="bg-slate-100 p-4 sm:p-8 text-slate-900 antialiased font-sans">
    <div class="no-print max-w-2xl mx-auto mb-4 flex items-center justify-between">
        <a href="{{ auth()->user()?->isSiswa() ? route('portal.riwayat') : route('pembayaran.show', $pembayaran->id) }}" class="text-xs font-semibold text-slate-600 hover:text-slate-900">
            &larr; Kembali ke Aplikasi
        </a>
        <div class="flex items-center gap-2">
            <a href="{{ $pembayaran->buktiUrl() }}" target="_blank" class="px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 rounded-xl text-xs font-semibold transition shadow-sm inline-flex items-center gap-1.5">
                Cek Link Bukti Digital
            </a>
            <button onclick="window.print()" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition shadow-sm inline-flex items-center gap-2 cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Cetak Kuitansi
            </button>
        </div>
    </div>

    <!-- Printable Receipt Container -->
    <div class="max-w-2xl mx-auto bg-white p-8 rounded-xl shadow-sm border border-slate-200">
        <!-- School Header -->
        <div class="flex items-center justify-between border-b-2 border-black pb-4">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-slate-900 text-white flex items-center justify-center font-black text-2xl uppercase">
                    {{ substr($sekolah->nama_sekolah, 0, 1) }}
                </div>
                <div>
                    <h1 class="text-base font-black tracking-wide uppercase">{{ $sekolah->nama_sekolah }}</h1>
                    <p class="text-xs text-slate-600">{{ $sekolah->alamatLengkap() }} &bull; Telp. {{ $sekolah->no_telp }}</p>
                    <p class="text-[10px] text-slate-500">Website: {{ $sekolah->website ?? '-' }} &bull; NPSN: {{ $sekolah->npsn ?? '-' }}</p>
                </div>
            </div>
            <div class="text-right">
                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500">KUITANSI PEMBAYARAN SPP</div>
                <div class="font-mono font-bold text-sm text-slate-900 mt-0.5">{{ $pembayaran->kode_transaksi }}</div>
            </div>
        </div>

        <!-- Student & Date Info -->
        <div class="grid grid-cols-2 gap-4 py-4 text-xs border-b border-slate-200">
            <div class="space-y-1">
                <div><span class="text-slate-500 inline-block w-24">Nama Siswa</span>: <strong class="text-slate-900">{{ $pembayaran->siswa?->nama }}</strong></div>
                <div><span class="text-slate-500 inline-block w-24">NISN / NIS</span>: <span class="font-mono text-slate-800">{{ $pembayaran->siswa?->nisn }} / {{ $pembayaran->siswa?->nis }}</span></div>
                <div><span class="text-slate-500 inline-block w-24">Kelas / Jurusan</span>: <span>{{ $pembayaran->siswa?->kelas?->nama_kelas }} ({{ $pembayaran->siswa?->kelas?->jurusan?->nama }})</span></div>
            </div>
            <div class="space-y-1 text-right">
                <div><span class="text-slate-500">Tanggal Bayar:</span> <strong class="text-slate-800">{{ $pembayaran->tgl_bayar->translatedFormat('d F Y') }}</strong></div>
                <div><span class="text-slate-500">Metode:</span> <span class="text-slate-800">{{ $pembayaran->metode_pembayaran }}</span></div>
                <div><span class="text-slate-500">Petugas Kasir:</span> <span class="text-slate-800">{{ $pembayaran->user?->name }}</span></div>
            </div>
        </div>

        <!-- Table of Items -->
        <table class="w-full text-left border-collapse text-xs my-4">
            <thead>
                <tr class="border-b border-slate-300 bg-slate-50 font-bold uppercase text-slate-700">
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
        <div class="grid grid-cols-3 gap-4 items-end text-xs pt-6 mt-6 border-t border-slate-200">
            <div class="text-center">
                <p class="text-slate-600 mb-16">Siswa / Orang Tua,</p>
                <p class="font-bold underline">{{ $pembayaran->siswa?->nama }}</p>
            </div>
            <div class="flex flex-col items-center justify-center text-center">
                <div class="p-1.5 bg-white border border-slate-300 rounded-lg inline-block shadow-2xs">
                    {!! $pembayaran->qrCodeSvg(96) !!}
                </div>
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-700 mt-1.5">Scan Cek Bukti Asli</p>
                <p class="text-[9px] text-slate-500 leading-tight mt-0.5">Verifikasi online orang tua/wali</p>
            </div>
            <div class="text-center">
                <p class="text-slate-600 mb-16">{{ $sekolah->kota ?? 'Bandung' }}, {{ $pembayaran->tgl_bayar->translatedFormat('d F Y') }}<br>Petugas Kasir,</p>
                <p class="font-bold underline">{{ $pembayaran->user?->name }}</p>
            </div>
        </div>

        <p class="text-center text-[10px] text-slate-500 mt-6 pt-3 border-t border-dashed border-slate-200">
            * Kuitansi ini sah diterbitkan oleh sistem sekolah. Orang tua dapat memindai (scan) QR Code di atas menggunakan kamera HP untuk melihat bukti pembayaran digital resmi.
        </p>
    </div>
</body>
</html>

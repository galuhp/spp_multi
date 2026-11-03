<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Rekapitulasi Pembayaran SPP</title>
    @vite(['resources/css/app.css'])
    <style>
        @media print {
            .no-print { display: none !important; }
            body { font-size: 11px; background: #fff !important; }
            @page { margin: 1cm; size: landscape; }
        }
    </style>
</head>
<body class="bg-slate-100 p-4 sm:p-8 text-slate-900 antialiased font-sans">
    <div class="no-print max-w-5xl mx-auto mb-4 flex items-center justify-between">
        <a href="{{ route('laporan.index') }}" class="text-xs font-semibold text-slate-600 hover:text-slate-900">
            &larr; Kembali ke Laporan
        </a>
        <button onclick="window.print()" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition shadow-sm inline-flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Cetak Dokumen Sekarang
        </button>
    </div>

    <!-- Official Report Paper Container -->
    <div class="max-w-5xl mx-auto bg-white p-8 sm:p-10 rounded-xl shadow-sm border border-slate-200">
        <!-- Official School Letterhead (Kop Surat) -->
        <div class="text-center border-b-2 border-black pb-4 mb-6">
            <h1 class="text-xl font-black uppercase tracking-wider">PEMERINTAH DAERAH PROVINSI JAWA BARAT</h1>
            <h2 class="text-lg font-extrabold uppercase">DINAS PENDIDIKAN DAN KEBUDAYAAN</h2>
            <h3 class="text-2xl font-black uppercase tracking-wide mt-1">SMK NEGERI CONTOH KOTA BANDUNG</h3>
            <p class="text-xs text-slate-600 mt-1">
                Jl. Pendidikan No. 123, Bandung 40123 &bull; Telp. (022) 1234567 &bull; Email: info@smknegericontoh.sch.id
            </p>
        </div>

        <!-- Document Title -->
        <div class="text-center mb-6">
            <h4 class="text-base font-bold uppercase underline">LAPORAN REKAPITULASI PEMBAYARAN IURAN SPP</h4>
            <div class="text-xs text-slate-700 mt-1">
                <span>Periode: <strong>{{ \Carbon\Carbon::parse($tglAwal)->translatedFormat('d F Y') }} s/d {{ \Carbon\Carbon::parse($tglAkhir)->translatedFormat('d F Y') }}</strong></span>
                @if($selectedKelas)
                    <span class="ml-3">&bull; Kelas: <strong>{{ $selectedKelas->nama_kelas }}</strong></span>
                @endif
                @if($metode)
                    <span class="ml-3">&bull; Metode: <strong>{{ $metode }}</strong></span>
                @endif
            </div>
        </div>

        <!-- Records Table -->
        <table class="w-full text-left border-collapse text-xs border border-slate-300">
            <thead>
                <tr class="bg-slate-100 border-b border-slate-300 font-bold uppercase text-slate-800">
                    <th class="py-2 px-2 border-r border-slate-300 w-10 text-center">No</th>
                    <th class="py-2 px-2 border-r border-slate-300">No. Transaksi</th>
                    <th class="py-2 px-2 border-r border-slate-300">Tgl. Bayar</th>
                    <th class="py-2 px-2 border-r border-slate-300">Nama Siswa</th>
                    <th class="py-2 px-2 border-r border-slate-300">NISN</th>
                    <th class="py-2 px-2 border-r border-slate-300">Kelas</th>
                    <th class="py-2 px-2 border-r border-slate-300">Bulan Dibayar</th>
                    <th class="py-2 px-2 border-r border-slate-300 text-right">Nominal</th>
                    <th class="py-2 px-2 border-r border-slate-300">Metode</th>
                    <th class="py-2 px-2">Petugas Kasir</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @forelse($pembayarans as $index => $item)
                    <tr>
                        <td class="py-2 px-2 border-r border-slate-200 text-center">{{ $index + 1 }}</td>
                        <td class="py-2 px-2 border-r border-slate-200 font-mono">{{ $item->kode_transaksi }}</td>
                        <td class="py-2 px-2 border-r border-slate-200">{{ $item->tgl_bayar->format('d/m/Y') }}</td>
                        <td class="py-2 px-2 border-r border-slate-200 font-semibold">{{ $item->siswa?->nama ?? '-' }}</td>
                        <td class="py-2 px-2 border-r border-slate-200 font-mono">{{ $item->siswa?->nisn ?? '-' }}</td>
                        <td class="py-2 px-2 border-r border-slate-200">{{ $item->siswa?->kelas?->nama_kelas ?? '-' }}</td>
                        <td class="py-2 px-2 border-r border-slate-200">{{ $item->bulan_dibayar }} {{ $item->tahun_dibayar }}</td>
                        <td class="py-2 px-2 border-r border-slate-200 text-right font-semibold">{{ $item->formattedJumlahBayar() }}</td>
                        <td class="py-2 px-2 border-r border-slate-200">{{ $item->metode_pembayaran }}</td>
                        <td class="py-2 px-2">{{ $item->user?->name ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="py-6 text-center text-slate-400">
                            Tidak ada transaksi pembayaran pada filter periode ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr class="border-t-2 border-black font-bold bg-slate-50 text-xs">
                    <td colspan="7" class="py-2.5 px-3 border-r border-slate-300 text-right uppercase">
                        Total Penerimaan SPP:
                    </td>
                    <td class="py-2.5 px-3 border-r border-slate-300 text-right text-sm">
                        Rp {{ number_format($totalPenerimaan, 0, ',', '.') }}
                    </td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>

        <!-- Signatures Block -->
        <div class="grid grid-cols-2 gap-8 text-xs text-center pt-8 mt-6">
            <div>
                <p class="text-slate-600 mb-20">
                    Mengetahui,<br>
                    <strong>Kepala Sekolah SMK Negeri Contoh</strong>
                </p>
                <p class="font-bold underline text-sm">Drs. H. Rahmat Hidayat, M.M.Pd.</p>
                <p class="text-[11px] text-slate-500">NIP. 19700101 199503 1 001</p>
            </div>
            <div>
                <p class="text-slate-600 mb-20">
                    Bandung, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}<br>
                    <strong>Bendahara / Petugas Keuangan</strong>
                </p>
                <p class="font-bold underline text-sm">{{ auth()->user()->name }}</p>
                <p class="text-[11px] text-slate-500">NIP / ID: {{ auth()->user()->username }}</p>
            </div>
        </div>
    </div>
</body>
</html>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php $sekolah = \App\Models\ProfilSekolah::current(); @endphp
    <title>Laporan Rekapitulasi Pembayaran SPP - {{ $sekolah->nama_sekolah }}</title>
    @vite(['resources/css/app.css'])
    <style>
        @media print {
            .no-print { display: none !important; }
            body { font-size: 11px; background: #fff !important; }
            @page { margin: 1cm; size: landscape; }
        }
    </style>
</head>
<body class="bg-zinc-100 p-4 sm:p-8 text-zinc-900 antialiased font-sans">
    <div class="no-print max-w-5xl mx-auto mb-4 flex items-center justify-between">
        <a href="{{ route('laporan.index') }}" class="text-xs font-semibold text-zinc-600 hover:text-zinc-900">
            &larr; Kembali ke Laporan
        </a>
        <button onclick="window.print()" class="px-4 py-2 bg-zinc-900 hover:bg-zinc-800 text-white rounded-xl text-xs font-bold transition shadow-sm inline-flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Cetak Dokumen Sekarang
        </button>
    </div>

    <!-- Official Report Paper Container -->
    <div class="max-w-5xl mx-auto bg-white p-8 sm:p-10 rounded-xl shadow-sm border border-zinc-200">
        <!-- Official School Letterhead (Kop Surat) -->
        <div class="text-center border-b-2 border-black pb-4 mb-6">
            <h1 class="text-xl font-black uppercase tracking-wider">{{ $sekolah->pemerintah_daerah }}</h1>
            <h2 class="text-lg font-extrabold uppercase">{{ $sekolah->dinas_pendidikan }}</h2>
            <h3 class="text-2xl font-black uppercase tracking-wide mt-1">{{ $sekolah->nama_sekolah }} {{ $sekolah->kota ? 'KOTA ' . strtoupper($sekolah->kota) : '' }}</h3>
            <p class="text-xs text-zinc-600 mt-1">
                {{ $sekolah->alamatLengkap() }} &bull; Telp. {{ $sekolah->no_telp }} &bull; Email: {{ $sekolah->email }}
            </p>
        </div>

        <!-- Document Title -->
        <div class="text-center mb-6">
            <h4 class="text-base font-bold uppercase underline">LAPORAN REKAPITULASI PENERIMAAN PEMBAYARAN</h4>
            <div class="text-xs text-zinc-700 mt-1">
                <span>Periode: <strong>{{ \Carbon\Carbon::parse($tglAwal)->translatedFormat('d F Y') }} s/d {{ \Carbon\Carbon::parse($tglAkhir)->translatedFormat('d F Y') }}</strong></span>
                @if($kategori ?? false)
                    <span class="ml-3">&bull; Pos Tarif: <strong>{{ $kategori }}</strong></span>
                @endif
                @if($selectedKelas)
                    <span class="ml-3">&bull; Kelas: <strong>{{ $selectedKelas->nama_kelas }}</strong></span>
                @endif
                @if($metode)
                    <span class="ml-3">&bull; Metode: <strong>{{ $metode }}</strong></span>
                @endif
            </div>
        </div>

        <!-- Records Table -->
        <table class="w-full text-left border-collapse text-xs border border-zinc-300">
            <thead>
                <tr class="bg-zinc-100 border-b border-zinc-300 font-bold uppercase text-zinc-800">
                    <th class="py-2 px-2 border-r border-zinc-300 w-10 text-center">No</th>
                    <th class="py-2 px-2 border-r border-zinc-300">No. Transaksi</th>
                    <th class="py-2 px-2 border-r border-zinc-300">Tgl. Bayar</th>
                    <th class="py-2 px-2 border-r border-zinc-300">Nama Siswa</th>
                    <th class="py-2 px-2 border-r border-zinc-300">NISN</th>
                    <th class="py-2 px-2 border-r border-zinc-300">Kelas</th>
                    <th class="py-2 px-2 border-r border-zinc-300">Pos Pembayaran</th>
                    <th class="py-2 px-2 border-r border-zinc-300">Sumber</th>
                    <th class="py-2 px-2 border-r border-zinc-300 text-right">Nominal</th>
                    <th class="py-2 px-2 border-r border-zinc-300">Metode</th>
                    <th class="py-2 px-2">Petugas Kasir</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200">
                @forelse($pembayarans as $index => $item)
                    <tr>
                        <td class="py-2 px-2 border-r border-zinc-200 text-center">{{ $index + 1 }}</td>
                        <td class="py-2 px-2 border-r border-zinc-200 font-mono">{{ $item->kode_transaksi }}</td>
                        <td class="py-2 px-2 border-r border-zinc-200">{{ $item->tgl_bayar->format('d/m/Y') }}</td>
                        <td class="py-2 px-2 border-r border-zinc-200 font-semibold">{{ $item->siswa?->nama ?? '-' }}</td>
                        <td class="py-2 px-2 border-r border-zinc-200 font-mono">{{ $item->siswa?->nisn ?? '-' }}</td>
                        <td class="py-2 px-2 border-r border-zinc-200">{{ $item->siswa?->kelas?->nama_kelas ?? '-' }}</td>
                        <td class="py-2 px-2 border-r border-zinc-200">
                            <strong>{{ $item->spp?->kategori ?? 'SPP' }}</strong> &bull; {{ $item->namaPos() }}
                        </td>
                        <td class="py-2 px-2 border-r border-zinc-200">
                            @if($item->gabungan_id)
                                Gabungan {{ $item->gabungan?->kode }}
                            @else
                                Loket
                            @endif
                        </td>
                        <td class="py-2 px-2 border-r border-zinc-200 text-right font-semibold">{{ $item->formattedJumlahBayar() }}</td>
                        <td class="py-2 px-2 border-r border-zinc-200">{{ $item->metode_pembayaran }}</td>
                        <td class="py-2 px-2">{{ $item->user?->name ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="py-6 text-center text-zinc-400">
                            Tidak ada transaksi pembayaran pada filter periode ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr class="border-t-2 border-black font-bold bg-zinc-50 text-xs">
                    <td colspan="8" class="py-2.5 px-3 border-r border-zinc-300 text-right uppercase">
                        Total Penerimaan:
                    </td>
                    <td class="py-2.5 px-3 border-r border-zinc-300 text-right text-sm">
                        Rp {{ number_format($totalPenerimaan, 0, ',', '.') }}
                    </td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>

        <!-- Signatures Block -->
        <div class="grid grid-cols-2 gap-8 text-xs text-center pt-8 mt-6">
            <div>
                <p class="text-zinc-600 mb-20">
                    Mengetahui,<br>
                    <strong>Kepala Sekolah {{ $sekolah->nama_sekolah }}</strong>
                </p>
                <p class="font-bold underline text-sm">{{ $sekolah->nama_kepala_sekolah }}</p>
                <p class="text-[11px] text-zinc-500">NIP. {{ $sekolah->nip_kepala_sekolah ?? '-' }}</p>
            </div>
            <div>
                <p class="text-zinc-600 mb-20">
                    {{ $sekolah->kota ?? 'Bandung' }}, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}<br>
                    <strong>Bendahara / Petugas Keuangan</strong>
                </p>
                <p class="font-bold underline text-sm">{{ auth()->user()->name }}</p>
                <p class="text-[11px] text-zinc-500">NIP / ID: {{ auth()->user()->username }}</p>
            </div>
        </div>
    </div>
</body>
</html>

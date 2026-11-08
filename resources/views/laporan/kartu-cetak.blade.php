<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php $sekolah = \App\Models\ProfilSekolah::current(); @endphp
    <title>Kartu Pembayaran Siswa - {{ $sekolah->nama_sekolah }}</title>
    @include('partials.app-icon')
    @vite(['resources/css/app.css'])
    <style>
        @media print {
            .no-print { display: none !important; }
            body { font-size: 10px; background: #fff !important; padding: 0 !important; }
            @page { margin: 1cm; size: A4 portrait; }
            .report-card {
                max-width: none !important;
                margin: 0 !important;
                padding: 0 !important;
                border: 0 !important;
                border-radius: 0 !important;
                box-shadow: none !important;
            }
            table { font-size: 9px; }
            th, td { padding: 3px 5px !important; }
            thead { display: table-header-group; }
            tr { break-inside: avoid; page-break-inside: avoid; }
        }
    </style>
</head>
<body class="bg-zinc-100 p-4 sm:p-8 text-zinc-900 antialiased font-print">
    @php
        $siswa = $kartu['siswa'];
        $kelas = $kartu['kelas'];
        $tahunAjaran = $kartu['tahun_ajaran'];
        $ringkasan = $kartu['ringkasan'];
        $voidPayments = $kartu['void_payments'];
        $items = $rincian['items'];
        $totalRincian = $rincian;
    @endphp

    <div class="no-print max-w-4xl mx-auto mb-4 flex items-center justify-between gap-4">
        <a href="{{ url()->previous() ?: route('filament.admin.pages.kartu-pembayaran-siswa') }}" class="text-xs font-semibold text-zinc-600 hover:text-zinc-900">
            &larr; Kembali
        </a>
        <div class="flex items-center gap-2">
            <a href="{{ request()->fullUrlWithQuery(['layout' => 'landscape']) }}" class="px-3 py-2 bg-white hover:bg-zinc-50 border border-zinc-300 text-zinc-800 rounded-xl text-xs font-semibold transition shadow-sm inline-flex items-center gap-1.5">
                <svg class="w-4 h-4 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"/></svg>
                Mode Landscape
            </a>
            <button onclick="window.print()" class="px-4 py-2 bg-zinc-900 hover:bg-zinc-800 text-white rounded-xl text-xs font-bold transition shadow-sm inline-flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Cetak Kartu Sekarang
            </button>
        </div>
    </div>

    <div class="report-card max-w-4xl mx-auto bg-white p-6 sm:p-8 rounded-xl shadow-sm border border-zinc-200">
        <!-- Kop Surat -->
        <div class="mb-3 flex items-center gap-3 border-b border-zinc-400 pb-2">
            @if ($sekolah->logoUrl())
                <img src="{{ $sekolah->logoUrl() }}" alt="Logo {{ $sekolah->nama_sekolah }}" class="h-9 w-9 shrink-0 object-contain">
            @endif
            <div class="min-w-0">
                <h1 class="text-sm font-bold uppercase leading-tight tracking-wide">
                    {{ $sekolah->nama_sekolah }}{{ $sekolah->kota ? ' — ' . strtoupper($sekolah->kota) : '' }}
                </h1>
                <p class="text-[10px] leading-tight text-zinc-600">
                    {{ $sekolah->alamatLengkap() }} &bull; Telp. {{ $sekolah->no_telp }} &bull; Email: {{ $sekolah->email }}
                </p>
            </div>
        </div>

        <!-- Judul Kartu -->
        <div class="mb-3 text-center">
            <h2 class="text-sm font-bold uppercase underline">KARTU PEMBAYARAN SISWA</h2>
            <p class="mt-0.5 text-[10px] text-zinc-700">Tahun Ajaran <strong>{{ $tahunAjaran->nama }}</strong></p>
        </div>

        <!-- Identitas Siswa -->
        <table class="mb-3 w-full border-collapse text-[10px] border border-zinc-300">
            <tbody>
                <tr>
                    <td class="border border-zinc-300 bg-zinc-50 px-2 py-1 font-semibold w-28">Nama Siswa</td>
                    <td class="border border-zinc-300 px-2 py-1">{{ $siswa->nama }}</td>
                    <td class="border border-zinc-300 bg-zinc-50 px-2 py-1 font-semibold w-28">NISN / NIS</td>
                    <td class="border border-zinc-300 px-2 py-1 font-mono">{{ $siswa->nisn ?: '-' }} / {{ $siswa->nis ?: '-' }}</td>
                </tr>
                <tr>
                    <td class="border border-zinc-300 bg-zinc-50 px-2 py-1 font-semibold">Kelas</td>
                    <td class="border border-zinc-300 px-2 py-1">{{ $kelas?->nama_kelas ?? '-' }}</td>
                    <td class="border border-zinc-300 bg-zinc-50 px-2 py-1 font-semibold">Jurusan</td>
                    <td class="border border-zinc-300 px-2 py-1">{{ $kelas?->jurusan?->nama ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="border border-zinc-300 bg-zinc-50 px-2 py-1 font-semibold">Tahun Ajaran</td>
                    <td colspan="3" class="border border-zinc-300 px-2 py-1">{{ $tahunAjaran->nama }}</td>
                </tr>
            </tbody>
        </table>

        <!-- Ringkasan Keuangan -->
        <table class="mb-4 w-full border-collapse text-[10px] border border-zinc-300">
            <tbody>
                <tr>
                    <td class="border border-zinc-300 bg-zinc-50 px-2 py-1 font-semibold">Tagihan Jatuh Tempo</td>
                    <td class="border border-zinc-300 px-2 py-1 text-right">{{ 'Rp '.number_format($ringkasan['tagihan_jatuh_tempo'], 0, ',', '.') }}</td>
                    <td class="border border-zinc-300 bg-zinc-50 px-2 py-1 font-semibold">Total Dibayar</td>
                    <td class="border border-zinc-300 px-2 py-1 text-right font-semibold text-emerald-700">{{ 'Rp '.number_format($ringkasan['total_dibayar'], 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td class="border border-zinc-300 bg-zinc-50 px-2 py-1 font-semibold">Tunggakan Jatuh Tempo</td>
                    <td class="border border-zinc-300 px-2 py-1 text-right font-bold text-rose-700">{{ 'Rp '.number_format($ringkasan['tunggakan_jatuh_tempo'], 0, ',', '.') }}</td>
                    <td class="border border-zinc-300 bg-zinc-50 px-2 py-1 font-semibold">Saldo Kartu</td>
                    <td class="border border-zinc-300 px-2 py-1 text-right font-bold">
                        {{ $ringkasan['saldo'] < 0 ? '- ' : '' }}Rp {{ number_format(abs($ringkasan['saldo']), 0, ',', '.') }}
                    </td>
                </tr>
                <tr>
                    <td class="border border-zinc-300 bg-zinc-50 px-2 py-1 font-semibold">Total Tagihan Setahun</td>
                    <td class="border border-zinc-300 px-2 py-1 text-right">{{ 'Rp '.number_format($ringkasan['total_tagihan'], 0, ',', '.') }}</td>
                    <td class="border border-zinc-300 bg-zinc-50 px-2 py-1 font-semibold">Rincian</td>
                    <td class="border border-zinc-300 px-2 py-1 text-right">
                        SPP {{ 'Rp '.number_format($ringkasan['total_tagihan_spp'], 0, ',', '.') }}
                        &bull; Non-SPP {{ 'Rp '.number_format($ringkasan['total_tagihan_non_spp'], 0, ',', '.') }}
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Transaksi Dibatalkan jika ada -->
        @if($voidPayments->isNotEmpty())
            <div class="mb-4">
                <h3 class="mb-1 text-[10px] font-bold uppercase text-zinc-700">Transaksi Dibatalkan (Tidak Dihitung)</h3>
                <table class="w-full text-left border-collapse text-[9px] border border-zinc-300">
                    <thead>
                        <tr class="bg-zinc-100 border-b border-zinc-300 font-bold uppercase text-zinc-800">
                            <th class="py-1 px-2 border-r border-zinc-300">Tanggal Batal</th>
                            <th class="py-1 px-2 border-r border-zinc-300">Kode</th>
                            <th class="py-1 px-2 border-r border-zinc-300">Uraian</th>
                            <th class="py-1 px-2 border-r border-zinc-300 text-right">Jumlah</th>
                            <th class="py-1 px-2">Alasan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200">
                        @foreach($voidPayments as $void)
                            <tr>
                                <td class="py-1 px-2 border-r border-zinc-200 whitespace-nowrap">{{ $void->void_at?->format('d/m/Y H:i') ?? '-' }}</td>
                                <td class="py-1 px-2 border-r border-zinc-200 font-mono">{{ $void->kode_transaksi }}</td>
                                <td class="py-1 px-2 border-r border-zinc-200">{{ $void->namaPos() }}</td>
                                <td class="py-1 px-2 border-r border-zinc-200 text-right">{{ 'Rp '.number_format($void->jumlah_bayar, 0, ',', '.') }}</td>
                                <td class="py-1 px-2">{{ $void->void_alasan ?: '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <!-- Rincian Tagihan -->
        <div class="mb-2">
            <h3 class="mb-1.5 text-[11px] font-bold uppercase text-zinc-900 tracking-wide">RINCIAN TAGIHAN</h3>
            <table class="w-full text-left border-collapse text-[9px] border border-zinc-300">
                <thead>
                    <tr class="bg-zinc-100 border-b border-zinc-300 font-bold uppercase text-zinc-800">
                        <th class="py-1.5 px-2 border-r border-zinc-300">Pos Tarif</th>
                        <th class="py-1.5 px-2 border-r border-zinc-300 text-right">Nominal Tagihan</th>
                        <th class="py-1.5 px-2 border-r border-zinc-300 text-right">Jumlah Terbayar</th>
                        <th class="py-1.5 px-2 border-r border-zinc-300 text-right">Sisa Tagihan</th>
                        <th class="py-1.5 px-2 border-r border-zinc-300">Tanggal Bayar</th>
                        <th class="py-1.5 px-2 border-r border-zinc-300">No. Kuitansi</th>
                        <th class="py-1.5 px-2 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200">
                    @forelse($items as $item)
                        <tr>
                            <td class="py-1.5 px-2 border-r border-zinc-200">
                                <div class="font-semibold">{{ $item['pos_tarif'] }}</div>
                                <div class="text-[8px] text-zinc-500">
                                    {{ $item['kategori'] }} &bull; {{ $item['tipe'] }}
                                </div>
                            </td>
                            <td class="py-1.5 px-2 border-r border-zinc-200 text-right whitespace-nowrap">
                                {{ 'Rp '.number_format($item['nominal'], 0, ',', '.') }}
                            </td>
                            <td class="py-1.5 px-2 border-r border-zinc-200 text-right whitespace-nowrap text-emerald-700">
                                {{ $item['dibayar'] > 0 ? 'Rp '.number_format($item['dibayar'], 0, ',', '.') : '-' }}
                            </td>
                            <td class="py-1.5 px-2 border-r border-zinc-200 text-right whitespace-nowrap {{ $item['sisa'] > 0 ? 'text-rose-700 font-semibold' : '' }}">
                                {{ $item['sisa'] > 0 ? 'Rp '.number_format($item['sisa'], 0, ',', '.') : '-' }}
                            </td>
                            <td class="py-1.5 px-2 border-r border-zinc-200 whitespace-nowrap">
                                {{ $item['tanggal_bayar'] ?: '-' }}
                            </td>
                            <td class="py-1.5 px-2 border-r border-zinc-200 font-mono text-[8px] whitespace-nowrap">
                                {{ $item['no_kuitansi'] ?: ($item['referensi'] ?: '-') }}
                            </td>
                            <td class="py-1.5 px-2 text-center whitespace-nowrap font-medium">
                                @if($item['ditanggung'])
                                    <span class="text-zinc-600">Ditanggung</span>
                                @elseif($item['status'] === 'lunas')
                                    <span class="text-emerald-700 font-semibold">Lunas</span>
                                @elseif($item['status'] === 'sebagian')
                                    <span class="text-amber-700 font-semibold">Sebagian</span>
                                @else
                                    <span class="text-zinc-500">Belum Lunas</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-6 text-center text-zinc-400">Belum ada tagihan atau pembayaran pada tahun ajaran ini.</td>
                        </tr>
                    @endforelse
                </tbody>
                @if ($items !== [])
                    <tfoot>
                        <tr class="border-t-2 border-black font-bold bg-zinc-50 text-[9px]">
                            <td class="py-1.5 px-2 border-r border-zinc-300 text-right uppercase">Total:</td>
                            <td class="py-1.5 px-2 border-r border-zinc-300 text-right whitespace-nowrap">{{ 'Rp '.number_format($totalRincian['total_nominal'], 0, ',', '.') }}</td>
                            <td class="py-1.5 px-2 border-r border-zinc-300 text-right whitespace-nowrap text-emerald-700">{{ 'Rp '.number_format($totalRincian['total_dibayar'], 0, ',', '.') }}</td>
                            <td class="py-1.5 px-2 border-r border-zinc-300 text-right whitespace-nowrap text-rose-700">{{ 'Rp '.number_format($totalRincian['total_sisa'], 0, ',', '.') }}</td>
                            <td colspan="3"></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>

        @if ($totalRincian['total_ditanggung'] > 0)
            <p class="mt-1 text-[9px] text-zinc-500">
                Ditanggung pemerintah: {{ 'Rp '.number_format($totalRincian['total_ditanggung'], 0, ',', '.') }} (tidak dihitung dalam total).
            </p>
        @endif

        <p class="mt-2 text-[9px] text-zinc-500">
            Saldo positif berarti masih ada kewajiban; saldo negatif berarti kelebihan bayar. Tagihan SPP bulan mendatang tetap ditampilkan sebagai bagian dari kartu tahun ajaran.
        </p>

        <div class="mt-4 flex items-center justify-between border-t border-zinc-200 pt-2 text-[9px] text-zinc-500">
            <span>{{ $sekolah->nama_sekolah }} &bull; Dicetak: {{ \Carbon\Carbon::now()->translatedFormat('d F Y H:i') }}</span>
            <span>Oleh: {{ auth()->user()->name }}</span>
        </div>
    </div>
</body>
</html>

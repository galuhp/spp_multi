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
            @page { margin: 1.2cm; size: A4 portrait; }
            .report-page {
                max-width: none !important;
                margin: 0 !important;
                padding: 0 !important;
                border: 0 !important;
                border-radius: 0 !important;
                box-shadow: none !important;
                break-after: page;
                page-break-after: always;
            }
            .report-page:last-child {
                break-after: auto;
                page-break-after: auto;
            }
            table { font-size: 9px; }
            th, td { padding: 3px 5px !important; }
            thead { display: table-header-group; }
            tr { break-inside: avoid; }
        }
    </style>
</head>
<body class="bg-zinc-100 p-4 sm:p-8 text-zinc-900 antialiased font-print">
    @php
        $siswa = $kartu['siswa'];
        $kelas = $kartu['kelas'];
        $tarif = $kartu['tarif'];
        $tahunAjaran = $kartu['tahun_ajaran'];
        $ringkasan = $kartu['ringkasan'];
        $entries = $kartu['entries'];
        $voidPayments = $kartu['void_payments'];
        $perHalaman = 22;
        $halamanData = $entries->isEmpty() ? collect([collect()]) : $entries->chunk($perHalaman);
        $totalHalaman = 1 + $halamanData->count() + 1;
    @endphp

    <div class="no-print max-w-4xl mx-auto mb-4 flex items-center justify-between">
        <a href="{{ route('filament.admin.pages.kartu-pembayaran-siswa') }}" class="text-xs font-semibold text-zinc-600 hover:text-zinc-900">
            &larr; Kembali ke Kartu Pembayaran
        </a>
        <button onclick="window.print()" class="px-4 py-2 bg-zinc-900 hover:bg-zinc-800 text-white rounded-xl text-xs font-bold transition shadow-sm inline-flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Cetak Kartu Sekarang
        </button>
    </div>

    <!-- Identitas & Ringkasan -->
    <section class="report-page max-w-4xl mx-auto mb-6 bg-white p-6 sm:p-8 rounded-xl shadow-sm border border-zinc-200">
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

        <div class="mb-3 text-center">
            <h2 class="text-sm font-bold uppercase underline">KARTU PEMBAYARAN SISWA</h2>
            <p class="mt-0.5 text-[10px] text-zinc-700">Tahun Ajaran <strong>{{ $tahunAjaran->nama }}</strong></p>
        </div>

        <table class="mb-4 w-full border-collapse text-[10px] border border-zinc-300">
            <tbody>
                <tr>
                    <td class="border border-zinc-300 bg-zinc-50 px-2 py-1.5 font-semibold w-28">Nama Siswa</td>
                    <td class="border border-zinc-300 px-2 py-1.5">{{ $siswa->nama }}</td>
                    <td class="border border-zinc-300 bg-zinc-50 px-2 py-1.5 font-semibold w-28">NISN / NIS</td>
                    <td class="border border-zinc-300 px-2 py-1.5 font-mono">{{ $siswa->nisn ?: '-' }} / {{ $siswa->nis ?: '-' }}</td>
                </tr>
                <tr>
                    <td class="border border-zinc-300 bg-zinc-50 px-2 py-1.5 font-semibold">Kelas</td>
                    <td class="border border-zinc-300 px-2 py-1.5">{{ $kelas?->nama_kelas ?? '-' }}</td>
                    <td class="border border-zinc-300 bg-zinc-50 px-2 py-1.5 font-semibold">Jurusan</td>
                    <td class="border border-zinc-300 px-2 py-1.5">{{ $kelas?->jurusan?->nama ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="border border-zinc-300 bg-zinc-50 px-2 py-1.5 font-semibold">Tarif SPP</td>
                    <td class="border border-zinc-300 px-2 py-1.5">{{ $tarif ? 'Rp '.number_format($tarif->nominal, 0, ',', '.').' / bulan' : 'Belum ada tarif' }}</td>
                    <td class="border border-zinc-300 bg-zinc-50 px-2 py-1.5 font-semibold">Tahun Ajaran</td>
                    <td class="border border-zinc-300 px-2 py-1.5">{{ $tahunAjaran->nama }}</td>
                </tr>
            </tbody>
        </table>

        <table class="mb-4 w-full border-collapse text-[10px] border border-zinc-300">
            <tbody>
                <tr>
                    <td class="border border-zinc-300 bg-zinc-50 px-2 py-1.5 font-semibold">Tagihan Jatuh Tempo</td>
                    <td class="border border-zinc-300 px-2 py-1.5 text-right">{{ 'Rp '.number_format($ringkasan['tagihan_jatuh_tempo'], 0, ',', '.') }}</td>
                    <td class="border border-zinc-300 bg-zinc-50 px-2 py-1.5 font-semibold">Total Dibayar</td>
                    <td class="border border-zinc-300 px-2 py-1.5 text-right">{{ 'Rp '.number_format($ringkasan['total_dibayar'], 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td class="border border-zinc-300 bg-zinc-50 px-2 py-1.5 font-semibold">Tunggakan Jatuh Tempo</td>
                    <td class="border border-zinc-300 px-2 py-1.5 text-right font-bold">{{ 'Rp '.number_format($ringkasan['tunggakan_jatuh_tempo'], 0, ',', '.') }}</td>
                    <td class="border border-zinc-300 bg-zinc-50 px-2 py-1.5 font-semibold">Saldo Kartu</td>
                    <td class="border border-zinc-300 px-2 py-1.5 text-right font-bold">
                        {{ $ringkasan['saldo'] < 0 ? '- ' : '' }}Rp {{ number_format(abs($ringkasan['saldo']), 0, ',', '.') }}
                    </td>
                </tr>
                <tr>
                    <td class="border border-zinc-300 bg-zinc-50 px-2 py-1.5 font-semibold">Total Tagihan Setahun</td>
                    <td class="border border-zinc-300 px-2 py-1.5 text-right">{{ 'Rp '.number_format($ringkasan['total_tagihan'], 0, ',', '.') }}</td>
                    <td class="border border-zinc-300 bg-zinc-50 px-2 py-1.5 font-semibold">Rincian</td>
                    <td class="border border-zinc-300 px-2 py-1.5 text-right">
                        SPP {{ 'Rp '.number_format($ringkasan['total_tagihan_spp'], 0, ',', '.') }}
                        &bull; Non-SPP {{ 'Rp '.number_format($ringkasan['total_tagihan_non_spp'], 0, ',', '.') }}
                    </td>
                </tr>
            </tbody>
        </table>

        @if($voidPayments->isNotEmpty())
            <h3 class="mb-1.5 text-[11px] font-bold uppercase">Transaksi Void (Tidak Dihitung)</h3>
            <table class="w-full text-left border-collapse text-[9px] border border-zinc-300">
                <thead>
                    <tr class="bg-zinc-100 border-b border-zinc-300 font-bold uppercase text-zinc-800">
                        <th class="py-1.5 px-2 border-r border-zinc-300">Tanggal Void</th>
                        <th class="py-1.5 px-2 border-r border-zinc-300">Kode</th>
                        <th class="py-1.5 px-2 border-r border-zinc-300">Uraian</th>
                        <th class="py-1.5 px-2 border-r border-zinc-300 text-right">Jumlah</th>
                        <th class="py-1.5 px-2">Alasan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200">
                    @foreach($voidPayments as $void)
                        <tr>
                            <td class="py-1.5 px-2 border-r border-zinc-200 whitespace-nowrap">{{ $void->void_at?->format('d/m/Y H:i') ?? '-' }}</td>
                            <td class="py-1.5 px-2 border-r border-zinc-200 font-mono">{{ $void->kode_transaksi }}</td>
                            <td class="py-1.5 px-2 border-r border-zinc-200">{{ $void->namaPos() }}</td>
                            <td class="py-1.5 px-2 border-r border-zinc-200 text-right">{{ 'Rp '.number_format($void->jumlah_bayar, 0, ',', '.') }}</td>
                            <td class="py-1.5 px-2">{{ $void->void_alasan ?: '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <p class="mt-2 text-[9px] text-zinc-500">
            Saldo positif berarti masih ada kewajiban; saldo negatif berarti kelebihan bayar. Tagihan SPP bulan mendatang tetap ditampilkan sebagai bagian dari kartu tahun ajaran.
        </p>

        <div class="mt-3 flex items-center justify-between border-t border-zinc-200 pt-1.5 text-[9px] text-zinc-500">
            <span>{{ $sekolah->nama_sekolah }} — Kartu Pembayaran Siswa</span>
            <span>Halaman 1 dari {{ $totalHalaman }}</span>
        </div>
    </section>

    <!-- Buku Pembayaran -->
    @foreach ($halamanData as $halamanIndex => $barisHalaman)
        <section class="report-page max-w-4xl mx-auto mb-6 bg-white p-6 sm:p-8 rounded-xl shadow-sm border border-zinc-200">
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

            <div class="mb-3 text-center">
                <h2 class="text-sm font-bold uppercase underline">KARTU PEMBAYARAN SISWA — BUKU PEMBAYARAN</h2>
                <p class="mt-0.5 text-[10px] text-zinc-700">
                    {{ $siswa->nama }} &bull; {{ $kelas?->nama_kelas ?? '-' }} &bull; Tahun Ajaran {{ $tahunAjaran->nama }}
                </p>
            </div>

            <table class="w-full text-left border-collapse text-[9px] border border-zinc-300">
                <thead>
                    <tr class="bg-zinc-100 border-b border-zinc-300 font-bold uppercase text-zinc-800">
                        <th class="py-1.5 px-2 border-r border-zinc-300">Tanggal</th>
                        <th class="py-1.5 px-2 border-r border-zinc-300">Uraian</th>
                        <th class="py-1.5 px-2 border-r border-zinc-300">Referensi</th>
                        <th class="py-1.5 px-2 border-r border-zinc-300 text-right">Tagihan</th>
                        <th class="py-1.5 px-2 border-r border-zinc-300 text-right">Pembayaran</th>
                        <th class="py-1.5 px-2 border-r border-zinc-300 text-right">Saldo</th>
                        <th class="py-1.5 px-2">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200">
                    @forelse($barisHalaman as $entry)
                        <tr>
                            <td class="py-1.5 px-2 border-r border-zinc-200 whitespace-nowrap">{{ $entry['tanggal']->format('d/m/Y') }}</td>
                            <td class="py-1.5 px-2 border-r border-zinc-200">
                                <div class="font-semibold">{{ $entry['uraian'] }}</div>
                                @if(($entry['meta']['metode'] ?? null) || ($entry['meta']['petugas'] ?? null))
                                    <div class="text-[8px] text-zinc-500">
                                        {{ $entry['meta']['metode'] ?? '-' }}
                                        @if($entry['meta']['petugas'] ?? null)
                                            &bull; {{ $entry['meta']['petugas'] }}
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td class="py-1.5 px-2 border-r border-zinc-200 font-mono">{{ $entry['referensi'] ?: '-' }}</td>
                            <td class="py-1.5 px-2 border-r border-zinc-200 text-right whitespace-nowrap">
                                {{ $entry['debit'] > 0 ? 'Rp '.number_format($entry['debit'], 0, ',', '.') : '-' }}
                            </td>
                            <td class="py-1.5 px-2 border-r border-zinc-200 text-right whitespace-nowrap">
                                {{ $entry['kredit'] > 0 ? 'Rp '.number_format($entry['kredit'], 0, ',', '.') : '-' }}
                            </td>
                            <td class="py-1.5 px-2 border-r border-zinc-200 text-right font-semibold whitespace-nowrap">
                                {{ $entry['saldo'] < 0 ? '- ' : '' }}Rp {{ number_format(abs($entry['saldo']), 0, ',', '.') }}
                            </td>
                            <td class="py-1.5 px-2 whitespace-nowrap">
                                @if($entry['tipe'] === 'pembayaran')
                                    Valid
                                @elseif($entry['status'] === 'jatuh_tempo')
                                    Jatuh Tempo
                                @else
                                    Belum Jatuh Tempo
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-6 text-center text-zinc-400">Belum ada tagihan atau pembayaran pada tahun ajaran ini.</td>
                        </tr>
                    @endforelse
                </tbody>
                @if ($halamanIndex === $halamanData->count() - 1)
                    <tfoot>
                        <tr class="border-t-2 border-black font-bold bg-zinc-50 text-[9px]">
                            <td colspan="3" class="py-1.5 px-3 border-r border-zinc-300 text-right uppercase">Total:</td>
                            <td class="py-1.5 px-3 border-r border-zinc-300 text-right whitespace-nowrap">{{ 'Rp '.number_format($ringkasan['total_tagihan'], 0, ',', '.') }}</td>
                            <td class="py-1.5 px-3 border-r border-zinc-300 text-right whitespace-nowrap">{{ 'Rp '.number_format($ringkasan['total_dibayar'], 0, ',', '.') }}</td>
                            <td class="py-1.5 px-3 border-r border-zinc-300 text-right whitespace-nowrap">
                                {{ $ringkasan['saldo'] < 0 ? '- ' : '' }}Rp {{ number_format(abs($ringkasan['saldo']), 0, ',', '.') }}
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                @endif
            </table>

            <div class="mt-3 flex items-center justify-between border-t border-zinc-200 pt-1.5 text-[9px] text-zinc-500">
                <span>{{ $sekolah->nama_sekolah }} — Kartu Pembayaran Siswa</span>
                <span>Halaman {{ $halamanIndex + 2 }} dari {{ $totalHalaman }}</span>
            </div>
        </section>
    @endforeach

    <!-- Lembar Pengesahan -->
    <section class="report-page max-w-4xl mx-auto mb-6 bg-white p-6 sm:p-8 rounded-xl shadow-sm border border-zinc-200">
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

        <div class="text-center">
            <h2 class="text-sm font-bold uppercase underline">LEMBAR PENGESAHAN</h2>
            <p class="mt-0.5 text-[10px] text-zinc-700">
                Kartu Pembayaran Siswa — {{ $siswa->nama }} — Tahun Ajaran {{ $tahunAjaran->nama }}
            </p>
        </div>

        <div class="mx-auto mt-20 grid max-w-3xl grid-cols-3 gap-8 text-center text-xs">
            <div>
                <p class="text-zinc-600 mb-20">
                    Mengetahui,<br>
                    <strong>Kepala Sekolah</strong>
                </p>
                <p class="font-bold underline text-sm">{{ $sekolah->nama_kepala_sekolah }}</p>
                <p class="text-[11px] text-zinc-500">NIP. {{ $sekolah->nip_kepala_sekolah ?? '-' }}</p>
            </div>
            <div>
                <p class="text-zinc-600 mb-20">
                    {{ $sekolah->kota ?? 'Bandung' }}, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}<br>
                    <strong>Bendahara / Petugas</strong>
                </p>
                <p class="font-bold underline text-sm">{{ auth()->user()->name }}</p>
                <p class="text-[11px] text-zinc-500">NIP / ID: {{ auth()->user()->username }}</p>
            </div>
            <div>
                <p class="text-zinc-600 mb-20">
                    Mengetahui,<br>
                    <strong>Orang Tua / Wali</strong>
                </p>
                <p class="font-bold underline text-sm">&nbsp;</p>
                <p class="text-[11px] text-zinc-500">&nbsp;</p>
            </div>
        </div>

        <div class="mt-16 flex items-center justify-between border-t border-zinc-200 pt-1.5 text-[9px] text-zinc-500">
            <span>{{ $sekolah->nama_sekolah }} — Kartu Pembayaran Siswa</span>
            <span>Halaman {{ $totalHalaman }} dari {{ $totalHalaman }}</span>
        </div>
    </section>
</body>
</html>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php $sekolah = \App\Models\ProfilSekolah::current(); @endphp
    <title>Laporan Rekapitulasi Pembayaran SPP - {{ $sekolah->nama_sekolah }}</title>
    @include('partials.app-icon')
    @vite(['resources/css/app.css'])
    <style>
        .terbilang-row td {
            height: 42px;
            vertical-align: middle;
        }

        @media print {
            .no-print { display: none !important; }
            body { font-size: 10px; background: #fff !important; padding: 0 !important; }
            @page { margin: 1cm; size: A4 landscape; }
            .report-page {
                display: flex;
                flex-direction: column;
                min-height: 18.98cm;
                max-width: none !important;
                margin: 0 !important;
                padding: 0 !important;
                border: 0 !important;
                border-radius: 0 !important;
                box-shadow: none !important;
                break-after: page;
                page-break-after: always;
            }
            .report-footer {
                margin-top: auto !important;
            }
            .report-page:last-child {
                break-after: auto;
                page-break-after: auto;
            }
            table { font-size: 9px; }
            th, td { padding: 3px 5px !important; }
            .terbilang-row td { height: 30px; }
            thead { display: table-header-group; }
            tr { break-inside: avoid; }
        }
    </style>
</head>
<body class="bg-zinc-100 p-4 sm:p-8 text-zinc-900 antialiased font-print">
    @php
        $perHalaman = 16;
        $halamanData = $pembayarans->isEmpty()
            ? collect([collect()])
            : $pembayarans->chunk($perHalaman);
        $totalHalaman = $halamanData->count();
    @endphp

    <div class="no-print max-w-6xl mx-auto mb-4 flex items-center justify-between">
        <a href="{{ route('laporan.index') }}" class="text-xs font-semibold text-zinc-600 hover:text-zinc-900">
            &larr; Kembali ke Laporan
        </a>
        <x-print-button onclick="window.print()">
            Cetak Dokumen Sekarang
        </x-print-button>
    </div>

    @foreach ($halamanData as $halamanIndex => $barisHalaman)
        <section class="report-page max-w-6xl mx-auto mb-6 bg-white p-6 sm:p-8 rounded-xl shadow-sm border border-zinc-200">
            <!-- Kop Surat Ringkas -->
            <div class="mb-3 flex items-center gap-3 border-b border-zinc-400 pb-2">
                @if ($sekolah->logoUrl())
                    <img src="{{ $sekolah->logoUrl() }}" alt="Logo {{ $sekolah->nama_sekolah }}"
                        class="h-9 w-9 shrink-0 object-contain">
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

            <!-- Judul Laporan -->
            <div class="mb-3 text-center">
                <h2 class="text-sm font-bold uppercase underline">LAPORAN REKAPITULASI PENERIMAAN PEMBAYARAN</h2>
                <div class="mt-0.5 text-[10px] text-zinc-700">
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

            <!-- Tabel Transaksi -->
            <table class="w-full text-left border-collapse text-[10px] border border-zinc-300">
                <thead>
                    <tr class="bg-zinc-100 border-b border-zinc-300 font-bold uppercase text-zinc-800">
                        <th class="py-1.5 px-2 border-r border-zinc-300 w-10 text-center">No</th>
                        <th class="py-1.5 px-2 border-r border-zinc-300">No. Transaksi</th>
                        <th class="py-1.5 px-2 border-r border-zinc-300">Tgl. Bayar</th>
                        <th class="py-1.5 px-2 border-r border-zinc-300">Nama Siswa</th>
                        <th class="py-1.5 px-2 border-r border-zinc-300">NISN</th>
                        <th class="py-1.5 px-2 border-r border-zinc-300">Kelas</th>
                        <th class="py-1.5 px-2 border-r border-zinc-300">Pos Pembayaran</th>
                        <th class="py-1.5 px-2 border-r border-zinc-300">Sumber</th>
                        <th class="py-1.5 px-2 border-r border-zinc-300 text-right">Nominal</th>
                        <th class="py-1.5 px-2 border-r border-zinc-300">Metode</th>
                        <th class="py-1.5 px-2">Petugas Kasir</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200">
                    @forelse($barisHalaman as $item)
                        <tr>
                            <td class="py-1.5 px-2 border-r border-zinc-200 text-center">{{ $halamanIndex * $perHalaman + $loop->iteration }}</td>
                            <td class="py-1.5 px-2 border-r border-zinc-200 font-mono whitespace-nowrap">{{ $item->kode_transaksi }}</td>
                            <td class="py-1.5 px-2 border-r border-zinc-200 whitespace-nowrap">{{ $item->tgl_bayar->format('d/m/Y') }}</td>
                            <td class="py-1.5 px-2 border-r border-zinc-200 font-semibold">{{ $item->siswa?->nama ?? '-' }}</td>
                            <td class="py-1.5 px-2 border-r border-zinc-200 font-mono whitespace-nowrap">{{ $item->siswa?->nisn ?? '-' }}</td>
                            <td class="py-1.5 px-2 border-r border-zinc-200 whitespace-nowrap">{{ $item->siswa?->kelas?->nama_kelas ?? '-' }}</td>
                            <td class="py-1.5 px-2 border-r border-zinc-200">
                                <div>{{ $item->namaPos() }}</div>
                                @if($sub = $item->subsidi())
                                    <div class="text-[9px] text-zinc-500 italic font-normal">* Diskon {{ $sub->persentase }}%</div>
                                @endif
                            </td>
                            <td class="py-1.5 px-2 border-r border-zinc-200 whitespace-nowrap">
                                @if($item->gabungan_id)
                                    Gabungan {{ $item->gabungan?->kode }}
                                @else
                                    Loket
                                @endif
                            </td>
                            <td class="py-1.5 px-2 border-r border-zinc-200 text-right font-semibold whitespace-nowrap">{{ $item->formattedJumlahBayar() }}</td>
                            <td class="py-1.5 px-2 border-r border-zinc-200 whitespace-nowrap">{{ $item->metode_pembayaran }}</td>
                            <td class="py-1.5 px-2">{{ $item->user?->name ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="py-6 text-center text-zinc-400">
                                Tidak ada transaksi pembayaran pada filter periode ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if ($halamanIndex === $halamanData->count() - 1)
                    <tfoot>
                        <tr class="border-t-2 border-black font-bold bg-zinc-50 text-[10px]">
                            <td colspan="8" class="py-1.5 px-3 border-r border-zinc-300 text-right uppercase">
                                Total Penerimaan:
                            </td>
                            <td class="py-1.5 px-3 border-r border-zinc-300 text-right text-xs whitespace-nowrap">
                                Rp {{ number_format($totalPenerimaan, 0, ',', '.') }}
                            </td>
                            <td colspan="2"></td>
                        </tr>
                        <tr class="terbilang-row border-t border-zinc-300 text-[10px]">
                            <td colspan="11" class="px-3 text-left">
                                <span class="font-bold uppercase tracking-wide">Terbilang:</span>
                                <em class="ml-1 font-semibold">{{ \App\Support\Terbilang::rupiah((int) $totalPenerimaan) }}</em>
                            </td>
                        </tr>
                    </tfoot>
                @endif
            </table>

            @if ($halamanIndex === $halamanData->count() - 1)
                <!-- Tanda Tangan (di atas footer halaman terakhir) -->
                <div class="mx-auto mt-5 grid max-w-2xl grid-cols-2 gap-16 text-center text-[10px]">
                    <div>
                        <p class="text-zinc-600 leading-snug">
                            Mengetahui,<br>
                            <strong>Kepala Sekolah {{ $sekolah->nama_sekolah }}</strong>
                        </p>
                        <div class="h-12"></div>
                        <p class="font-bold underline text-xs">{{ $sekolah->nama_kepala_sekolah }}</p>
                        <p class="text-[9px] text-zinc-500">NIP. {{ $sekolah->nip_kepala_sekolah ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-zinc-600 leading-snug">
                            {{ $sekolah->kota ?? 'Bandung' }}, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}<br>
                            <strong>Bendahara / Petugas Keuangan</strong>
                        </p>
                        <div class="h-12"></div>
                        <p class="font-bold underline text-xs">{{ auth()->user()->name }}</p>
                        <p class="text-[9px] text-zinc-500">NIP / ID: {{ auth()->user()->username }}</p>
                    </div>
                </div>
            @endif

            <!-- Footer Halaman -->
            <div class="report-footer mt-5 flex items-center justify-between border-t border-zinc-200 pt-1.5 text-[9px] text-zinc-500">
                <span>{{ $sekolah->nama_sekolah }} — Laporan Rekapitulasi Pembayaran</span>
                <span>Halaman {{ $halamanIndex + 1 }} dari {{ $totalHalaman }}</span>
            </div>
        </section>
    @endforeach
</body>
</html>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php $sekolah = \App\Models\ProfilSekolah::current(); @endphp
    <title>Laporan Tunggakan SPP & Non-SPP - {{ $sekolah->nama_sekolah }}</title>
    @include('partials.app-icon')
    @vite(['resources/css/app.css'])
    <style>
        @media print {
            .no-print { display: none !important; }
            body { font-size: 10px; background: #fff !important; padding: 0 !important; }
            @page { margin: 1cm; size: landscape; }
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
<body class="bg-zinc-100 p-4 sm:p-8 text-zinc-900 antialiased font-sans">
    @php
        $ringkasan = $laporan['ringkasan'];
        $perKelas = $laporan['per_kelas'];
        $perKategoriNonSpp = $laporan['per_kategori_non_spp'];
        $siswa = $laporan['siswa'];
        $tahunAjaran = $laporan['tahun_ajaran'];
        $bulan = collect($laporan['bulan']);
        $perHalaman = 18;
        $halamanData = $siswa->isEmpty() ? collect([collect()]) : $siswa->chunk($perHalaman);
        $totalHalaman = 1 + $halamanData->count() + 1;
        $statusLabel = [
            'menunggak' => 'Hanya Menunggak',
            'lunas' => 'Sudah Lunas',
            'tanpa_tarif' => 'Tanpa Tarif',
            'semua' => 'Semua Siswa',
        ][$filters['status'] ?? 'semua'] ?? 'Semua Siswa';
    @endphp

    <div class="no-print max-w-6xl mx-auto mb-4 flex items-center justify-between">
        <a href="{{ route('filament.admin.pages.laporan-tunggakan') }}" class="text-xs font-semibold text-zinc-600 hover:text-zinc-900">
            &larr; Kembali ke Laporan Tunggakan
        </a>
        <button onclick="window.print()" class="px-4 py-2 bg-zinc-900 hover:bg-zinc-800 text-white rounded-xl text-xs font-bold transition shadow-sm inline-flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Cetak Dokumen Sekarang
        </button>
    </div>

    <!-- Ringkasan -->
    <section class="report-page max-w-6xl mx-auto mb-6 bg-white p-6 sm:p-8 rounded-xl shadow-sm border border-zinc-200">
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
            <h2 class="text-sm font-bold uppercase underline">LAPORAN TUNGGAKAN SPP & NON-SPP</h2>
            <div class="mt-0.5 text-[10px] text-zinc-700">
                <span>Tahun Ajaran: <strong>{{ $tahunAjaran?->nama ?? '-' }}</strong></span>
                @if($bulan->isNotEmpty())
                    <span class="ml-3">&bull; Periode SPP: <strong>{{ $bulan->first()['bulan'] }} {{ $bulan->first()['tahun'] }} s/d {{ $bulan->last()['bulan'] }} {{ $bulan->last()['tahun'] }}</strong></span>
                @endif
                @if($selectedKelas)
                    <span class="ml-3">&bull; Kelas: <strong>{{ $selectedKelas->nama_kelas }}</strong></span>
                @endif
                <span class="ml-3">&bull; Status: <strong>{{ $statusLabel }}</strong></span>
            </div>
        </div>

        <table class="mb-4 w-full border-collapse text-[10px] border border-zinc-300">
            <tbody>
                <tr>
                    <td class="border border-zinc-300 bg-zinc-50 px-2 py-1.5 font-semibold">Siswa Ditampilkan</td>
                    <td class="border border-zinc-300 px-2 py-1.5 text-right">{{ number_format($ringkasan['jumlah_siswa'], 0, ',', '.') }} siswa</td>
                    <td class="border border-zinc-300 bg-zinc-50 px-2 py-1.5 font-semibold">Siswa Menunggak</td>
                    <td class="border border-zinc-300 px-2 py-1.5 text-right">{{ number_format($ringkasan['jumlah_menunggak'], 0, ',', '.') }} siswa</td>
                </tr>
                <tr>
                    <td class="border border-zinc-300 bg-zinc-50 px-2 py-1.5 font-semibold">Tagihan SPP</td>
                    <td class="border border-zinc-300 px-2 py-1.5 text-right">{{ 'Rp '.number_format($ringkasan['tagihan_spp'], 0, ',', '.') }}</td>
                    <td class="border border-zinc-300 bg-zinc-50 px-2 py-1.5 font-semibold">Tunggakan SPP</td>
                    <td class="border border-zinc-300 px-2 py-1.5 text-right">{{ 'Rp '.number_format($ringkasan['tunggakan_spp'], 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td class="border border-zinc-300 bg-zinc-50 px-2 py-1.5 font-semibold">Tagihan Non-SPP</td>
                    <td class="border border-zinc-300 px-2 py-1.5 text-right">{{ 'Rp '.number_format($ringkasan['tagihan_non_spp'], 0, ',', '.') }}</td>
                    <td class="border border-zinc-300 bg-zinc-50 px-2 py-1.5 font-semibold">Tunggakan Non-SPP</td>
                    <td class="border border-zinc-300 px-2 py-1.5 text-right">{{ 'Rp '.number_format($ringkasan['tunggakan_non_spp'], 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td class="border border-zinc-300 bg-zinc-50 px-2 py-1.5 font-semibold">Total Tagihan</td>
                    <td class="border border-zinc-300 px-2 py-1.5 text-right">{{ 'Rp '.number_format($ringkasan['tagihan'], 0, ',', '.') }}</td>
                    <td class="border border-zinc-300 bg-zinc-50 px-2 py-1.5 font-semibold">Total Tunggakan</td>
                    <td class="border border-zinc-300 px-2 py-1.5 text-right font-bold">{{ 'Rp '.number_format($ringkasan['tunggakan'], 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td class="border border-zinc-300 bg-zinc-50 px-2 py-1.5 font-semibold">Sudah Dibayar</td>
                    <td class="border border-zinc-300 px-2 py-1.5 text-right">{{ 'Rp '.number_format($ringkasan['dibayar'], 0, ',', '.') }}</td>
                    <td class="border border-zinc-300 bg-zinc-50 px-2 py-1.5 font-semibold">Capaian</td>
                    <td class="border border-zinc-300 px-2 py-1.5 text-right">{{ number_format($ringkasan['persentase'], 1, ',', '.') }}%</td>
                </tr>
            </tbody>
        </table>

        <h3 class="mb-1.5 text-[11px] font-bold uppercase">Ringkasan per Kelas</h3>
        <table class="mb-4 w-full text-left border-collapse text-[10px] border border-zinc-300">
            <thead>
                <tr class="bg-zinc-100 border-b border-zinc-300 font-bold uppercase text-zinc-800">
                    <th class="py-1.5 px-2 border-r border-zinc-300">Kelas</th>
                    <th class="py-1.5 px-2 border-r border-zinc-300 text-right">Siswa</th>
                    <th class="py-1.5 px-2 border-r border-zinc-300 text-right">Menunggak</th>
                    <th class="py-1.5 px-2 border-r border-zinc-300 text-right">Tagihan SPP</th>
                    <th class="py-1.5 px-2 border-r border-zinc-300 text-right">Tunggakan SPP</th>
                    <th class="py-1.5 px-2 border-r border-zinc-300 text-right">Tagihan Non-SPP</th>
                    <th class="py-1.5 px-2 border-r border-zinc-300 text-right">Tunggakan Non-SPP</th>
                    <th class="py-1.5 px-2 border-r border-zinc-300 text-right">Total Tunggakan</th>
                    <th class="py-1.5 px-2 text-right">Capaian</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200">
                @forelse($perKelas as $kelas)
                    <tr>
                        <td class="py-1.5 px-2 border-r border-zinc-200 font-semibold">
                            {{ $kelas['kelas']->nama_kelas }}@if($kelas['kelas']->jurusan?->kode) ({{ $kelas['kelas']->jurusan->kode }})@endif
                        </td>
                        <td class="py-1.5 px-2 border-r border-zinc-200 text-right">{{ $kelas['jumlah_siswa'] }}</td>
                        <td class="py-1.5 px-2 border-r border-zinc-200 text-right">{{ $kelas['jumlah_menunggak'] }}</td>
                        <td class="py-1.5 px-2 border-r border-zinc-200 text-right">{{ 'Rp '.number_format($kelas['tagihan_spp'], 0, ',', '.') }}</td>
                        <td class="py-1.5 px-2 border-r border-zinc-200 text-right">{{ 'Rp '.number_format($kelas['tunggakan_spp'], 0, ',', '.') }}</td>
                        <td class="py-1.5 px-2 border-r border-zinc-200 text-right">{{ 'Rp '.number_format($kelas['tagihan_non_spp'], 0, ',', '.') }}</td>
                        <td class="py-1.5 px-2 border-r border-zinc-200 text-right">{{ 'Rp '.number_format($kelas['tunggakan_non_spp'], 0, ',', '.') }}</td>
                        <td class="py-1.5 px-2 border-r border-zinc-200 text-right font-semibold">{{ 'Rp '.number_format($kelas['tunggakan'], 0, ',', '.') }}</td>
                        <td class="py-1.5 px-2 text-right">{{ number_format($kelas['persentase'], 1, ',', '.') }}%</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="py-6 text-center text-zinc-400">Tidak ada data kelas pada filter ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($perKategoriNonSpp->isNotEmpty())
            <h3 class="mb-1.5 text-[11px] font-bold uppercase">Rincian Non-SPP per Pos</h3>
            <table class="w-full text-left border-collapse text-[10px] border border-zinc-300">
                <thead>
                    <tr class="bg-zinc-100 border-b border-zinc-300 font-bold uppercase text-zinc-800">
                        <th class="py-1.5 px-2 border-r border-zinc-300">Pos Biaya</th>
                        <th class="py-1.5 px-2 border-r border-zinc-300 text-right">Siswa</th>
                        <th class="py-1.5 px-2 border-r border-zinc-300 text-right">Jumlah Tagihan</th>
                        <th class="py-1.5 px-2 border-r border-zinc-300 text-right">Potensi</th>
                        <th class="py-1.5 px-2 border-r border-zinc-300 text-right">Realisasi</th>
                        <th class="py-1.5 px-2 border-r border-zinc-300 text-right">Tunggakan</th>
                        <th class="py-1.5 px-2 text-right">Capaian</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200">
                    @foreach($perKategoriNonSpp as $pos)
                        <tr>
                            <td class="py-1.5 px-2 border-r border-zinc-200 font-semibold">{{ $pos['kategori'] }}</td>
                            <td class="py-1.5 px-2 border-r border-zinc-200 text-right">{{ $pos['jumlah_siswa'] }}</td>
                            <td class="py-1.5 px-2 border-r border-zinc-200 text-right">{{ $pos['jumlah_pos'] }}</td>
                            <td class="py-1.5 px-2 border-r border-zinc-200 text-right">{{ 'Rp '.number_format($pos['potensi'], 0, ',', '.') }}</td>
                            <td class="py-1.5 px-2 border-r border-zinc-200 text-right">{{ 'Rp '.number_format($pos['realisasi'], 0, ',', '.') }}</td>
                            <td class="py-1.5 px-2 border-r border-zinc-200 text-right font-semibold">{{ 'Rp '.number_format($pos['tunggakan'], 0, ',', '.') }}</td>
                            <td class="py-1.5 px-2 text-right">{{ number_format($pos['persentase'], 1, ',', '.') }}%</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <p class="mt-2 text-[9px] text-zinc-500">
            Tunggakan SPP dihitung dari bulan yang sudah jatuh tempo. Non-SPP dihitung penuh untuk tahun ajaran yang sudah dimulai.
        </p>

        <div class="mt-3 flex items-center justify-between border-t border-zinc-200 pt-1.5 text-[9px] text-zinc-500">
            <span>{{ $sekolah->nama_sekolah }} — Laporan Tunggakan SPP & Non-SPP</span>
            <span>Halaman 1 dari {{ $totalHalaman }}</span>
        </div>
    </section>

    <!-- Detail per Siswa -->
    @foreach ($halamanData as $halamanIndex => $barisHalaman)
        <section class="report-page max-w-6xl mx-auto mb-6 bg-white p-6 sm:p-8 rounded-xl shadow-sm border border-zinc-200">
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
                <h2 class="text-sm font-bold uppercase underline">LAPORAN TUNGGAKAN SPP & NON-SPP — DETAIL SISWA</h2>
                <div class="mt-0.5 text-[10px] text-zinc-700">
                    <span>Tahun Ajaran: <strong>{{ $tahunAjaran?->nama ?? '-' }}</strong></span>
                    @if($bulan->isNotEmpty())
                        <span class="ml-3">&bull; Periode SPP: <strong>{{ $bulan->first()['bulan'] }} {{ $bulan->first()['tahun'] }} s/d {{ $bulan->last()['bulan'] }} {{ $bulan->last()['tahun'] }}</strong></span>
                    @endif
                </div>
            </div>

            <table class="w-full text-left border-collapse text-[10px] border border-zinc-300">
                <thead>
                    <tr class="bg-zinc-100 border-b border-zinc-300 font-bold uppercase text-zinc-800">
                        <th class="py-1.5 px-2 border-r border-zinc-300 w-8 text-center">No</th>
                        <th class="py-1.5 px-2 border-r border-zinc-300">NISN</th>
                        <th class="py-1.5 px-2 border-r border-zinc-300">Nama Siswa</th>
                        <th class="py-1.5 px-2 border-r border-zinc-300">Kelas</th>
                        <th class="py-1.5 px-2 border-r border-zinc-300 text-right">Tarif SPP</th>
                        <th class="py-1.5 px-2 border-r border-zinc-300 text-right">Tunggakan SPP</th>
                        <th class="py-1.5 px-2 border-r border-zinc-300 text-right">Tunggakan Non-SPP</th>
                        <th class="py-1.5 px-2 border-r border-zinc-300 text-right">Total Tunggakan</th>
                        <th class="py-1.5 px-2 border-r border-zinc-300">Bulan SPP</th>
                        <th class="py-1.5 px-2">Pos Non-SPP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200">
                    @forelse($barisHalaman as $item)
                        <tr>
                            <td class="py-1.5 px-2 border-r border-zinc-200 text-center">{{ $halamanIndex * $perHalaman + $loop->iteration }}</td>
                            <td class="py-1.5 px-2 border-r border-zinc-200 font-mono whitespace-nowrap">{{ $item['siswa']->nisn ?: '-' }}</td>
                            <td class="py-1.5 px-2 border-r border-zinc-200 font-semibold">{{ $item['siswa']->nama }}</td>
                            <td class="py-1.5 px-2 border-r border-zinc-200 whitespace-nowrap">{{ $item['kelas']->nama_kelas }}</td>
                            <td class="py-1.5 px-2 border-r border-zinc-200 text-right whitespace-nowrap">
                                {{ $item['nominal'] > 0 ? 'Rp '.number_format($item['nominal'], 0, ',', '.') : '-' }}
                            </td>
                            <td class="py-1.5 px-2 border-r border-zinc-200 text-right whitespace-nowrap">{{ 'Rp '.number_format($item['tunggakan_spp'], 0, ',', '.') }}</td>
                            <td class="py-1.5 px-2 border-r border-zinc-200 text-right whitespace-nowrap">{{ 'Rp '.number_format($item['tunggakan_non_spp'], 0, ',', '.') }}</td>
                            <td class="py-1.5 px-2 border-r border-zinc-200 text-right font-semibold whitespace-nowrap">{{ 'Rp '.number_format($item['tunggakan'], 0, ',', '.') }}</td>
                            <td class="py-1.5 px-2 border-r border-zinc-200">
                                {{ $item['bulan_tertunggak'] === [] ? '-' : implode(', ', $item['bulan_tertunggak']) }}
                            </td>
                            <td class="py-1.5 px-2">
                                {{ $item['pos_non_spp_tertunggak'] === [] ? '-' : implode(', ', $item['pos_non_spp_tertunggak']) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="py-6 text-center text-zinc-400">Tidak ada data siswa pada filter ini.</td>
                        </tr>
                    @endforelse
                </tbody>
                @if ($halamanIndex === $halamanData->count() - 1)
                    <tfoot>
                        <tr class="border-t-2 border-black font-bold bg-zinc-50 text-[10px]">
                            <td colspan="5" class="py-1.5 px-3 border-r border-zinc-300 text-right uppercase">Total:</td>
                            <td class="py-1.5 px-3 border-r border-zinc-300 text-right whitespace-nowrap">{{ 'Rp '.number_format($ringkasan['tunggakan_spp'], 0, ',', '.') }}</td>
                            <td class="py-1.5 px-3 border-r border-zinc-300 text-right whitespace-nowrap">{{ 'Rp '.number_format($ringkasan['tunggakan_non_spp'], 0, ',', '.') }}</td>
                            <td class="py-1.5 px-3 border-r border-zinc-300 text-right whitespace-nowrap">{{ 'Rp '.number_format($ringkasan['tunggakan'], 0, ',', '.') }}</td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                @endif
            </table>

            <div class="mt-3 flex items-center justify-between border-t border-zinc-200 pt-1.5 text-[9px] text-zinc-500">
                <span>{{ $sekolah->nama_sekolah }} — Laporan Tunggakan SPP & Non-SPP</span>
                <span>Halaman {{ $halamanIndex + 2 }} dari {{ $totalHalaman }}</span>
            </div>
        </section>
    @endforeach

    <!-- Lembar Pengesahan -->
    <section class="report-page max-w-6xl mx-auto mb-6 bg-white p-6 sm:p-8 rounded-xl shadow-sm border border-zinc-200">
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
                Laporan Tunggakan SPP & Non-SPP
                Tahun Ajaran {{ $tahunAjaran?->nama ?? '-' }}
                @if($bulan->isNotEmpty())
                    Periode SPP {{ $bulan->first()['bulan'] }} {{ $bulan->first()['tahun'] }} s/d {{ $bulan->last()['bulan'] }} {{ $bulan->last()['tahun'] }}
                @endif
            </p>
        </div>

        <div class="mx-auto mt-20 grid max-w-3xl grid-cols-2 gap-16 text-center text-xs">
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

        <div class="mt-16 flex items-center justify-between border-t border-zinc-200 pt-1.5 text-[9px] text-zinc-500">
            <span>{{ $sekolah->nama_sekolah }} — Laporan Tunggakan SPP & Non-SPP</span>
            <span>Halaman {{ $totalHalaman }} dari {{ $totalHalaman }}</span>
        </div>
    </section>
</body>
</html>

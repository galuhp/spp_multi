<x-filament-panels::page>
    @push('styles')
        @vite('resources/css/laporan-pembayaran.css')
    @endpush

    @php
        $kartu = $this->kartu;
        $ringkasan = $kartu['ringkasan'] ?? null;
        $entries = $kartu['entries'] ?? collect();
        $voidPayments = $kartu['void_payments'] ?? collect();
    @endphp

    <!-- Filter Card -->
    <div class="fi-section rounded-xl bg-white p-6 shadow-xs ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h3 class="text-base font-semibold leading-6 text-gray-950 dark:text-white">
                    Pilih Kartu Pembayaran
                </h3>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Menampilkan tagihan SPP, tagihan non-SPP, pembayaran, dan saldo berjalan per siswa pada tahun ajaran terpilih.
                </p>
            </div>
            <x-filament::button color="gray" size="sm" wire:click="resetFilter">
                Reset Filter
            </x-filament::button>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Tahun Ajaran</label>
                <select wire:model.live="tahun_ajaran_id" class="laporan-field">
                    @foreach($this->tahunAjaranList as $tahun)
                        <option value="{{ $tahun->id }}">
                            {{ $tahun->nama }}{{ $tahun->aktif ? ' (Aktif)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Kelas</label>
                <select wire:model.live="kelas_id" class="laporan-field">
                    <option value="">Semua Kelas</option>
                    @foreach($this->kelasList as $kelas)
                        <option value="{{ $kelas->id }}">
                            {{ $kelas->nama_kelas }}{{ $kelas->jurusan?->kode ? ' ('.$kelas->jurusan->kode.')' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Siswa</label>
                <select wire:model.live="siswa_id" class="laporan-field">
                    <option value="">Pilih siswa...</option>
                    @foreach($this->siswaList as $item)
                        <option value="{{ $item['siswa']->id }}">
                            {{ $item['siswa']->nama }} — {{ $item['kelas']->nama_kelas }}
                            @if($item['siswa']->nisn) ({{ $item['siswa']->nisn }}) @endif
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    @if(! $kartu)
        <div class="fi-section rounded-xl bg-white px-6 py-12 text-center shadow-xs ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-primary-50 text-primary-600 dark:bg-primary-950/50 dark:text-primary-300">
                <x-filament::icon icon="heroicon-o-identification" class="h-6 w-6" />
            </div>
            <h3 class="mt-4 text-base font-semibold text-gray-950 dark:text-white">Belum ada siswa dipilih</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Pilih tahun ajaran, kelas, lalu siswa untuk menampilkan kartu pembayaran.
            </p>
        </div>
    @else
        @php
            $siswa = $kartu['siswa'];
            $kelas = $kartu['kelas'];
            $tarif = $kartu['tarif'];
            $tahunAjaran = $kartu['tahun_ajaran'];
        @endphp

        <!-- Identitas Siswa -->
        <div class="fi-section rounded-xl bg-white p-6 shadow-xs ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h3 class="text-lg font-bold text-gray-950 dark:text-white">{{ $siswa->nama }}</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        NISN: <span class="font-mono">{{ $siswa->nisn ?: '-' }}</span>
                        &bull; NIS: <span class="font-mono">{{ $siswa->nis ?: '-' }}</span>
                        &bull; Masuk: <span class="font-medium text-gray-700 dark:text-gray-300">{{ $siswa->tanggal_masuk ? $siswa->tanggal_masuk->translatedFormat('d M Y') : ($siswa->tahun_masuk ? 'Tahun ' . $siswa->tahun_masuk : '-') }}</span>
                    </p>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                        {{ $kelas?->nama_kelas ?? 'Kelas tidak ditemukan' }}
                        @if($kelas?->jurusan?->nama)
                            &bull; {{ $kelas->jurusan->nama }}
                        @endif
                    </p>
                </div>
                <div class="rounded-lg bg-gray-50 px-4 py-3 text-sm ring-1 ring-gray-950/5 dark:bg-gray-800 dark:ring-white/10">
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Tahun Ajaran</p>
                    <p class="mt-1 font-semibold text-gray-950 dark:text-white">{{ $tahunAjaran->nama }}</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Tarif SPP:
                        {{ $tarif ? 'Rp '.number_format($tarif->nominal, 0, ',', '.').' / bulan' : 'Belum ada tarif' }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Summary -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="fi-section rounded-xl bg-white p-5 shadow-xs ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <span class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Tagihan Jatuh Tempo</span>
                <div class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">
                    Rp {{ number_format($ringkasan['tagihan_jatuh_tempo'], 0, ',', '.') }}
                </div>
                <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                    SPP Rp {{ number_format($ringkasan['tagihan_jatuh_tempo_spp'], 0, ',', '.') }}
                    &bull; Non-SPP Rp {{ number_format($ringkasan['tagihan_jatuh_tempo_non_spp'], 0, ',', '.') }}
                </p>
            </div>

            <div class="fi-section rounded-xl bg-white p-5 shadow-xs ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <span class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Total Dibayar</span>
                <div class="mt-2 text-2xl font-bold text-emerald-600 dark:text-emerald-400">
                    Rp {{ number_format($ringkasan['total_dibayar'], 0, ',', '.') }}
                </div>
                <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">Pembayaran valid pada tahun ajaran ini</p>
            </div>

            <div class="fi-section rounded-xl bg-white p-5 shadow-xs ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <span class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Tunggakan Jatuh Tempo</span>
                <div class="mt-2 text-2xl font-bold text-rose-600 dark:text-rose-400">
                    Rp {{ number_format($ringkasan['tunggakan_jatuh_tempo'], 0, ',', '.') }}
                </div>
                <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">Setelah memperhitungkan pembayaran</p>
            </div>

            <div class="fi-section rounded-xl bg-white p-5 shadow-xs ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <span class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Saldo Kartu</span>
                <div class="mt-2 text-2xl font-bold {{ $ringkasan['saldo'] > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                    {{ $ringkasan['saldo'] < 0 ? '- ' : '' }}Rp {{ number_format(abs($ringkasan['saldo']), 0, ',', '.') }}
                </div>
                <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                    Total tagihan setahun Rp {{ number_format($ringkasan['total_tagihan'], 0, ',', '.') }}
                </p>
            </div>
        </div>

        <!-- Ledger -->
        <div class="fi-section overflow-hidden rounded-xl bg-white shadow-xs ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                <h3 class="text-sm font-semibold text-gray-950 dark:text-white">Buku Pembayaran Siswa</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
                    <thead class="border-b border-gray-200 bg-gray-50 text-xs font-semibold uppercase tracking-wider text-gray-700 dark:border-gray-800 dark:bg-gray-800/50 dark:text-gray-300">
                        <tr>
                            <th class="px-4 py-3.5">Tanggal</th>
                            <th class="px-4 py-3.5">Uraian</th>
                            <th class="px-4 py-3.5">Referensi</th>
                            <th class="px-4 py-3.5 text-right">Tagihan</th>
                            <th class="px-4 py-3.5 text-right">Pembayaran</th>
                            <th class="px-4 py-3.5 text-right">Saldo</th>
                            <th class="px-4 py-3.5 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse($entries as $entry)
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/40">
                                <td class="px-4 py-3 whitespace-nowrap text-xs">
                                    {{ $entry['tanggal']->translatedFormat('d M Y') }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-900 dark:text-white">{{ $entry['uraian'] }}</div>
                                    @if(($entry['meta']['metode'] ?? null) || ($entry['meta']['petugas'] ?? null))
                                        <div class="mt-0.5 text-xs text-gray-500">
                                            {{ $entry['meta']['metode'] ?? '-' }}
                                            @if($entry['meta']['petugas'] ?? null)
                                                &bull; {{ $entry['meta']['petugas'] }}
                                            @endif
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 font-mono text-xs">
                                    {{ $entry['referensi'] ?: '-' }}
                                </td>
                                <td class="px-4 py-3 text-right text-xs">
                                    {{ $entry['debit'] > 0 ? 'Rp '.number_format($entry['debit'], 0, ',', '.') : '-' }}
                                </td>
                                <td class="px-4 py-3 text-right text-xs text-emerald-600 dark:text-emerald-400">
                                    {{ $entry['kredit'] > 0 ? 'Rp '.number_format($entry['kredit'], 0, ',', '.') : '-' }}
                                </td>
                                <td class="px-4 py-3 text-right font-semibold whitespace-nowrap {{ $entry['saldo'] < 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-900 dark:text-white' }}">
                                    {{ $entry['saldo'] < 0 ? '- ' : '' }}Rp {{ number_format(abs($entry['saldo']), 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if($entry['tipe'] === 'pembayaran')
                                        <span class="inline-flex items-center rounded-md border border-emerald-200/80 bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-950/50 dark:text-emerald-300">
                                            Valid
                                        </span>
                                    @elseif($entry['status'] === 'jatuh_tempo')
                                        <span class="inline-flex items-center rounded-md border border-rose-200/80 bg-rose-50 px-2.5 py-0.5 text-xs font-semibold text-rose-700 dark:border-rose-500/30 dark:bg-rose-950/50 dark:text-rose-300">
                                            Jatuh Tempo
                                        </span>
                                    @else
                                        <span class="inline-flex items-center rounded-md border border-slate-200 bg-slate-50 px-2.5 py-0.5 text-xs font-semibold text-slate-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                            Belum Jatuh Tempo
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500">
                                    Belum ada tagihan atau pembayaran untuk siswa ini pada tahun ajaran yang dipilih.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($voidPayments->isNotEmpty())
            <!-- Transaksi Void -->
            <div class="fi-section overflow-hidden rounded-xl bg-white shadow-xs ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                    <h3 class="text-sm font-semibold text-gray-950 dark:text-white">Transaksi Dibatalkan (Void)</h3>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Tidak dihitung dalam saldo kartu. Ditampilkan untuk keperluan audit.
                    </p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
                        <thead class="border-b border-gray-200 bg-gray-50 text-xs font-semibold uppercase tracking-wider text-gray-700 dark:border-gray-800 dark:bg-gray-800/50 dark:text-gray-300">
                            <tr>
                                <th class="px-4 py-3.5">Tanggal Void</th>
                                <th class="px-4 py-3.5">Kode</th>
                                <th class="px-4 py-3.5">Uraian</th>
                                <th class="px-4 py-3.5 text-right">Jumlah</th>
                                <th class="px-4 py-3.5">Alasan</th>
                                <th class="px-4 py-3.5">Oleh</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach($voidPayments as $void)
                                <tr>
                                    <td class="px-4 py-3 whitespace-nowrap text-xs">
                                        {{ $void->void_at?->translatedFormat('d M Y H:i') ?? '-' }}
                                    </td>
                                    <td class="px-4 py-3 font-mono text-xs">{{ $void->kode_transaksi }}</td>
                                    <td class="px-4 py-3">{{ $void->namaPos() }}</td>
                                    <td class="px-4 py-3 text-right">Rp {{ number_format($void->jumlah_bayar, 0, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-xs">{{ $void->void_alasan ?: '-' }}</td>
                                    <td class="px-4 py-3 text-xs">{{ $void->pemberiVoid?->name ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <div class="rounded-xl border border-primary-200 bg-primary-50 px-4 py-3 text-xs text-primary-800 dark:border-primary-500/20 dark:bg-primary-500/10 dark:text-primary-200">
            Saldo positif berarti masih ada kewajiban; saldo negatif berarti kelebihan bayar. Tagihan SPP bulan mendatang tetap ditampilkan sebagai bagian dari kartu tahun ajaran.
        </div>
    @endif
</x-filament-panels::page>

<x-filament-panels::page>
    @push('styles')
        @vite('resources/css/laporan-pembayaran.css')
    @endpush

    @php
        $laporan = $this->laporan;
        $ringkasan = $laporan['ringkasan'];
        $perKelas = $laporan['per_kelas'];
        $perKategoriNonSpp = $laporan['per_kategori_non_spp'];
        $tahunAjaran = $laporan['tahun_ajaran'];
        $bulan = collect($laporan['bulan']);
    @endphp

    @php
        $isWaliKelas = auth()->user()?->isWaliKelas();
    @endphp

    @if($isWaliKelas)
        <div class="rounded-xl border border-purple-200 bg-purple-50/80 px-4 py-3 text-sm text-purple-900 shadow-xs dark:border-purple-500/30 dark:bg-purple-950/40 dark:text-purple-200 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2.5">
                <span class="flex h-2.5 w-2.5 relative">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-purple-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-purple-600"></span>
                </span>
                <div>
                    <span class="font-semibold">Mode Pemantauan Wali Kelas (View Only):</span>
                    <span>Memantau laporan tagihan dan tunggakan siswa seluruh kelas. Anda dapat memfilter per kelas, tingkat, atau melihat rekap keseluruhan sekolah.</span>
                </div>
            </div>
            <div class="inline-flex items-center gap-1.5 rounded-full bg-purple-100/90 px-3 py-1 text-xs font-semibold text-purple-800 dark:bg-purple-900/60 dark:text-purple-200">
                <x-filament::icon icon="heroicon-o-eye" class="size-3.5" />
                <span>Akses View Only</span>
            </div>
        </div>
    @endif

    <!-- Filter Card -->
    <div class="fi-section rounded-xl bg-white p-6 shadow-xs ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h3 class="text-base font-semibold leading-6 text-gray-950 dark:text-white">
                    Filter Laporan Tunggakan
                </h3>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    {{ $isWaliKelas ? 'Memantau data tagihan dan tunggakan seluruh kelas secara view-only.' : 'SPP dihitung sampai bulan jatuh tempo; non-SPP dihitung penuh untuk tahun ajaran yang sudah dimulai.' }}
                </p>
            </div>
            <x-filament::button color="gray" size="sm" wire:click="resetFilter">
                Reset Filter
            </x-filament::button>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-6">
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
                <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Tingkat</label>
                <select wire:model.live="tingkat" class="laporan-field">
                    <option value="">Semua Tingkat</option>
                    @foreach($this->tingkatList as $nilai => $label)
                        <option value="{{ $nilai }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Status</label>
                <select wire:model.live="status" class="laporan-field">
                    @foreach($this->statusList as $nilai => $label)
                        <option value="{{ $nilai }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Urutkan</label>
                <select wire:model.live="urutkan" class="laporan-field">
                    @foreach($this->urutkanList as $nilai => $label)
                        <option value="{{ $nilai }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end">
                <div class="w-full rounded-lg bg-gray-50 px-3 py-2 text-xs text-gray-600 ring-1 ring-gray-950/5 dark:bg-gray-800 dark:text-gray-300 dark:ring-white/10">
                    {{ $tahunAjaran?->nama ?? 'Tahun ajaran belum dipilih' }}
                </div>
            </div>
        </div>
    </div>

    @if($bulan->isNotEmpty())
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-200">
            Periode tunggakan SPP: <strong>{{ $bulan->first()['bulan'] }} {{ $bulan->first()['tahun'] }}</strong>
            s/d <strong>{{ $bulan->last()['bulan'] }} {{ $bulan->last()['tahun'] }}</strong>
            ({{ $bulan->count() }} bulan jatuh tempo).
        </div>
    @else
        <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
            Belum ada bulan SPP yang jatuh tempo untuk tahun ajaran ini.
        </div>
    @endif

    <!-- Summary Metrics -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
        <div class="fi-section rounded-xl bg-white p-5 shadow-xs ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <span class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Siswa Ditampilkan</span>
            <div class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">
                {{ number_format($ringkasan['jumlah_siswa'], 0, ',', '.') }}
            </div>
        </div>

        <div class="fi-section rounded-xl bg-white p-5 shadow-xs ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <span class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Siswa Menunggak</span>
            <div class="mt-2 text-2xl font-bold text-rose-600 dark:text-rose-400">
                {{ number_format($ringkasan['jumlah_menunggak'], 0, ',', '.') }}
            </div>
        </div>

        <div class="fi-section rounded-xl bg-white p-5 shadow-xs ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <span class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Total Tagihan</span>
            <div class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">
                Rp {{ number_format($ringkasan['tagihan'], 0, ',', '.') }}
            </div>
            <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                SPP Rp {{ number_format($ringkasan['tagihan_spp'], 0, ',', '.') }}
                &bull; Non-SPP Rp {{ number_format($ringkasan['tagihan_non_spp'], 0, ',', '.') }}
            </p>
        </div>

        <div class="fi-section rounded-xl bg-white p-5 shadow-xs ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <span class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Tunggakan SPP</span>
            <div class="mt-2 text-2xl font-bold text-rose-600 dark:text-rose-400">
                Rp {{ number_format($ringkasan['tunggakan_spp'], 0, ',', '.') }}
            </div>
        </div>

        <div class="fi-section rounded-xl bg-white p-5 shadow-xs ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <span class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Tunggakan Non-SPP</span>
            <div class="mt-2 text-2xl font-bold text-amber-600 dark:text-amber-400">
                Rp {{ number_format($ringkasan['tunggakan_non_spp'], 0, ',', '.') }}
            </div>
        </div>

        <div class="fi-section rounded-xl bg-white p-5 shadow-xs ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <span class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Capaian Pembayaran</span>
            <div class="mt-2 text-2xl font-bold text-emerald-600 dark:text-emerald-400">
                {{ number_format($ringkasan['persentase'], 1, ',', '.') }}%
            </div>
            <p class="mt-1 text-[11px] text-rose-600 dark:text-rose-400">
                Total tunggakan Rp {{ number_format($ringkasan['tunggakan'], 0, ',', '.') }}
            </p>
        </div>
    </div>

    <!-- Ringkasan per Kelas -->
    @if($perKelas->isNotEmpty())
        <div class="fi-section overflow-hidden rounded-xl bg-white shadow-xs ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                <h3 class="text-sm font-semibold text-gray-950 dark:text-white">Ringkasan per Kelas</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
                    <thead class="border-b border-gray-200 bg-gray-50 text-xs font-semibold uppercase tracking-wider text-gray-700 dark:border-gray-800 dark:bg-gray-800/50 dark:text-gray-300">
                        <tr>
                            <th class="px-4 py-3.5">Kelas</th>
                            <th class="px-4 py-3.5 text-right">Siswa</th>
                            <th class="px-4 py-3.5 text-right">Menunggak</th>
                            <th class="px-4 py-3.5 text-right">Tagihan SPP</th>
                            <th class="px-4 py-3.5 text-right">Tunggakan SPP</th>
                            <th class="px-4 py-3.5 text-right">Tagihan Non-SPP</th>
                            <th class="px-4 py-3.5 text-right">Tunggakan Non-SPP</th>
                            <th class="px-4 py-3.5 text-right">Total Tagihan</th>
                            <th class="px-4 py-3.5 text-right">Total Tunggakan</th>
                            <th class="px-4 py-3.5 text-right">Capaian</th>
                            <th class="px-4 py-3.5 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach($perKelas as $kelas)
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/40">
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                    {{ $kelas['kelas']->nama_kelas }}
                                    @if($kelas['kelas']->jurusan?->kode)
                                        <span class="text-xs font-normal text-gray-500">({{ $kelas['kelas']->jurusan->kode }})</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">{{ $kelas['jumlah_siswa'] }}</td>
                                <td class="px-4 py-3 text-right font-semibold text-rose-600 dark:text-rose-400">{{ $kelas['jumlah_menunggak'] }}</td>
                                <td class="px-4 py-3 text-right">Rp {{ number_format($kelas['tagihan_spp'], 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right text-rose-600 dark:text-rose-400">Rp {{ number_format($kelas['tunggakan_spp'], 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right">Rp {{ number_format($kelas['tagihan_non_spp'], 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right text-amber-600 dark:text-amber-400">Rp {{ number_format($kelas['tunggakan_non_spp'], 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right">Rp {{ number_format($kelas['tagihan'], 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right font-semibold text-rose-600 dark:text-rose-400">Rp {{ number_format($kelas['tunggakan'], 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right">{{ number_format($kelas['persentase'], 1, ',', '.') }}%</td>
                                <td class="px-4 py-3 text-center">
                                    <a href="{{ route('laporan.tunggakan.cetak', array_filter(['tahun_ajaran_id' => $tahun_ajaran_id, 'kelas_id' => $kelas['kelas']->id, 'status' => $status])) }}"
                                        target="_blank"
                                        class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-2.5 py-1 text-xs font-semibold text-slate-700 shadow-xs hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                                        <x-filament::icon icon="heroicon-o-printer" class="size-3.5" />
                                        <span>Cetak Kelas</span>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Rincian Non-SPP per Pos -->
    @if($perKategoriNonSpp->isNotEmpty())
        <div class="fi-section overflow-hidden rounded-xl bg-white shadow-xs ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                <h3 class="text-sm font-semibold text-gray-950 dark:text-white">Rincian Tunggakan Non-SPP per Pos</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
                    <thead class="border-b border-gray-200 bg-gray-50 text-xs font-semibold uppercase tracking-wider text-gray-700 dark:border-gray-800 dark:bg-gray-800/50 dark:text-gray-300">
                        <tr>
                            <th class="px-4 py-3.5">Pos Biaya</th>
                            <th class="px-4 py-3.5 text-right">Siswa</th>
                            <th class="px-4 py-3.5 text-right">Jumlah Tagihan</th>
                            <th class="px-4 py-3.5 text-right">Potensi</th>
                            <th class="px-4 py-3.5 text-right">Realisasi</th>
                            <th class="px-4 py-3.5 text-right">Tunggakan</th>
                            <th class="px-4 py-3.5 text-right">Capaian</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach($perKategoriNonSpp as $pos)
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/40">
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $pos['kategori'] }}</td>
                                <td class="px-4 py-3 text-right">{{ $pos['jumlah_siswa'] }}</td>
                                <td class="px-4 py-3 text-right">{{ $pos['jumlah_pos'] }}</td>
                                <td class="px-4 py-3 text-right">Rp {{ number_format($pos['potensi'], 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right">Rp {{ number_format($pos['realisasi'], 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right font-semibold text-amber-600 dark:text-amber-400">Rp {{ number_format($pos['tunggakan'], 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right">{{ number_format($pos['persentase'], 1, ',', '.') }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

</x-filament-panels::page>

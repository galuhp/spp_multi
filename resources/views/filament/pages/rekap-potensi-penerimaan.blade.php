<x-filament-panels::page>
    @push('styles')
        @vite('resources/css/laporan-pembayaran.css')
    @endpush

    @php
        $rekap = $this->rekap;
        $ringkasan = $rekap['ringkasan'];
        $baris = $rekap['baris'];
        $perKategoriNonSpp = $rekap['per_kategori_non_spp'];
        $tahunAjaran = $rekap['tahun_ajaran'];
        $bulan = collect($rekap['bulan']);
        $labelKelompok = match ($rekap['kelompok']) {
            'jurusan' => 'Jurusan',
            'tingkat' => 'Tingkat',
            default => 'Kelas',
        };
    @endphp

    <!-- Filter Card -->
    <div class="fi-section rounded-xl bg-white p-6 shadow-xs ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h3 class="text-base font-semibold leading-6 text-gray-950 dark:text-white">
                    Filter Rekap Penerimaan vs Potensi
                </h3>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    SPP dihitung dari tarif bulanan sampai bulan jatuh tempo; non-SPP dihitung penuh untuk tahun ajaran yang sudah dimulai.
                </p>
            </div>
            <x-filament::button color="gray" size="sm" wire:click="resetFilter">
                Reset Filter
            </x-filament::button>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
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
                <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Kelompokkan</label>
                <select wire:model.live="kelompok" class="laporan-field">
                    @foreach($this->kelompokList as $nilai => $label)
                        <option value="{{ $nilai }}">{{ $label }}</option>
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
                <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Jurusan</label>
                <select wire:model.live="jurusan_id" class="laporan-field">
                    <option value="">Semua Jurusan</option>
                    @foreach($this->jurusanList as $jurusan)
                        <option value="{{ $jurusan->id }}">{{ $jurusan->kode }} — {{ $jurusan->nama }}</option>
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
        </div>
    </div>

    @if($bulan->isNotEmpty())
        <div class="rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-3 text-sm text-indigo-800 dark:border-indigo-500/20 dark:bg-indigo-500/10 dark:text-indigo-200">
            Potensi SPP dihitung untuk periode
            <strong>{{ $bulan->first()['bulan'] }} {{ $bulan->first()['tahun'] }}</strong>
            s/d <strong>{{ $bulan->last()['bulan'] }} {{ $bulan->last()['tahun'] }}</strong>
            ({{ $bulan->count() }} bulan jatuh tempo). Non-SPP dihitung penuh satu tahun ajaran.
        </div>
    @else
        <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
            Belum ada bulan SPP yang jatuh tempo untuk tahun ajaran ini.
        </div>
    @endif

    <!-- Summary Metrics -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
        <div class="fi-section rounded-xl bg-white p-5 shadow-xs ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <span class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Total Potensi</span>
            <div class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">
                Rp {{ number_format($ringkasan['tagihan'], 0, ',', '.') }}
            </div>
            <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                SPP Rp {{ number_format($ringkasan['tagihan_spp'], 0, ',', '.') }}
                &bull; Non-SPP Rp {{ number_format($ringkasan['tagihan_non_spp'], 0, ',', '.') }}
            </p>
        </div>

        <div class="fi-section rounded-xl bg-white p-5 shadow-xs ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <span class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Total Realisasi</span>
            <div class="mt-2 text-2xl font-bold text-emerald-600 dark:text-emerald-400">
                Rp {{ number_format($ringkasan['dibayar'], 0, ',', '.') }}
            </div>
            <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                SPP Rp {{ number_format($ringkasan['dibayar_spp'], 0, ',', '.') }}
                &bull; Non-SPP Rp {{ number_format($ringkasan['dibayar_non_spp'], 0, ',', '.') }}
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
            <span class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Capaian</span>
            <div class="mt-2 text-2xl font-bold text-indigo-600 dark:text-indigo-400">
                {{ number_format($ringkasan['persentase'], 1, ',', '.') }}%
            </div>
            <p class="mt-1 text-[11px] text-rose-600 dark:text-rose-400">
                Total tunggakan Rp {{ number_format($ringkasan['tunggakan'], 0, ',', '.') }}
            </p>
        </div>

        <div class="fi-section rounded-xl bg-white p-5 shadow-xs ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <span class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Jumlah Siswa</span>
            <div class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">
                {{ number_format($ringkasan['jumlah_siswa'], 0, ',', '.') }}
            </div>
            <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">
                {{ number_format($ringkasan['jumlah_menunggak'], 0, ',', '.') }} menunggak
            </p>
        </div>
    </div>

    <!-- Rekap per Kelompok -->
    <div class="fi-section overflow-hidden rounded-xl bg-white shadow-xs ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
            <h3 class="text-sm font-semibold text-gray-950 dark:text-white">
                Rekap per {{ $labelKelompok }}
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
                <thead class="border-b border-gray-200 bg-gray-50 text-xs font-semibold uppercase tracking-wider text-gray-700 dark:border-gray-800 dark:bg-gray-800/50 dark:text-gray-300">
                    <tr>
                        <th class="px-4 py-3.5">{{ $labelKelompok }}</th>
                        <th class="px-4 py-3.5 text-right">Siswa</th>
                        <th class="px-4 py-3.5 text-right">Menunggak</th>
                        <th class="px-4 py-3.5 text-right">Potensi SPP</th>
                        <th class="px-4 py-3.5 text-right">Realisasi SPP</th>
                        <th class="px-4 py-3.5 text-right">Tunggakan SPP</th>
                        <th class="px-4 py-3.5 text-right">Potensi Non-SPP</th>
                        <th class="px-4 py-3.5 text-right">Realisasi Non-SPP</th>
                        <th class="px-4 py-3.5 text-right">Tunggakan Non-SPP</th>
                        <th class="px-4 py-3.5 text-right">Total Potensi</th>
                        <th class="px-4 py-3.5 text-right">Total Tunggakan</th>
                        <th class="px-4 py-3.5 text-right">Capaian</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($baris as $kelompok)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/40">
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                {{ $kelompok['label'] }}
                            </td>
                            <td class="px-4 py-3 text-right">{{ $kelompok['jumlah_siswa'] }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-rose-600 dark:text-rose-400">
                                {{ $kelompok['jumlah_menunggak'] }}
                            </td>
                            <td class="px-4 py-3 text-right">Rp {{ number_format($kelompok['tagihan_spp'], 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right">Rp {{ number_format($kelompok['dibayar_spp'], 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right text-rose-600 dark:text-rose-400">Rp {{ number_format($kelompok['tunggakan_spp'], 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right">Rp {{ number_format($kelompok['tagihan_non_spp'], 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right">Rp {{ number_format($kelompok['dibayar_non_spp'], 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right text-amber-600 dark:text-amber-400">Rp {{ number_format($kelompok['tunggakan_non_spp'], 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right">Rp {{ number_format($kelompok['tagihan'], 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-rose-600 dark:text-rose-400">
                                Rp {{ number_format($kelompok['tunggakan'], 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-right">{{ number_format($kelompok['persentase'], 1, ',', '.') }}%</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="px-4 py-8 text-center text-sm text-gray-500">
                                Tidak ada data siswa yang sesuai dengan filter yang dipilih.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if($baris->isNotEmpty())
                    <tfoot>
                        <tr class="border-t-2 border-gray-200 bg-gray-50 font-semibold text-gray-900 dark:border-gray-700 dark:bg-gray-800/60 dark:text-white">
                            <td class="px-4 py-3">Total</td>
                            <td class="px-4 py-3 text-right">{{ $ringkasan['jumlah_siswa'] }}</td>
                            <td class="px-4 py-3 text-right text-rose-600 dark:text-rose-400">{{ $ringkasan['jumlah_menunggak'] }}</td>
                            <td class="px-4 py-3 text-right">Rp {{ number_format($ringkasan['tagihan_spp'], 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right">Rp {{ number_format($ringkasan['dibayar_spp'], 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right text-rose-600 dark:text-rose-400">Rp {{ number_format($ringkasan['tunggakan_spp'], 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right">Rp {{ number_format($ringkasan['tagihan_non_spp'], 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right">Rp {{ number_format($ringkasan['dibayar_non_spp'], 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right text-amber-600 dark:text-amber-400">Rp {{ number_format($ringkasan['tunggakan_non_spp'], 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right">Rp {{ number_format($ringkasan['tagihan'], 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right text-rose-600 dark:text-rose-400">Rp {{ number_format($ringkasan['tunggakan'], 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format($ringkasan['persentase'], 1, ',', '.') }}%</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>

    <!-- Rincian Non-SPP per Pos -->
    @if($perKategoriNonSpp->isNotEmpty())
        <div class="fi-section overflow-hidden rounded-xl bg-white shadow-xs ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                <h3 class="text-sm font-semibold text-gray-950 dark:text-white">Rincian Non-SPP per Pos</h3>
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

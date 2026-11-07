<x-filament-panels::page>
    @push('styles')
        @vite('resources/css/impor-siswa.css')
    @endpush

    @php
        $ringkasanBatch = $this->ringkasanBatch;
        $jumlahAkanDitimpa = $ringkasanBatch['akan_ditimpa'] ?? 0;
    @endphp

    <div class="impor-siswa space-y-4">
        @if ($galat)
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-500/30 dark:bg-rose-950/30 dark:text-rose-300" role="alert">
                {{ $galat }}
            </div>
        @endif

        @if ($ringkasan)
            <div class="fi-section rounded-xl border border-slate-200/80 bg-white p-5 shadow-xs dark:border-white/10 dark:bg-gray-900">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900 dark:text-white">Hasil Impor</h3>
                        <p class="mt-0.5 text-xs text-slate-500 dark:text-gray-400">
                            Hanya baris valid dan baris berperingatan yang disimpan. Baris error dilewati.
                        </p>
                    </div>
                    <x-filament::button tag="a" href="{{ route('impor-siswa.template') }}" color="gray" size="sm">
                        Unduh Template
                    </x-filament::button>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <div class="rounded-lg border border-slate-200 p-3 dark:border-gray-700">
                        <p class="text-[11px] font-medium uppercase tracking-wide text-slate-500 dark:text-gray-400">Berhasil disimpan</p>
                        <p class="mt-1 text-xl font-semibold text-emerald-700 dark:text-emerald-400 tabular-nums">{{ $ringkasan['berhasil'] }}</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 p-3 dark:border-gray-700">
                        <p class="text-[11px] font-medium uppercase tracking-wide text-slate-500 dark:text-gray-400">Dilewati</p>
                        <p class="mt-1 text-xl font-semibold text-rose-700 dark:text-rose-400 tabular-nums">{{ $ringkasan['dilewati'] }}</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 p-3 dark:border-gray-700">
                        <p class="text-[11px] font-medium uppercase tracking-wide text-slate-500 dark:text-gray-400">Peringatan</p>
                        <p class="mt-1 text-xl font-semibold text-amber-700 dark:text-amber-400 tabular-nums">{{ $ringkasan['peringatan_tersimpan'] }}</p>
                    </div>
                </div>
            </div>
        @endif

        <div class="fi-section rounded-xl border border-slate-200/80 bg-white p-5 shadow-xs dark:border-white/10 dark:bg-gray-900">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h3 class="text-base font-semibold text-slate-900 dark:text-white">Unggah Berkas Impor</h3>
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-gray-400">
                        Format .xlsx, maksimal 5 MB dan 2.000 baris. Data dibaca per 500 baris ke tabel staging.
                    </p>
                </div>
                <x-filament::button tag="a" href="{{ route('impor-siswa.template') }}" color="gray" size="sm">
                    Unduh Template Excel
                </x-filament::button>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label for="tahun_ajaran_id" class="mb-1 block text-xs font-medium text-slate-600 dark:text-gray-300">
                        Tahun ajaran tujuan
                    </label>
                    <select id="tahun_ajaran_id" wire:model.live="tahun_ajaran_id"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        @disabled($batchId !== null)>
                        <option value="">Pilih tahun ajaran</option>
                        @foreach ($this->tahunAjaranOptions as $id => $nama)
                            <option value="{{ $id }}">{{ $nama }}</option>
                        @endforeach
                    </select>
                    @error('tahun_ajaran_id')
                        <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="spp_id" class="mb-1 block text-xs font-medium text-slate-600 dark:text-gray-300">
                        Tarif SPP siswa baru
                    </label>
                    <select id="spp_id" wire:model="spp_id"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        @disabled($batchId !== null)>
                        <option value="">Pilih tarif SPP</option>
                        @foreach ($this->sppOptions as $id => $label)
                            <option value="{{ $id }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('spp_id')
                        <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label for="berkas" class="mb-1 block text-xs font-medium text-slate-600 dark:text-gray-300">
                        Berkas Excel
                    </label>
                    <input id="berkas" type="file" wire:model="berkas" accept=".xlsx"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-1.5 file:text-xs file:font-medium file:text-slate-900 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:file:bg-gray-700 dark:file:text-white"
                        @disabled($batchId !== null) />
                    @error('berkas')
                        <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
                    @enderror
                    <div wire:loading wire:target="berkas" class="mt-1 text-xs text-slate-500 dark:text-gray-400">
                        Mengunggah berkas...
                    </div>
                </div>

                <div class="flex flex-col justify-end">
                    <label class="flex cursor-pointer items-start gap-2 rounded-lg border border-slate-200 p-3 dark:border-gray-700">
                        <input type="checkbox" wire:model="perbarui"
                            class="mt-0.5 h-4 w-4 rounded border-slate-300 accent-primary-600 focus:ring-primary-200 dark:border-gray-600"
                            @disabled($batchId !== null) />
                        <span>
                            <span class="block text-xs font-medium text-slate-900 dark:text-white">Perbarui data siswa yang sudah ada</span>
                            <span class="mt-0.5 block text-[11px] leading-relaxed text-slate-500 dark:text-gray-400">
                                Pencocokan NISN, lalu NIK, lalu NIS. Opsi ini mati secara default. Baris yang cocok akan ditimpa.
                            </span>
                        </span>
                    </label>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap items-center gap-2">
                <x-filament::button type="button" size="sm" :loading-indicator="false"
                    wire:click="unggah" wire:loading.attr="disabled" wire:target="unggah,berkas"
                    :disabled="$batchId !== null">
                    <span wire:loading.remove wire:target="unggah">Unggah dan Validasi</span>
                    <span wire:loading wire:target="unggah">Memproses...</span>
                </x-filament::button>

                @if ($batchId !== null)
                    <x-filament::button type="button" color="gray" size="sm" wire:click="batalkan">
                        Batalkan Pratinjau
                    </x-filament::button>
                    <x-filament::button tag="a" href="{{ route('impor-siswa.error', $batchId) }}" color="gray" size="sm">
                        Unduh Laporan Error
                    </x-filament::button>
                @endif
            </div>
        </div>

        @if ($batchId !== null)
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-4">
                <div class="fi-section rounded-lg border border-slate-200/80 bg-white p-3 shadow-xs dark:border-white/10 dark:bg-gray-900">
                    <p class="text-[11px] font-medium uppercase tracking-wide text-slate-500 dark:text-gray-400">Total baris</p>
                    <p class="mt-1 text-xl font-semibold text-slate-900 dark:text-white tabular-nums">{{ $ringkasanBatch['total'] ?? 0 }}</p>
                </div>
                <div class="fi-section rounded-lg border border-slate-200/80 bg-white p-3 shadow-xs dark:border-white/10 dark:bg-gray-900">
                    <p class="text-[11px] font-medium uppercase tracking-wide text-slate-500 dark:text-gray-400">Valid</p>
                    <p class="mt-1 text-xl font-semibold text-emerald-700 dark:text-emerald-400 tabular-nums">{{ $ringkasanBatch['valid'] ?? 0 }}</p>
                </div>
                <div class="fi-section rounded-lg border border-slate-200/80 bg-white p-3 shadow-xs dark:border-white/10 dark:bg-gray-900">
                    <p class="text-[11px] font-medium uppercase tracking-wide text-slate-500 dark:text-gray-400">Peringatan</p>
                    <p class="mt-1 text-xl font-semibold text-amber-700 dark:text-amber-400 tabular-nums">{{ $ringkasanBatch['peringatan'] ?? 0 }}</p>
                </div>
                <div class="fi-section rounded-lg border border-slate-200/80 bg-white p-3 shadow-xs dark:border-white/10 dark:bg-gray-900">
                    <p class="text-[11px] font-medium uppercase tracking-wide text-slate-500 dark:text-gray-400">Error</p>
                    <p class="mt-1 text-xl font-semibold text-rose-700 dark:text-rose-400 tabular-nums">{{ $ringkasanBatch['error'] ?? 0 }}</p>
                </div>
            </div>

            @if ($perbarui && $jumlahAkanDitimpa > 0)
                <div class="rounded-lg border border-amber-200/80 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-500/30 dark:bg-amber-950/30 dark:text-amber-300">
                    {{ $jumlahAkanDitimpa }} baris akan menimpa data siswa yang sudah ada. Periksa pratinjau sebelum menyimpan.
                </div>
            @endif

            <div class="fi-section rounded-xl border border-slate-200/80 bg-white p-5 shadow-xs dark:border-white/10 dark:bg-gray-900">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900 dark:text-white">Pratinjau Validasi</h3>
                        <p class="mt-0.5 text-xs text-slate-500 dark:text-gray-400">
                            Menampilkan {{ $this->jumlahPratinjau }} baris. Filter aktif: {{ ucfirst($filter) }}.
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-1.5">
                        @foreach (['semua' => 'Semua', 'error' => 'Error', 'peringatan' => 'Peringatan', 'valid' => 'Valid'] as $nilai => $label)
                            <x-filament::button type="button" size="sm"
                                :color="$filter === $nilai ? 'primary' : 'gray'"
                                :outlined="$filter !== $nilai"
                                wire:click="gantiFilter('{{ $nilai }}')">
                                {{ $label }}
                            </x-filament::button>
                        @endforeach
                    </div>
                </div>

                <div class="mt-4 overflow-x-auto">
                    <table class="table table-sm w-full text-left text-sm">
                        <thead class="bg-slate-50 text-slate-600 dark:bg-gray-800/60 dark:text-gray-400">
                            <tr>
                                <th>Baris</th>
                                <th>NISN</th>
                                <th>NIK</th>
                                <th>NIS</th>
                                <th>Nama</th>
                                <th>JK</th>
                                <th>Kelas</th>
                                <th>Kompetensi</th>
                                <th>HP Wali</th>
                                <th>Status</th>
                                <th>Alasan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-gray-800">
                            @forelse ($this->pratinjau as $baris)
                                <tr wire:key="staging-{{ $baris->id }}">
                                    <td class="text-xs text-slate-500 dark:text-gray-400 tabular-nums">{{ $baris->baris }}</td>
                                    <td class="font-mono text-xs text-slate-700 dark:text-gray-300">{{ $baris->nisn ?: '-' }}</td>
                                    <td class="font-mono text-xs text-slate-700 dark:text-gray-300">{{ $baris->nik ?: '-' }}</td>
                                    <td class="font-mono text-xs text-slate-700 dark:text-gray-300">{{ $baris->nis ?: '-' }}</td>
                                    <td class="text-xs font-medium text-slate-900 dark:text-white">{{ $baris->nama_lengkap ?: '-' }}</td>
                                    <td class="text-xs text-slate-700 dark:text-gray-300">{{ $baris->jenis_kelamin ?: '-' }}</td>
                                    <td class="text-xs text-slate-700 dark:text-gray-300">{{ $baris->kelas ?: '-' }}</td>
                                    <td class="text-xs text-slate-700 dark:text-gray-300">{{ $baris->kode_kompetensi ?: '-' }}</td>
                                    <td class="font-mono text-xs text-slate-700 dark:text-gray-300">{{ $baris->hp_wali ?: '-' }}</td>
                                    <td>
                                        <span class="{{ $baris->status->badgeClass() }}">{{ $baris->status->label() }}</span>
                                    </td>
                                    <td class="max-w-md whitespace-pre-line text-xs text-slate-600 dark:text-gray-300">{{ $baris->alasan }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="py-6 text-center text-xs text-slate-500 dark:text-gray-400">
                                        Tidak ada baris pada filter ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                    <p class="text-xs text-slate-500 dark:text-gray-400 tabular-nums">
                        Halaman {{ $halaman }} dari {{ $this->totalHalaman }}
                    </p>
                    <div class="flex items-center gap-2">
                        <x-filament::button type="button" color="gray" size="sm" outlined
                            wire:click="keHalaman({{ $halaman - 1 }})"
                            :disabled="$halaman <= 1">
                            Sebelumnya
                        </x-filament::button>
                        <x-filament::button type="button" color="gray" size="sm" outlined
                            wire:click="keHalaman({{ $halaman + 1 }})"
                            :disabled="$halaman >= $this->totalHalaman">
                            Berikutnya
                        </x-filament::button>
                    </div>
                </div>

                <div class="mt-4 border-t border-slate-200 pt-4 dark:border-gray-800">
                    @if ($perbarui)
                        <x-filament::button type="button" size="sm" :loading-indicator="false"
                            wire:click="simpan" wire:loading.attr="disabled" wire:target="simpan"
                            wire:confirm="Simpan impor dan timpa {{ $jumlahAkanDitimpa }} data siswa yang sudah ada?">
                            <span wire:loading.remove wire:target="simpan">Simpan Baris Valid</span>
                            <span wire:loading wire:target="simpan">Menyimpan...</span>
                        </x-filament::button>
                    @else
                        <x-filament::button type="button" size="sm" :loading-indicator="false"
                            wire:click="simpan" wire:loading.attr="disabled" wire:target="simpan">
                            <span wire:loading.remove wire:target="simpan">Simpan Baris Valid</span>
                            <span wire:loading wire:target="simpan">Menyimpan...</span>
                        </x-filament::button>
                    @endif
                    <p class="mt-2 text-[11px] text-slate-500 dark:text-gray-400">
                        Penyimpanan dilakukan per 500 baris dalam transaksi database. Baris error tidak disimpan.
                    </p>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
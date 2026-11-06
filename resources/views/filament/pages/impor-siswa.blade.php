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
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert">
                {{ $galat }}
            </div>
        @endif

        @if ($ringkasan)
            <div class="rounded-xl border border-zinc-200 bg-white p-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h3 class="text-base font-semibold text-zinc-900">Hasil Impor</h3>
                        <p class="mt-0.5 text-xs text-zinc-500">
                            Hanya baris valid dan baris berperingatan yang disimpan. Baris error dilewati.
                        </p>
                    </div>
                    <x-filament::button tag="a" href="{{ route('impor-siswa.template') }}" color="gray" size="sm">
                        Unduh Template
                    </x-filament::button>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <div class="rounded-lg border border-zinc-200 p-3">
                        <p class="text-[11px] font-medium uppercase tracking-wide text-zinc-500">Berhasil disimpan</p>
                        <p class="mt-1 text-xl font-semibold text-zinc-900">{{ $ringkasan['berhasil'] }}</p>
                    </div>
                    <div class="rounded-lg border border-zinc-200 p-3">
                        <p class="text-[11px] font-medium uppercase tracking-wide text-zinc-500">Dilewati</p>
                        <p class="mt-1 text-xl font-semibold text-zinc-900">{{ $ringkasan['dilewati'] }}</p>
                    </div>
                    <div class="rounded-lg border border-zinc-200 p-3">
                        <p class="text-[11px] font-medium uppercase tracking-wide text-zinc-500">Peringatan</p>
                        <p class="mt-1 text-xl font-semibold text-zinc-900">{{ $ringkasan['peringatan_tersimpan'] }}</p>
                    </div>
                </div>
            </div>
        @endif

        <div class="rounded-xl border border-zinc-200 bg-white p-5">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h3 class="text-base font-semibold text-zinc-900">Unggah Berkas Impor</h3>
                    <p class="mt-0.5 text-xs text-zinc-500">
                        Format .xlsx, maksimal 5 MB dan 2.000 baris. Data dibaca per 500 baris ke tabel staging.
                    </p>
                </div>
                <x-filament::button tag="a" href="{{ route('impor-siswa.template') }}" color="gray" size="sm">
                    Unduh Template Excel
                </x-filament::button>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label for="tahun_ajaran_id" class="mb-1 block text-xs font-medium text-zinc-600">
                        Tahun ajaran tujuan
                    </label>
                    <select id="tahun_ajaran_id" wire:model.live="tahun_ajaran_id"
                        class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm focus:border-zinc-500 focus:outline-none focus:ring-2 focus:ring-zinc-400"
                        @disabled($batchId !== null)>
                        <option value="">Pilih tahun ajaran</option>
                        @foreach ($this->tahunAjaranOptions as $id => $nama)
                            <option value="{{ $id }}">{{ $nama }}</option>
                        @endforeach
                    </select>
                    @error('tahun_ajaran_id')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="spp_id" class="mb-1 block text-xs font-medium text-zinc-600">
                        Tarif SPP siswa baru
                    </label>
                    <select id="spp_id" wire:model="spp_id"
                        class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm focus:border-zinc-500 focus:outline-none focus:ring-2 focus:ring-zinc-400"
                        @disabled($batchId !== null)>
                        <option value="">Pilih tarif SPP</option>
                        @foreach ($this->sppOptions as $id => $label)
                            <option value="{{ $id }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('spp_id')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label for="berkas" class="mb-1 block text-xs font-medium text-zinc-600">
                        Berkas Excel
                    </label>
                    <input id="berkas" type="file" wire:model="berkas" accept=".xlsx"
                        class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm file:mr-3 file:rounded-md file:border-0 file:bg-zinc-100 file:px-3 file:py-1.5 file:text-xs file:font-medium file:text-zinc-900"
                        @disabled($batchId !== null) />
                    @error('berkas')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                    <div wire:loading wire:target="berkas" class="mt-1 text-xs text-zinc-500">
                        Mengunggah berkas...
                    </div>
                </div>

                <div class="flex flex-col justify-end">
                    <label class="flex cursor-pointer items-start gap-2 rounded-lg border border-zinc-200 p-3">
                        <input type="checkbox" wire:model="perbarui"
                            class="mt-0.5 h-4 w-4 rounded border-zinc-300 text-zinc-900 focus:ring-zinc-400"
                            @disabled($batchId !== null) />
                        <span>
                            <span class="block text-xs font-medium text-zinc-900">Perbarui data siswa yang sudah ada</span>
                            <span class="mt-0.5 block text-[11px] leading-relaxed text-zinc-500">
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
                <div class="rounded-lg border border-zinc-200 bg-white p-3">
                    <p class="text-[11px] font-medium uppercase tracking-wide text-zinc-500">Total baris</p>
                    <p class="mt-1 text-xl font-semibold text-zinc-900">{{ $ringkasanBatch['total'] ?? 0 }}</p>
                </div>
                <div class="rounded-lg border border-zinc-200 bg-white p-3">
                    <p class="text-[11px] font-medium uppercase tracking-wide text-zinc-500">Valid</p>
                    <p class="mt-1 text-xl font-semibold text-zinc-900">{{ $ringkasanBatch['valid'] ?? 0 }}</p>
                </div>
                <div class="rounded-lg border border-zinc-200 bg-white p-3">
                    <p class="text-[11px] font-medium uppercase tracking-wide text-zinc-500">Peringatan</p>
                    <p class="mt-1 text-xl font-semibold text-zinc-900">{{ $ringkasanBatch['peringatan'] ?? 0 }}</p>
                </div>
                <div class="rounded-lg border border-zinc-200 bg-white p-3">
                    <p class="text-[11px] font-medium uppercase tracking-wide text-zinc-500">Error</p>
                    <p class="mt-1 text-xl font-semibold text-zinc-900">{{ $ringkasanBatch['error'] ?? 0 }}</p>
                </div>
            </div>

            @if ($perbarui && $jumlahAkanDitimpa > 0)
                <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    {{ $jumlahAkanDitimpa }} baris akan menimpa data siswa yang sudah ada. Periksa pratinjau sebelum menyimpan.
                </div>
            @endif

            <div class="rounded-xl border border-zinc-200 bg-white p-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h3 class="text-base font-semibold text-zinc-900">Pratinjau Validasi</h3>
                        <p class="mt-0.5 text-xs text-zinc-500">
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
                        <thead>
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
                        <tbody>
                            @forelse ($this->pratinjau as $baris)
                                <tr wire:key="staging-{{ $baris->id }}">
                                    <td class="text-xs text-zinc-500">{{ $baris->baris }}</td>
                                    <td class="font-mono text-xs">{{ $baris->nisn ?: '-' }}</td>
                                    <td class="font-mono text-xs">{{ $baris->nik ?: '-' }}</td>
                                    <td class="font-mono text-xs">{{ $baris->nis ?: '-' }}</td>
                                    <td class="text-xs">{{ $baris->nama_lengkap ?: '-' }}</td>
                                    <td class="text-xs">{{ $baris->jenis_kelamin ?: '-' }}</td>
                                    <td class="text-xs">{{ $baris->kelas ?: '-' }}</td>
                                    <td class="text-xs">{{ $baris->kode_kompetensi ?: '-' }}</td>
                                    <td class="font-mono text-xs">{{ $baris->hp_wali ?: '-' }}</td>
                                    <td>
                                        <span class="{{ $baris->status->badgeClass() }}">{{ $baris->status->label() }}</span>
                                    </td>
                                    <td class="max-w-md whitespace-pre-line text-xs text-zinc-600">{{ $baris->alasan }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="py-6 text-center text-xs text-zinc-500">
                                        Tidak ada baris pada filter ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                    <p class="text-xs text-zinc-500">
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

                <div class="mt-4 border-t border-zinc-200 pt-4">
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
                    <p class="mt-2 text-[11px] text-zinc-500">
                        Penyimpanan dilakukan per 500 baris dalam transaksi database. Baris error tidak disimpan.
                    </p>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
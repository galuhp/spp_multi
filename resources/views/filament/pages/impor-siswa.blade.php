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
            <div class="alert alert-error alert-soft" role="alert">
                <span class="text-sm">{{ $galat }}</span>
            </div>
        @endif

        @if ($ringkasan)
            <div class="card border border-base-300 bg-base-100">
                <div class="card-body p-5">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h3 class="text-base font-semibold text-base-content">Hasil Impor</h3>
                            <p class="mt-0.5 text-xs text-base-content/60">
                                Hanya baris valid dan baris berperingatan yang disimpan. Baris error dilewati.
                            </p>
                        </div>
                        <a href="{{ route('impor-siswa.template') }}" class="btn btn-outline btn-sm">
                            Unduh Template
                        </a>
                    </div>

                    <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <div class="rounded-lg border border-base-300 p-3">
                            <p class="text-[11px] font-medium uppercase tracking-wide text-base-content/60">Berhasil disimpan</p>
                            <p class="mt-1 text-xl font-semibold text-base-content">{{ $ringkasan['berhasil'] }}</p>
                        </div>
                        <div class="rounded-lg border border-base-300 p-3">
                            <p class="text-[11px] font-medium uppercase tracking-wide text-base-content/60">Dilewati</p>
                            <p class="mt-1 text-xl font-semibold text-base-content">{{ $ringkasan['dilewati'] }}</p>
                        </div>
                        <div class="rounded-lg border border-base-300 p-3">
                            <p class="text-[11px] font-medium uppercase tracking-wide text-base-content/60">Peringatan</p>
                            <p class="mt-1 text-xl font-semibold text-base-content">{{ $ringkasan['peringatan_tersimpan'] }}</p>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div class="card border border-base-300 bg-base-100">
            <div class="card-body p-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h3 class="text-base font-semibold text-base-content">Unggah Berkas Impor</h3>
                        <p class="mt-0.5 text-xs text-base-content/60">
                            Format .xlsx, maksimal 5 MB dan 2.000 baris. Data dibaca per 500 baris ke tabel staging.
                        </p>
                    </div>
                    <a href="{{ route('impor-siswa.template') }}" class="btn btn-primary btn-sm">
                        Unduh Template Excel
                    </a>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label for="tahun_ajaran_id" class="mb-1 block text-xs font-medium text-base-content/70">
                            Tahun ajaran tujuan
                        </label>
                        <select
                            id="tahun_ajaran_id"
                            wire:model.live="tahun_ajaran_id"
                            class="select select-sm w-full"
                            @disabled($batchId !== null)
                        >
                            <option value="">Pilih tahun ajaran</option>
                            @foreach ($this->tahunAjaranOptions as $id => $nama)
                                <option value="{{ $id }}">{{ $nama }}</option>
                            @endforeach
                        </select>
                        @error('tahun_ajaran_id')
                            <p class="mt-1 text-xs text-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="spp_id" class="mb-1 block text-xs font-medium text-base-content/70">
                            Tarif SPP siswa baru
                        </label>
                        <select
                            id="spp_id"
                            wire:model="spp_id"
                            class="select select-sm w-full"
                            @disabled($batchId !== null)
                        >
                            <option value="">Pilih tarif SPP</option>
                            @foreach ($this->sppOptions as $id => $label)
                                <option value="{{ $id }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('spp_id')
                            <p class="mt-1 text-xs text-error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label for="berkas" class="mb-1 block text-xs font-medium text-base-content/70">
                            Berkas Excel
                        </label>
                        <input
                            id="berkas"
                            type="file"
                            wire:model="berkas"
                            accept=".xlsx"
                            class="file-input file-input-sm w-full"
                            @disabled($batchId !== null)
                        >
                        @error('berkas')
                            <p class="mt-1 text-xs text-error">{{ $message }}</p>
                        @enderror
                        <div wire:loading wire:target="berkas" class="mt-1 text-xs text-base-content/60">
                            Mengunggah berkas...
                        </div>
                    </div>

                    <div class="flex flex-col justify-end">
                        <label class="flex cursor-pointer items-start gap-2 rounded-lg border border-base-300 p-3">
                            <input
                                type="checkbox"
                                wire:model="perbarui"
                                class="checkbox checkbox-sm mt-0.5"
                                @disabled($batchId !== null)
                            >
                            <span>
                                <span class="block text-xs font-medium text-base-content">Perbarui data siswa yang sudah ada</span>
                                <span class="mt-0.5 block text-[11px] leading-relaxed text-base-content/60">
                                    Pencocokan NISN, lalu NIK, lalu NIS. Opsi ini mati secara default. Baris yang cocok akan ditimpa.
                                </span>
                            </span>
                        </label>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-2">
                    <button
                        type="button"
                        wire:click="unggah"
                        wire:loading.attr="disabled"
                        wire:target="unggah,berkas"
                        class="btn btn-primary btn-sm"
                        @disabled($batchId !== null)
                    >
                        <span wire:loading.remove wire:target="unggah">Unggah dan Validasi</span>
                        <span wire:loading wire:target="unggah">Memproses...</span>
                    </button>

                    @if ($batchId !== null)
                        <button type="button" wire:click="batalkan" class="btn btn-ghost btn-sm">
                            Batalkan Pratinjau
                        </button>
                        <a href="{{ route('impor-siswa.error', $batchId) }}" class="btn btn-outline btn-sm">
                            Unduh Laporan Error
                        </a>
                    @endif
                </div>
            </div>
        </div>

        @if ($batchId !== null)
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-4">
                <div class="rounded-lg border border-base-300 bg-base-100 p-3">
                    <p class="text-[11px] font-medium uppercase tracking-wide text-base-content/60">Total baris</p>
                    <p class="mt-1 text-xl font-semibold text-base-content">{{ $ringkasanBatch['total'] ?? 0 }}</p>
                </div>
                <div class="rounded-lg border border-base-300 bg-base-100 p-3">
                    <p class="text-[11px] font-medium uppercase tracking-wide text-base-content/60">Valid</p>
                    <p class="mt-1 text-xl font-semibold text-base-content">{{ $ringkasanBatch['valid'] ?? 0 }}</p>
                </div>
                <div class="rounded-lg border border-base-300 bg-base-100 p-3">
                    <p class="text-[11px] font-medium uppercase tracking-wide text-base-content/60">Peringatan</p>
                    <p class="mt-1 text-xl font-semibold text-base-content">{{ $ringkasanBatch['peringatan'] ?? 0 }}</p>
                </div>
                <div class="rounded-lg border border-base-300 bg-base-100 p-3">
                    <p class="text-[11px] font-medium uppercase tracking-wide text-base-content/60">Error</p>
                    <p class="mt-1 text-xl font-semibold text-base-content">{{ $ringkasanBatch['error'] ?? 0 }}</p>
                </div>
            </div>

            @if ($perbarui && $jumlahAkanDitimpa > 0)
                <div class="alert alert-warning alert-soft">
                    <span class="text-sm">
                        {{ $jumlahAkanDitimpa }} baris akan menimpa data siswa yang sudah ada. Periksa pratinjau sebelum menyimpan.
                    </span>
                </div>
            @endif

            <div class="card border border-base-300 bg-base-100">
                <div class="card-body p-5">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h3 class="text-base font-semibold text-base-content">Pratinjau Validasi</h3>
                            <p class="mt-0.5 text-xs text-base-content/60">
                                Menampilkan {{ $this->jumlahPratinjau }} baris. Filter aktif: {{ ucfirst($filter) }}.
                            </p>
                        </div>

                        <div class="flex flex-wrap items-center gap-1">
                            @foreach (['semua' => 'Semua', 'error' => 'Error', 'peringatan' => 'Peringatan', 'valid' => 'Valid'] as $nilai => $label)
                                <button
                                    type="button"
                                    wire:click="gantiFilter('{{ $nilai }}')"
                                    class="btn btn-xs {{ $filter === $nilai ? 'btn-primary' : 'btn-ghost' }}"
                                >
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <div class="mt-4 overflow-x-auto">
                        <table class="table table-sm">
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
                                        <td class="text-xs text-base-content/60">{{ $baris->baris }}</td>
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
                                        <td class="max-w-md whitespace-pre-line text-xs text-base-content/70">{{ $baris->alasan }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="11" class="py-6 text-center text-xs text-base-content/60">
                                            Tidak ada baris pada filter ini.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                        <p class="text-xs text-base-content/60">
                            Halaman {{ $halaman }} dari {{ $this->totalHalaman }}
                        </p>
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                wire:click="keHalaman({{ $halaman - 1 }})"
                                class="btn btn-ghost btn-sm"
                                @disabled($halaman <= 1)
                            >
                                Sebelumnya
                            </button>
                            <button
                                type="button"
                                wire:click="keHalaman({{ $halaman + 1 }})"
                                class="btn btn-ghost btn-sm"
                                @disabled($halaman >= $this->totalHalaman)
                            >
                                Berikutnya
                            </button>
                        </div>
                    </div>

                    <div class="mt-4 border-t border-base-300 pt-4">
                        @if ($perbarui)
                            <button
                                type="button"
                                wire:click="simpan"
                                wire:loading.attr="disabled"
                                wire:target="simpan"
                                class="btn btn-primary btn-sm"
                                wire:confirm="Simpan impor dan timpa {{ $jumlahAkanDitimpa }} data siswa yang sudah ada?"
                            >
                                <span wire:loading.remove wire:target="simpan">Simpan Baris Valid</span>
                                <span wire:loading wire:target="simpan">Menyimpan...</span>
                            </button>
                        @else
                            <button
                                type="button"
                                wire:click="simpan"
                                wire:loading.attr="disabled"
                                wire:target="simpan"
                                class="btn btn-primary btn-sm"
                            >
                                <span wire:loading.remove wire:target="simpan">Simpan Baris Valid</span>
                                <span wire:loading wire:target="simpan">Menyimpan...</span>
                            </button>
                        @endif
                        <p class="mt-2 text-[11px] text-base-content/60">
                            Penyimpanan dilakukan per 500 baris dalam transaksi database. Baris error tidak disimpan.
                        </p>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>

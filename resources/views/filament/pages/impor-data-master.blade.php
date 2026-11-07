<x-filament-panels::page>
    @push('styles')
        @vite('resources/css/impor-data-master.css')
    @endpush

    @php
        $tahunAjaranOptions = $this->tahunAjaranOptions;

        $kolomTarif = [
            ['nama' => 'kategori', 'wajib' => true, 'deskripsi' => 'Kelompok tarif, misalnya SPP, Biaya Ujian, Uang Seragam, atau Biaya Praktik.'],
            ['nama' => 'nama', 'wajib' => false, 'deskripsi' => 'Nama spesifik tarif. Jika kosong, akan memakai nilai kategori.'],
            ['nama' => 'tipe', 'wajib' => false, 'deskripsi' => 'Isi bulanan untuk tarif rutin atau sekali_bayar untuk tarif sekali bayar.'],
            ['nama' => 'tahun_ajaran', 'wajib' => false, 'deskripsi' => 'Contoh: 2026/2027. Jika kosong, memakai tahun ajaran aktif.'],
            ['nama' => 'tingkat', 'wajib' => false, 'deskripsi' => '10, 11, atau 12. Kosongkan jika berlaku untuk semua tingkat.'],
            ['nama' => 'nominal', 'wajib' => true, 'deskripsi' => 'Nominal tarif dalam angka, tanpa titik atau koma. Contoh: 100000.'],
            ['nama' => 'keterangan', 'wajib' => false, 'deskripsi' => 'Catatan tambahan untuk tarif (opsional).'],
        ];

        $kolomKelas = [
            ['nama' => 'nama_kelas', 'wajib' => true, 'deskripsi' => 'Contoh: X RPL 1 atau XI TKJ 2.'],
            ['nama' => 'tingkat', 'wajib' => false, 'deskripsi' => '10, 11, atau 12. Bisa dideteksi otomatis dari nama kelas.'],
            ['nama' => 'kode_jurusan', 'wajib' => false, 'deskripsi' => 'Kode jurusan, misalnya RPL atau TKJ. Dibuat otomatis jika belum ada.'],
            ['nama' => 'tahun_ajaran', 'wajib' => false, 'deskripsi' => 'Contoh: 2026/2027. Jika kosong, memakai pilihan tahun ajaran di form.'],
            ['nama' => 'wali_kelas', 'wajib' => false, 'deskripsi' => 'Username atau nama guru wali kelas.'],
        ];

        $kolomJurusan = [
            ['nama' => 'kode', 'wajib' => true, 'deskripsi' => 'Singkatan jurusan, misalnya RPL, TKJ, atau AKL.'],
            ['nama' => 'nama', 'wajib' => true, 'deskripsi' => 'Nama lengkap kompetensi keahlian.'],
            ['nama' => 'deskripsi', 'wajib' => false, 'deskripsi' => 'Penjelasan singkat kompetensi keahlian (opsional).'],
        ];

        $tabAktifClass = 'bg-white text-slate-900 shadow-sm ring-1 ring-slate-900/5 dark:bg-gray-800 dark:text-white dark:ring-white/10';
        $tabNonAktifClass = 'text-slate-600 hover:bg-white/70 hover:text-slate-900 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-white';
        $tabIkonAktifClass = 'bg-primary-50 text-primary-600 ring-1 ring-primary-100 dark:bg-primary-500/15 dark:text-primary-400 dark:ring-primary-500/20';
        $tabIkonNonAktifClass = 'bg-white/80 text-slate-500 ring-1 ring-slate-200/70 dark:bg-white/5 dark:text-gray-400 dark:ring-white/10';
    @endphp

    <div class="impor-master space-y-5">
        {{-- Navigasi tab --}}
        <div class="rounded-xl border border-slate-200/80 bg-slate-100/80 p-1.5 shadow-xs dark:border-white/10 dark:bg-white/[0.04]">
            <nav
                class="impor-tabs-scroll flex min-w-0 gap-1.5 overflow-x-auto"
                role="tablist"
                aria-label="Jenis data master"
                x-on:keydown.arrow-right.prevent="$event.target.nextElementSibling?.focus()"
                x-on:keydown.arrow-left.prevent="$event.target.previousElementSibling?.focus()"
                x-on:keydown.home.prevent="$event.target.parentElement.firstElementChild?.focus()"
                x-on:keydown.end.prevent="$event.target.parentElement.lastElementChild?.focus()"
            >
                <button
                    type="button"
                    id="tab-tarif"
                    role="tab"
                    aria-selected="{{ $tabAktif === 'tarif' ? 'true' : 'false' }}"
                    aria-controls="panel-tarif"
                    tabindex="{{ $tabAktif === 'tarif' ? '0' : '-1' }}"
                    wire:click="setTab('tarif')"
                    class="group flex min-w-[15rem] flex-1 items-center gap-3 rounded-lg px-3 py-2.5 text-left transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-500 {{ $tabAktif === 'tarif' ? $tabAktifClass : $tabNonAktifClass }}"
                >
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-lg {{ $tabAktif === 'tarif' ? $tabIkonAktifClass : $tabIkonNonAktifClass }}">
                        <x-heroicon-o-banknotes class="size-5" aria-hidden="true" />
                    </span>

                    <span class="min-w-0">
                        <span class="block whitespace-nowrap text-sm font-semibold">Impor Tarif</span>
                        <span class="mt-0.5 block whitespace-nowrap text-[11px] font-normal text-slate-500 dark:text-gray-400">SPP &amp; biaya lainnya</span>
                    </span>
                </button>

                <button
                    type="button"
                    id="tab-kelas"
                    role="tab"
                    aria-selected="{{ $tabAktif === 'kelas' ? 'true' : 'false' }}"
                    aria-controls="panel-kelas"
                    tabindex="{{ $tabAktif === 'kelas' ? '0' : '-1' }}"
                    wire:click="setTab('kelas')"
                    class="group flex min-w-[15rem] flex-1 items-center gap-3 rounded-lg px-3 py-2.5 text-left transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-500 {{ $tabAktif === 'kelas' ? $tabAktifClass : $tabNonAktifClass }}"
                >
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-lg {{ $tabAktif === 'kelas' ? $tabIkonAktifClass : $tabIkonNonAktifClass }}">
                        <x-heroicon-o-building-office-2 class="size-5" aria-hidden="true" />
                    </span>

                    <span class="min-w-0">
                        <span class="block whitespace-nowrap text-sm font-semibold">Impor Kelas</span>
                        <span class="mt-0.5 block whitespace-nowrap text-[11px] font-normal text-slate-500 dark:text-gray-400">Rombel &amp; wali kelas</span>
                    </span>
                </button>

                <button
                    type="button"
                    id="tab-jurusan"
                    role="tab"
                    aria-selected="{{ $tabAktif === 'jurusan' ? 'true' : 'false' }}"
                    aria-controls="panel-jurusan"
                    tabindex="{{ $tabAktif === 'jurusan' ? '0' : '-1' }}"
                    wire:click="setTab('jurusan')"
                    class="group flex min-w-[15rem] flex-1 items-center gap-3 rounded-lg px-3 py-2.5 text-left transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-500 {{ $tabAktif === 'jurusan' ? $tabAktifClass : $tabNonAktifClass }}"
                >
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-lg {{ $tabAktif === 'jurusan' ? $tabIkonAktifClass : $tabIkonNonAktifClass }}">
                        <x-heroicon-o-academic-cap class="size-5" aria-hidden="true" />
                    </span>

                    <span class="min-w-0">
                        <span class="block whitespace-nowrap text-sm font-semibold">Impor Jurusan</span>
                        <span class="mt-0.5 block whitespace-nowrap text-[11px] font-normal text-slate-500 dark:text-gray-400">Kompetensi keahlian</span>
                    </span>
                </button>
            </nav>
        </div>

        {{-- TAB 1: IMPOR TARIF --}}
        @if ($tabAktif === 'tarif')
            <div id="panel-tarif" role="tabpanel" aria-labelledby="tab-tarif" tabindex="0" class="space-y-5 focus-visible:outline-none">
                @if ($hasilTarif)
                    <x-impor.hasil
                        judul="Hasil Impor Tarif"
                        :hasil="$hasilTarif"
                        properti="hasilTarif"
                    />
                @endif

                <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
                    <div class="fi-section rounded-xl border border-slate-200/80 bg-white p-5 shadow-xs dark:border-white/10 dark:bg-gray-900 lg:col-span-2">
                        <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-200/80 pb-4 dark:border-white/10">
                            <div class="flex items-start gap-3">
                                <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-primary-50 text-primary-600 ring-1 ring-primary-100 dark:bg-primary-500/10 dark:text-primary-400 dark:ring-primary-500/20">
                                    <x-heroicon-o-banknotes class="size-5" aria-hidden="true" />
                                </span>

                                <div>
                                    <h3 class="text-base font-semibold text-slate-900 dark:text-white">Impor Tarif SPP &amp; Biaya</h3>
                                    <p class="mt-0.5 text-xs leading-relaxed text-slate-500 dark:text-gray-400">
                                        Unggah berkas tarif untuk menambah atau memperbarui data tarif SPP dan biaya lainnya.
                                    </p>
                                </div>
                            </div>

                            <x-filament::button tag="a" href="{{ route('impor.template.tarif') }}" color="gray" size="sm" icon="heroicon-o-arrow-down-tray">
                                Unduh Template
                            </x-filament::button>
                        </div>

                        <form wire:submit="prosesImporTarif" class="mt-5 space-y-4">
                            <x-impor.unggah-berkas
                                model="berkasTarif"
                                label="berkas tarif"
                                :berkas="$berkasTarif"
                                :selesai="(bool) $hasilTarif"
                            />

                            <div class="flex items-start gap-2 rounded-lg border border-primary-100 bg-primary-50/50 p-3 dark:border-primary-500/20 dark:bg-primary-500/5">
                                <x-heroicon-o-information-circle class="mt-0.5 size-4 shrink-0 text-primary-500 dark:text-primary-400" aria-hidden="true" />
                                <p class="text-[11px] leading-relaxed text-primary-800 dark:text-primary-300">
                                    Tarif dengan kombinasi <strong>kategori</strong>, <strong>nama</strong>, <strong>tahun ajaran</strong>, dan <strong>tingkat</strong> yang sama akan diperbarui otomatis, bukan diduplikasi.
                                </p>
                            </div>

                            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200/80 pt-4 dark:border-white/10">
                                <p class="text-[11px] text-slate-500 dark:text-gray-400">
                                    Pastikan berkas sudah sesuai template sebelum diimpor.
                                </p>

                                <x-filament::button type="submit" color="primary" icon="heroicon-o-arrow-up-tray" :loading-indicator="false" class="w-full sm:w-auto" wire:loading.attr="disabled" wire:target="prosesImporTarif">
                                    <span wire:loading.remove wire:target="prosesImporTarif">Impor Tarif Sekarang</span>
                                    <span wire:loading wire:target="prosesImporTarif" class="inline-flex items-center gap-2">
                                        <x-heroicon-o-arrow-path class="size-4 animate-spin motion-reduce:animate-none" aria-hidden="true" />
                                        Memproses...
                                    </span>
                                </x-filament::button>
                            </div>
                        </form>
                    </div>

                    <x-impor.panduan-kolom
                        :kolom="$kolomTarif"
                        catatan="Baris pertama harus berisi header kolom. Baris kosong diabaikan, dan nominal harus berupa angka."
                    />
                </div>
            </div>
        @endif

        {{-- TAB 2: IMPOR KELAS --}}
        @if ($tabAktif === 'kelas')
            <div id="panel-kelas" role="tabpanel" aria-labelledby="tab-kelas" tabindex="0" class="space-y-5 focus-visible:outline-none">
                @if ($hasilKelas)
                    <x-impor.hasil
                        judul="Hasil Impor Kelas"
                        :hasil="$hasilKelas"
                        properti="hasilKelas"
                    />
                @endif

                <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
                    <div class="fi-section rounded-xl border border-slate-200/80 bg-white p-5 shadow-xs dark:border-white/10 dark:bg-gray-900 lg:col-span-2">
                        <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-200/80 pb-4 dark:border-white/10">
                            <div class="flex items-start gap-3">
                                <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-primary-50 text-primary-600 ring-1 ring-primary-100 dark:bg-primary-500/10 dark:text-primary-400 dark:ring-primary-500/20">
                                    <x-heroicon-o-building-office-2 class="size-5" aria-hidden="true" />
                                </span>

                                <div>
                                    <h3 class="text-base font-semibold text-slate-900 dark:text-white">Impor Data Kelas</h3>
                                    <p class="mt-0.5 text-xs leading-relaxed text-slate-500 dark:text-gray-400">
                                        Unggah berkas data kelas beserta jurusan dan wali kelasnya.
                                    </p>
                                </div>
                            </div>

                            <x-filament::button tag="a" href="{{ route('impor.template.kelas') }}" color="gray" size="sm" icon="heroicon-o-arrow-down-tray">
                                Unduh Template
                            </x-filament::button>
                        </div>

                        <form wire:submit="prosesImporKelas" class="mt-5 space-y-4">
                            <div>
                                <label for="tahun_ajaran_id" class="mb-1 block text-xs font-medium text-slate-600 dark:text-gray-300">
                                    Tahun ajaran default
                                </label>

                                <select
                                    id="tahun_ajaran_id"
                                    wire:model="tahun_ajaran_id"
                                    class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-xs transition focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:focus:ring-primary-500/20"
                                >
                                    <option value="">Pilih tahun ajaran</option>
                                    @foreach ($tahunAjaranOptions as $id => $nama)
                                        <option value="{{ $id }}">{{ $nama }}</option>
                                    @endforeach
                                </select>

                                <p class="mt-1 text-[11px] leading-relaxed text-slate-500 dark:text-gray-400">
                                    Digunakan jika kolom <code class="rounded bg-slate-100 px-1 py-0.5 font-mono text-[10px] text-slate-700 dark:bg-white/5 dark:text-gray-300">tahun_ajaran</code> pada berkas dikosongkan.
                                </p>

                                @error('tahun_ajaran_id')
                                    <p class="mt-1 flex items-start gap-1.5 text-xs text-rose-600 dark:text-rose-400" aria-live="polite">
                                        <x-heroicon-o-exclamation-circle class="mt-0.5 size-3.5 shrink-0" aria-hidden="true" />
                                        <span>{{ $message }}</span>
                                    </p>
                                @enderror
                            </div>

                            @if ($tahunAjaranOptions === [])
                                <div class="flex items-start gap-2 rounded-lg border border-amber-200/80 bg-amber-50/70 p-3 dark:border-amber-500/20 dark:bg-amber-500/10">
                                    <x-heroicon-o-exclamation-triangle class="mt-0.5 size-4 shrink-0 text-amber-600 dark:text-amber-400" aria-hidden="true" />

                                    <div class="text-xs leading-relaxed text-amber-800 dark:text-amber-300">
                                        <p class="font-semibold">Belum ada tahun ajaran.</p>
                                        <p class="mt-0.5">Tambahkan tahun ajaran terlebih dahulu agar data kelas dapat disimpan.</p>

                                        <x-filament::button tag="a" href="{{ \App\Filament\Resources\TahunAjarans\TahunAjaranResource::getUrl('index') }}" color="warning" size="sm" class="mt-2">
                                            Kelola Tahun Ajaran
                                        </x-filament::button>
                                    </div>
                                </div>
                            @endif

                            <x-impor.unggah-berkas
                                model="berkasKelas"
                                label="berkas kelas"
                                :berkas="$berkasKelas"
                                :selesai="(bool) $hasilKelas"
                            />

                            <div class="flex items-start gap-2 rounded-lg border border-primary-100 bg-primary-50/50 p-3 dark:border-primary-500/20 dark:bg-primary-500/5">
                                <x-heroicon-o-information-circle class="mt-0.5 size-4 shrink-0 text-primary-500 dark:text-primary-400" aria-hidden="true" />
                                <p class="text-[11px] leading-relaxed text-primary-800 dark:text-primary-300">
                                    Kelas dengan <strong>nama</strong> dan <strong>tahun ajaran</strong> yang sama akan diperbarui. Wali kelas hanya diisi jika username atau nama guru ditemukan.
                                </p>
                            </div>

                            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200/80 pt-4 dark:border-white/10">
                                <p class="text-[11px] text-slate-500 dark:text-gray-400">
                                    Pastikan tahun ajaran tujuan sudah benar.
                                </p>

                                <x-filament::button type="submit" color="primary" icon="heroicon-o-arrow-up-tray" :loading-indicator="false" :disabled="$tahunAjaranOptions === []" class="w-full sm:w-auto" wire:loading.attr="disabled" wire:target="prosesImporKelas">
                                    <span wire:loading.remove wire:target="prosesImporKelas">Impor Kelas Sekarang</span>
                                    <span wire:loading wire:target="prosesImporKelas" class="inline-flex items-center gap-2">
                                        <x-heroicon-o-arrow-path class="size-4 animate-spin motion-reduce:animate-none" aria-hidden="true" />
                                        Memproses...
                                    </span>
                                </x-filament::button>
                            </div>
                        </form>
                    </div>

                    <x-impor.panduan-kolom
                        :kolom="$kolomKelas"
                        catatan="Baris pertama harus berisi header kolom. Tahun ajaran default di form digunakan jika kolom tahun_ajaran kosong."
                    />
                </div>
            </div>
        @endif

        {{-- TAB 3: IMPOR JURUSAN --}}
        @if ($tabAktif === 'jurusan')
            <div id="panel-jurusan" role="tabpanel" aria-labelledby="tab-jurusan" tabindex="0" class="space-y-5 focus-visible:outline-none">
                @if ($hasilJurusan)
                    <x-impor.hasil
                        judul="Hasil Impor Jurusan"
                        :hasil="$hasilJurusan"
                        properti="hasilJurusan"
                    />
                @endif

                <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
                    <div class="fi-section rounded-xl border border-slate-200/80 bg-white p-5 shadow-xs dark:border-white/10 dark:bg-gray-900 lg:col-span-2">
                        <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-200/80 pb-4 dark:border-white/10">
                            <div class="flex items-start gap-3">
                                <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-primary-50 text-primary-600 ring-1 ring-primary-100 dark:bg-primary-500/10 dark:text-primary-400 dark:ring-primary-500/20">
                                    <x-heroicon-o-academic-cap class="size-5" aria-hidden="true" />
                                </span>

                                <div>
                                    <h3 class="text-base font-semibold text-slate-900 dark:text-white">Impor Jurusan</h3>
                                    <p class="mt-0.5 text-xs leading-relaxed text-slate-500 dark:text-gray-400">
                                        Unggah berkas kompetensi keahlian untuk menambah atau memperbarui data jurusan.
                                    </p>
                                </div>
                            </div>

                            <x-filament::button tag="a" href="{{ route('impor.template.jurusan') }}" color="gray" size="sm" icon="heroicon-o-arrow-down-tray">
                                Unduh Template
                            </x-filament::button>
                        </div>

                        <form wire:submit="prosesImporJurusan" class="mt-5 space-y-4">
                            <x-impor.unggah-berkas
                                model="berkasJurusan"
                                label="berkas jurusan"
                                :berkas="$berkasJurusan"
                                :selesai="(bool) $hasilJurusan"
                            />

                            <div class="flex items-start gap-2 rounded-lg border border-primary-100 bg-primary-50/50 p-3 dark:border-primary-500/20 dark:bg-primary-500/5">
                                <x-heroicon-o-information-circle class="mt-0.5 size-4 shrink-0 text-primary-500 dark:text-primary-400" aria-hidden="true" />
                                <p class="text-[11px] leading-relaxed text-primary-800 dark:text-primary-300">
                                    Jurusan dengan <strong>kode</strong> yang sama akan diperbarui otomatis, bukan diduplikasi.
                                </p>
                            </div>

                            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200/80 pt-4 dark:border-white/10">
                                <p class="text-[11px] text-slate-500 dark:text-gray-400">
                                    Kode jurusan akan disimpan dalam huruf kapital.
                                </p>

                                <x-filament::button type="submit" color="primary" icon="heroicon-o-arrow-up-tray" :loading-indicator="false" class="w-full sm:w-auto" wire:loading.attr="disabled" wire:target="prosesImporJurusan">
                                    <span wire:loading.remove wire:target="prosesImporJurusan">Impor Jurusan Sekarang</span>
                                    <span wire:loading wire:target="prosesImporJurusan" class="inline-flex items-center gap-2">
                                        <x-heroicon-o-arrow-path class="size-4 animate-spin motion-reduce:animate-none" aria-hidden="true" />
                                        Memproses...
                                    </span>
                                </x-filament::button>
                            </div>
                        </form>
                    </div>

                    <x-impor.panduan-kolom
                        :kolom="$kolomJurusan"
                        catatan="Baris pertama harus berisi header kolom. Kode jurusan disimpan dalam huruf kapital."
                    />
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>

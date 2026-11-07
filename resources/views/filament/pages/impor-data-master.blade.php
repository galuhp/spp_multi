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

        $tabs = [
            [
                'id' => 'tarif',
                'label' => 'Impor Tarif',
                'deskripsi' => 'SPP & biaya lainnya',
                'ikon' => 'heroicon-o-banknotes',
            ],
            [
                'id' => 'kelas',
                'label' => 'Impor Kelas',
                'deskripsi' => 'Rombel & wali kelas',
                'ikon' => 'heroicon-o-building-office-2',
            ],
            [
                'id' => 'jurusan',
                'label' => 'Impor Jurusan',
                'deskripsi' => 'Kompetensi keahlian',
                'ikon' => 'heroicon-o-academic-cap',
            ],
        ];

        $tabAktifClass = 'bg-white text-slate-900 shadow-sm ring-1 ring-slate-900/5 dark:bg-gray-800 dark:text-white dark:ring-white/10';
        $tabNonAktifClass = 'text-slate-600 hover:bg-white/70 hover:text-slate-900 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-white';
        $tabIkonAktifClass = 'bg-primary-50 text-primary-600 ring-1 ring-primary-100 dark:bg-primary-500/15 dark:text-primary-400 dark:ring-primary-500/20';
        $tabIkonNonAktifClass = 'bg-white/80 text-slate-500 ring-1 ring-slate-200/70 dark:bg-white/5 dark:text-gray-400 dark:ring-white/10';
    @endphp

    <div class="impor-master space-y-5">
        {{-- Navigasi tab --}}
        <div class="rounded-2xl border border-slate-200/80 bg-slate-100/80 p-1.5 shadow-xs dark:border-white/10 dark:bg-white/[0.04]">
            <nav
                class="grid grid-cols-3 gap-1.5"
                role="tablist"
                aria-label="Jenis data master"
                aria-orientation="horizontal"
                x-data="{
                    fokusTab(indeks) {
                        const tabs = [...$el.querySelectorAll('[role=tab]')];

                        if (! tabs.length) {
                            return;
                        }

                        tabs[(indeks + tabs.length) % tabs.length].focus();
                    },
                }"
                x-on:keydown.home.prevent="fokusTab(0)"
                x-on:keydown.end.prevent="fokusTab(-1)"
            >
                @foreach ($tabs as $tab)
                    @php
                        $aktif = $tabAktif === $tab['id'];
                    @endphp

                    <button
                        type="button"
                        id="tab-{{ $tab['id'] }}"
                        role="tab"
                        aria-selected="{{ $aktif ? 'true' : 'false' }}"
                        aria-controls="panel-{{ $tab['id'] }}"
                        tabindex="{{ $aktif ? '0' : '-1' }}"
                        wire:key="tab-{{ $tab['id'] }}"
                        wire:click="setTab('{{ $tab['id'] }}')"
                        x-on:keydown.arrow-right.prevent="fokusTab({{ $loop->index + 1 }})"
                        x-on:keydown.arrow-left.prevent="fokusTab({{ $loop->index - 1 }})"
                        class="group flex min-w-0 flex-col items-center justify-center gap-1.5 rounded-xl px-2 py-2.5 text-center transition-colors duration-150 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-500 sm:flex-row sm:justify-start sm:gap-3 sm:px-3.5 sm:text-left {{ $aktif ? $tabAktifClass : $tabNonAktifClass }}"
                    >
                        <span class="flex size-8 shrink-0 items-center justify-center rounded-lg transition-colors sm:size-9 {{ $aktif ? $tabIkonAktifClass : $tabIkonNonAktifClass }}">
                            <x-dynamic-component :component="$tab['ikon']" class="size-5" aria-hidden="true" />
                        </span>

                        <span class="min-w-0">
                            <span class="block truncate text-[11px] font-semibold leading-tight sm:text-sm">{{ $tab['label'] }}</span>
                            <span class="mt-0.5 hidden truncate text-[11px] font-normal text-slate-600 lg:block dark:text-gray-400">{{ $tab['deskripsi'] }}</span>
                        </span>
                    </button>
                @endforeach
            </nav>
        </div>

        {{-- TAB 1: IMPOR TARIF --}}
        <div id="panel-tarif" role="tabpanel" aria-labelledby="tab-tarif" class="space-y-5 {{ $tabAktif === 'tarif' ? '' : 'hidden' }}">
            @if ($hasilTarif)
                <x-impor.hasil
                    wire:key="hasil-tarif-{{ $hasilTarif['berhasil'] }}-{{ $hasilTarif['gagal'] }}"
                    judul="Hasil Impor Tarif"
                    :hasil="$hasilTarif"
                    properti="hasilTarif"
                />
            @endif

            <div class="grid grid-cols-1 gap-5 xl:grid-cols-[minmax(0,1fr)_22rem]">
                <x-impor.kartu-impor
                    judul="Impor Tarif SPP & Biaya"
                    deskripsi="Unggah berkas tarif untuk menambah atau memperbarui data tarif SPP dan biaya lainnya."
                    ikon="heroicon-o-banknotes"
                    :template-url="route('impor.template.tarif')"
                >
                    <form wire:submit="prosesImporTarif" class="space-y-4">
                        <x-impor.unggah-berkas
                            model="berkasTarif"
                            label="berkas tarif"
                            :berkas="$berkasTarif"
                            :selesai="(bool) $hasilTarif"
                        />

                        <div class="flex items-start gap-2.5 rounded-xl border border-primary-100 bg-primary-50/60 p-3.5 dark:border-primary-500/20 dark:bg-primary-500/5">
                            <x-heroicon-o-information-circle class="mt-0.5 size-4 shrink-0 text-primary-600 dark:text-primary-400" aria-hidden="true" />
                            <p class="text-xs leading-relaxed text-primary-900 dark:text-primary-200">
                                Tarif dengan kombinasi <strong class="font-semibold">kategori</strong>, <strong class="font-semibold">nama</strong>, <strong class="font-semibold">tahun ajaran</strong>, dan <strong class="font-semibold">tingkat</strong> yang sama akan diperbarui otomatis, bukan diduplikasi.
                            </p>
                        </div>

                        <div class="flex flex-col gap-3 border-t border-slate-200/80 pt-4 sm:flex-row sm:items-center sm:justify-between dark:border-white/10">
                            <p class="text-xs text-slate-500 dark:text-gray-400">
                                Pastikan berkas sudah sesuai template sebelum diimpor.
                            </p>

                            <x-filament::button
                                type="submit"
                                color="primary"
                                icon="heroicon-o-arrow-up-tray"
                                :loading-indicator="false"
                                class="w-full sm:w-auto"
                                wire:loading.attr="disabled"
                                wire:target="prosesImporTarif, berkasTarif"
                            >
                                <span wire:loading.remove wire:target="prosesImporTarif">Impor Tarif Sekarang</span>
                                <span wire:loading wire:target="prosesImporTarif" class="inline-flex items-center gap-2">
                                    <x-heroicon-o-arrow-path class="size-4 animate-spin motion-reduce:animate-none" aria-hidden="true" />
                                    Memproses...
                                </span>
                            </x-filament::button>
                        </div>
                    </form>
                </x-impor.kartu-impor>

                <x-impor.panduan-kolom
                    :kolom="$kolomTarif"
                    catatan="Baris pertama harus berisi header kolom. Baris kosong diabaikan, dan nominal harus berupa angka."
                />
            </div>
        </div>

        {{-- TAB 2: IMPOR KELAS --}}
        <div id="panel-kelas" role="tabpanel" aria-labelledby="tab-kelas" class="space-y-5 {{ $tabAktif === 'kelas' ? '' : 'hidden' }}">
            @if ($hasilKelas)
                <x-impor.hasil
                    wire:key="hasil-kelas-{{ $hasilKelas['berhasil'] }}-{{ $hasilKelas['gagal'] }}"
                    judul="Hasil Impor Kelas"
                    :hasil="$hasilKelas"
                    properti="hasilKelas"
                />
            @endif

            <div class="grid grid-cols-1 gap-5 xl:grid-cols-[minmax(0,1fr)_22rem]">
                <x-impor.kartu-impor
                    judul="Impor Data Kelas"
                    deskripsi="Unggah berkas data kelas beserta jurusan dan wali kelasnya."
                    ikon="heroicon-o-building-office-2"
                    :template-url="route('impor.template.kelas')"
                >
                    <form wire:submit="prosesImporKelas" class="space-y-4">
                        <div>
                            <label for="tahun_ajaran_id" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-gray-200">
                                Tahun ajaran default
                            </label>

                            <div class="relative">
                                <select
                                    id="tahun_ajaran_id"
                                    wire:model="tahun_ajaran_id"
                                    @class([
                                        'w-full appearance-none rounded-lg border bg-white px-3 py-2 pr-9 text-sm text-slate-900 shadow-xs transition focus:outline-none focus:ring-2 dark:bg-gray-800 dark:text-white',
                                        'border-slate-300 focus:border-primary-500 focus:ring-primary-200 dark:border-gray-700 dark:focus:ring-primary-500/20' => ! $errors->has('tahun_ajaran_id'),
                                        'border-rose-300 focus:border-rose-500 focus:ring-rose-200 dark:border-rose-500/50 dark:focus:ring-rose-500/20' => $errors->has('tahun_ajaran_id'),
                                    ])
                                    @if ($errors->has('tahun_ajaran_id')) aria-invalid="true" @endif
                                    aria-describedby="tahun_ajaran_id-hint{{ $errors->has('tahun_ajaran_id') ? ' tahun_ajaran_id-error' : '' }}"
                                >
                                    <option value="">Pilih tahun ajaran</option>
                                    @foreach ($tahunAjaranOptions as $id => $nama)
                                        <option value="{{ $id }}">{{ $nama }}</option>
                                    @endforeach
                                </select>

                                <x-heroicon-o-chevron-down class="pointer-events-none absolute top-1/2 right-3 size-4 -translate-y-1/2 text-slate-500 dark:text-gray-400" aria-hidden="true" />
                            </div>

                            <p id="tahun_ajaran_id-hint" class="mt-1.5 text-xs leading-relaxed text-slate-500 dark:text-gray-400">
                                Digunakan jika kolom <code class="rounded-md bg-slate-100 px-1.5 py-0.5 font-mono text-[11px] text-slate-700 dark:bg-white/5 dark:text-gray-300">tahun_ajaran</code> pada berkas dikosongkan.
                            </p>

                            @error('tahun_ajaran_id')
                                <p id="tahun_ajaran_id-error" class="mt-1.5 flex items-start gap-1.5 text-xs text-rose-600 dark:text-rose-400" aria-live="polite">
                                    <x-heroicon-o-exclamation-circle class="mt-0.5 size-3.5 shrink-0" aria-hidden="true" />
                                    <span>{{ $message }}</span>
                                </p>
                            @enderror
                        </div>

                        @if ($tahunAjaranOptions === [])
                            <div class="flex items-start gap-2.5 rounded-xl border border-amber-200/80 bg-amber-50/70 p-3.5 dark:border-amber-500/20 dark:bg-amber-500/10">
                                <x-heroicon-o-exclamation-triangle class="mt-0.5 size-4 shrink-0 text-amber-600 dark:text-amber-400" aria-hidden="true" />

                                <div class="text-xs leading-relaxed text-amber-800 dark:text-amber-300">
                                    <p class="font-semibold">Belum ada tahun ajaran.</p>
                                    <p class="mt-0.5">Tambahkan tahun ajaran terlebih dahulu agar data kelas dapat disimpan.</p>

                                    <x-filament::button tag="a" href="{{ \App\Filament\Resources\TahunAjarans\TahunAjaranResource::getUrl('index') }}" color="warning" size="sm" class="mt-2.5">
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

                        <div class="flex items-start gap-2.5 rounded-xl border border-primary-100 bg-primary-50/60 p-3.5 dark:border-primary-500/20 dark:bg-primary-500/5">
                            <x-heroicon-o-information-circle class="mt-0.5 size-4 shrink-0 text-primary-600 dark:text-primary-400" aria-hidden="true" />
                            <p class="text-xs leading-relaxed text-primary-900 dark:text-primary-200">
                                Kelas dengan <strong class="font-semibold">nama</strong> dan <strong class="font-semibold">tahun ajaran</strong> yang sama akan diperbarui. Wali kelas hanya diisi jika username atau nama guru ditemukan.
                            </p>
                        </div>

                        <div class="flex flex-col gap-3 border-t border-slate-200/80 pt-4 sm:flex-row sm:items-center sm:justify-between dark:border-white/10">
                            <p class="text-xs text-slate-500 dark:text-gray-400">
                                Pastikan tahun ajaran tujuan sudah benar.
                            </p>

                            <x-filament::button
                                type="submit"
                                color="primary"
                                icon="heroicon-o-arrow-up-tray"
                                :loading-indicator="false"
                                :disabled="$tahunAjaranOptions === []"
                                class="w-full sm:w-auto"
                                wire:loading.attr="disabled"
                                wire:target="prosesImporKelas, berkasKelas"
                            >
                                <span wire:loading.remove wire:target="prosesImporKelas">Impor Kelas Sekarang</span>
                                <span wire:loading wire:target="prosesImporKelas" class="inline-flex items-center gap-2">
                                    <x-heroicon-o-arrow-path class="size-4 animate-spin motion-reduce:animate-none" aria-hidden="true" />
                                    Memproses...
                                </span>
                            </x-filament::button>
                        </div>
                    </form>
                </x-impor.kartu-impor>

                <x-impor.panduan-kolom
                    :kolom="$kolomKelas"
                    catatan="Baris pertama harus berisi header kolom. Tahun ajaran default di form digunakan jika kolom tahun_ajaran kosong."
                />
            </div>
        </div>

        {{-- TAB 3: IMPOR JURUSAN --}}
        <div id="panel-jurusan" role="tabpanel" aria-labelledby="tab-jurusan" class="space-y-5 {{ $tabAktif === 'jurusan' ? '' : 'hidden' }}">
            @if ($hasilJurusan)
                <x-impor.hasil
                    wire:key="hasil-jurusan-{{ $hasilJurusan['berhasil'] }}-{{ $hasilJurusan['gagal'] }}"
                    judul="Hasil Impor Jurusan"
                    :hasil="$hasilJurusan"
                    properti="hasilJurusan"
                />
            @endif

            <div class="grid grid-cols-1 gap-5 xl:grid-cols-[minmax(0,1fr)_22rem]">
                <x-impor.kartu-impor
                    judul="Impor Jurusan"
                    deskripsi="Unggah berkas kompetensi keahlian untuk menambah atau memperbarui data jurusan."
                    ikon="heroicon-o-academic-cap"
                    :template-url="route('impor.template.jurusan')"
                >
                    <form wire:submit="prosesImporJurusan" class="space-y-4">
                        <x-impor.unggah-berkas
                            model="berkasJurusan"
                            label="berkas jurusan"
                            :berkas="$berkasJurusan"
                            :selesai="(bool) $hasilJurusan"
                        />

                        <div class="flex items-start gap-2.5 rounded-xl border border-primary-100 bg-primary-50/60 p-3.5 dark:border-primary-500/20 dark:bg-primary-500/5">
                            <x-heroicon-o-information-circle class="mt-0.5 size-4 shrink-0 text-primary-600 dark:text-primary-400" aria-hidden="true" />
                            <p class="text-xs leading-relaxed text-primary-900 dark:text-primary-200">
                                Jurusan dengan <strong class="font-semibold">kode</strong> yang sama akan diperbarui otomatis, bukan diduplikasi.
                            </p>
                        </div>

                        <div class="flex flex-col gap-3 border-t border-slate-200/80 pt-4 sm:flex-row sm:items-center sm:justify-between dark:border-white/10">
                            <p class="text-xs text-slate-500 dark:text-gray-400">
                                Kode jurusan akan disimpan dalam huruf kapital.
                            </p>

                            <x-filament::button
                                type="submit"
                                color="primary"
                                icon="heroicon-o-arrow-up-tray"
                                :loading-indicator="false"
                                class="w-full sm:w-auto"
                                wire:loading.attr="disabled"
                                wire:target="prosesImporJurusan, berkasJurusan"
                            >
                                <span wire:loading.remove wire:target="prosesImporJurusan">Impor Jurusan Sekarang</span>
                                <span wire:loading wire:target="prosesImporJurusan" class="inline-flex items-center gap-2">
                                    <x-heroicon-o-arrow-path class="size-4 animate-spin motion-reduce:animate-none" aria-hidden="true" />
                                    Memproses...
                                </span>
                            </x-filament::button>
                        </div>
                    </form>
                </x-impor.kartu-impor>

                <x-impor.panduan-kolom
                    :kolom="$kolomJurusan"
                    catatan="Baris pertama harus berisi header kolom. Kode jurusan disimpan dalam huruf kapital."
                />
            </div>
        </div>
    </div>
</x-filament-panels::page>

<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Navigation Tabs --}}
        <div class="border-b border-gray-200 dark:border-gray-800">
            <nav class="-mb-px flex space-x-6" aria-label="Tabs">
                <button
                    type="button"
                    wire:click="setTab('tarif')"
                    class="whitespace-nowrap pb-3 px-1 border-b-2 font-medium text-sm transition-colors {{ $tabAktif === 'tarif' ? 'border-primary-600 text-primary-600 dark:border-primary-400 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}"
                >
                    <span class="flex items-center gap-2">
                        <x-heroicon-o-banknotes class="w-5 h-5" />
                        Impor Tarif SPP & Biaya
                    </span>
                </button>

                <button
                    type="button"
                    wire:click="setTab('kelas')"
                    class="whitespace-nowrap pb-3 px-1 border-b-2 font-medium text-sm transition-colors {{ $tabAktif === 'kelas' ? 'border-primary-600 text-primary-600 dark:border-primary-400 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}"
                >
                    <span class="flex items-center gap-2">
                        <x-heroicon-o-building-office-2 class="w-5 h-5" />
                        Impor Data Kelas
                    </span>
                </button>

                <button
                    type="button"
                    wire:click="setTab('jurusan')"
                    class="whitespace-nowrap pb-3 px-1 border-b-2 font-medium text-sm transition-colors {{ $tabAktif === 'jurusan' ? 'border-primary-600 text-primary-600 dark:border-primary-400 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}"
                >
                    <span class="flex items-center gap-2">
                        <x-heroicon-o-academic-cap class="w-5 h-5" />
                        Impor Jurusan (Kompetensi Keahlian)
                    </span>
                </button>
            </nav>
        </div>

        {{-- TAB 1: IMPOR TARIF --}}
        @if ($tabAktif === 'tarif')
            <div class="space-y-6">
                @if ($hasilTarif)
                    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-xs dark:border-gray-800 dark:bg-gray-900">
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">Hasil Impor Tarif</h3>
                        <div class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-4">
                            <div class="rounded-lg border border-gray-100 bg-gray-50/50 p-3 dark:border-gray-800 dark:bg-gray-800/40">
                                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Total Diproses</p>
                                <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white tabular-nums">{{ $hasilTarif['berhasil'] }}</p>
                            </div>
                            <div class="rounded-lg border border-emerald-100 bg-emerald-50/50 p-3 dark:border-emerald-900/30 dark:bg-emerald-950/20">
                                <p class="text-xs font-medium text-emerald-700 dark:text-emerald-400">Tarif Baru</p>
                                <p class="mt-1 text-2xl font-bold text-emerald-700 dark:text-emerald-400 tabular-nums">{{ $hasilTarif['baru'] }}</p>
                            </div>
                            <div class="rounded-lg border border-sky-100 bg-sky-50/50 p-3 dark:border-sky-900/30 dark:bg-sky-950/20">
                                <p class="text-xs font-medium text-sky-700 dark:text-sky-400">Diperbarui</p>
                                <p class="mt-1 text-2xl font-bold text-sky-700 dark:text-sky-400 tabular-nums">{{ $hasilTarif['diperbarui'] }}</p>
                            </div>
                            <div class="rounded-lg border border-rose-100 bg-rose-50/50 p-3 dark:border-rose-900/30 dark:bg-rose-950/20">
                                <p class="text-xs font-medium text-rose-700 dark:text-rose-400">Gagal / Dilewati</p>
                                <p class="mt-1 text-2xl font-bold text-rose-700 dark:text-rose-400 tabular-nums">{{ $hasilTarif['gagal'] }}</p>
                            </div>
                        </div>

                        @if (!empty($hasilTarif['errors']))
                            <div class="mt-4 rounded-lg bg-rose-50 p-3 text-xs text-rose-800 dark:bg-rose-950/40 dark:text-rose-300">
                                <p class="font-semibold mb-1">Catatan Kesalahan:</p>
                                <ul class="list-disc pl-4 space-y-0.5">
                                    @foreach (array_slice($hasilTarif['errors'], 0, 10) as $err)
                                        <li>{{ $err }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                @endif

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-2 rounded-xl border border-gray-200 bg-white p-6 shadow-xs dark:border-gray-800 dark:bg-gray-900">
                        <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-800">
                            <div>
                                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Formulir Impor Tarif</h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Unggah berkas spreadsheet tarif SPP & Non-SPP</p>
                            </div>
                            <x-filament::button tag="a" href="{{ route('impor.template.tarif') }}" color="gray" size="sm" icon="heroicon-o-arrow-down-tray">
                                Unduh Template
                            </x-filament::button>
                        </div>

                        <form wire:submit="prosesImporTarif" class="mt-5 space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Pilih Berkas Excel / CSV <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    type="file"
                                    wire:model="berkasTarif"
                                    accept=".xlsx,.xls,.csv"
                                    class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100 dark:file:bg-primary-950/50 dark:file:text-primary-400 border border-gray-300 dark:border-gray-700 rounded-lg p-2 dark:bg-gray-800"
                                />
                                @error('berkasTarif') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <div class="pt-2 flex items-center justify-end">
                                <x-filament::button type="submit" color="primary" icon="heroicon-o-arrow-up-tray" wire:loading.attr="disabled">
                                    <span wire:loading.remove wire:target="prosesImporTarif">Impor Tarif Sekarang</span>
                                    <span wire:loading wire:target="prosesImporTarif">Memproses Berkas...</span>
                                </x-filament::button>
                            </div>
                        </form>
                    </div>

                    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-xs dark:border-gray-800 dark:bg-gray-900">
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-2">Panduan Kolom Template:</h4>
                        <ul class="text-xs space-y-2 text-gray-600 dark:text-gray-400">
                            <li><strong class="text-gray-800 dark:text-gray-200">kategori</strong>: SPP, Biaya Ujian, Uang Seragam, Biaya Praktik, dll.</li>
                            <li><strong class="text-gray-800 dark:text-gray-200">nama</strong>: Nama tarif spesifik (mis. PTS Ganjil).</li>
                            <li><strong class="text-gray-800 dark:text-gray-200">tipe</strong>: <code class="bg-gray-100 dark:bg-gray-800 px-1 rounded">bulanan</code> atau <code class="bg-gray-100 dark:bg-gray-800 px-1 rounded">sekali_bayar</code>.</li>
                            <li><strong class="text-gray-800 dark:text-gray-200">tahun_ajaran</strong>: Contoh: 2026/2027.</li>
                            <li><strong class="text-gray-800 dark:text-gray-200">tingkat</strong>: 10, 11, 12, atau kosongkan jika berlaku untuk semua tingkat.</li>
                            <li><strong class="text-gray-800 dark:text-gray-200">nominal</strong>: Angka nominal (misal 100000).</li>
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        {{-- TAB 2: IMPOR KELAS --}}
        @if ($tabAktif === 'kelas')
            <div class="space-y-6">
                @if ($hasilKelas)
                    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-xs dark:border-gray-800 dark:bg-gray-900">
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">Hasil Impor Kelas</h3>
                        <div class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-4">
                            <div class="rounded-lg border border-gray-100 bg-gray-50/50 p-3 dark:border-gray-800 dark:bg-gray-800/40">
                                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Total Diproses</p>
                                <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white tabular-nums">{{ $hasilKelas['berhasil'] }}</p>
                            </div>
                            <div class="rounded-lg border border-emerald-100 bg-emerald-50/50 p-3 dark:border-emerald-900/30 dark:bg-emerald-950/20">
                                <p class="text-xs font-medium text-emerald-700 dark:text-emerald-400">Kelas Baru</p>
                                <p class="mt-1 text-2xl font-bold text-emerald-700 dark:text-emerald-400 tabular-nums">{{ $hasilKelas['baru'] }}</p>
                            </div>
                            <div class="rounded-lg border border-sky-100 bg-sky-50/50 p-3 dark:border-sky-900/30 dark:bg-sky-950/20">
                                <p class="text-xs font-medium text-sky-700 dark:text-sky-400">Diperbarui</p>
                                <p class="mt-1 text-2xl font-bold text-sky-700 dark:text-sky-400 tabular-nums">{{ $hasilKelas['diperbarui'] }}</p>
                            </div>
                            <div class="rounded-lg border border-rose-100 bg-rose-50/50 p-3 dark:border-rose-900/30 dark:bg-rose-950/20">
                                <p class="text-xs font-medium text-rose-700 dark:text-rose-400">Gagal / Dilewati</p>
                                <p class="mt-1 text-2xl font-bold text-rose-700 dark:text-rose-400 tabular-nums">{{ $hasilKelas['gagal'] }}</p>
                            </div>
                        </div>

                        @if (!empty($hasilKelas['errors']))
                            <div class="mt-4 rounded-lg bg-rose-50 p-3 text-xs text-rose-800 dark:bg-rose-950/40 dark:text-rose-300">
                                <p class="font-semibold mb-1">Catatan Kesalahan:</p>
                                <ul class="list-disc pl-4 space-y-0.5">
                                    @foreach (array_slice($hasilKelas['errors'], 0, 10) as $err)
                                        <li>{{ $err }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                @endif

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-2 rounded-xl border border-gray-200 bg-white p-6 shadow-xs dark:border-gray-800 dark:bg-gray-900">
                        <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-800">
                            <div>
                                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Formulir Impor Kelas</h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Unggah berkas spreadsheet data kelas</p>
                            </div>
                            <x-filament::button tag="a" href="{{ route('impor.template.kelas') }}" color="gray" size="sm" icon="heroicon-o-arrow-down-tray">
                                Unduh Template
                            </x-filament::button>
                        </div>

                        <form wire:submit="prosesImporKelas" class="mt-5 space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Tahun Ajaran Default
                                </label>
                                <select
                                    wire:model="tahun_ajaran_id"
                                    class="block w-full text-sm border-gray-300 dark:border-gray-700 rounded-lg p-2 dark:bg-gray-800 dark:text-white"
                                >
                                    @foreach (\App\Models\TahunAjaran::orderByDesc('nama')->get() as $ta)
                                        <option value="{{ $ta->id }}">{{ $ta->nama }} {{ $ta->aktif ? '(Aktif)' : '' }}</option>
                                    @endforeach
                                </select>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Digunakan jika kolom tahun_ajaran pada berkas dikosongkan.</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Pilih Berkas Excel / CSV <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    type="file"
                                    wire:model="berkasKelas"
                                    accept=".xlsx,.xls,.csv"
                                    class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100 dark:file:bg-primary-950/50 dark:file:text-primary-400 border border-gray-300 dark:border-gray-700 rounded-lg p-2 dark:bg-gray-800"
                                />
                                @error('berkasKelas') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <div class="pt-2 flex items-center justify-end">
                                <x-filament::button type="submit" color="primary" icon="heroicon-o-arrow-up-tray" wire:loading.attr="disabled">
                                    <span wire:loading.remove wire:target="prosesImporKelas">Impor Kelas Sekarang</span>
                                    <span wire:loading wire:target="prosesImporKelas">Memproses Berkas...</span>
                                </x-filament::button>
                            </div>
                        </form>
                    </div>

                    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-xs dark:border-gray-800 dark:bg-gray-900">
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-2">Panduan Kolom Template:</h4>
                        <ul class="text-xs space-y-2 text-gray-600 dark:text-gray-400">
                            <li><strong class="text-gray-800 dark:text-gray-200">nama_kelas</strong>: Contoh: X RPL 1, XI TKJ 2.</li>
                            <li><strong class="text-gray-800 dark:text-gray-200">tingkat</strong>: 10, 11, atau 12 (bisa dideteksi otomatis dari nama kelas).</li>
                            <li><strong class="text-gray-800 dark:text-gray-200">kode_jurusan</strong>: Kode keahlian (misal RPL, TKJ).</li>
                            <li><strong class="text-gray-800 dark:text-gray-200">tahun_ajaran</strong>: Contoh: 2026/2027 (opsional).</li>
                            <li><strong class="text-gray-800 dark:text-gray-200">wali_kelas</strong>: Username atau nama guru wali kelas (opsional).</li>
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        {{-- TAB 3: IMPOR JURUSAN --}}
        @if ($tabAktif === 'jurusan')
            <div class="space-y-6">
                @if ($hasilJurusan)
                    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-xs dark:border-gray-800 dark:bg-gray-900">
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">Hasil Impor Jurusan</h3>
                        <div class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-4">
                            <div class="rounded-lg border border-gray-100 bg-gray-50/50 p-3 dark:border-gray-800 dark:bg-gray-800/40">
                                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Total Diproses</p>
                                <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white tabular-nums">{{ $hasilJurusan['berhasil'] }}</p>
                            </div>
                            <div class="rounded-lg border border-emerald-100 bg-emerald-50/50 p-3 dark:border-emerald-900/30 dark:bg-emerald-950/20">
                                <p class="text-xs font-medium text-emerald-700 dark:text-emerald-400">Jurusan Baru</p>
                                <p class="mt-1 text-2xl font-bold text-emerald-700 dark:text-emerald-400 tabular-nums">{{ $hasilJurusan['baru'] }}</p>
                            </div>
                            <div class="rounded-lg border border-sky-100 bg-sky-50/50 p-3 dark:border-sky-900/30 dark:bg-sky-950/20">
                                <p class="text-xs font-medium text-sky-700 dark:text-sky-400">Diperbarui</p>
                                <p class="mt-1 text-2xl font-bold text-sky-700 dark:text-sky-400 tabular-nums">{{ $hasilJurusan['diperbarui'] }}</p>
                            </div>
                            <div class="rounded-lg border border-rose-100 bg-rose-50/50 p-3 dark:border-rose-900/30 dark:bg-rose-950/20">
                                <p class="text-xs font-medium text-rose-700 dark:text-rose-400">Gagal / Dilewati</p>
                                <p class="mt-1 text-2xl font-bold text-rose-700 dark:text-rose-400 tabular-nums">{{ $hasilJurusan['gagal'] }}</p>
                            </div>
                        </div>

                        @if (!empty($hasilJurusan['errors']))
                            <div class="mt-4 rounded-lg bg-rose-50 p-3 text-xs text-rose-800 dark:bg-rose-950/40 dark:text-rose-300">
                                <p class="font-semibold mb-1">Catatan Kesalahan:</p>
                                <ul class="list-disc pl-4 space-y-0.5">
                                    @foreach (array_slice($hasilJurusan['errors'], 0, 10) as $err)
                                        <li>{{ $err }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                @endif

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-2 rounded-xl border border-gray-200 bg-white p-6 shadow-xs dark:border-gray-800 dark:bg-gray-900">
                        <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-800">
                            <div>
                                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Formulir Impor Jurusan</h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Unggah berkas spreadsheet kompetensi keahlian</p>
                            </div>
                            <x-filament::button tag="a" href="{{ route('impor.template.jurusan') }}" color="gray" size="sm" icon="heroicon-o-arrow-down-tray">
                                Unduh Template
                            </x-filament::button>
                        </div>

                        <form wire:submit="prosesImporJurusan" class="mt-5 space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Pilih Berkas Excel / CSV <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    type="file"
                                    wire:model="berkasJurusan"
                                    accept=".xlsx,.xls,.csv"
                                    class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100 dark:file:bg-primary-950/50 dark:file:text-primary-400 border border-gray-300 dark:border-gray-700 rounded-lg p-2 dark:bg-gray-800"
                                />
                                @error('berkasJurusan') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <div class="pt-2 flex items-center justify-end">
                                <x-filament::button type="submit" color="primary" icon="heroicon-o-arrow-up-tray" wire:loading.attr="disabled">
                                    <span wire:loading.remove wire:target="prosesImporJurusan">Impor Jurusan Sekarang</span>
                                    <span wire:loading wire:target="prosesImporJurusan">Memproses Berkas...</span>
                                </x-filament::button>
                            </div>
                        </form>
                    </div>

                    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-xs dark:border-gray-800 dark:bg-gray-900">
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-2">Panduan Kolom Template:</h4>
                        <ul class="text-xs space-y-2 text-gray-600 dark:text-gray-400">
                            <li><strong class="text-gray-800 dark:text-gray-200">kode</strong>: Singkatan jurusan (misal RPL, TKJ, AKL).</li>
                            <li><strong class="text-gray-800 dark:text-gray-200">nama</strong>: Nama lengkap jurusan (misal Rekayasa Perangkat Lunak).</li>
                            <li><strong class="text-gray-800 dark:text-gray-200">deskripsi</strong>: Penjelasan kompetensi keahlian (opsional).</li>
                        </ul>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>

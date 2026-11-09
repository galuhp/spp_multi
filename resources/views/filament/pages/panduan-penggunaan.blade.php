<x-filament-panels::page>
    @push('styles')
        @vite('resources/css/panduan-penggunaan.css')
    @endpush


    <div
        x-data="{
            cari: '',
            aktifBab: @js($this->daftarBab[0]['id'] ?? ''),
            tocOpen: false,
            showTop: false,
            babList: @js($this->daftarBab),
            init() {
                this.wrapTables();
                this.onScroll = () => this.updateState();
                window.addEventListener('scroll', this.onScroll, { passive: true });
                this.updateState();
            },
            destroy() {
                if (this.onScroll) {
                    window.removeEventListener('scroll', this.onScroll);
                }
                if (this._frame) {
                    cancelAnimationFrame(this._frame);
                }
            },
            wrapTables() {
                this.$nextTick(() => {
                    this.$el.querySelectorAll('.panduan-content table').forEach((table) => {
                        if (table.parentElement && table.parentElement.classList.contains('panduan-table-wrap')) {
                            return;
                        }
                        const wrap = document.createElement('div');
                        wrap.className = 'panduan-table-wrap';
                        wrap.setAttribute('tabindex', '0');
                        wrap.setAttribute('role', 'region');
                        wrap.setAttribute('aria-label', 'Tabel dapat digeser secara horizontal');
                        table.parentNode.insertBefore(wrap, table);
                        wrap.appendChild(table);
                    });
                });
            },
            updateState() {
                if (this._frame) {
                    return;
                }
                this._frame = requestAnimationFrame(() => {
                    this._frame = null;
                    this.showTop = window.scrollY > 600;
                    let current = this.babList.length ? this.babList[0].id : '';
                    for (const bab of this.babList) {
                        const el = document.getElementById(bab.id);
                        if (el && el.getBoundingClientRect().top <= 160) {
                            current = bab.id;
                        }
                    }
                    this.aktifBab = current;
                });
            },
            goTo(id) {
                const el = document.getElementById(id);
                if (!el) {
                    return;
                }
                const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                const top = el.getBoundingClientRect().top + window.scrollY - 88;
                window.scrollTo({ top, behavior: reduce ? 'auto' : 'smooth' });
                this.aktifBab = id;
                this.tocOpen = false;
                if (window.history && window.history.replaceState) {
                    window.history.replaceState(null, '', '#' + id);
                }
            },
            matches(bab) {
                const query = this.cari.trim().toLowerCase();
                if (query === '') {
                    return true;
                }
                return `${bab.judul} ${bab.deskripsi} ${bab.topik}`.toLowerCase().includes(query);
            },
            get hasilCount() {
                return this.babList.filter((bab) => this.matches(bab)).length;
            },
            scrollTop() {
                const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                window.scrollTo({ top: 0, behavior: reduce ? 'auto' : 'smooth' });
            },
        }"
        class="space-y-6"
    >
        {{-- Banner Sambutan --}}
        <div class="panduan-hero no-print relative overflow-hidden rounded-2xl bg-gradient-to-br from-primary-600 via-primary-700 to-indigo-800 p-6 text-white shadow-lg shadow-primary-900/10 sm:p-8">
            <div class="relative z-10 flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                <div class="max-w-2xl space-y-3">
                    <div class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1 text-xs font-medium ring-1 ring-inset ring-white/20">
                        <x-filament::icon icon="heroicon-o-book-open" class="size-3.5" />
                        <span>Dokumentasi Resmi E-SPP</span>
                        <span class="opacity-60" aria-hidden="true">&bull;</span>
                        <span>Versi Lengkap</span>
                    </div>

                    <h2 class="text-2xl font-bold tracking-tight sm:text-3xl">
                        Buku Panduan Penggunaan &amp; SOP Operasional
                    </h2>

                    <p class="text-sm leading-relaxed text-primary-100">
                        Panduan komprehensif bagi Administrator, Kasir Keuangan, Wali Kelas, dan Siswa.
                        Gunakan daftar isi untuk melompat langsung ke topik yang dibutuhkan, atau cetak buku
                        pedoman resmi melalui tombol di kanan atas.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <button
                        type="button"
                        @click="goTo(babList[0]?.id)"
                        class="panduan-action inline-flex cursor-pointer items-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-primary-700 shadow-sm transition hover:bg-primary-50"
                    >
                        <x-filament::icon icon="heroicon-o-play" class="size-4" />
                        <span>Mulai Membaca</span>
                    </button>

                    <button
                        type="button"
                        onclick="window.print()"
                        class="panduan-action inline-flex cursor-pointer items-center gap-2 rounded-xl bg-white/10 px-4 py-2.5 text-sm font-semibold text-white ring-1 ring-inset ring-white/25 transition hover:bg-white/20"
                    >
                        <x-filament::icon icon="heroicon-o-printer" class="size-4" />
                        <span>Cetak Panduan</span>
                    </button>
                </div>
            </div>

            <div class="pointer-events-none absolute -right-10 -top-10 h-48 w-48 rounded-full bg-white/10 blur-2xl" aria-hidden="true"></div>
            <div class="pointer-events-none absolute -bottom-16 right-24 h-56 w-56 rounded-full bg-indigo-400/20 blur-3xl" aria-hidden="true"></div>
        </div>

        {{-- Konten Utama: Sidebar Daftar Isi + Dokumen --}}
        <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-4">

            {{-- Kolom Kiri: Daftar Isi --}}
            <aside class="no-print lg:sticky lg:top-20 lg:col-span-1" aria-label="Daftar isi buku panduan">
                <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xs dark:border-white/10 dark:bg-gray-900">
                    <div class="flex items-center justify-between gap-2 border-b border-gray-100 p-4 dark:border-white/10">
                        <div class="flex items-center gap-2">
                            <x-filament::icon icon="heroicon-o-list-bullet" class="size-4 text-gray-400 dark:text-gray-500" />
                            <h2 class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                Daftar Isi
                            </h2>
                        </div>

                        <div class="flex items-center gap-2">
                            <span class="rounded-md bg-primary-50 px-2 py-0.5 text-[11px] font-semibold text-primary-700 dark:bg-primary-950/50 dark:text-primary-300">
                                9 Bab
                            </span>
                            <button
                                type="button"
                                class="inline-flex cursor-pointer items-center gap-1 rounded-md border border-gray-200 px-2 py-1 text-[11px] font-medium text-gray-600 transition hover:bg-gray-50 lg:hidden dark:border-white/10 dark:text-gray-300 dark:hover:bg-white/5"
                                @click="tocOpen = !tocOpen"
                                :aria-expanded="tocOpen"
                                aria-controls="panduan-toc"
                            >
                                <span x-text="tocOpen ? 'Tutup' : 'Buka'">Buka</span>
                                <x-filament::icon
                                    icon="heroicon-o-chevron-down"
                                    class="size-3.5 transition-transform duration-200"
                                    x-bind:class="tocOpen ? 'rotate-180' : ''"
                                />
                            </button>
                        </div>
                    </div>

                    <div id="panduan-toc" :class="tocOpen ? 'block' : 'hidden lg:block'">
                        <div class="p-3 pb-0">
                            <label for="panduan-cari" class="sr-only">Cari bab panduan</label>
                            <div class="relative">
                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400 dark:text-gray-500">
                                    <x-filament::icon icon="heroicon-o-magnifying-glass" class="size-4" />
                                </span>
                                <input
                                    id="panduan-cari"
                                    type="search"
                                    x-model="cari"
                                    placeholder="Cari bab atau topik&hellip;"
                                    autocomplete="off"
                                    class="block w-full rounded-lg border border-gray-200 bg-white py-2 pl-9 pr-8 text-xs text-gray-900 placeholder:text-gray-400 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30 dark:border-white/10 dark:bg-gray-950 dark:text-white dark:placeholder:text-gray-500"
                                />
                                <button
                                    type="button"
                                    :class="cari !== '' ? 'flex' : 'hidden'"
                                    @click="cari = ''"
                                    class="absolute inset-y-0 right-0 cursor-pointer items-center pr-2.5 text-gray-400 transition hover:text-gray-600 dark:hover:text-gray-200"
                                    aria-label="Hapus pencarian"
                                >
                                    <x-filament::icon icon="heroicon-o-x-mark" class="size-4" />
                                </button>
                            </div>
                        </div>

                        <nav class="max-h-[60vh] space-y-0.5 overflow-y-auto p-3 lg:max-h-[calc(100vh-16rem)]" aria-label="Bab panduan">
                            @foreach ($this->daftarBab as $bab)
                                <a
                                    href="#{{ $bab['id'] }}"
                                    @click.prevent="goTo('{{ $bab['id'] }}')"
                                    :aria-current="aktifBab === '{{ $bab['id'] }}' ? 'location' : 'false'"
                                    :class="{
                                        'hidden': !matches(@js($bab)),
                                        'border-primary-500 bg-primary-50 text-primary-800 dark:border-primary-400 dark:bg-primary-950/40 dark:text-primary-200': aktifBab === '{{ $bab['id'] }}',
                                        'border-transparent text-gray-600 hover:border-gray-300 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-300 dark:hover:border-white/20 dark:hover:bg-white/5 dark:hover:text-white': aktifBab !== '{{ $bab['id'] }}',
                                    }"
                                    class="panduan-toc-link group flex cursor-pointer items-start gap-2.5 rounded-lg border-l-4 px-2.5 py-2"
                                >
                                    <x-filament::icon
                                        :icon="'heroicon-o-'.$bab['icon']"
                                        class="mt-0.5 size-4 shrink-0 opacity-70 group-hover:opacity-100"
                                    />
                                    <span class="min-w-0">
                                        <span class="block truncate text-xs font-medium">{{ $bab['judul'] }}</span>
                                        <span class="mt-0.5 block text-[10px] leading-snug text-gray-400 line-clamp-2 dark:text-gray-500">
                                            {{ $bab['deskripsi'] }}
                                        </span>
                                    </span>
                                </a>
                            @endforeach

                            <p
                                :class="hasilCount === 0 ? 'block' : 'hidden'"
                                class="rounded-lg border border-dashed border-gray-200 px-3 py-6 text-center text-xs text-gray-500 dark:border-white/10 dark:text-gray-400"
                            >
                                Tidak ada bab yang cocok dengan kata kunci tersebut.
                            </p>
                        </nav>

                        <div class="border-t border-gray-100 p-3 text-center dark:border-white/10">
                            <a
                                href="{{ route('panduan.unduh') }}"
                                target="_blank"
                                rel="noopener"
                                class="panduan-action inline-flex items-center gap-1.5 rounded-md px-2 py-1 text-xs font-medium text-gray-500 transition hover:text-primary-600 dark:text-gray-400 dark:hover:text-primary-400"
                            >
                                <x-filament::icon icon="heroicon-o-arrow-down-tray" class="size-3.5" />
                                <span>Unduh berkas .md asli</span>
                            </a>
                        </div>
                    </div>
                </div>
            </aside>

            {{-- Kolom Kanan: Dokumen Panduan --}}
            <div class="min-w-0 lg:col-span-3">
                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-xs sm:p-6 md:p-8 dark:border-white/10 dark:bg-gray-900">
                    <div class="panduan-print-header" aria-hidden="true">
                        <h1>Buku Panduan Penggunaan &amp; SOP Operasional E-SPP</h1>
                        <p>Dokumen resmi sistem informasi pembayaran SPP dan keuangan sekolah.</p>
                    </div>

                    <article id="panduan-article" class="panduan-content max-w-none">
                        {!! $this->kontenHtml !!}
                    </article>

                    <div class="no-print mt-10 flex flex-wrap items-center justify-between gap-4 border-t border-gray-200 pt-6 text-xs text-gray-500 dark:border-white/10 dark:text-gray-400">
                        <div class="flex items-center gap-1.5">
                            <x-filament::icon icon="heroicon-o-document-text" class="size-3.5" />
                            <span>Sistem E-SPP &bull; Buku Panduan Resmi Versi 1.0</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                @click="scrollTop()"
                                class="panduan-action inline-flex cursor-pointer items-center gap-1.5 rounded-lg border border-gray-200 px-3 py-1.5 font-medium transition hover:bg-gray-50 dark:border-white/10 dark:hover:bg-white/5"
                            >
                                <x-filament::icon icon="heroicon-o-arrow-up" class="size-3.5" />
                                <span>Kembali ke Atas</span>
                            </button>
                            <button
                                type="button"
                                onclick="window.print()"
                                class="panduan-action inline-flex cursor-pointer items-center gap-1.5 rounded-lg bg-primary-600 px-3 py-1.5 font-medium text-white transition hover:bg-primary-700"
                            >
                                <x-filament::icon icon="heroicon-o-printer" class="size-3.5" />
                                <span>Cetak Dokumen</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tombol kembali ke atas (muncul setelah menggulir) --}}
        <button
            type="button"
            :class="showTop ? 'inline-flex' : 'hidden'"
            @click="scrollTop()"
            class="panduan-action no-print fixed bottom-5 right-5 z-40 size-11 cursor-pointer items-center justify-center rounded-full bg-primary-600 text-white shadow-lg shadow-primary-900/20 transition hover:bg-primary-700"
            aria-label="Kembali ke atas"
        >
            <x-filament::icon icon="heroicon-o-arrow-up" class="size-5" />
        </button>
    </div>
</x-filament-panels::page>

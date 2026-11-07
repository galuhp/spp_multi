<x-filament-panels::page>
    @push('styles')
        @vite('resources/css/entri-pembayaran-kasir.css')
    @endpush

    @php
        $siswa = $this->siswa;
    @endphp

    <div class="space-y-5">
        {{-- Student picker --}}
        <section class="rounded-xl bg-white p-5 shadow-xs ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            @if ($siswa)
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex min-w-0 items-center gap-3">
                        <div class="flex size-11 shrink-0 items-center justify-center rounded-full bg-primary-50 text-primary-700 dark:bg-primary-950/50 dark:text-primary-300">
                            <x-heroicon-o-user class="size-5" />
                        </div>
                        <div class="min-w-0">
                            <h2 class="truncate text-sm font-semibold text-gray-950 dark:text-white">{{ $siswa->nama }}</h2>
                            <p class="truncate text-xs text-gray-500 dark:text-gray-400">
                                NISN {{ $siswa->nisn ?: '-' }}
                                &bull; {{ $siswa->kelas?->nama_kelas ?: '-' }}
                                &bull; {{ $siswa->spp?->tahun_ajaran ?: 'Tarif SPP belum diset' }}
                            </p>
                        </div>
                    </div>

                    <x-filament::button type="button" color="gray" size="sm" wire:click="gantiSiswa">
                        Ganti Siswa
                    </x-filament::button>
                </div>
            @else
                <div class="flex flex-col gap-2">
                    <div>
                        <h2 class="text-sm font-semibold text-gray-950 dark:text-white">Pilih Siswa</h2>
                        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Pilih tingkat kelas, lalu cari berdasarkan nama, NISN, atau NIS.</p>
                    </div>

                    <div class="mt-2">
                        <p class="text-xs font-medium text-gray-600 dark:text-gray-300">Tingkat Kelas</p>
                        <div class="mt-2 inline-flex flex-wrap gap-1 rounded-lg bg-gray-100 p-1 dark:bg-gray-800" role="group" aria-label="Filter tingkat kelas">
                            @foreach ($this->tingkatOptions as $nilai => $label)
                                <button type="button" wire:click="pilihTingkat('{{ $nilai }}')"
                                    aria-pressed="{{ $tingkat === (string) $nilai ? 'true' : 'false' }}"
                                    @class([
                                        'rounded-md px-3.5 py-1.5 text-sm font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-900',
                                        'bg-white text-gray-900 shadow-sm dark:bg-gray-950 dark:text-white' => $tingkat === (string) $nilai,
                                        'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white' => $tingkat !== (string) $nilai,
                                    ])>
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <div class="relative mt-2">
                        <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-gray-400">
                            <x-heroicon-o-magnifying-glass class="size-4" />
                        </span>
                        <input type="search" wire:model.live.debounce.300ms="cari"
                            x-init="$nextTick(() => $el.focus())" x-on:focus-cari.window="$nextTick(() => $el.focus())" autofocus
                            placeholder="Ketik nama atau NISN..."
                            class="w-full rounded-lg border border-gray-300 bg-white py-2.5 pl-9 pr-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-200 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                    </div>

                    @if (mb_strlen(trim($cari)) >= 2)
                        <div class="mt-2 overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
                            @forelse ($this->siswaHasil as $hasil)
                                <button type="button" wire:key="hasil-{{ $hasil->id }}" wire:click="pilihSiswa({{ $hasil->id }})"
                                    class="flex w-full items-center justify-between gap-3 border-b border-gray-100 px-3.5 py-3 text-left text-sm transition last:border-b-0 hover:bg-primary-50/60 dark:border-gray-800 dark:hover:bg-primary-950/30">
                                    <span class="min-w-0">
                                        <span class="block truncate font-medium text-gray-900 dark:text-white">{{ $hasil->nama }}</span>
                                        <span class="block truncate text-xs text-gray-500 dark:text-gray-400">
                                            NISN {{ $hasil->nisn ?: '-' }} &bull; {{ $hasil->kelas?->nama_kelas ?: '-' }}
                                        </span>
                                    </span>
                                    <span class="shrink-0 text-xs font-semibold text-primary-600 dark:text-primary-400">Pilih</span>
                                </button>
                            @empty
                                <p class="px-3.5 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                    Tidak ada siswa aktif yang cocok.
                                </p>
                            @endforelse
                        </div>
                    @endif
                </div>
            @endif

            @if ($galat)
                <div class="mt-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    {{ $galat }}
                </div>
            @endif
        </section>

        @if ($siswa)
            @php
                $data = $this->kasirData;
            @endphp

            <div x-data="kasir(@js($data))" wire:key="kasir-{{ $siswa->id }}" class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_20rem]">
                {{-- Detail --}}
                <section class="overflow-hidden rounded-xl bg-white shadow-xs ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                        <h1 class="text-base font-semibold text-gray-950 dark:text-white">Detail Tagihan</h1>
                        <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">Pilih bulan SPP dan biaya lain yang dibayar hari ini.</p>
                    </div>

                    <div class="p-5">
                        {{-- Tabs --}}
                        <div class="inline-flex rounded-lg bg-gray-100 p-1 text-sm dark:bg-gray-800" role="tablist">
                            <button type="button" @click="tab = 'spp'"
                                :class="tab === 'spp' ? 'bg-white shadow-sm font-medium text-gray-900 dark:bg-gray-950 dark:text-white' : 'text-gray-500 dark:text-gray-400'"
                                class="flex items-center gap-2 rounded-md px-4 py-2 text-sm transition focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-900">
                                Iuran SPP Bulanan
                                <span :class="selected.length ? '' : 'hidden'" x-cloak class="rounded-full bg-primary-600 px-1.5 text-xs text-white" x-text="selected.length"></span>
                            </button>
                            <button type="button" @click="tab = 'lain'"
                                :class="tab === 'lain' ? 'bg-white shadow-sm font-medium text-gray-900 dark:bg-gray-950 dark:text-white' : 'text-gray-500 dark:text-gray-400'"
                                class="flex items-center gap-2 rounded-md px-4 py-2 text-sm transition focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-900">
                                Biaya Lainnya
                                <span :class="selectedLain.length ? '' : 'hidden'" x-cloak class="rounded-full bg-primary-600 px-1.5 text-xs text-white" x-text="selectedLain.length"></span>
                            </button>
                        </div>

                        {{-- SPP & Tarif Bulanan --}}
                        <div :class="tab === 'spp' ? '' : 'hidden'" class="mt-5 space-y-6">
                            <template x-for="(grupBulanan, gIndex) in monthlyGroups" :key="grupBulanan.id">
                                <div :class="gIndex > 0 ? 'border-t border-gray-200 pt-5 dark:border-gray-800' : ''">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                                            <span x-text="grupBulanan.nama"></span>
                                            <span class="font-normal text-gray-500 dark:text-gray-400">&bull; <span x-text="rp(grupBulanan.tarif)"></span> / bulan</span>
                                        </p>
                                        <div class="flex flex-wrap items-center gap-3">
                                            <x-filament::link tag="button" size="sm" @click="presetGroup(gIndex, 'tunggakan')">
                                                Tunggakan saja
                                            </x-filament::link>
                                            <x-filament::link tag="button" size="sm" @click="presetGroup(gIndex, 'sem1')">
                                                Sampai Februari
                                            </x-filament::link>
                                            <x-filament::link tag="button" color="gray" size="sm" @click="presetGroup(gIndex, 'reset')">
                                                Kosongkan
                                            </x-filament::link>
                                        </div>
                                    </div>

                                    <div class="mt-3 grid grid-cols-2 gap-2.5 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-5">
                                        <template x-for="(m, index) in grupBulanan.months" :key="m.key">
                                            <button type="button" @click="toggleGroupMonth(gIndex, index)" :disabled="m.status === 'lunas' || m.status === 'ditanggung'"
                                                :class="monthClass(m)"
                                                class="relative flex flex-col items-start rounded-lg border px-3 py-2.5 text-left transition">
                                                <span class="text-sm font-medium" x-text="m.bulan"></span>
                                                <span class="text-xs opacity-70" x-text="m.tahun"></span>
                                                <span class="mt-2 text-xs" x-text="m.status === 'lunas' ? 'Lunas' : (m.status === 'ditanggung' ? 'Ditanggung Pemerintah' : rp(m.nominal))"></span>

                                                <span :class="hasMonthly(m.key) ? '' : 'hidden'" class="absolute right-2 top-2 text-xs">&check;</span>
                                                <span :class="m.status === 'lunas' ? '' : 'hidden'" class="absolute right-2 top-2 text-xs">&check;</span>
                                                <span :class="m.status === 'ditanggung' ? '' : 'hidden'" class="absolute right-2 top-2 rounded border border-sky-200/80 bg-sky-50 px-1.5 text-[10px] font-medium text-sky-700">Pemerintah</span>
                                                <span :class="(m.status === 'tunggakan' && ! hasMonthly(m.key)) ? '' : 'hidden'" class="absolute right-2 top-2 rounded border border-amber-200/80 bg-amber-50 px-1.5 text-[10px] font-medium text-amber-700">Tunggakan</span>
                                                <span :class="(m.status === 'berjalan' && ! hasMonthly(m.key)) ? '' : 'hidden'" class="absolute right-2 top-2 rounded border border-slate-200 bg-slate-100 px-1.5 text-[10px] font-medium text-slate-700 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">Bulan ini</span>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </template>

                            <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                                Klik satu bulan untuk memilih semua bulan belum lunas sampai bulan itu. Klik lagi untuk membatalkan dari bulan tersebut.
                            </p>
                        </div>

                        {{-- Non-SPP --}}
                        <div :class="tab === 'lain' ? '' : 'hidden'" x-cloak class="mt-5">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">Pos biaya non-SPP</p>

                            <p :class="nonSpp.length === 0 ? '' : 'hidden'" class="mt-3 rounded-lg border border-dashed border-gray-300 px-4 py-6 text-center text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                Tidak ada biaya lain yang belum dibayar.
                            </p>

                            <div class="mt-3 space-y-4">
                                <template x-for="grup in nonSppGroups" :key="grup.key">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <h4 class="text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400" x-text="grup.label"></h4>
                                            <span class="rounded-full bg-gray-100 px-1.5 text-[10px] font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-300" x-text="grup.items.length"></span>
                                        </div>

                                        <ul class="mt-2 divide-y divide-gray-200 rounded-lg border border-gray-200 dark:divide-gray-800 dark:border-gray-800">
                                            <template x-for="x in grup.items" :key="x.key">
                                                <li class="flex items-center justify-between gap-3 px-4 py-3 transition"
                                                    :class="hasLain(x.key) ? 'bg-primary-50 dark:bg-primary-950/40' : ''">
                                                    <div class="min-w-0">
                                                        <p class="text-sm font-medium text-gray-900 dark:text-white" x-text="x.nama"></p>
                                                        <p class="truncate text-xs text-gray-500 dark:text-gray-400" x-text="x.deskripsi"></p>
                                                    </div>
                                                    <div class="flex shrink-0 items-center gap-3">
                                                        <span class="text-sm text-gray-800 dark:text-gray-200" x-text="rp(x.sisa)"></span>
                                                        <x-filament::button type="button" color="gray" size="sm" outlined
                                                            class="w-28 whitespace-nowrap" x-show="! hasLain(x.key)" @click="toggleLain(x.key)">
                                                            Tambah
                                                        </x-filament::button>
                                                        <x-filament::button type="button" size="sm"
                                                            class="w-28 whitespace-nowrap" x-show="hasLain(x.key)" x-cloak @click="toggleLain(x.key)">
                                                            Ditambahkan
                                                        </x-filament::button>
                                                    </div>
                                                </li>
                                            </template>
                                        </ul>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- Summary --}}
                <aside class="self-start rounded-xl border border-gray-200 bg-gray-50 p-5 lg:sticky lg:top-6 dark:border-gray-800 dark:bg-gray-950/40">
                    <h2 class="text-sm font-semibold text-gray-950 dark:text-white">Ringkasan Pembayaran</h2>

                    <div class="mt-3 space-y-3 text-sm">
                        <p :class="(! selected.length && ! selectedLain.length) ? '' : 'hidden'" class="text-gray-500 dark:text-gray-400">
                            Belum ada tagihan dipilih.
                        </p>

                        <div :class="selected.length ? '' : 'hidden'" x-cloak class="flex justify-between gap-3">
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">Iuran SPP</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    <span x-text="rangeSpp"></span> &bull; <span x-text="selected.length"></span> bulan
                                </p>
                            </div>
                            <p class="text-gray-900 dark:text-white" x-text="rp(totalSpp)"></p>
                        </div>

                        <template x-for="x in selectedLainItems" :key="x.key">
                            <div class="flex justify-between gap-3">
                                <p class="font-medium text-gray-900 dark:text-white" x-text="x.nama"></p>
                                <p class="text-gray-900 dark:text-white" x-text="rp(x.sisa)"></p>
                            </div>
                        </template>
                    </div>

                    <div class="mt-4 flex items-baseline justify-between border-t border-gray-300 pt-4 dark:border-gray-700">
                        <span class="text-sm text-gray-500 dark:text-gray-400">Total</span>
                        <span class="text-xl font-semibold text-gray-950 dark:text-white" x-text="rp(total)"></span>
                    </div>

                    <x-filament::button type="button" size="md" class="mt-4 w-full" @click="bayar = true" x-bind:disabled="! total">
                        Lanjut ke Pembayaran
                    </x-filament::button>
                </aside>

                {{-- Payment modal --}}
                <div :class="bayar ? '' : 'hidden'" x-cloak @click.self="bayar = false"
                    class="fixed inset-0 z-50 flex items-end justify-center bg-gray-950/60 p-4 sm:items-center">
                    <div class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-xl dark:bg-gray-900">
                        <div class="flex items-start justify-between gap-4 border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                            <div>
                                <h3 class="text-base font-semibold text-gray-950 dark:text-white">Konfirmasi Pembayaran</h3>
                                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ $siswa->nama }} &bull; {{ $siswa->kelas?->nama_kelas }}</p>
                            </div>
                            <x-filament::icon-button icon="heroicon-o-x-mark" color="gray" size="sm"
                                label="Tutup" @click="bayar = false" />
                        </div>

                        <div class="space-y-4 px-5 py-4">
                            <div class="rounded-lg bg-gray-50 px-4 py-3 dark:bg-gray-950/50">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-500 dark:text-gray-400">Total dibayar</span>
                                    <span class="text-lg font-bold text-gray-950 dark:text-white" x-text="rp(total)"></span>
                                </div>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    <span x-text="selected.length"></span> bulan SPP &bull; <span x-text="selectedLain.length"></span> biaya lain
                                </p>
                            </div>

                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">Metode Pembayaran</label>
                                <select x-model="metode"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-200 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                                    <option value="Tunai">Tunai</option>
                                    <option value="Transfer Bank">Transfer Bank</option>
                                    <option value="QRIS">QRIS</option>
                                </select>
                            </div>

                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">Tanggal Pembayaran</label>
                                <input type="date" x-model="tanggal"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-200 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                            </div>

                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">Catatan (Opsional)</label>
                                <textarea x-model="catatan" rows="2" placeholder="Catatan pembayaran..."
                                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-200 dark:border-gray-700 dark:bg-gray-950 dark:text-white"></textarea>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-2 border-t border-gray-200 px-5 py-4 dark:border-gray-800">
                            <x-filament::button type="button" color="gray" size="sm" @click="bayar = false">
                                Batal
                            </x-filament::button>
                            <x-filament::button type="button" size="sm" @click="submit()" x-bind:disabled="loading || ! total">
                                <span :class="! loading ? '' : 'hidden'">Simpan &amp; Cetak Kuitansi</span>
                                <span :class="loading ? '' : 'hidden'" x-cloak>Menyimpan...</span>
                            </x-filament::button>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="rounded-xl border border-dashed border-gray-300 bg-white px-6 py-10 text-center dark:border-gray-700 dark:bg-gray-900">
                <div class="mx-auto flex size-11 items-center justify-center rounded-full bg-primary-50 text-primary-600 dark:bg-primary-950/50 dark:text-primary-300">
                    <x-heroicon-o-receipt-percent class="size-5" />
                </div>
                <p class="mt-3 text-sm font-medium text-gray-900 dark:text-white">Belum ada siswa dipilih</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Cari dan pilih siswa di atas untuk mulai mencatat pembayaran.</p>
            </div>
        @endif
    </div>

    <script>
        function kasir(data) {
            return {
                tab: 'spp',
                tarif: data.tarif,
                months: data.months,
                monthlyGroups: data.monthly_groups && data.monthly_groups.length
                    ? data.monthly_groups
                    : [{ id: 'spp', nama: 'Bulan SPP yang dibayar', tarif: data.tarif, tipe: 'spp', months: data.months || [] }],
                nonSpp: data.non_spp,
                selected: [],
                selectedLain: [],
                metode: 'Tunai',
                tanggal: data.tanggal,
                catatan: '',
                bayar: false,
                loading: false,

                rp(nilai) {
                    return 'Rp ' + Number(nilai).toLocaleString('id-ID');
                },

                has(key) {
                    return this.selected.includes(key);
                },

                hasLain(key) {
                    return this.selectedLain.includes(key);
                },

                hasMonthly(key) {
                    return this.selected.includes(key) || this.selectedLain.includes(key);
                },

                get nonSppGroups() {
                    const grup = [
                        { key: 'bulanan', label: 'Tarif Bulanan', items: [] },
                        { key: 'sekali_bayar', label: 'Sekali Bayar', items: [] },
                    ];

                    this.nonSpp.forEach((x) => {
                        const target = grup.find((g) => g.key === x.grup) ?? grup[1];
                        target.items.push(x);
                    });

                    return grup.filter((g) => g.items.length > 0);
                },

                unpaidUpTo(index) {
                    return this.months
                        .filter((m, i) => i <= index && m.status !== 'lunas' && m.status !== 'ditanggung')
                        .map((m) => m.key);
                },

                toggleMonth(index) {
                    this.toggleGroupMonth(0, index);
                },

                toggleGroupMonth(gIndex, index) {
                    const grp = this.monthlyGroups[gIndex];
                    if (!grp) {
                        return;
                    }

                    const m = grp.months[index];
                    if (!m || m.status === 'lunas' || m.status === 'ditanggung') {
                        return;
                    }

                    const groupKeys = grp.months.map((x) => x.key);
                    const targetList = grp.tipe === 'spp' ? 'selected' : 'selectedLain';
                    const current = this[targetList];
                    const otherKeys = current.filter((k) => !groupKeys.includes(k));

                    if (current.includes(m.key)) {
                        const kept = grp.months
                            .filter((x, i) => i < index && current.includes(x.key))
                            .map((x) => x.key);
                        this[targetList] = [...otherKeys, ...kept];
                    } else {
                        const added = grp.months
                            .filter((x, i) => i <= index && x.status !== 'lunas' && x.status !== 'ditanggung')
                            .map((x) => x.key);
                        this[targetList] = [...otherKeys, ...added];
                    }
                },

                preset(mode) {
                    this.presetGroup(0, mode);
                },

                presetGroup(gIndex, mode) {
                    const grp = this.monthlyGroups[gIndex];
                    if (!grp) {
                        return;
                    }

                    const groupKeys = grp.months.map((x) => x.key);
                    const targetList = grp.tipe === 'spp' ? 'selected' : 'selectedLain';
                    const otherKeys = this[targetList].filter((k) => !groupKeys.includes(k));

                    if (mode === 'tunggakan') {
                        const added = grp.months
                            .filter((m) => m.status === 'tunggakan')
                            .map((m) => m.key);
                        this[targetList] = [...otherKeys, ...added];

                        return;
                    }

                    if (mode === 'sem1') {
                        const index = grp.months.findIndex((m) => m.bulan === 'Februari');
                        const added = index >= 0
                            ? grp.months
                                .filter((m, i) => i <= index && m.status !== 'lunas' && m.status !== 'ditanggung')
                                .map((m) => m.key)
                            : [];
                        this[targetList] = [...otherKeys, ...added];

                        return;
                    }

                    this[targetList] = otherKeys;
                },

                toggleLain(key) {
                    this.selectedLain = this.hasLain(key)
                        ? this.selectedLain.filter((k) => k !== key)
                        : [...this.selectedLain, key];
                },

                get selectedLainItems() {
                    return this.nonSpp.filter((x) => this.selectedLain.includes(x.key));
                },

                get totalSpp() {
                    return this.months
                        .filter((m) => this.selected.includes(m.key))
                        .reduce((sum, m) => sum + Number(m.nominal || 0), 0);
                },

                get total() {
                    return this.totalSpp
                        + this.selectedLainItems.reduce((sum, x) => sum + Number(x.sisa || 0), 0);
                },

                get rangeSpp() {
                    const items = this.months.filter((m) => this.selected.includes(m.key));

                    if (items.length === 0) {
                        return '';
                    }

                    const format = (m) => m.bulan.slice(0, 3) + ' ' + m.tahun;

                    return items.length > 1
                        ? format(items[0]) + ' – ' + format(items[items.length - 1])
                        : format(items[0]);
                },

                monthClass(m) {
                    if (m.status === 'lunas') {
                        return 'cursor-not-allowed border-emerald-200/80 bg-emerald-50/45 text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-300';
                    }

                    if (m.status === 'ditanggung') {
                        return 'cursor-not-allowed border-sky-200/80 bg-sky-50/45 text-sky-700 dark:border-sky-900 dark:bg-sky-950/30 dark:text-sky-300';
                    }

                    if (this.hasMonthly(m.key)) {
                        return 'border-primary-600 bg-primary-600 text-white shadow-sm';
                    }

                    if (m.status === 'tunggakan') {
                        return 'border-amber-300 bg-amber-50/25 hover:border-primary-500 dark:bg-gray-900';
                    }

                    return 'border-gray-200 bg-white hover:border-primary-500 dark:border-gray-700 dark:bg-gray-900';
                },

                submit() {
                    if (!this.total || this.loading) {
                        return;
                    }

                    this.loading = true;

                    this.$wire
                        .simpan(this.selected, this.selectedLain, this.metode, this.tanggal, this.catatan)
                        .finally(() => {
                            this.loading = false;
                        });
                },
            };
        }
    </script>
</x-filament-panels::page>

<x-filament-panels::page>
    @push('styles')
        @vite('resources/css/tagihan-siswa.css')
    @endpush

    @php
        $siswa = $this->siswa;
        $tagihan = $this->tagihanData;
        $filteredItems = $this->filteredItems;
        $isStaff = auth()->user()?->isStaff() ?? false;
    @endphp

    <div class="space-y-5">
        {{-- 1. FILTER SISWA (Mirip https://spp.rote.biz.id/admin/tagihan-siswa) --}}
        @if($isStaff)
            <x-ui.card class="!max-w-none !p-5">
                <div class="flex flex-col gap-4">
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                        <div>
                            <h3 class="text-base font-semibold text-slate-900 dark:text-white">Pilih Siswa untuk Ditinjau</h3>
                            <p class="mt-0.5 text-xs text-slate-500 dark:text-gray-400">Cari langsung berdasarkan nama/NISN, atau pilih kelas lalu siswa.</p>
                        </div>

                        <div class="flex flex-wrap items-center gap-4 text-sm text-slate-700 dark:text-gray-300">
                            <label class="flex cursor-pointer items-center gap-2">
                                <input type="radio" wire:model.live="cara_pilih" value="cari"
                                    class="size-4 accent-primary-600">
                                <span class="font-medium">Cari Nama / NISN</span>
                            </label>
                            <label class="flex cursor-pointer items-center gap-2">
                                <input type="radio" wire:model.live="cara_pilih" value="kelas"
                                    class="size-4 accent-primary-600">
                                <span class="font-medium">Bertingkat (Kelas)</span>
                            </label>
                        </div>
                    </div>

                    @if($cara_pilih === 'cari')
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label for="cari-siswa" class="mb-1 block text-xs font-medium text-slate-600 dark:text-gray-300">Kata kunci</label>
                                <input type="search" id="cari-siswa" wire:model.live.debounce.300ms="cari"
                                    placeholder="Ketik nama, NISN, atau NIS..."
                                    class="w-full min-h-10 rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                                <p class="mt-1 text-[11px] text-slate-500 dark:text-gray-400">Minimal 2 karakter. Hasil maksimal 20 siswa.</p>
                            </div>

                            <div>
                                @if($this->siswa)
                                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-3 dark:border-gray-700 dark:bg-gray-800/60">
                                        <div class="text-[11px] font-medium uppercase tracking-wide text-slate-500 dark:text-gray-400">Siswa terpilih</div>
                                        <div class="mt-1 font-semibold text-slate-900 dark:text-white">{{ $this->siswa->nama }}</div>
                                        <div class="text-xs text-slate-500 dark:text-gray-400">
                                            NISN {{ $this->siswa->nisn ?: '-' }} &bull; {{ $this->siswa->kelas?->nama_kelas }}
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        @if(mb_strlen(trim($cari)) >= 2)
                            <div class="overflow-hidden rounded-lg border border-slate-200 dark:border-gray-700">
                                @forelse($this->siswaHasil as $hasil)
                                    <button type="button" wire:key="hasil-{{ $hasil->id }}" wire:click="pilihSiswa({{ $hasil->id }})"
                                        class="flex w-full items-center justify-between gap-3 border-b border-slate-100 px-3 py-2.5 text-left text-sm last:border-b-0 hover:bg-primary-50/60 dark:border-gray-800 dark:hover:bg-primary-950/30">
                                        <span>
                                            <span class="font-medium text-slate-900 dark:text-white">{{ $hasil->nama }}</span>
                                            <span class="block text-xs text-slate-500 dark:text-gray-400">NISN {{ $hasil->nisn ?: '-' }} &bull; {{ $hasil->kelas?->nama_kelas }}</span>
                                        </span>
                                        <span class="text-xs font-semibold text-primary-600 dark:text-primary-400">Pilih</span>
                                    </button>
                                @empty
                                    <div class="px-3 py-6 text-center text-sm text-slate-500 dark:text-gray-400">Tidak ada siswa yang cocok.</div>
                                @endforelse
                            </div>
                        @endif
                    @else
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label for="kelas-siswa" class="mb-1 block text-xs font-medium text-slate-600 dark:text-gray-300">Kelas</label>
                                <select id="kelas-siswa" wire:model.live="kelas_id"
                                    class="w-full min-h-10 rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                                    <option value="">Pilih kelas</option>
                                    @foreach($this->kelasOptions as $id => $label)
                                        <option value="{{ $id }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="siswa-kelas" class="mb-1 block text-xs font-medium text-slate-600 dark:text-gray-300">Siswa</label>
                                <select id="siswa-kelas" wire:model.live="selectedSiswaId"
                                    class="w-full min-h-10 rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                    @disabled(! $kelas_id)>
                                    <option value="">Pilih siswa</option>
                                    @foreach($this->siswaHasil as $hasil)
                                        <option value="{{ $hasil->id }}">{{ $hasil->nama }} (NISN: {{ $hasil->nisn }})</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    @endif
                </div>
            </x-ui.card>
        @endif

        @if($siswa)
            {{-- 2. DETIL SISWA & RINGKASAN --}}
            <x-ui.card class="!max-w-none !p-6">
                <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                    <div class="space-y-1">
                        <div class="flex flex-wrap items-center gap-2.5">
                            <h2 class="text-xl font-bold text-slate-900 dark:text-white">{{ $siswa->nama }}</h2>
                            @if($tagihan['totalDitanggung'] > 0)
                                <x-ui.badge color="sky">Ditanggung Pemerintah</x-ui.badge>
                            @endif
                        </div>
                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-500 dark:text-gray-400">
                            <span>NISN: <strong class="font-mono text-slate-700 dark:text-gray-300">{{ $siswa->nisn ?: '-' }}</strong></span>
                            <span>NIS: <strong class="font-mono text-slate-700 dark:text-gray-300">{{ $siswa->nis ?: '-' }}</strong></span>
                            <span>Kelas: <strong class="text-slate-800 dark:text-gray-200">{{ $siswa->kelas?->nama_kelas ?? '-' }}</strong></span>
                            <span>Jurusan: <strong class="text-slate-800 dark:text-gray-200">{{ $siswa->kelas?->jurusan?->nama ?? '-' }}</strong></span>
                        </div>
                        <div class="text-xs text-slate-500 dark:text-gray-400">
                            Tahun Ajaran: <strong class="text-slate-800 dark:text-gray-200">{{ $siswa->spp?->tahun_ajaran ?? '-' }}</strong>
                            &bull; Tarif SPP: <strong class="text-slate-800 dark:text-gray-200 tabular-nums">Rp {{ number_format($siswa->spp?->nominal ?? 0, 0, ',', '.') }} / bulan</strong>
                            @if($siswa->no_telp)
                                &bull; Kontak: <span class="text-slate-700 dark:text-gray-300">{{ $siswa->no_telp }}</span>
                            @endif
                        </div>
                    </div>

                    {{-- Quick Action (misal Cetak Kartu jika Staff) --}}
                    @if($isStaff)
                        <div class="shrink-0 flex items-center gap-2">
                            <x-filament::button tag="a"
                                href="{{ route('laporan.kartu.cetak', ['siswa_id' => $siswa->id]) }}"
                                target="_blank" color="gray" size="sm" outlined icon="heroicon-o-printer">
                                Cetak Kartu Ledger
                            </x-filament::button>
                        </div>
                    @endif
                </div>

                {{-- Metric Cards --}}
                <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 border-t border-slate-200 pt-5 dark:border-gray-800">
                    <div class="rounded-xl border border-slate-200/80 bg-slate-50/70 p-4 dark:border-white/10 dark:bg-white/5">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-gray-400">Total Kewajiban</span>
                        <div class="mt-1.5 text-2xl font-bold text-slate-900 dark:text-white tabular-nums">
                            Rp {{ number_format($tagihan['totalNominal'], 0, ',', '.') }}
                        </div>
                        <span class="text-[11px] text-slate-400 dark:text-gray-500">{{ $tagihan['countSemua'] }} pos tagihan</span>
                    </div>

                    <div class="rounded-xl border border-emerald-200/80 bg-emerald-50/50 p-4 dark:border-emerald-500/30 dark:bg-emerald-950/25">
                        <span class="text-xs font-semibold uppercase tracking-wider text-emerald-700 dark:text-emerald-300">Total Terbayar</span>
                        <div class="mt-1.5 text-2xl font-bold text-emerald-700 dark:text-emerald-400 tabular-nums">
                            Rp {{ number_format($tagihan['totalDibayar'], 0, ',', '.') }}
                        </div>
                        <span class="text-[11px] text-emerald-600 dark:text-emerald-400 font-medium">{{ $tagihan['countLunas'] }} pos lunas</span>
                    </div>

                    <div class="rounded-xl border border-rose-200/80 bg-rose-50/50 p-4 dark:border-rose-500/30 dark:bg-rose-950/25">
                        <span class="text-xs font-semibold uppercase tracking-wider text-rose-700 dark:text-rose-300">Sisa Tagihan</span>
                        <div class="mt-1.5 text-2xl font-bold text-rose-700 dark:text-rose-400 tabular-nums">
                            Rp {{ number_format($tagihan['totalSisa'], 0, ',', '.') }}
                        </div>
                        <span class="text-[11px] text-rose-600 dark:text-rose-400 font-medium">{{ $tagihan['countBelumLunas'] }} pos belum lunas</span>
                    </div>

                    <div class="rounded-xl border border-sky-200/80 bg-sky-50/50 p-4 dark:border-sky-500/30 dark:bg-sky-950/25">
                        <span class="text-xs font-semibold uppercase tracking-wider text-sky-700 dark:text-sky-300">Ditanggung Pemerintah</span>
                        <div class="mt-1.5 text-2xl font-bold text-sky-700 dark:text-sky-400 tabular-nums">
                            Rp {{ number_format($tagihan['totalDitanggung'], 0, ',', '.') }}
                        </div>
                        <span class="text-[11px] text-sky-600 dark:text-sky-400 font-medium">Bantuan & afirmasi</span>
                    </div>
                </div>
            </x-ui.card>

            {{-- 3. TABEL RINCIAN TAGIHAN (Pos Tarif, Status, Tanggal Bayar) --}}
            <x-ui.card class="!max-w-none !p-0">
                <div class="border-b border-slate-200 px-6 py-4 dark:border-gray-800">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="text-base font-semibold text-slate-900 dark:text-white">Rincian Tagihan Siswa</h3>
                            <p class="text-xs text-slate-500 dark:text-gray-400">Daftar seluruh kewajiban SPP bulanan dan iuran non-SPP beserta status pelunasan.</p>
                        </div>

                        {{-- Search Input in Table --}}
                        <div class="w-full sm:w-64">
                            <input type="search" wire:model.live.debounce.250ms="cari_pos"
                                placeholder="Cari pos tarif / tanggal..."
                                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs text-slate-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                        </div>
                    </div>

                    {{-- Filter Tabs / Pills --}}
                    <div class="mt-4 flex flex-wrap items-center gap-2">
                        <button type="button" wire:click="setStatusFilter('semua')"
                            class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1 text-xs font-medium transition-colors {{ $status_filter === 'semua' ? 'bg-primary-50 text-primary-700 border border-primary-200/80 font-semibold dark:bg-primary-950/40 dark:text-primary-300 dark:border-primary-500/30' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-gray-800 dark:text-gray-300' }}">
                            <span>Semua</span>
                            <span class="rounded-full bg-white/70 px-1.5 py-0.2 text-[10px] tabular-nums dark:bg-gray-900/60">{{ $tagihan['countSemua'] }}</span>
                        </button>

                        <button type="button" wire:click="setStatusFilter('lunas')"
                            class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1 text-xs font-medium transition-colors {{ $status_filter === 'lunas' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200/80 font-semibold dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-500/30' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-gray-800 dark:text-gray-300' }}">
                            <span>Lunas / Terbayar</span>
                            <span class="rounded-full bg-white/70 px-1.5 py-0.2 text-[10px] tabular-nums dark:bg-gray-900/60">{{ $tagihan['countLunas'] }}</span>
                        </button>

                        <button type="button" wire:click="setStatusFilter('belum_lunas')"
                            class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1 text-xs font-medium transition-colors {{ $status_filter === 'belum_lunas' ? 'bg-rose-50 text-rose-700 border border-rose-200/80 font-semibold dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-500/30' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-gray-800 dark:text-gray-300' }}">
                            <span>Belum Lunas</span>
                            <span class="rounded-full bg-white/70 px-1.5 py-0.2 text-[10px] tabular-nums dark:bg-gray-900/60">{{ $tagihan['countBelumLunas'] }}</span>
                        </button>

                        @if($tagihan['countSebagian'] > 0)
                            <button type="button" wire:click="setStatusFilter('semua')"
                                class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1 text-xs font-medium transition-colors {{ $status_filter === 'sebagian' ? 'bg-amber-50 text-amber-700 border border-amber-200/80 font-semibold dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-500/30' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-gray-800 dark:text-gray-300' }}">
                                <span>Sebagian / Dicicil</span>
                                <span class="rounded-full bg-white/70 px-1.5 py-0.2 text-[10px] tabular-nums dark:bg-gray-900/60">{{ $tagihan['countSebagian'] }}</span>
                            </button>
                        @endif

                        @if($status_filter !== 'semua' || $cari_pos !== '')
                            <button type="button" wire:click="resetFilterTabel"
                                class="text-xs text-primary-600 hover:underline dark:text-primary-400 ml-auto">
                                Reset Filter
                            </button>
                        @endif
                    </div>
                </div>

                <div class="px-6 py-2 overflow-x-auto">
                    <x-ui.table>
                        <x-ui.table.columns>
                            <x-ui.table.head class="w-12 text-center">#</x-ui.table.head>
                            <x-ui.table.head>Pos Tarif</x-ui.table.head>
                            <x-ui.table.head class="text-right">Nominal Tagihan</x-ui.table.head>
                            <x-ui.table.head class="text-right">Jumlah Terbayar</x-ui.table.head>
                            <x-ui.table.head class="text-right">Sisa Tagihan</x-ui.table.head>
                            <x-ui.table.head>Tanggal Bayar</x-ui.table.head>
                            <x-ui.table.head class="text-center">Status</x-ui.table.head>
                            <x-ui.table.head class="text-center">Aksi</x-ui.table.head>
                        </x-ui.table.columns>
                        <x-ui.table.rows>
                            @forelse($filteredItems as $index => $item)
                                <x-ui.table.row :key="$item['id']">
                                    {{-- Kolom #: Nomor urut --}}
                                    <x-ui.table.cell class="text-center text-xs font-medium text-slate-400 tabular-nums">
                                        {{ $index + 1 }}
                                    </x-ui.table.cell>

                                    {{-- Kolom: POS TARIF --}}
                                    <x-ui.table.cell>
                                        <div class="flex flex-col gap-0.5">
                                            <div class="flex items-center gap-1.5">
                                                <span class="font-semibold text-slate-900 dark:text-white">
                                                    {{ $item['pos_tarif'] }}
                                                </span>
                                            </div>
                                            <div class="flex items-center gap-2 text-[11px] text-slate-500 dark:text-gray-400">
                                                <span class="inline-flex items-center rounded px-1.5 py-0.2 text-[10px] font-medium bg-slate-100 text-slate-600 dark:bg-gray-800 dark:text-gray-300">
                                                    {{ $item['kategori'] }}
                                                </span>
                                                <span>{{ $item['tipe'] }}</span>
                                            </div>
                                        </div>
                                    </x-ui.table.cell>

                                    {{-- Kolom: NOMINAL TAGIHAN --}}
                                    <x-ui.table.cell class="text-right font-medium text-slate-800 dark:text-gray-200 tabular-nums">
                                        Rp {{ number_format($item['nominal'], 0, ',', '.') }}
                                    </x-ui.table.cell>

                                    {{-- Kolom: JUMLAH TERBAYAR --}}
                                    <x-ui.table.cell class="text-right font-semibold text-emerald-700 dark:text-emerald-400 tabular-nums">
                                        Rp {{ number_format($item['dibayar'], 0, ',', '.') }}
                                    </x-ui.table.cell>

                                    {{-- Kolom: SISA TAGIHAN --}}
                                    <x-ui.table.cell class="text-right font-semibold text-rose-700 dark:text-rose-400 tabular-nums">
                                        Rp {{ number_format($item['sisa'], 0, ',', '.') }}
                                    </x-ui.table.cell>

                                    {{-- Kolom: TANGGAL BAYAR --}}
                                    <x-ui.table.cell class="text-xs text-slate-700 dark:text-gray-300 tabular-nums">
                                        @if($item['tanggal_bayar'])
                                            <div class="flex items-center gap-1.5">
                                                <span class="font-medium text-slate-900 dark:text-white">{{ $item['tanggal_bayar'] }}</span>
                                            </div>
                                        @elseif($item['ditanggung'])
                                            <span class="text-sky-600 dark:text-sky-400 font-medium">Ditanggung Pemda</span>
                                        @else
                                            <span class="text-slate-400 dark:text-gray-500">-</span>
                                        @endif
                                    </x-ui.table.cell>

                                    {{-- Kolom: STATUS (Terbayar / Lunas) --}}
                                    <x-ui.table.cell class="text-center">
                                        @if($item['ditanggung'])
                                            <x-ui.badge color="sky">Ditanggung</x-ui.badge>
                                        @elseif($item['status'] === 'lunas')
                                            <x-ui.badge color="emerald">Lunas</x-ui.badge>
                                        @elseif($item['status'] === 'sebagian')
                                            <x-ui.badge color="amber">Sebagian</x-ui.badge>
                                        @else
                                            <x-ui.badge color="rose">Belum Lunas</x-ui.badge>
                                        @endif
                                    </x-ui.table.cell>

                                    {{-- Kolom: AKSI (Cetak Kuitansi) --}}
                                    <x-ui.table.cell class="text-center">
                                        @if($item['pembayaran_terakhir'])
                                            <x-filament::button tag="a"
                                                href="{{ auth()->user()?->isSiswa() ? route('portal.kuitansi', $item['pembayaran_terakhir']->id) : route('pembayaran.cetak', $item['pembayaran_terakhir']->id) }}"
                                                target="_blank" color="gray" size="xs" outlined icon="heroicon-o-printer"
                                                class="whitespace-nowrap">
                                                Kuitansi
                                            </x-filament::button>
                                        @else
                                            <span class="text-xs text-slate-300 dark:text-gray-600">-</span>
                                        @endif
                                    </x-ui.table.cell>
                                </x-ui.table.row>
                            @empty
                                <x-ui.table.row>
                                    <x-ui.table.cell colspan="8" class="py-8 text-center text-sm text-slate-500 dark:text-gray-400">
                                        Tidak ada rincian tagihan yang sesuai dengan filter.
                                    </x-ui.table.cell>
                                </x-ui.table.row>
                            @endforelse
                        </x-ui.table.rows>

                        {{-- Footer Total --}}
                        @if(count($filteredItems) > 0)
                            <tfoot>
                                @php
                                    $subTotalNominal = array_sum(array_column($filteredItems, 'nominal'));
                                    $subTotalDibayar = array_sum(array_column($filteredItems, 'dibayar'));
                                    $subTotalSisa = array_sum(array_column($filteredItems, 'sisa'));
                                @endphp
                                <tr class="border-t-2 border-slate-300 bg-slate-50 font-semibold dark:border-gray-700 dark:bg-gray-800/60">
                                    <td colspan="2" class="px-3 py-3 text-left text-xs text-slate-900 dark:text-white uppercase tracking-wider">
                                        Total ({{ count($filteredItems) }} baris ditampilkan)
                                    </td>
                                    <td class="px-3 py-3 text-right text-xs text-slate-900 dark:text-white tabular-nums">
                                        Rp {{ number_format($subTotalNominal, 0, ',', '.') }}
                                    </td>
                                    <td class="px-3 py-3 text-right text-xs text-emerald-700 dark:text-emerald-400 tabular-nums">
                                        Rp {{ number_format($subTotalDibayar, 0, ',', '.') }}
                                    </td>
                                    <td class="px-3 py-3 text-right text-xs text-rose-700 dark:text-rose-400 tabular-nums">
                                        Rp {{ number_format($subTotalSisa, 0, ',', '.') }}
                                    </td>
                                    <td colspan="3" class="px-3 py-3"></td>
                                </tr>
                            </tfoot>
                        @endif
                    </x-ui.table>
                </div>
            </x-ui.card>
        @else
            <x-ui.card class="!max-w-none !p-12 text-center">
                <div class="mx-auto flex size-12 items-center justify-center rounded-full bg-slate-100 dark:bg-gray-800">
                    <x-filament::icon icon="heroicon-o-user" class="size-6 text-slate-400 dark:text-gray-500" />
                </div>
                <h3 class="mt-3 text-sm font-semibold text-slate-900 dark:text-white">Siswa Belum Dipilih</h3>
                <p class="mt-1 text-xs text-slate-500 dark:text-gray-400">Silakan gunakan filter di atas untuk mencari atau memilih siswa.</p>
            </x-ui.card>
        @endif
    </div>
</x-filament-panels::page>

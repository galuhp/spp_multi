<x-filament-panels::page>
    @push('styles')
        @vite('resources/css/tagihan-siswa.css')
    @endpush

    @php
        $siswa = $this->siswa;
        $tagihan = $this->tagihanData;
        $filteredItems = $this->filteredItems;
        $isStaff = auth()->user()?->isStaff() ?? false;
        $countWajib = $tagihan['countSemua'] - $tagihan['countDitanggung'];
    @endphp

    <div class="space-y-5">
        {{-- 1. FILTER SISWA --}}
        @if($isStaff)
            <x-ui.card class="!max-w-none !p-5">
                <div class="flex flex-col gap-4">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h3 class="text-base font-semibold text-slate-900 dark:text-white">Pilih Siswa untuk Ditinjau</h3>
                            <p class="mt-0.5 text-xs text-slate-500 dark:text-gray-400">Cari langsung berdasarkan nama/NISN, atau pilih kelas lalu siswa.</p>
                        </div>

                        <fieldset class="flex flex-wrap items-center gap-4 text-sm text-slate-700 dark:text-gray-300">
                            <legend class="sr-only">Metode pemilihan siswa</legend>
                            <label class="flex cursor-pointer items-center gap-2">
                                <input type="radio" wire:model.live="cara_pilih" value="cari" class="size-4 accent-primary-600">
                                <span class="font-medium">Cari Nama / NISN</span>
                            </label>
                            <label class="flex cursor-pointer items-center gap-2">
                                <input type="radio" wire:model.live="cara_pilih" value="kelas" class="size-4 accent-primary-600">
                                <span class="font-medium">Bertingkat (Kelas)</span>
                            </label>
                        </fieldset>
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
                                        class="flex w-full items-center justify-between gap-3 border-b border-slate-100 px-3 py-2.5 text-left text-sm last:border-b-0 hover:bg-primary-50/60 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-primary-400 dark:border-gray-800 dark:hover:bg-primary-950/30">
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
                                    class="w-full min-h-10 rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-200 disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
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
                <div class="mt-6 grid grid-cols-1 gap-4 border-t border-slate-200 pt-5 sm:grid-cols-2 lg:grid-cols-4 dark:border-gray-800">
                    <div class="rounded-xl border border-slate-200/80 bg-slate-50/70 p-4 dark:border-gray-700 dark:bg-gray-800/60">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-gray-400">Total Kewajiban</span>
                        <div class="mt-1.5 text-2xl font-bold text-slate-900 dark:text-white tabular-nums">
                            Rp {{ number_format($tagihan['totalNominal'], 0, ',', '.') }}
                        </div>
                        <span class="text-[11px] text-slate-500 dark:text-gray-400">{{ $countWajib }} pos tagihan (di luar ditanggung)</span>
                    </div>

                    <div class="rounded-xl border border-emerald-200/80 bg-emerald-50/50 p-4 dark:border-emerald-500/30 dark:bg-emerald-950/25">
                        <span class="text-xs font-semibold uppercase tracking-wider text-emerald-700 dark:text-emerald-300">Total Terbayar</span>
                        <div class="mt-1.5 text-2xl font-bold text-emerald-700 dark:text-emerald-400 tabular-nums">
                            Rp {{ number_format($tagihan['totalDibayar'], 0, ',', '.') }}
                        </div>
                        <span class="text-[11px] font-medium text-emerald-600 dark:text-emerald-400">{{ $tagihan['countLunas'] }} pos lunas / ditanggung</span>
                    </div>

                    <div class="rounded-xl border border-rose-200/80 bg-rose-50/50 p-4 dark:border-rose-500/30 dark:bg-rose-950/25">
                        <span class="text-xs font-semibold uppercase tracking-wider text-rose-700 dark:text-rose-300">Sisa Tagihan</span>
                        <div class="mt-1.5 text-2xl font-bold text-rose-700 dark:text-rose-400 tabular-nums">
                            Rp {{ number_format($tagihan['totalSisa'], 0, ',', '.') }}
                        </div>
                        <span class="text-[11px] font-medium text-rose-600 dark:text-rose-400">
                            {{ $tagihan['countBelumLunas'] }} pos belum lunas
                            @if($tagihan['countSebagian'] > 0)
                                &bull; {{ $tagihan['countSebagian'] }} dicicil
                            @endif
                        </span>
                    </div>

                    <div class="rounded-xl border border-sky-200/80 bg-sky-50/50 p-4 dark:border-sky-500/30 dark:bg-sky-950/25">
                        <span class="text-xs font-semibold uppercase tracking-wider text-sky-700 dark:text-sky-300">Ditanggung Pemerintah</span>
                        <div class="mt-1.5 text-2xl font-bold text-sky-700 dark:text-sky-400 tabular-nums">
                            Rp {{ number_format($tagihan['totalDitanggung'], 0, ',', '.') }}
                        </div>
                        <span class="text-[11px] font-medium text-sky-600 dark:text-sky-400">{{ $tagihan['countDitanggung'] }} pos ditanggung</span>
                    </div>
                </div>
            </x-ui.card>

            {{-- 3. TABEL RINCIAN TAGIHAN --}}
            <x-ui.card class="!max-w-none !p-0">
                <div class="border-b border-slate-200 px-6 py-4 dark:border-gray-800">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="text-base font-semibold text-slate-900 dark:text-white">Rincian Tagihan Siswa</h3>
                            <p class="text-xs text-slate-500 dark:text-gray-400">Daftar seluruh kewajiban SPP bulanan dan iuran non-SPP beserta status pelunasan.</p>
                        </div>

                        {{-- Pencarian pos tarif --}}
                        <div class="relative w-full sm:w-64">
                            <label for="cari-pos" class="sr-only">Cari pos tarif atau tanggal bayar</label>
                            <input type="search" id="cari-pos" wire:model.live.debounce.250ms="cari_pos"
                                placeholder="Cari pos tarif / tanggal..."
                                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-1.5 pr-8 text-xs text-slate-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                            <span wire:loading wire:target="cari_pos" class="absolute inset-y-0 right-2 flex items-center text-primary-600 dark:text-primary-400" aria-hidden="true">
                                <x-ui.icon.loading class="size-3.5" />
                            </span>
                        </div>
                    </div>

                    {{-- Filter status --}}
                    <div class="mt-4 flex flex-wrap items-center gap-2">
                        <button type="button" wire:click="setStatusFilter('semua')" wire:loading.attr="disabled"
                            aria-pressed="{{ $status_filter === 'semua' ? 'true' : 'false' }}"
                            class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-1 text-xs transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-400 disabled:opacity-60 {{ $status_filter === 'semua' ? 'border-primary-200/80 bg-primary-50 font-semibold text-primary-700 dark:border-primary-500/30 dark:bg-primary-950/40 dark:text-primary-300' : 'border-transparent bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-gray-800 dark:text-gray-300' }}">
                            <span>Semua</span>
                            <span class="rounded-full bg-white/70 px-1.5 py-0.5 text-[10px] tabular-nums text-slate-600 dark:bg-gray-900/60 dark:text-gray-300">{{ $tagihan['countSemua'] }}</span>
                        </button>

                        <button type="button" wire:click="setStatusFilter('lunas')" wire:loading.attr="disabled"
                            aria-pressed="{{ $status_filter === 'lunas' ? 'true' : 'false' }}"
                            class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-1 text-xs transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400 disabled:opacity-60 {{ $status_filter === 'lunas' ? 'border-emerald-200/80 bg-emerald-50 font-semibold text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-950/40 dark:text-emerald-300' : 'border-transparent bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-gray-800 dark:text-gray-300' }}">
                            <span>Lunas / Ditanggung</span>
                            <span class="rounded-full bg-white/70 px-1.5 py-0.5 text-[10px] tabular-nums text-slate-600 dark:bg-gray-900/60 dark:text-gray-300">{{ $tagihan['countLunas'] }}</span>
                        </button>

                        <button type="button" wire:click="setStatusFilter('belum_lunas')" wire:loading.attr="disabled"
                            aria-pressed="{{ $status_filter === 'belum_lunas' ? 'true' : 'false' }}"
                            class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-1 text-xs transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-400 disabled:opacity-60 {{ $status_filter === 'belum_lunas' ? 'border-rose-200/80 bg-rose-50 font-semibold text-rose-700 dark:border-rose-500/30 dark:bg-rose-950/40 dark:text-rose-300' : 'border-transparent bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-gray-800 dark:text-gray-300' }}">
                            <span>Belum Lunas</span>
                            <span class="rounded-full bg-white/70 px-1.5 py-0.5 text-[10px] tabular-nums text-slate-600 dark:bg-gray-900/60 dark:text-gray-300">{{ $tagihan['countBelumLunas'] }}</span>
                        </button>

                        @if($tagihan['countSebagian'] > 0)
                            <button type="button" wire:click="setStatusFilter('sebagian')" wire:loading.attr="disabled"
                                aria-pressed="{{ $status_filter === 'sebagian' ? 'true' : 'false' }}"
                                class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-1 text-xs transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-400 disabled:opacity-60 {{ $status_filter === 'sebagian' ? 'border-amber-200/80 bg-amber-50 font-semibold text-amber-700 dark:border-amber-500/30 dark:bg-amber-950/40 dark:text-amber-300' : 'border-transparent bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-gray-800 dark:text-gray-300' }}">
                                <span>Sebagian / Dicicil</span>
                                <span class="rounded-full bg-white/70 px-1.5 py-0.5 text-[10px] tabular-nums text-slate-600 dark:bg-gray-900/60 dark:text-gray-300">{{ $tagihan['countSebagian'] }}</span>
                            </button>
                        @endif

                        @if($status_filter !== 'semua' || $cari_pos !== '')
                            <button type="button" wire:click="resetFilterTabel"
                                class="ml-auto rounded-md px-2 py-1 text-xs font-medium text-primary-600 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-400 dark:text-primary-400">
                                Reset Filter
                            </button>
                        @endif
                    </div>
                </div>

                <div class="px-6 py-2">
                    <x-ui.table wire:loading wire:target="cari_pos,status_filter,setStatusFilter,resetFilterTabel,selectedSiswaId,kelas_id,cari,pilihSiswa"
                        class="!max-h-[68vh] !overflow-y-auto">
                        <x-ui.table.columns>
                            <x-ui.table.head class="sticky top-0 z-10 bg-white dark:bg-gray-900">Pos Tarif</x-ui.table.head>
                            <x-ui.table.head class="sticky top-0 z-10 bg-white text-right dark:bg-gray-900">Nominal Tagihan</x-ui.table.head>
                            <x-ui.table.head class="sticky top-0 z-10 bg-white text-right dark:bg-gray-900">Jumlah Terbayar</x-ui.table.head>
                            <x-ui.table.head class="sticky top-0 z-10 bg-white text-right dark:bg-gray-900">Sisa Tagihan</x-ui.table.head>
                            <x-ui.table.head class="sticky top-0 z-10 bg-white dark:bg-gray-900">Tanggal Bayar</x-ui.table.head>
                            <x-ui.table.head class="sticky top-0 z-10 bg-white text-center dark:bg-gray-900">Status</x-ui.table.head>
                        </x-ui.table.columns>
                        <x-ui.table.rows>
                            @forelse($filteredItems as $item)
                                <x-ui.table.row :key="$item['id']">
                                    {{-- Pos Tarif --}}
                                    <x-ui.table.cell class="!py-2">
                                        <div class="flex flex-col gap-0.5">
                                            <span class="font-semibold text-slate-900 dark:text-white">
                                                {{ $item['pos_tarif'] }}
                                            </span>
                                            <div class="flex items-center gap-2 text-[11px] text-slate-500 dark:text-gray-400">
                                                <span class="inline-flex items-center rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-medium text-slate-600 dark:bg-gray-800 dark:text-gray-300">
                                                    {{ $item['kategori'] }}
                                                </span>
                                                <span>{{ $item['tipe'] }}</span>
                                            </div>
                                        </div>
                                    </x-ui.table.cell>

                                    {{-- Nominal --}}
                                    <x-ui.table.cell class="!py-2 text-right font-medium text-slate-800 tabular-nums dark:text-gray-200">
                                        Rp {{ number_format($item['nominal'], 0, ',', '.') }}
                                    </x-ui.table.cell>

                                    {{-- Terbayar --}}
                                    <x-ui.table.cell class="!py-2 text-right font-semibold text-emerald-700 tabular-nums dark:text-emerald-400">
                                        Rp {{ number_format($item['dibayar'], 0, ',', '.') }}
                                    </x-ui.table.cell>

                                    {{-- Sisa --}}
                                    <x-ui.table.cell class="!py-2 text-right font-semibold text-rose-700 tabular-nums dark:text-rose-400">
                                        Rp {{ number_format($item['sisa'], 0, ',', '.') }}
                                    </x-ui.table.cell>

                                    {{-- Tanggal bayar --}}
                                    <x-ui.table.cell class="!py-2 text-xs tabular-nums text-slate-700 dark:text-gray-300">
                                        @if($item['tanggal_bayar'])
                                            <span class="font-medium text-slate-900 dark:text-white">{{ $item['tanggal_bayar'] }}</span>
                                        @else
                                            <span class="text-slate-500 dark:text-gray-400">-</span>
                                        @endif
                                    </x-ui.table.cell>

                                    {{-- Status --}}
                                    <x-ui.table.cell class="!py-2 text-center">
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
                                </x-ui.table.row>
                            @empty
                                <x-ui.table.row>
                                    <x-ui.table.cell colspan="6" class="!py-8 text-center text-sm text-slate-500 dark:text-gray-400">
                                        Tidak ada rincian tagihan yang sesuai dengan filter.
                                    </x-ui.table.cell>
                                </x-ui.table.row>
                            @endforelse
                        </x-ui.table.rows>

                        {{-- Footer total (di luar pos ditanggung) --}}
                        @if(count($filteredItems) > 0)
                            @php
                                $barisDihitung = array_values(array_filter($filteredItems, fn (array $item): bool => ! $item['ditanggung']));
                                $jumlahDitanggung = count($filteredItems) - count($barisDihitung);
                                $subTotalNominal = array_sum(array_column($barisDihitung, 'nominal'));
                                $subTotalDibayar = array_sum(array_column($barisDihitung, 'dibayar'));
                                $subTotalSisa = array_sum(array_column($barisDihitung, 'sisa'));
                            @endphp
                            <tfoot>
                                <tr class="border-t-2 border-slate-300 bg-slate-50 font-semibold dark:border-gray-700 dark:bg-gray-800/60">
                                    <td class="px-3 py-3 text-left text-xs uppercase tracking-wider text-slate-900 dark:text-white">
                                        Total {{ count($barisDihitung) }} baris
                                        @if($jumlahDitanggung > 0)
                                            <span class="block text-[10px] font-normal normal-case tracking-normal text-slate-500 dark:text-gray-400">+ {{ $jumlahDitanggung }} pos ditanggung pemerintah</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-3 text-right text-xs tabular-nums text-slate-900 dark:text-white">
                                        Rp {{ number_format($subTotalNominal, 0, ',', '.') }}
                                    </td>
                                    <td class="px-3 py-3 text-right text-xs tabular-nums text-emerald-700 dark:text-emerald-400">
                                        Rp {{ number_format($subTotalDibayar, 0, ',', '.') }}
                                    </td>
                                    <td class="px-3 py-3 text-right text-xs tabular-nums text-rose-700 dark:text-rose-400">
                                        Rp {{ number_format($subTotalSisa, 0, ',', '.') }}
                                    </td>
                                    <td colspan="2" class="px-3 py-3"></td>
                                </tr>
                            </tfoot>
                        @endif
                    </x-ui.table>
                </div>
            </x-ui.card>
        @else
            <x-ui.card class="!max-w-none !p-12 text-center">
                <div class="mx-auto flex size-12 items-center justify-center rounded-full bg-slate-100 dark:bg-gray-800">
                    <x-filament::icon icon="heroicon-o-user" class="size-6 text-slate-500 dark:text-gray-400" />
                </div>
                <h3 class="mt-3 text-sm font-semibold text-slate-900 dark:text-white">Siswa Belum Dipilih</h3>
                <p class="mt-1 text-xs text-slate-500 dark:text-gray-400">Silakan gunakan filter di atas untuk mencari atau memilih siswa.</p>
            </x-ui.card>
        @endif
    </div>
</x-filament-panels::page>

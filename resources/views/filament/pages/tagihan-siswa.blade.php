<x-filament-panels::page>
    @push('styles')
        @vite('resources/css/tagihan-siswa.css')
    @endpush

    @php
        $siswa = $this->siswa;
        $tagihan = $this->tagihanData;
        $isStaff = auth()->user()?->isStaff() ?? false;
    @endphp

    <div class="space-y-5">
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
                                <span>Cari Nama / NISN</span>
                            </label>
                            <label class="flex cursor-pointer items-center gap-2">
                                <input type="radio" wire:model.live="cara_pilih" value="kelas"
                                    class="size-4 accent-primary-600">
                                <span>Bertingkat (Kelas)</span>
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
            <!-- Identitas Siswa -->
            <x-ui.card class="!max-w-none !p-6">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <span class="text-xs font-medium text-slate-500 dark:text-gray-400">Nama Siswa</span>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-base font-bold text-slate-900 dark:text-white">{{ $siswa->nama }}</span>
                            @if(($tagihan['totalDitanggung'] + $tagihan['totalNonSppDitanggung']) > 0)
                                <x-ui.badge color="sky">Ditanggung Pemerintah</x-ui.badge>
                            @endif
                        </div>
                        <div class="text-xs text-slate-500 dark:text-gray-400">NISN: {{ $siswa->nisn }} &bull; NIS: {{ $siswa->nis }}</div>
                    </div>
                    <div>
                        <span class="text-xs font-medium text-slate-500 dark:text-gray-400">Kelas & Jurusan</span>
                        <div class="text-base font-bold text-slate-900 dark:text-white">{{ $siswa->kelas?->nama_kelas }}</div>
                        <div class="text-xs text-slate-500 dark:text-gray-400">{{ $siswa->kelas?->jurusan?->nama }}</div>
                    </div>
                    <div>
                        <span class="text-xs font-medium text-slate-500 dark:text-gray-400">Tarif SPP Bulanan</span>
                        <div class="text-base font-bold text-slate-900 dark:text-white tabular-nums">
                            Rp {{ number_format($siswa->spp?->nominal ?? 0, 0, ',', '.') }} / bulan
                        </div>
                        <div class="text-xs text-slate-500 dark:text-gray-400">Tahun Ajaran: {{ $siswa->spp?->tahun_ajaran ?? '-' }}</div>
                    </div>
                    <div>
                        <span class="text-xs font-medium text-slate-500 dark:text-gray-400">Kontak & Alamat</span>
                        <div class="text-xs text-slate-700 dark:text-gray-300">{{ $siswa->no_telp ?: '-' }}</div>
                        <div class="text-xs text-slate-500 dark:text-gray-400 truncate">{{ $siswa->alamat ?: '-' }}</div>
                    </div>
                </div>
            </x-ui.card>

            <!-- Ringkasan -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <x-ui.card class="!max-w-none !p-5">
                    <span class="text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-gray-400">SPP Bulanan Terbayar</span>
                    <div class="mt-2 text-2xl font-bold text-emerald-700 dark:text-emerald-400 tabular-nums">
                        Rp {{ number_format($tagihan['totalLunas'], 0, ',', '.') }}
                    </div>
                </x-ui.card>

                <x-ui.card class="!max-w-none !p-5">
                    <span class="text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-gray-400">Sisa Tagihan SPP</span>
                    <div class="mt-2 text-2xl font-bold text-rose-700 dark:text-rose-400 tabular-nums">
                        Rp {{ number_format($tagihan['totalBelumLunas'], 0, ',', '.') }}
                    </div>
                </x-ui.card>

                <x-ui.card class="!max-w-none !p-5">
                    <span class="text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-gray-400">Sisa Tagihan Non-SPP</span>
                    <div class="mt-2 text-2xl font-bold text-amber-700 dark:text-amber-400 tabular-nums">
                        Rp {{ number_format($tagihan['totalNonSppBelumLunas'], 0, ',', '.') }}
                    </div>
                </x-ui.card>

                <x-ui.card class="!max-w-none !p-5">
                    <span class="text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-gray-400">Ditanggung Pemerintah</span>
                    <div class="mt-2 text-2xl font-bold text-sky-700 dark:text-sky-400 tabular-nums">
                        Rp {{ number_format($tagihan['totalDitanggung'] + $tagihan['totalNonSppDitanggung'], 0, ',', '.') }}
                    </div>
                </x-ui.card>
            </div>

            @if(($tagihan['totalTunggakanPrior'] ?? 0) > 0)
                <div class="rounded-xl border border-rose-300 bg-rose-50/70 p-5 shadow-xs dark:border-rose-500/30 dark:bg-rose-950/30">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex size-2 rounded-full bg-rose-600 animate-pulse"></span>
                            <h3 class="text-sm font-bold text-rose-900 dark:text-rose-200">
                                Tunggakan Tahun Pelajaran Sebelumnya (Wajib Dilunasi)
                            </h3>
                        </div>
                        <span class="rounded-md bg-rose-600 px-2.5 py-1 text-xs font-bold text-white tabular-nums">
                            Total: Rp {{ number_format($tagihan['totalTunggakanPrior'], 0, ',', '.') }}
                        </span>
                    </div>
                    <p class="mt-1 text-xs text-rose-700 dark:text-rose-300">
                        Siswa masih memiliki tanggungan iuran SPP atau non-SPP dari tingkat/tahun ajaran sebelumnya yang belum terselesaikan.
                    </p>
                    <div class="mt-3 overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="border-b border-rose-200 bg-rose-100/60 font-semibold text-rose-900 dark:border-rose-900 dark:bg-rose-900/40 dark:text-rose-200">
                                <tr>
                                    <th class="px-3 py-2">Tingkat</th>
                                    <th class="px-3 py-2">Pos / Bulan Tagihan</th>
                                    <th class="px-3 py-2 text-center">Jenis</th>
                                    <th class="px-3 py-2 text-right">Tarif</th>
                                    <th class="px-3 py-2 text-right">Sudah Dibayar</th>
                                    <th class="px-3 py-2 text-right">Sisa Tunggakan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-rose-100 bg-white/70 dark:divide-rose-900/50 dark:bg-gray-900/50">
                                @foreach($tagihan['tunggakanPrior'] as $tp)
                                    <tr>
                                        <td class="px-3 py-2 font-medium text-slate-800 dark:text-gray-200">{{ $tp['tahun_ajaran'] }}</td>
                                        <td class="px-3 py-2 text-slate-700 dark:text-gray-300">{{ $tp['bulan'] }}{{ !empty($tp['tahun']) ? ' ' . $tp['tahun'] : '' }}</td>
                                        <td class="px-3 py-2 text-center">
                                            <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-semibold {{ ($tp['tipe'] ?? '') === 'SPP' ? 'bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300' }}">
                                                {{ $tp['tipe'] ?? 'Biaya' }}
                                            </span>
                                        </td>
                                        <td class="px-3 py-2 text-right tabular-nums text-slate-600 dark:text-gray-400">Rp {{ number_format($tp['nominal'], 0, ',', '.') }}</td>
                                        <td class="px-3 py-2 text-right tabular-nums text-emerald-600 dark:text-emerald-400">Rp {{ number_format($tp['dibayar'], 0, ',', '.') }}</td>
                                        <td class="px-3 py-2 text-right font-bold tabular-nums text-rose-600 dark:text-rose-400">Rp {{ number_format($tp['sisa'], 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <!-- Matriks SPP Bulanan -->
            <x-ui.card class="!max-w-none !p-6">
                <div class="mb-4">
                    <h3 class="text-base font-semibold text-slate-900 dark:text-white">Matriks Pembayaran SPP Bulanan (12 Bulan Tahun Pelajaran)</h3>
                    <p class="text-xs text-slate-500 dark:text-gray-400">Status pelunasan iuran bulanan dari Juli hingga Juni.</p>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
                    @foreach($tagihan['bulanStatus'] as $item)
                        <div class="rounded-xl border p-4 text-center flex flex-col justify-between {{ $item['ditanggung'] ? 'border-sky-200/80 bg-sky-50/35 dark:border-sky-500/30 dark:bg-sky-950/25' : ($item['lunas'] ? 'border-emerald-200/80 bg-emerald-50/35 dark:border-emerald-500/30 dark:bg-emerald-950/25' : ($item['status'] === 'sebagian' ? 'border-amber-200/80 bg-amber-50/30 dark:border-amber-500/30 dark:bg-amber-950/25' : 'border-slate-200 bg-white dark:border-gray-700 dark:bg-gray-800/50')) }}">
                            <div>
                                <div class="text-sm font-bold text-slate-900 dark:text-white">{{ $item['bulan'] }}</div>
                                <div class="text-xs text-slate-500 dark:text-gray-400">{{ $item['tahun'] }}</div>
                                <div class="mt-2.5">
                                    @if($item['ditanggung'])
                                        <x-ui.badge color="sky">Ditanggung Pemerintah</x-ui.badge>
                                        <div class="mt-1.5 text-xs font-semibold text-sky-700 dark:text-sky-300 tabular-nums">
                                            Rp {{ number_format($item['nominal'], 0, ',', '.') }}
                                        </div>
                                    @elseif($item['lunas'])
                                        <x-ui.badge color="emerald">Lunas</x-ui.badge>
                                        <div class="mt-1.5 text-xs font-semibold text-emerald-700 dark:text-emerald-300 tabular-nums">
                                            Rp {{ number_format($item['dibayar'], 0, ',', '.') }}
                                        </div>
                                    @elseif($item['status'] === 'sebagian')
                                        <x-ui.badge color="amber">Sebagian</x-ui.badge>
                                        <div class="mt-1.5 text-xs text-emerald-700 dark:text-emerald-300 tabular-nums">
                                            Dibayar Rp {{ number_format($item['dibayar'], 0, ',', '.') }}
                                        </div>
                                        <div class="text-xs font-semibold text-amber-700 dark:text-amber-300 tabular-nums">
                                            Sisa Rp {{ number_format($item['sisa'], 0, ',', '.') }}
                                        </div>
                                    @else
                                        <x-ui.badge color="rose">Belum Lunas</x-ui.badge>
                                        <div class="mt-1.5 text-xs font-medium text-slate-500 dark:text-gray-400 tabular-nums">
                                            Rp {{ number_format($item['nominal'], 0, ',', '.') }}
                                        </div>
                                    @endif
                                </div>
                            </div>

                            @if($item['pembayaran'])
                                <div class="mt-3">
                                    <x-filament::button tag="a"
                                        href="{{ auth()->user()?->isSiswa() ? route('portal.kuitansi', $item['pembayaran']->id) : route('pembayaran.cetak', $item['pembayaran']->id) }}"
                                        target="_blank" color="gray" size="sm" outlined icon="heroicon-o-printer"
                                        class="w-full whitespace-nowrap">
                                        Cetak Kuitansi
                                    </x-filament::button>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </x-ui.card>

            <!-- Non-SPP -->
            @if(count($tagihan['nonSppStatus']) > 0)
                <x-ui.card class="!max-w-none !p-6">
                    <div class="mb-4">
                        <h3 class="text-base font-semibold text-slate-900 dark:text-white">Pos Tagihan Lainnya (Non-SPP)</h3>
                        <p class="text-xs text-slate-500 dark:text-gray-400">Uang Gedung, Seragam, Praktik, Ujian dan biaya kegiatan non-bulanan.</p>
                    </div>

                    <div class="overflow-x-auto">
                        <x-ui.table>
                            <x-ui.table.columns>
                                <x-ui.table.head>Pos Tarif</x-ui.table.head>
                                <x-ui.table.head>Kategori</x-ui.table.head>
                                <x-ui.table.head class="text-right">Total Kewajiban</x-ui.table.head>
                                <x-ui.table.head class="text-right">Sudah Dibayar</x-ui.table.head>
                                <x-ui.table.head class="text-right">Sisa Tagihan</x-ui.table.head>
                                <x-ui.table.head class="text-center">Status</x-ui.table.head>
                            </x-ui.table.columns>
                            <x-ui.table.rows>
                                @foreach($tagihan['nonSppStatus'] as $item)
                                    <x-ui.table.row>
                                        <x-ui.table.cell class="font-medium">
                                            {{ $item['tarif']->nama ?: $item['tarif']->kategori }}
                                        </x-ui.table.cell>
                                        <x-ui.table.cell class="text-xs">
                                            <x-ui.badge variant="outline">{{ $item['tarif']->kategori }}</x-ui.badge>
                                        </x-ui.table.cell>
                                        <x-ui.table.cell class="text-right font-medium tabular-nums">
                                            Rp {{ number_format($item['nominal'], 0, ',', '.') }}
                                        </x-ui.table.cell>
                                        <x-ui.table.cell class="text-right font-semibold text-emerald-700 dark:text-emerald-400 tabular-nums">
                                            Rp {{ number_format($item['dibayar'], 0, ',', '.') }}
                                        </x-ui.table.cell>
                                        <x-ui.table.cell class="text-right font-semibold text-rose-700 dark:text-rose-400 tabular-nums">
                                            Rp {{ number_format($item['sisa'], 0, ',', '.') }}
                                        </x-ui.table.cell>
                                        <x-ui.table.cell class="text-center">
                                            @if($item['ditanggung'])
                                                <x-ui.badge color="sky">Ditanggung Pemerintah</x-ui.badge>
                                            @elseif($item['lunas'])
                                                <x-ui.badge color="green">Lunas</x-ui.badge>
                                            @elseif($item['dibayar'] > 0)
                                                <x-ui.badge color="yellow">Dicicil</x-ui.badge>
                                            @else
                                                <x-ui.badge color="red">Belum Bayar</x-ui.badge>
                                            @endif
                                        </x-ui.table.cell>
                                    </x-ui.table.row>
                                @endforeach
                            </x-ui.table.rows>
                        </x-ui.table>
                    </div>
                </x-ui.card>
            @endif
        @else
            <x-ui.card class="!max-w-none !p-8 text-center">
                <p class="text-sm text-slate-500 dark:text-gray-400">Data siswa tidak ditemukan.</p>
            </x-ui.card>
        @endif
    </div>
</x-filament-panels::page>
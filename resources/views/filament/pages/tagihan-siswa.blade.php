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
                            <h3 class="text-base font-semibold text-zinc-900">Pilih Siswa untuk Ditinjau</h3>
                            <p class="mt-0.5 text-xs text-zinc-500">Cari langsung berdasarkan nama/NISN, atau pilih kelas lalu siswa.</p>
                        </div>

                        <div class="flex flex-wrap items-center gap-4 text-sm">
                            <label class="flex cursor-pointer items-center gap-2">
                                <input type="radio" wire:model.live="cara_pilih" value="cari"
                                    class="size-4 accent-zinc-900">
                                <span>Cari Nama / NISN</span>
                            </label>
                            <label class="flex cursor-pointer items-center gap-2">
                                <input type="radio" wire:model.live="cara_pilih" value="kelas"
                                    class="size-4 accent-zinc-900">
                                <span>Bertingkat (Kelas)</span>
                            </label>
                        </div>
                    </div>

                    @if($cara_pilih === 'cari')
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label for="cari-siswa" class="mb-1 block text-xs font-medium text-zinc-600">Kata kunci</label>
                                <input type="search" id="cari-siswa" wire:model.live.debounce.300ms="cari"
                                    placeholder="Ketik nama, NISN, atau NIS..."
                                    class="w-full min-h-10 rounded-field border border-zinc-300 bg-white px-3 text-sm focus:outline-none focus:ring-2 focus:ring-zinc-300">
                                <p class="mt-1 text-[11px] text-zinc-500">Minimal 2 karakter. Hasil maksimal 20 siswa.</p>
                            </div>

                            <div>
                                @if($this->siswa)
                                    <div class="rounded-field border border-zinc-200 bg-zinc-50 p-3">
                                        <div class="text-[11px] font-medium uppercase tracking-wide text-zinc-500">Siswa terpilih</div>
                                        <div class="mt-1 font-semibold text-zinc-900">{{ $this->siswa->nama }}</div>
                                        <div class="text-xs text-zinc-500">
                                            NISN {{ $this->siswa->nisn ?: '-' }} &bull; {{ $this->siswa->kelas?->nama_kelas }}
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        @if(mb_strlen(trim($cari)) >= 2)
                            <div class="overflow-hidden rounded-field border border-zinc-200">
                                @forelse($this->siswaHasil as $hasil)
                                    <button type="button" wire:key="hasil-{{ $hasil->id }}" wire:click="pilihSiswa({{ $hasil->id }})"
                                        class="flex w-full items-center justify-between gap-3 border-b border-zinc-100 px-3 py-2.5 text-left text-sm last:border-b-0 hover:bg-zinc-50">
                                        <span>
                                            <span class="font-medium text-zinc-900">{{ $hasil->nama }}</span>
                                            <span class="block text-xs text-zinc-500">NISN {{ $hasil->nisn ?: '-' }} &bull; {{ $hasil->kelas?->nama_kelas }}</span>
                                        </span>
                                        <span class="text-xs font-medium text-zinc-500">Pilih</span>
                                    </button>
                                @empty
                                    <div class="px-3 py-6 text-center text-sm text-zinc-500">Tidak ada siswa yang cocok.</div>
                                @endforelse
                            </div>
                        @endif
                    @else
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label for="kelas-siswa" class="mb-1 block text-xs font-medium text-zinc-600">Kelas</label>
                                <select id="kelas-siswa" wire:model.live="kelas_id"
                                    class="w-full min-h-10 rounded-field border border-zinc-300 bg-white px-3 text-sm focus:outline-none focus:ring-2 focus:ring-zinc-300">
                                    <option value="">Pilih kelas</option>
                                    @foreach($this->kelasOptions as $id => $label)
                                        <option value="{{ $id }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="siswa-kelas" class="mb-1 block text-xs font-medium text-zinc-600">Siswa</label>
                                <select id="siswa-kelas" wire:model.live="selectedSiswaId"
                                    class="w-full min-h-10 rounded-field border border-zinc-300 bg-white px-3 text-sm focus:outline-none focus:ring-2 focus:ring-zinc-300"
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
                        <span class="text-xs font-medium text-zinc-500">Nama Siswa</span>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-base font-bold text-zinc-900">{{ $siswa->nama }}</span>
                            @if(($tagihan['totalDitanggung'] + $tagihan['totalNonSppDitanggung']) > 0)
                                <x-ui.badge color="sky">Ditanggung Pemerintah</x-ui.badge>
                            @endif
                        </div>
                        <div class="text-xs text-zinc-500">NISN: {{ $siswa->nisn }} &bull; NIS: {{ $siswa->nis }}</div>
                    </div>
                    <div>
                        <span class="text-xs font-medium text-zinc-500">Kelas & Jurusan</span>
                        <div class="text-base font-bold text-zinc-900">{{ $siswa->kelas?->nama_kelas }}</div>
                        <div class="text-xs text-zinc-500">{{ $siswa->kelas?->jurusan?->nama }}</div>
                    </div>
                    <div>
                        <span class="text-xs font-medium text-zinc-500">Tarif SPP Bulanan</span>
                        <div class="text-base font-bold text-zinc-900">
                            Rp {{ number_format($siswa->spp?->nominal ?? 0, 0, ',', '.') }} / bulan
                        </div>
                        <div class="text-xs text-zinc-500">Tahun Ajaran: {{ $siswa->spp?->tahun_ajaran ?? '-' }}</div>
                    </div>
                    <div>
                        <span class="text-xs font-medium text-zinc-500">Kontak & Alamat</span>
                        <div class="text-xs text-zinc-700">{{ $siswa->no_telp ?: '-' }}</div>
                        <div class="text-xs text-zinc-500 truncate">{{ $siswa->alamat ?: '-' }}</div>
                    </div>
                </div>
            </x-ui.card>

            <!-- Ringkasan -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <x-ui.card class="!max-w-none !p-5">
                    <span class="text-xs font-medium uppercase tracking-wider text-emerald-700">SPP Bulanan Terbayar</span>
                    <div class="mt-2 text-2xl font-bold text-emerald-700">
                        Rp {{ number_format($tagihan['totalLunas'], 0, ',', '.') }}
                    </div>
                </x-ui.card>

                <x-ui.card class="!max-w-none !p-5">
                    <span class="text-xs font-medium uppercase tracking-wider text-rose-700">Sisa Tagihan SPP</span>
                    <div class="mt-2 text-2xl font-bold text-rose-700">
                        Rp {{ number_format($tagihan['totalBelumLunas'], 0, ',', '.') }}
                    </div>
                </x-ui.card>

                <x-ui.card class="!max-w-none !p-5">
                    <span class="text-xs font-medium uppercase tracking-wider text-amber-700">Sisa Tagihan Non-SPP</span>
                    <div class="mt-2 text-2xl font-bold text-amber-700">
                        Rp {{ number_format($tagihan['totalNonSppBelumLunas'], 0, ',', '.') }}
                    </div>
                </x-ui.card>

                <x-ui.card class="!max-w-none !p-5">
                    <span class="text-xs font-medium uppercase tracking-wider text-sky-700">Ditanggung Pemerintah</span>
                    <div class="mt-2 text-2xl font-bold text-sky-700">
                        Rp {{ number_format($tagihan['totalDitanggung'] + $tagihan['totalNonSppDitanggung'], 0, ',', '.') }}
                    </div>
                </x-ui.card>
            </div>

            <!-- Matriks SPP Bulanan -->
            <x-ui.card class="!max-w-none !p-6">
                <div class="mb-4">
                    <h3 class="text-base font-semibold text-zinc-900">Matriks Pembayaran SPP Bulanan (12 Bulan Tahun Pelajaran)</h3>
                    <p class="text-xs text-zinc-500">Status pelunasan iuran bulanan dari Juli hingga Juni.</p>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
                    @foreach($tagihan['bulanStatus'] as $item)
                        <div class="rounded-xl border p-4 text-center flex flex-col justify-between {{ $item['ditanggung'] ? 'border-sky-200/80 bg-sky-50/35' : ($item['lunas'] ? 'border-emerald-200/80 bg-emerald-50/35' : ($item['status'] === 'sebagian' ? 'border-amber-200/80 bg-amber-50/30' : 'border-slate-200 bg-white')) }}">
                            <div>
                                <div class="text-sm font-bold text-slate-900">{{ $item['bulan'] }}</div>
                                <div class="text-xs text-slate-500">{{ $item['tahun'] }}</div>
                                <div class="mt-2.5">
                                    @if($item['ditanggung'])
                                        <x-ui.badge color="sky">Ditanggung Pemerintah</x-ui.badge>
                                        <div class="mt-1.5 text-xs font-semibold text-sky-700 tabular-nums">
                                            Rp {{ number_format($item['nominal'], 0, ',', '.') }}
                                        </div>
                                    @elseif($item['lunas'])
                                        <x-ui.badge color="emerald">Lunas</x-ui.badge>
                                        <div class="mt-1.5 text-xs font-semibold text-emerald-700 tabular-nums">
                                            Rp {{ number_format($item['dibayar'], 0, ',', '.') }}
                                        </div>
                                    @elseif($item['status'] === 'sebagian')
                                        <x-ui.badge color="amber">Sebagian</x-ui.badge>
                                        <div class="mt-1.5 text-xs text-emerald-700 tabular-nums">
                                            Dibayar Rp {{ number_format($item['dibayar'], 0, ',', '.') }}
                                        </div>
                                        <div class="text-xs font-semibold text-amber-700 tabular-nums">
                                            Sisa Rp {{ number_format($item['sisa'], 0, ',', '.') }}
                                        </div>
                                    @else
                                        <x-ui.badge color="rose">Belum Lunas</x-ui.badge>
                                        <div class="mt-1.5 text-xs font-medium text-slate-500 tabular-nums">
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
                        <h3 class="text-base font-semibold text-zinc-900">Pos Tagihan Lainnya (Non-SPP)</h3>
                        <p class="text-xs text-zinc-500">Uang Gedung, Seragam, Praktik, Ujian dan biaya kegiatan non-bulanan.</p>
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
                                        <x-ui.table.cell class="text-right font-medium">
                                            Rp {{ number_format($item['nominal'], 0, ',', '.') }}
                                        </x-ui.table.cell>
                                        <x-ui.table.cell class="text-right font-semibold text-emerald-700">
                                            Rp {{ number_format($item['dibayar'], 0, ',', '.') }}
                                        </x-ui.table.cell>
                                        <x-ui.table.cell class="text-right font-semibold text-rose-700">
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
                <p class="text-sm text-zinc-500">Data siswa tidak ditemukan.</p>
            </x-ui.card>
        @endif
    </div>
</x-filament-panels::page>
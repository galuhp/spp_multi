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
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h3 class="text-base font-semibold text-zinc-900">Pilih Siswa untuk Ditinjau</h3>
                        <p class="mt-0.5 text-xs text-zinc-500">Pratinjau matriks kewajiban dan pembayaran iuran siswa terpilih.</p>
                    </div>
                    <div class="w-full sm:w-80">
                        <select wire:model.live="selectedSiswaId"
                            class="w-full min-h-10 rounded-field border border-zinc-300 bg-white px-3 text-sm focus:outline-none focus:ring-2 focus:ring-zinc-300">
                            @foreach(\App\Models\Siswa::with('kelas')->tanpaArsip()->orderBy('nama')->get() as $s)
                                <option value="{{ $s->id }}">{{ $s->nama }} (NISN: {{ $s->nisn }} - {{ $s->kelas?->nama_kelas }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </x-ui.card>
        @endif

        @if($siswa)
            <!-- Identitas Siswa -->
            <x-ui.card class="!max-w-none !p-6">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <span class="text-xs font-medium text-zinc-500">Nama Siswa</span>
                        <div class="text-base font-bold text-zinc-900">{{ $siswa->nama }}</div>
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
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
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
            </div>

            <!-- Matriks SPP Bulanan -->
            <x-ui.card class="!max-w-none !p-6">
                <div class="mb-4">
                    <h3 class="text-base font-semibold text-zinc-900">Matriks Pembayaran SPP Bulanan (12 Bulan Tahun Pelajaran)</h3>
                    <p class="text-xs text-zinc-500">Status pelunasan iuran bulanan dari Juli hingga Juni.</p>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
                    @foreach($tagihan['bulanStatus'] as $item)
                        <div class="rounded-field border p-4 text-center {{ $item['lunas'] ? 'border-emerald-200 bg-emerald-50' : 'border-zinc-200 bg-zinc-50' }}">
                            <div class="text-sm font-bold text-zinc-900">{{ $item['bulan'] }}</div>
                            <div class="text-xs text-zinc-500">{{ $item['tahun'] }}</div>
                            <div class="mt-3">
                                @if($item['lunas'])
                                    <x-ui.badge color="green">Lunas</x-ui.badge>
                                    <div class="mt-1 text-xs font-semibold text-emerald-700">
                                        Rp {{ number_format($item['pembayaran']->jumlah_bayar, 0, ',', '.') }}
                                    </div>
                                    @if($item['pembayaran'])
                                        <div class="mt-2">
                                            <a href="{{ route('pembayaran.cetak', $item['pembayaran']->id) }}" target="_blank"
                                                class="inline-block text-[11px] font-medium text-zinc-900 hover:underline">
                                                Kwitansi &raquo;
                                            </a>
                                        </div>
                                    @endif
                                @else
                                    <x-ui.badge color="red">Belum Lunas</x-ui.badge>
                                    <div class="mt-1 text-xs text-zinc-500">
                                        Rp {{ number_format($item['nominal'], 0, ',', '.') }}
                                    </div>
                                @endif
                            </div>
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
                                            @if($item['lunas'])
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
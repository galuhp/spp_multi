<x-filament-panels::page>
    @push('styles')
        @vite('resources/css/laporan-pembayaran.css')
    @endpush

    @php
        $pembayarans = $this->filteredPembayarans;
        $totalPenerimaan = $pembayarans->sum('jumlah_bayar');
        $totalTransaksi = $pembayarans->count();
        $totalSiswa = $pembayarans->pluck('siswa_id')->unique()->count();
    @endphp

    <!-- Filter Card -->
    <div class="fi-section rounded-xl bg-white p-6 shadow-xs ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="text-base font-semibold leading-6 text-gray-950 dark:text-white">
                Filter Rekapitulasi Pembayaran
            </h3>
            <x-filament::button color="gray" size="sm" wire:click="resetFilter">
                Reset Filter
            </x-filament::button>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Tanggal Awal</label>
                <input type="date" wire:model.live="tgl_awal" class="laporan-field">
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Tanggal Akhir</label>
                <input type="date" wire:model.live="tgl_akhir" class="laporan-field">
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Pos / Kategori</label>
                <select wire:model.live="kategori" class="laporan-field">
                    <option value="">Semua Pos Tarif</option>
                    @foreach($this->kategoriList as $kat)
                        <option value="{{ $kat }}">{{ $kat }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Rombel Kelas</label>
                <select wire:model.live="kelas_id" class="laporan-field">
                    <option value="">Semua Kelas</option>
                    @foreach($this->kelasList as $k)
                        <option value="{{ $k->id }}">{{ $k->nama_kelas }} ({{ $k->jurusan?->kode }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Metode Pembayaran</label>
                <select wire:model.live="metode_pembayaran" class="laporan-field">
                    <option value="">Semua Metode</option>
                    <option value="Tunai / Kasir">Tunai / Kasir</option>
                    <option value="Transfer Bank">Transfer Bank</option>
                    <option value="QRIS / Digital">QRIS / Digital</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Summary Metrics -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="fi-section rounded-xl bg-white p-5 shadow-xs ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <span class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Total Penerimaan</span>
            <div class="mt-2 text-2xl font-bold text-primary-600 dark:text-primary-400">
                Rp {{ number_format($totalPenerimaan, 0, ',', '.') }}
            </div>
        </div>

        <div class="fi-section rounded-xl bg-white p-5 shadow-xs ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <span class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Total Transaksi</span>
            <div class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">
                {{ number_format($totalTransaksi, 0, ',', '.') }}
            </div>
        </div>

        <div class="fi-section rounded-xl bg-white p-5 shadow-xs ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <span class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Siswa Terlayani</span>
            <div class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">
                {{ number_format($totalSiswa, 0, ',', '.') }} Siswa
            </div>
        </div>
    </div>

    <!-- Data Table -->
    <div class="fi-section overflow-hidden rounded-xl bg-white shadow-xs ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
                <thead class="border-b border-gray-200 bg-gray-50 text-xs font-semibold uppercase tracking-wider text-gray-700 dark:border-gray-800 dark:bg-gray-800/50 dark:text-gray-300">
                    <tr>
                        <th class="px-4 py-3.5">Kode Transaksi</th>
                        <th class="px-4 py-3.5">Tanggal</th>
                        <th class="px-4 py-3.5">Siswa</th>
                        <th class="px-4 py-3.5">Pos & Keterangan</th>
                        <th class="px-4 py-3.5">Metode</th>
                        <th class="px-4 py-3.5 text-right">Jumlah</th>
                        <th class="px-4 py-3.5">Petugas</th>
                        <th class="px-4 py-3.5 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($pembayarans as $p)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/40">
                            <td class="px-4 py-3 font-mono text-xs font-semibold text-primary-600 dark:text-primary-400">
                                {{ $p->kode_transaksi }}
                            </td>
                            <td class="px-4 py-3 text-xs">
                                {{ \Carbon\Carbon::parse($p->tgl_bayar)->translatedFormat('d M Y') }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900 dark:text-white">{{ $p->siswa?->nama }}</div>
                                <div class="text-xs text-gray-500">NISN: {{ $p->siswa?->nisn }} &bull; {{ $p->siswa?->kelas?->nama_kelas }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-md bg-primary-50 px-2 py-0.5 text-xs font-medium text-primary-700 dark:bg-primary-950/50 dark:text-primary-300">
                                    {{ $p->spp?->kategori ?? 'SPP' }}
                                </span>
                                <div class="text-xs text-gray-600 dark:text-gray-300 font-medium mt-0.5">
                                    {{ $p->namaPos() }}
                                </div>
                                @if($p->catatan)
                                    <div class="text-[11px] text-gray-400 mt-0.5">{{ $p->catatan }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs">
                                {{ $p->metode_pembayaran }}
                            </td>
                            <td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-white">
                                Rp {{ number_format($p->jumlah_bayar, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-500">
                                {{ $p->user?->name ?? 'Kasir' }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <x-filament::button tag="a" href="{{ route('pembayaran.cetak', $p->id) }}" target="_blank"
                                    color="gray" size="sm" outlined icon="heroicon-o-printer" class="whitespace-nowrap">
                                    Cetak Kuitansi
                                </x-filament::button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-sm text-gray-500">
                                Tidak ada data transaksi pembayaran yang sesuai dengan filter yang dipilih.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>

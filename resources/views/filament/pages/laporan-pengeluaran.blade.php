<x-filament-panels::page>
    @push('styles')
        @vite('resources/css/laporan-pembayaran.css')
    @endpush

    @php
        $pengeluarans = $this->filteredPengeluarans;
        $totalPengeluaran = $pengeluarans->sum('nominal');
        $totalTransaksi = $pengeluarans->count();
        $rataRata = $totalTransaksi > 0 ? (int) ($totalPengeluaran / $totalTransaksi) : 0;
    @endphp

    <!-- Filter Card -->
    <div class="fi-section rounded-xl bg-white p-6 shadow-xs ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="text-base font-semibold leading-6 text-gray-950 dark:text-white">
                Filter Rekapitulasi Pengeluaran Sekolah
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
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Kategori Pengeluaran</label>
                <select wire:model.live="kategori" class="laporan-field">
                    <option value="">Semua Kategori</option>
                    @foreach($this->kategoriList as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Metode Pembayaran</label>
                <select wire:model.live="metode_pembayaran" class="laporan-field">
                    <option value="">Semua Metode</option>
                    <option value="Tunai">Tunai</option>
                    <option value="Transfer Bank">Transfer Bank</option>
                    <option value="QRIS">QRIS</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Tahun Ajaran</label>
                <select wire:model.live="tahun_ajaran_id" class="laporan-field">
                    <option value="">Semua Tahun Ajaran</option>
                    @foreach($this->tahunAjaranList as $ta)
                        <option value="{{ $ta->id }}">{{ $ta->nama }} {{ $ta->aktif ? '(Aktif)' : '' }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <!-- Summary Metrics -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="fi-section rounded-xl bg-white p-5 shadow-xs ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <span class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Total Pengeluaran</span>
            <div class="mt-2 text-2xl font-bold text-rose-600 dark:text-rose-400">
                Rp {{ number_format($totalPengeluaran, 0, ',', '.') }}
            </div>
            <div class="mt-1 text-xs text-gray-500">
                Akumulasi belanja operasional terverifikasi
            </div>
        </div>

        <div class="fi-section rounded-xl bg-white p-5 shadow-xs ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <span class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Total Transaksi</span>
            <div class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">
                {{ number_format($totalTransaksi, 0, ',', '.') }}
            </div>
            <div class="mt-1 text-xs text-gray-500">
                Voucher kas keluar tercatat pada periode ini
            </div>
        </div>

        <div class="fi-section rounded-xl bg-white p-5 shadow-xs ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <span class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Rata-rata Pengeluaran</span>
            <div class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">
                Rp {{ number_format($rataRata, 0, ',', '.') }}
            </div>
            <div class="mt-1 text-xs text-gray-500">
                Rata-rata nominal per transaksi kas keluar
            </div>
        </div>
    </div>

    <!-- Data Table Preview -->
    <div class="fi-section overflow-hidden rounded-xl bg-white shadow-xs ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
                <thead class="border-b border-gray-200 bg-gray-50 text-xs font-semibold uppercase tracking-wider text-gray-700 dark:border-gray-800 dark:bg-gray-800/50 dark:text-gray-300">
                    <tr>
                        <th class="px-4 py-3.5">No. Bukti</th>
                        <th class="px-4 py-3.5">Tanggal</th>
                        <th class="px-4 py-3.5">Kategori</th>
                        <th class="px-4 py-3.5">Uraian / Keperluan</th>
                        <th class="px-4 py-3.5">Penerima</th>
                        <th class="px-4 py-3.5">Metode</th>
                        <th class="px-4 py-3.5 text-right">Nominal</th>
                        <th class="px-4 py-3.5">Pencatat</th>
                        <th class="px-4 py-3.5 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($pengeluarans as $item)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/40">
                            <td class="px-4 py-3 font-mono text-xs font-semibold text-primary-600 dark:text-primary-400 whitespace-nowrap">
                                {{ $item->nomor_transaksi }}
                            </td>
                            <td class="px-4 py-3 text-xs whitespace-nowrap">
                                {{ $item->tanggal->translatedFormat('d M Y') }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-md bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-800 dark:bg-zinc-800 dark:text-zinc-200">
                                    {{ $item->labelKategori() }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900 dark:text-white">{{ $item->keperluan }}</div>
                                @if($item->keterangan)
                                    <div class="text-xs text-gray-500">{{ $item->keterangan }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-700 dark:text-gray-300">
                                {{ $item->penerima ?: '-' }}
                            </td>
                            <td class="px-4 py-3 text-xs whitespace-nowrap">
                                {{ $item->metode_pembayaran }}
                            </td>
                            <td class="px-4 py-3 text-right font-semibold text-rose-600 dark:text-rose-400 whitespace-nowrap">
                                {{ $item->formattedNominal() }}
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-500 whitespace-nowrap">
                                {{ $item->user?->name ?? '-' }}
                            </td>
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                <x-filament::button tag="a" href="{{ route('pengeluaran.cetak', $item->id) }}" target="_blank"
                                    color="gray" size="sm" outlined icon="heroicon-o-printer" class="whitespace-nowrap">
                                    Cetak Bukti
                                </x-filament::button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-8 text-center text-sm text-gray-500">
                                Tidak ada data transaksi pengeluaran yang sesuai dengan filter yang dipilih.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>

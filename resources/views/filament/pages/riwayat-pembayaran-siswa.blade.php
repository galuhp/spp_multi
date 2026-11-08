<x-filament-panels::page>
    @push('styles')
        @vite('resources/css/riwayat-pembayaran-siswa.css')
    @endpush

    @php
        $siswa = $this->siswa;
        $pembayarans = $this->pembayarans;
        $totalDibayar = $this->totalDibayar;
    @endphp

    <div class="space-y-5">
        <x-ui.card class="!max-w-none !p-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Riwayat Pembayaran SPP Saya</h3>
                    <p class="mt-1 text-xs text-slate-500 dark:text-gray-400">Daftar seluruh kuitansi pembayaran SPP yang tercatat atas nama Anda.</p>
                </div>

                <div class="rounded-xl border border-emerald-200/80 bg-emerald-50/40 px-4 py-3 sm:text-right dark:border-emerald-500/30 dark:bg-emerald-950/30">
                    <div class="text-[11px] font-semibold uppercase tracking-wider text-emerald-700 dark:text-emerald-300">Total Pembayaran Berhasil</div>
                    <div class="mt-1 text-xl font-bold text-emerald-700 dark:text-emerald-400 tabular-nums">Rp {{ number_format($totalDibayar, 0, ',', '.') }}</div>
                </div>
            </div>

            @if($siswa)
                <div class="mt-5 grid grid-cols-1 gap-4 border-t border-slate-200 pt-4 sm:grid-cols-3 dark:border-gray-800">
                    <div>
                        <span class="text-xs font-medium text-slate-500 dark:text-gray-400">Nama Siswa</span>
                        <div class="text-sm font-bold text-slate-900 dark:text-white">{{ $siswa->nama }}</div>
                        <div class="text-xs text-slate-500 dark:text-gray-400">NISN {{ $siswa->nisn ?: '-' }} &bull; NIS {{ $siswa->nis ?: '-' }}</div>
                    </div>
                    <div>
                        <span class="text-xs font-medium text-slate-500 dark:text-gray-400">Kelas & Jurusan</span>
                        <div class="text-sm font-bold text-slate-900 dark:text-white">{{ $siswa->kelas?->nama_kelas ?? '-' }}</div>
                        <div class="text-xs text-slate-500 dark:text-gray-400">{{ $siswa->kelas?->jurusan?->nama ?? '-' }}</div>
                    </div>
                    <div>
                        <span class="text-xs font-medium text-slate-500 dark:text-gray-400">Jumlah Kuitansi</span>
                        <div class="text-sm font-bold text-slate-900 dark:text-white tabular-nums">{{ $pembayarans->total() }} transaksi</div>
                        <div class="text-xs text-slate-500 dark:text-gray-400">Seluruh kuitansi yang tercatat.</div>
                    </div>
                </div>
            @endif
        </x-ui.card>

        <x-ui.card class="!max-w-none !p-0">
            <div class="border-b border-slate-200 px-5 py-4 dark:border-gray-800">
                <h3 class="text-base font-semibold text-slate-900 dark:text-white">Kuitansi Pembayaran</h3>
                <p class="text-xs text-slate-500 dark:text-gray-400">Klik tombol cetak untuk membuka kuitansi resmi pada tab baru.</p>
            </div>

            <div class="px-5 py-2">
                <x-ui.table :paginator="$pembayarans">
                    <x-ui.table.columns>
                        <x-ui.table.head class="w-14 text-center">No</x-ui.table.head>
                        <x-ui.table.head>No. Kuitansi</x-ui.table.head>
                        <x-ui.table.head>Tgl. Bayar</x-ui.table.head>
                        <x-ui.table.head>Pos Pembayaran</x-ui.table.head>
                        <x-ui.table.head class="text-right">Jumlah Bayar</x-ui.table.head>
                        <x-ui.table.head>Metode</x-ui.table.head>
                        <x-ui.table.head>Petugas Kasir</x-ui.table.head>
                        <x-ui.table.head class="text-center">Aksi</x-ui.table.head>
                    </x-ui.table.columns>
                    <x-ui.table.rows>
                        @forelse($pembayarans as $index => $item)
                            <x-ui.table.row :key="$item->id">
                                <x-ui.table.cell class="text-center text-xs font-medium text-slate-400 tabular-nums">
                                    {{ $pembayarans->firstItem() + $index }}
                                </x-ui.table.cell>
                                <x-ui.table.cell class="font-mono text-xs font-medium text-slate-900 dark:text-white">
                                    {{ $item->kode_transaksi }}
                                </x-ui.table.cell>
                                <x-ui.table.cell class="text-xs text-slate-600 dark:text-gray-300 tabular-nums">
                                    {{ $item->tgl_bayar->format('d/m/Y') }}
                                </x-ui.table.cell>
                                <x-ui.table.cell>
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <span class="inline-flex items-center rounded-md border px-2 py-0.5 text-[10px] font-semibold {{ $item->spp?->badgeClass() }}">
                                            {{ $item->labelKategori() }}
                                        </span>
                                        <span class="text-xs font-semibold text-slate-900 dark:text-white">{{ $item->namaPos() }}</span>
                                    </div>
                                </x-ui.table.cell>
                                <x-ui.table.cell class="text-right font-bold text-emerald-700 dark:text-emerald-400 tabular-nums">
                                    {{ $item->formattedJumlahBayar() }}
                                </x-ui.table.cell>
                                <x-ui.table.cell class="text-xs text-slate-600 dark:text-gray-300">
                                    {{ $item->metode_pembayaran }}
                                </x-ui.table.cell>
                                <x-ui.table.cell class="text-xs text-slate-600 dark:text-gray-300">
                                    {{ $item->user?->name ?? '-' }}
                                </x-ui.table.cell>
                                <x-ui.table.cell class="text-center">
                                    <x-filament::button tag="a" href="{{ route('portal.kuitansi', $item->id) }}" target="_blank"
                                        color="gray" size="sm" outlined icon="heroicon-o-printer" class="whitespace-nowrap">
                                        Cetak Kuitansi
                                    </x-filament::button>
                                </x-ui.table.cell>
                            </x-ui.table.row>
                        @empty
                            <x-ui.table.empty>
                                <div class="py-8 text-center text-sm text-slate-400 dark:text-gray-500">
                                    Belum ada riwayat transaksi pembayaran SPP yang tercatat.
                                </div>
                            </x-ui.table.empty>
                        @endforelse
                    </x-ui.table.rows>
                </x-ui.table>
            </div>
        </x-ui.card>
    </div>
</x-filament-panels::page>

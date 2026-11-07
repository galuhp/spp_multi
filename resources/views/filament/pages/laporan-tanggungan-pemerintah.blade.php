<x-filament-panels::page>
    @push('styles')
        @vite('resources/css/tagihan-siswa.css')
    @endpush

    @php
        $rows = $this->rows;
        $totalNominal = $this->totalNominal;
    @endphp

    <div class="space-y-5">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <x-ui.card class="!max-w-none !p-5">
                <span class="text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-gray-400">Jumlah Data</span>
                <div class="mt-2 text-2xl font-bold text-sky-700 dark:text-sky-400 tabular-nums">{{ $rows->count() }}</div>
                <p class="mt-1 text-xs text-slate-500 dark:text-gray-400">Siswa/pos yang ditanggung pemerintah.</p>
            </x-ui.card>

            <x-ui.card class="!max-w-none !p-5">
                <span class="text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-gray-400">Total Nominal Acuan</span>
                <div class="mt-2 text-2xl font-bold text-amber-700 dark:text-amber-400 tabular-nums">
                    Rp {{ number_format($totalNominal, 0, ',', '.') }}
                </div>
                <p class="mt-1 text-xs text-slate-500 dark:text-gray-400">Memo, bukan penerimaan kas.</p>
            </x-ui.card>
        </div>

        <x-ui.card class="!max-w-none !p-5">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div class="grid flex-1 grid-cols-1 gap-3 sm:grid-cols-2">
                    <div>
                        <label for="status-tanggungan" class="mb-1 block text-xs font-medium text-slate-600 dark:text-gray-300">Status</label>
                        <select id="status-tanggungan" wire:model.live="status"
                            class="w-full min-h-10 rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                            <option value="aktif">Aktif</option>
                            <option value="dicabut">Dicabut</option>
                            <option value="semua">Semua</option>
                        </select>
                    </div>

                    <div>
                        <label for="kelas-tanggungan" class="mb-1 block text-xs font-medium text-slate-600 dark:text-gray-300">Kelas</label>
                        <select id="kelas-tanggungan" wire:model.live="kelas_id"
                            class="w-full min-h-10 rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                            <option value="">Semua Kelas</option>
                            @foreach($this->kelasOptions as $id => $label)
                                <option value="{{ $id }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <x-filament::button color="gray" size="sm" wire:click="resetFilter">
                    Reset Filter
                </x-filament::button>
            </div>
        </x-ui.card>

        <x-ui.card class="!max-w-none !p-0">
            <div class="border-b border-slate-200 px-5 py-4 dark:border-gray-800">
                <h3 class="text-base font-semibold text-slate-900 dark:text-white">Rekap Ditanggung Pemerintah</h3>
                <p class="text-xs text-slate-500 dark:text-gray-400">Hanya sebagai memo. Pembayaran pemerintah tidak dihitung sebagai penerimaan siswa.</p>
            </div>

            <div class="overflow-x-auto px-5 py-2">
                <x-ui.table>
                    <x-ui.table.columns>
                        <x-ui.table.head class="w-12 text-center">No</x-ui.table.head>
                        <x-ui.table.head>Nama Siswa</x-ui.table.head>
                        <x-ui.table.head>Kelas</x-ui.table.head>
                        <x-ui.table.head>Cakupan</x-ui.table.head>
                        <x-ui.table.head>Pos Biaya</x-ui.table.head>
                        <x-ui.table.head>Mulai</x-ui.table.head>
                        <x-ui.table.head class="text-center">Status</x-ui.table.head>
                        <x-ui.table.head class="text-right">Nominal</x-ui.table.head>
                    </x-ui.table.columns>
                    <x-ui.table.rows>
                        @forelse($rows as $index => $row)
                            <x-ui.table.row :key="$row->id">
                                <x-ui.table.cell class="text-center text-xs text-slate-400 tabular-nums">
                                    {{ $index + 1 }}
                                </x-ui.table.cell>
                                <x-ui.table.cell>
                                    <div class="text-xs font-semibold text-slate-900 dark:text-white">{{ $row->siswa?->nama ?? '-' }}</div>
                                    <div class="text-[11px] text-slate-500 dark:text-gray-400">NISN {{ $row->siswa?->nisn ?: '-' }}</div>
                                </x-ui.table.cell>
                                <x-ui.table.cell class="text-xs text-slate-600 dark:text-gray-300">
                                    {{ $row->siswa?->kelas?->nama_kelas ?? '-' }}
                                </x-ui.table.cell>
                                <x-ui.table.cell>
                                    <x-ui.badge color="{{ $row->cakupan === \App\Models\SiswaTanggunganPemerintah::CAKUPAN_SPP ? 'sky' : 'amber' }}">
                                        {{ $row->cakupanLabel() }}
                                    </x-ui.badge>
                                </x-ui.table.cell>
                                <x-ui.table.cell class="text-xs text-slate-600 dark:text-gray-300">
                                    {{ $row->cakupan === \App\Models\SiswaTanggunganPemerintah::CAKUPAN_POS ? ($row->spp?->namaLengkap() ?? '-') : 'SPP bulanan' }}
                                </x-ui.table.cell>
                                <x-ui.table.cell class="text-xs text-slate-600 dark:text-gray-300 tabular-nums">
                                    {{ $row->mulai->format('d/m/Y') }}
                                </x-ui.table.cell>
                                <x-ui.table.cell class="text-center">
                                    @if($row->dicabut_at === null)
                                        <x-ui.badge color="sky">Aktif</x-ui.badge>
                                    @else
                                        <x-ui.badge variant="outline">Dicabut</x-ui.badge>
                                    @endif
                                </x-ui.table.cell>
                                <x-ui.table.cell class="text-right font-semibold text-slate-900 dark:text-white tabular-nums">
                                    Rp {{ number_format($row->nominal(), 0, ',', '.') }}
                                </x-ui.table.cell>
                            </x-ui.table.row>
                        @empty
                            <x-ui.table.empty>
                                <div class="py-8 text-center text-sm text-slate-400 dark:text-gray-500">
                                    Belum ada data tanggungan pemerintah untuk filter ini.
                                </div>
                            </x-ui.table.empty>
                        @endforelse
                    </x-ui.table.rows>
                </x-ui.table>
            </div>
        </x-ui.card>
    </div>
</x-filament-panels::page>

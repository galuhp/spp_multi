<x-filament-panels::page>
    @push('styles')
        @vite('resources/css/proses-kelas.css')
    @endpush

    @php
        $siswaList = $this->siswa;
        $totalTunggakan = $siswaList
            ->filter(fn ($baris) => in_array((int) $baris['siswa']->id, array_map('intval', $siswa_ids), true))
            ->sum(fn ($baris) => $baris['tunggakan']['nominal']);
        $jumlahPilih = count($siswa_ids);
    @endphp

    <div class="space-y-5">
        @if ($galat)
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-500/30 dark:bg-rose-950/30 dark:text-rose-300">
                {{ $galat }}
            </div>
        @endif

        <div class="fi-section rounded-xl border border-slate-200/80 bg-white p-5 shadow-xs dark:border-white/10 dark:bg-gray-900">
            <h3 class="text-base font-semibold text-slate-900 dark:text-white">Pilih Tahun Ajaran & Kelas Tingkat Akhir</h3>
            <p class="mt-0.5 text-xs text-slate-500 dark:text-gray-400">
                Siswa yang diluluskan akan diarsipkan. Tunggakan tetap tercatat dan dapat ditagih kembali.
            </p>

            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600 dark:text-gray-300">Tahun Ajaran</label>
                    <select wire:model.live="tahun_ajaran_id"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                        <option value="">Pilih tahun ajaran</option>
                        @foreach ($this->tahunAjaranOptions as $id => $nama)
                            <option value="{{ $id }}">{{ $nama }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600 dark:text-gray-300">Kelas Tingkat Akhir</label>
                    <select wire:model.live="kelas_id"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                        <option value="">Pilih kelas</option>
                        @foreach ($this->kelasOptions as $id => $label)
                            <option value="{{ $id }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600 dark:text-gray-300">Tanggal Lulus</label>
                    <input type="date" wire:model="tanggal_lulus"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                </div>
            </div>
        </div>

        @if ($siswaList->isNotEmpty())
            <div class="fi-section rounded-xl border border-slate-200/80 bg-white p-5 shadow-xs dark:border-white/10 dark:bg-gray-900">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900 dark:text-white">Daftar Siswa</h3>
                        <p class="mt-0.5 text-xs text-slate-500 dark:text-gray-400">Semua siswa tercentang secara default. Hilangkan centang untuk siswa yang belum lulus.</p>
                    </div>
                    <div class="flex flex-wrap gap-2 text-xs tabular-nums">
                        <span class="rounded-md border border-slate-200 bg-slate-50 px-2.5 py-1 font-medium text-slate-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Dipilih: {{ $jumlahPilih }}</span>
                        <span class="rounded-md border border-amber-200/80 bg-amber-50 px-2.5 py-1 font-medium text-amber-700 dark:border-amber-500/30 dark:bg-amber-950/30 dark:text-amber-300">Tunggakan: Rp {{ number_format($totalTunggakan, 0, ',', '.') }}</span>
                    </div>
                </div>

                <div class="mt-4 overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase text-slate-600 dark:bg-gray-800/60 dark:text-gray-400">
                            <tr>
                                <th class="px-3 py-2">Pilih</th>
                                <th class="px-3 py-2">NISN</th>
                                <th class="px-3 py-2">Nama</th>
                                <th class="px-3 py-2">Tunggakan SPP</th>
                                <th class="px-3 py-2">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-gray-800">
                            @foreach ($siswaList as $baris)
                                @php
                                    $siswa = $baris['siswa'];
                                    $tunggakan = $baris['tunggakan'];
                                @endphp
                                <tr wire:key="lulus-{{ $siswa->id }}">
                                    <td class="px-3 py-2">
                                        <input type="checkbox" value="{{ $siswa->id }}" wire:model.live="siswa_ids"
                                            class="size-4 rounded border-slate-300 accent-primary-600 dark:border-gray-600">
                                    </td>
                                    <td class="px-3 py-2 font-mono text-xs text-slate-500 dark:text-gray-400">{{ $siswa->nisn ?: '-' }}</td>
                                    <td class="px-3 py-2 font-medium text-slate-900 dark:text-white">{{ $siswa->nama }}</td>
                                    <td class="px-3 py-2 text-xs tabular-nums">
                                        @if ($tunggakan['jumlah_bulan'] > 0)
                                            <span class="rounded-md border border-amber-200/80 bg-amber-50 px-2 py-0.5 font-medium text-amber-700 dark:border-amber-500/30 dark:bg-amber-950/30 dark:text-amber-300">
                                                {{ $tunggakan['jumlah_bulan'] }} bulan — Rp {{ number_format($tunggakan['nominal'], 0, ',', '.') }}
                                            </span>
                                        @else
                                            <span class="rounded-md border border-emerald-200/80 bg-emerald-50 px-2 py-0.5 font-semibold text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-950/30 dark:text-emerald-300">Lunas</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-xs text-slate-500 dark:text-gray-400">
                                        @if ($tunggakan['jumlah_bulan'] > 0)
                                            Boleh diluluskan setelah konfirmasi; tunggakan tetap tercatat.
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-2">
                    @if (auth()->user()?->isStaff())
                        <x-filament::button type="button" color="gray" size="sm" wire:click="pratinjau">
                            Pratinjau
                        </x-filament::button>

                        @if ($pratinjau)
                            <x-filament::button type="button" size="sm" wire:click="eksekusi"
                                wire:confirm="Luluskan {{ $jumlahPilih }} siswa dan arsipkan? Total tunggakan Rp {{ number_format($totalTunggakan, 0, ',', '.') }} tetap tercatat.">
                                Simpan Kelulusan
                            </x-filament::button>
                        @endif
                    @else
                        <p class="text-xs text-slate-500 dark:text-gray-400">Hanya staf yang dapat menyimpan kelulusan.</p>
                    @endif
                </div>
            </div>
        @endif

        @if ($hasil)
            <div class="rounded-xl border border-emerald-200/80 bg-emerald-50 p-5 text-sm text-emerald-900 dark:border-emerald-500/30 dark:bg-emerald-950/30 dark:text-emerald-200">
                <h3 class="font-semibold">Hasil Kelulusan</h3>
                <p class="mt-1">{{ $hasil['diproses'] }} siswa diluluskan dan diarsipkan. Batch #{{ $hasil['batch_id'] ?? '-' }}.</p>

                @if (! empty($hasil['dilewati']))
                    <ul class="mt-2 list-inside list-disc text-xs">
                        @foreach ($hasil['dilewati'] as $alasan)
                            <li>{{ $alasan }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endif

        <div class="fi-section rounded-xl border border-slate-200/80 bg-white p-5 shadow-xs dark:border-white/10 dark:bg-gray-900">
            <h3 class="text-base font-semibold text-slate-900 dark:text-white">Riwayat Batch Kelulusan</h3>

            <div class="mt-3 overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase text-slate-600 dark:bg-gray-800/60 dark:text-gray-400">
                        <tr>
                            <th class="px-3 py-2">Batch</th>
                            <th class="px-3 py-2">Tahun Ajaran</th>
                            <th class="px-3 py-2">Jumlah</th>
                            <th class="px-3 py-2">Status</th>
                            <th class="px-3 py-2">Waktu</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-gray-800">
                        @forelse ($this->batch as $batch)
                            <tr>
                                <td class="px-3 py-2 font-mono text-xs text-slate-900 dark:text-white">#{{ $batch->id }}</td>
                                <td class="px-3 py-2 text-xs text-slate-700 dark:text-gray-300">{{ $batch->tahunAjaranAsal?->nama }}</td>
                                <td class="px-3 py-2 text-xs text-slate-700 dark:text-gray-300 tabular-nums">{{ $batch->jumlah_siswa }}</td>
                                <td class="px-3 py-2 text-xs text-slate-700 dark:text-gray-300">{{ $batch->status }}</td>
                                <td class="px-3 py-2 text-xs text-slate-500 dark:text-gray-400 tabular-nums">{{ $batch->created_at?->format('d/m/Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-3 py-6 text-center text-xs text-slate-500 dark:text-gray-400">Belum ada batch kelulusan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>
<x-filament-panels::page>
    @push('styles')
        @vite('resources/css/proses-kelas.css')
    @endpush

    @php
        $siswaList = $this->siswa;
        $siswaBebasTunggakan = $siswaList->filter(fn ($b) => ($b['tunggakan']['nominal'] ?? 0) === 0);
        $siswaMenunggak = $siswaList->filter(fn ($b) => ($b['tunggakan']['nominal'] ?? 0) > 0);
        $totalTunggakan = $siswaMenunggak->sum(fn ($baris) => $baris['tunggakan']['nominal']);
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
                Kelulusan hanya dapat diproses untuk siswa yang <strong>telah melunasi seluruh kewajiban administrasi (Bebas Tunggakan)</strong>. Siswa yang masih memiliki tunggakan tidak dapat diluluskan.
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
                        <h3 class="text-base font-semibold text-slate-900 dark:text-white">Daftar Siswa Tingkat Akhir</h3>
                        <p class="mt-0.5 text-xs text-slate-500 dark:text-gray-400">
                            Hanya siswa berstatus <strong>Lunas</strong> yang dapat dicentang untuk kelulusan.
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <div class="flex flex-wrap gap-2 text-xs tabular-nums">
                            <span class="rounded-md border border-emerald-200/80 bg-emerald-50 px-2.5 py-1 font-medium text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-950/30 dark:text-emerald-300">
                                Bebas Tunggakan: {{ $siswaBebasTunggakan->count() }}
                            </span>
                            <span class="rounded-md border border-rose-200/80 bg-rose-50 px-2.5 py-1 font-medium text-rose-700 dark:border-rose-500/30 dark:bg-rose-950/30 dark:text-rose-300">
                                Menunggak: {{ $siswaMenunggak->count() }} (Rp {{ number_format($totalTunggakan, 0, ',', '.') }})
                            </span>
                            <span class="rounded-md border border-slate-200 bg-slate-50 px-2.5 py-1 font-medium text-slate-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                Dipilih: {{ $jumlahPilih }}
                            </span>
                        </div>

                        @if (auth()->user()?->isStaff())
                            <div class="flex items-center gap-2">
                                <x-filament::button type="button" color="gray" size="sm" outlined wire:click="pratinjau"
                                    wire:loading.attr="disabled" wire:target="pratinjau">
                                    <x-filament::icon icon="heroicon-o-eye" class="size-4 mr-1 inline" />
                                    <span>Pratinjau</span>
                                </x-filament::button>

                                <x-filament::button type="button" size="sm" color="primary" wire:click="eksekusi"
                                    wire:loading.attr="disabled" wire:target="eksekusi"
                                    :disabled="$jumlahPilih === 0"
                                    wire:confirm="Yakin ingin langsung meluluskan dan mengarsipkan {{ $jumlahPilih }} siswa terpilih?">
                                    <x-filament::icon icon="heroicon-o-check-circle" class="size-4 mr-1 inline" />
                                    <span>Simpan &amp; Proses Kelulusan</span>
                                </x-filament::button>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="mt-3 rounded-lg border border-amber-200 bg-amber-50 px-3.5 py-2 text-xs text-amber-800 dark:border-amber-500/30 dark:bg-amber-950/30 dark:text-amber-300 flex items-center gap-2">
                    <svg class="h-4 w-4 shrink-0 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                    </svg>
                    <span>
                        <strong>Aturan Kelulusan:</strong> Berbeda dengan kenaikan kelas, <strong>kelulusan mensyaratkan pelunasan penuh</strong>. Siswa dengan tunggakan tidak dapat dicentang dan tidak dapat diluluskan sebelum seluruh tunggakan dilunasi di kasir.
                    </span>
                </div>

                <div class="mt-4 overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase text-slate-600 dark:bg-gray-800/60 dark:text-gray-400">
                            <tr>
                                <th class="px-3 py-2 w-12 text-center">Pilih</th>
                                <th class="px-3 py-2">NISN</th>
                                <th class="px-3 py-2">Nama Siswa</th>
                                <th class="px-3 py-2">Status Tunggakan</th>
                                <th class="px-3 py-2">Kelayakan Lulus</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-gray-800">
                            @foreach ($siswaList as $baris)
                                @php
                                    $siswa = $baris['siswa'];
                                    $tunggakan = $baris['tunggakan'];
                                    $adaTunggakan = ($tunggakan['nominal'] ?? 0) > 0;
                                @endphp
                                <tr wire:key="lulus-{{ $siswa->id }}" class="{{ $adaTunggakan ? 'bg-rose-50/20' : '' }}">
                                    <td class="px-3 py-2 text-center">
                                        <input type="checkbox" value="{{ $siswa->id }}" wire:model.live="siswa_ids"
                                            @disabled($adaTunggakan)
                                            class="size-4 rounded border-slate-300 accent-primary-600 disabled:opacity-30 disabled:cursor-not-allowed dark:border-gray-600">
                                    </td>
                                    <td class="px-3 py-2 font-mono text-xs text-slate-500 dark:text-gray-400">{{ $siswa->nisn ?: '-' }}</td>
                                    <td class="px-3 py-2 font-medium text-slate-900 dark:text-white">{{ $siswa->nama }}</td>
                                    <td class="px-3 py-2 text-xs tabular-nums">
                                        @if ($adaTunggakan)
                                            <span class="rounded-md border border-rose-200/80 bg-rose-50 px-2 py-0.5 font-semibold text-rose-700 dark:border-rose-500/30 dark:bg-rose-950/30 dark:text-rose-300">
                                                Tunggakan: Rp {{ number_format($tunggakan['nominal'], 0, ',', '.') }} ({{ $tunggakan['jumlah_bulan'] }} bln)
                                            </span>
                                        @else
                                            <span class="rounded-md border border-emerald-200/80 bg-emerald-50 px-2 py-0.5 font-semibold text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-950/30 dark:text-emerald-300">
                                                Lunas (Bebas Tunggakan)
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-xs">
                                        @if ($adaTunggakan)
                                            <span class="font-medium text-rose-600 dark:text-rose-400">
                                                &cross; Tidak Dapat Lulus — Wajib melunasi tunggakan terlebih dahulu
                                            </span>
                                        @else
                                            <span class="font-medium text-emerald-600 dark:text-emerald-400">
                                                &check; Memenuhi Syarat Kelulusan
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($pratinjau)
                    <div class="mt-5 rounded-xl border-2 border-emerald-500/40 bg-emerald-50/70 p-4 dark:border-emerald-500/30 dark:bg-emerald-950/20">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex items-center rounded-full bg-emerald-600 px-2.5 py-0.5 text-xs font-semibold text-white">
                                        Pratinjau Kelulusan Siap
                                    </span>
                                    <h4 class="text-sm font-bold text-slate-900 dark:text-white">
                                        Konfirmasi Kelulusan Siswa Bebas Tunggakan
                                    </h4>
                                </div>
                                <p class="mt-1 text-xs text-slate-600 dark:text-slate-300">
                                    Sebanyak <strong>{{ $jumlahPilih }} siswa</strong> yang bebas tunggakan akan diluluskan dan statusnya diarsipkan per tanggal <strong>{{ \Carbon\Carbon::parse($tanggal_lulus)->translatedFormat('d F Y') }}</strong>.
                                </p>
                            </div>

                            <div class="flex items-center gap-2">
                                <x-filament::button type="button" color="gray" size="sm" outlined wire:click="$set('pratinjau', false)">
                                    Ubah Pilihan
                                </x-filament::button>

                                <x-filament::button type="button" size="sm" color="primary" wire:click="eksekusi"
                                    wire:loading.attr="disabled" wire:target="eksekusi"
                                    wire:confirm="Yakin ingin meluluskan dan mengarsipkan {{ $jumlahPilih }} siswa bebas tunggakan?">
                                    Simpan Kelulusan Sekarang
                                </x-filament::button>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="mt-5 flex flex-wrap items-center justify-between gap-3 border-t border-slate-200/80 pt-4 dark:border-gray-800">
                        <div class="text-xs text-slate-500 dark:text-gray-400">
                            Anda dapat langsung mengeksekusi kelulusan siswa terpilih dengan tombol <strong class="text-slate-800 dark:text-gray-200">Simpan &amp; Proses Kelulusan</strong>, atau klik <strong class="text-slate-800 dark:text-gray-200">Pratinjau</strong> terlebih dahulu.
                        </div>

                        @if (auth()->user()?->isStaff())
                            <div class="flex items-center gap-2">
                                <x-filament::button type="button" color="gray" size="sm" outlined wire:click="pratinjau"
                                    wire:loading.attr="disabled" wire:target="pratinjau">
                                    <x-filament::icon icon="heroicon-o-eye" class="size-4 mr-1 inline" />
                                    <span wire:loading.remove wire:target="pratinjau">Pratinjau Kelulusan</span>
                                    <span wire:loading wire:target="pratinjau">Memproses Pratinjau...</span>
                                </x-filament::button>

                                <x-filament::button type="button" size="sm" color="primary" wire:click="eksekusi"
                                    wire:loading.attr="disabled" wire:target="eksekusi"
                                    :disabled="$jumlahPilih === 0"
                                    wire:confirm="Yakin ingin langsung meluluskan dan mengarsipkan {{ $jumlahPilih }} siswa terpilih?">
                                    <x-filament::icon icon="heroicon-o-check-circle" class="size-4 mr-1 inline" />
                                    <span>Simpan &amp; Proses Kelulusan</span>
                                </x-filament::button>
                            </div>
                        @else
                            <p class="text-xs text-slate-500 dark:text-gray-400">Hanya staf yang dapat menyimpan kelulusan.</p>
                        @endif
                    </div>
                @endif
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
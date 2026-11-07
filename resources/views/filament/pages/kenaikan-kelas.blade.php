<x-filament-panels::page>
    @push('styles')
        @vite('resources/css/proses-kelas.css')
    @endpush

    @php
        $siswaList = $this->siswa;
        $kelasTujuanOptions = $this->kelasTujuanOptions;
        $jumlahNaik = collect($keputusan)->where('status', 'naik')->count();
        $jumlahTinggal = collect($keputusan)->where('status', 'tinggal')->count();
        $service = app(\App\Support\KenaikanKelas\ProsesKelasService::class);
    @endphp

    <div class="space-y-5">
        @if ($galat)
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-500/30 dark:bg-rose-950/30 dark:text-rose-300">
                {{ $galat }}
            </div>
        @endif

        <div class="fi-section rounded-xl border border-slate-200/80 bg-white p-5 shadow-xs dark:border-white/10 dark:bg-gray-900">
            <h3 class="text-base font-semibold text-slate-900 dark:text-white">Pilih Tahun Ajaran, Kelas Asal &amp; Tujuan Batch</h3>
            <p class="mt-0.5 text-xs text-slate-500 dark:text-gray-400">
                Pilih kelas asal untuk mengisi otomatis kelas tujuan seluruh siswa secara massal (batch). Untuk SMK/SMA jurusan yang sama diprioritaskan otomatis; untuk SD/SMP atau lintas rombel Anda dapat memilih kelas tujuan mana saja, lalu menyesuaikan per siswa di daftar bawah bila diperlukan.
            </p>

            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600 dark:text-gray-300">Tahun Ajaran Asal</label>
                    <select wire:model.live="tahun_ajaran_asal_id"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                        <option value="">Pilih tahun ajaran</option>
                        @foreach ($this->tahunAjaranOptions as $id => $nama)
                            <option value="{{ $id }}">{{ $nama }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600 dark:text-gray-300">Tahun Ajaran Tujuan</label>
                    <select wire:model.live="tahun_ajaran_tujuan_id"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                        <option value="">Pilih tahun ajaran</option>
                        @foreach ($this->tahunAjaranTujuanOptions as $id => $nama)
                            <option value="{{ $id }}">{{ $nama }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600 dark:text-gray-300">Kelas Asal</label>
                    <select wire:model.live="kelas_asal_id"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                        <option value="">Pilih kelas asal</option>
                        @foreach ($this->kelasAsalOptions as $id => $label)
                            <option value="{{ $id }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <div class="mb-1 flex items-center justify-between gap-2">
                        <label class="block text-xs font-medium text-slate-600 dark:text-gray-300">Kelas Tujuan (Batch / Default)</label>
                        @if ($kelas_tujuan_batch_id && $siswaList->isNotEmpty())
                            <button type="button" wire:click="terapkanKelasTujuanBatch"
                                class="text-[11px] font-semibold text-primary-600 hover:underline dark:text-primary-400">
                                Terapkan ke semua
                            </button>
                        @endif
                    </div>
                    <select wire:model.live="kelas_tujuan_batch_id"
                        @disabled(! $tahun_ajaran_tujuan_id)
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-200 disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                        <option value="">Pilih kelas tujuan batch</option>
                        @foreach ($kelasTujuanOptions as $id => $label)
                            <option value="{{ $id }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        @if ($siswaList->isNotEmpty())
            <div class="fi-section rounded-xl border border-slate-200/80 bg-white p-5 shadow-xs dark:border-white/10 dark:bg-gray-900">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900 dark:text-white">Daftar Siswa</h3>
                        <p class="mt-0.5 text-xs text-slate-500 dark:text-gray-400">
                            Seluruh siswa aktif otomatis terisi dengan kelas tujuan batch di atas. Anda tetap dapat mengubah status atau kelas tujuan secara manual per siswa.
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2 text-xs tabular-nums">
                        <span class="rounded-md border border-slate-200 bg-slate-50 px-2.5 py-1 font-medium text-slate-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Naik: {{ $jumlahNaik }}</span>
                        <span class="rounded-md border border-amber-200/80 bg-amber-50 px-2.5 py-1 font-medium text-amber-700 dark:border-amber-500/30 dark:bg-amber-950/30 dark:text-amber-300">Tinggal: {{ $jumlahTinggal }}</span>
                    </div>
                </div>

                <div class="mt-4 overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase text-slate-600 dark:bg-gray-800/60 dark:text-gray-400">
                            <tr>
                                <th class="px-3 py-2">NISN</th>
                                <th class="px-3 py-2">Nama</th>
                                <th class="px-3 py-2">Status</th>
                                <th class="px-3 py-2">Kelas Tujuan</th>
                                <th class="px-3 py-2">Catatan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-gray-800">
                            @foreach ($siswaList as $baris)
                                @php $siswa = $baris['siswa']; @endphp
                                <tr wire:key="siswa-{{ $siswa->id }}">
                                    <td class="px-3 py-2 font-mono text-xs text-slate-500 dark:text-gray-400">{{ $siswa->nisn ?: '-' }}</td>
                                    <td class="px-3 py-2 font-medium text-slate-900 dark:text-white">{{ $siswa->nama }}</td>
                                    <td class="px-3 py-2">
                                        <select wire:model.live="keputusan.{{ $siswa->id }}.status"
                                            class="w-32 rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-xs text-slate-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                                            <option value="naik" @disabled($baris['tingkat_akhir'])>Naik</option>
                                            <option value="tinggal">Tinggal</option>
                                        </select>
                                    </td>
                                    <td class="px-3 py-2">
                                        <select wire:model.live="keputusan.{{ $siswa->id }}.kelas_tujuan_id"
                                            class="w-64 rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-xs text-slate-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                                            <option value="">Pilih kelas tujuan</option>
                                            @foreach ($kelasTujuanOptions as $id => $label)
                                                <option value="{{ $id }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="px-3 py-2 text-xs">
                                        @if ($baris['sudah_ada'])
                                            <span class="text-amber-700 dark:text-amber-400">Sudah punya riwayat di TA tujuan — akan dilewati.</span>
                                        @elseif ($baris['tingkat_akhir'])
                                            <span class="text-rose-700 dark:text-rose-400">Tingkat akhir — gunakan Kelulusan.</span>
                                        @else
                                            <span class="text-slate-400 dark:text-gray-500">-</span>
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
                                wire:confirm="Simpan kenaikan kelas untuk {{ $jumlahNaik }} siswa naik dan {{ $jumlahTinggal }} siswa tinggal?">
                                Simpan Kenaikan Kelas
                            </x-filament::button>
                        @endif
                    @else
                        <p class="text-xs text-slate-500 dark:text-gray-400">Hanya staf yang dapat menyimpan proses kenaikan kelas.</p>
                    @endif
                </div>
            </div>
        @endif

        @if ($hasil)
            <div class="rounded-xl border border-emerald-200/80 bg-emerald-50 p-5 text-sm text-emerald-900 dark:border-emerald-500/30 dark:bg-emerald-950/30 dark:text-emerald-200">
                <h3 class="font-semibold">Hasil Kenaikan Kelas</h3>
                <p class="mt-1">{{ $hasil['diproses'] }} siswa berhasil diproses. Batch #{{ $hasil['batch_id'] ?? '-' }}.</p>

                @if (! empty($hasil['dilewati']))
                    <p class="mt-3 font-medium">Dilewati:</p>
                    <ul class="mt-1 list-inside list-disc text-xs">
                        @foreach ($hasil['dilewati'] as $alasan)
                            <li>{{ $alasan }}</li>
                        @endforeach
                    </ul>
                @endif

                @if (! empty($hasil['pesan']))
                    <p class="mt-3 font-medium">Peringatan:</p>
                    <ul class="mt-1 list-inside list-disc text-xs">
                        @foreach ($hasil['pesan'] as $pesan)
                            <li>{{ $pesan }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endif

        <div class="fi-section rounded-xl border border-slate-200/80 bg-white p-5 shadow-xs dark:border-white/10 dark:bg-gray-900">
            <h3 class="text-base font-semibold text-slate-900 dark:text-white">Riwayat Batch Kenaikan Kelas</h3>

            <div class="mt-3 overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase text-slate-600 dark:bg-gray-800/60 dark:text-gray-400">
                        <tr>
                            <th class="px-3 py-2">Batch</th>
                            <th class="px-3 py-2">Asal → Tujuan</th>
                            <th class="px-3 py-2">Jumlah</th>
                            <th class="px-3 py-2">Status</th>
                            <th class="px-3 py-2">Waktu</th>
                            <th class="px-3 py-2">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-gray-800">
                        @forelse ($this->batch as $batch)
                            <tr>
                                <td class="px-3 py-2 font-mono text-xs text-slate-900 dark:text-white">#{{ $batch->id }}</td>
                                <td class="px-3 py-2 text-xs text-slate-700 dark:text-gray-300">
                                    {{ $batch->tahunAjaranAsal?->nama }} → {{ $batch->tahunAjaranTujuan?->nama }}
                                </td>
                                <td class="px-3 py-2 text-xs text-slate-700 dark:text-gray-300 tabular-nums">{{ $batch->jumlah_siswa }}</td>
                                <td class="px-3 py-2">
                                    <span class="rounded-md border px-2 py-0.5 text-xs font-semibold {{ $batch->status === 'selesai' ? 'border-emerald-200/80 bg-emerald-50 text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-950/30 dark:text-emerald-300' : 'border-slate-200 bg-slate-50 text-slate-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300' }}">
                                        {{ $batch->status }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-xs text-slate-500 dark:text-gray-400 tabular-nums">{{ $batch->created_at?->format('d/m/Y H:i') }}</td>
                                <td class="px-3 py-2">
                                    @if (auth()->user()?->isStaff() && $service->bisaDibatalkan($batch))
                                        <x-filament::button type="button" color="danger" size="sm" outlined
                                            wire:click="batalkan({{ $batch->id }})"
                                            wire:confirm="Batalkan batch #{{ $batch->id }}? Riwayat kelas baru akan dihapus dan status lama dipulihkan.">
                                            Batalkan
                                        </x-filament::button>
                                    @else
                                        <span class="text-xs text-slate-400 dark:text-gray-500">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-3 py-6 text-center text-xs text-slate-500 dark:text-gray-400">Belum ada batch kenaikan kelas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>
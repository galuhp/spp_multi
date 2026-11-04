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
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                {{ $galat }}
            </div>
        @endif

        <div class="rounded-xl border border-zinc-200 bg-white p-5">
            <h3 class="text-base font-semibold text-zinc-900">Pilih Tahun Ajaran & Kelas Asal</h3>
            <p class="mt-0.5 text-xs text-zinc-500">
                Tahun ajaran tujuan harus sudah memiliki rombel. Siswa yang tidak naik tetap dipilihkan kelas di tingkat yang sama.
            </p>

            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
                <div>
                    <label class="mb-1 block text-xs font-medium text-zinc-600">Tahun Ajaran Asal</label>
                    <select wire:model.live="tahun_ajaran_asal_id"
                        class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm focus:border-zinc-500 focus:outline-none focus:ring-2 focus:ring-zinc-200">
                        <option value="">Pilih tahun ajaran</option>
                        @foreach ($this->tahunAjaranOptions as $id => $nama)
                            <option value="{{ $id }}">{{ $nama }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-medium text-zinc-600">Tahun Ajaran Tujuan</label>
                    <select wire:model.live="tahun_ajaran_tujuan_id"
                        class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm focus:border-zinc-500 focus:outline-none focus:ring-2 focus:ring-zinc-200">
                        <option value="">Pilih tahun ajaran</option>
                        @foreach ($this->tahunAjaranOptions as $id => $nama)
                            <option value="{{ $id }}">{{ $nama }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-medium text-zinc-600">Kelas Asal</label>
                    <select wire:model.live="kelas_asal_id"
                        class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm focus:border-zinc-500 focus:outline-none focus:ring-2 focus:ring-zinc-200">
                        <option value="">Pilih kelas</option>
                        @foreach ($this->kelasAsalOptions as $id => $label)
                            <option value="{{ $id }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        @if ($siswaList->isNotEmpty())
            <div class="rounded-xl border border-zinc-200 bg-white p-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h3 class="text-base font-semibold text-zinc-900">Daftar Siswa</h3>
                        <p class="mt-0.5 text-xs text-zinc-500">
                            Semua siswa aktif tercentang naik. Ubah status menjadi "Tinggal" bila siswa tidak naik kelas.
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2 text-xs">
                        <span class="rounded-full bg-zinc-100 px-2.5 py-1 font-medium text-zinc-700">Naik: {{ $jumlahNaik }}</span>
                        <span class="rounded-full bg-amber-100 px-2.5 py-1 font-medium text-amber-800">Tinggal: {{ $jumlahTinggal }}</span>
                    </div>
                </div>

                <div class="mt-4 overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-zinc-50 text-xs uppercase text-zinc-600">
                            <tr>
                                <th class="px-3 py-2">NISN</th>
                                <th class="px-3 py-2">Nama</th>
                                <th class="px-3 py-2">Status</th>
                                <th class="px-3 py-2">Kelas Tujuan</th>
                                <th class="px-3 py-2">Catatan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100">
                            @foreach ($siswaList as $baris)
                                @php $siswa = $baris['siswa']; @endphp
                                <tr wire:key="siswa-{{ $siswa->id }}">
                                    <td class="px-3 py-2 font-mono text-xs text-zinc-500">{{ $siswa->nisn ?: '-' }}</td>
                                    <td class="px-3 py-2 font-medium text-zinc-900">{{ $siswa->nama }}</td>
                                    <td class="px-3 py-2">
                                        <select wire:model.live="keputusan.{{ $siswa->id }}.status"
                                            class="w-32 rounded-lg border border-zinc-300 px-2 py-1.5 text-xs focus:border-zinc-500 focus:outline-none focus:ring-2 focus:ring-zinc-200">
                                            <option value="naik" @disabled($baris['tingkat_akhir'])>Naik</option>
                                            <option value="tinggal">Tinggal</option>
                                        </select>
                                    </td>
                                    <td class="px-3 py-2">
                                        <select wire:model="keputusan.{{ $siswa->id }}.kelas_tujuan_id"
                                            class="w-64 rounded-lg border border-zinc-300 px-2 py-1.5 text-xs focus:border-zinc-500 focus:outline-none focus:ring-2 focus:ring-zinc-200">
                                            <option value="">Pilih kelas tujuan</option>
                                            @foreach ($kelasTujuanOptions as $id => $label)
                                                <option value="{{ $id }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="px-3 py-2 text-xs">
                                        @if ($baris['sudah_ada'])
                                            <span class="text-amber-700">Sudah punya riwayat di TA tujuan — akan dilewati.</span>
                                        @elseif ($baris['tingkat_akhir'])
                                            <span class="text-rose-700">Tingkat akhir — gunakan Kelulusan.</span>
                                        @else
                                            <span class="text-zinc-400">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-2">
                    @if (auth()->user()?->isAdmin())
                        <button type="button" wire:click="pratinjau"
                            class="rounded-lg border border-zinc-300 px-3 py-1.5 text-xs font-medium text-zinc-700 hover:bg-zinc-50">
                            Pratinjau
                        </button>

                        @if ($pratinjau)
                            <button type="button" wire:click="eksekusi"
                                wire:confirm="Simpan kenaikan kelas untuk {{ $jumlahNaik }} siswa naik dan {{ $jumlahTinggal }} siswa tinggal?"
                                class="rounded-lg bg-zinc-900 px-3 py-1.5 text-xs font-medium text-white hover:bg-zinc-800">
                                Simpan Kenaikan Kelas
                            </button>
                        @endif
                    @else
                        <p class="text-xs text-zinc-500">Hanya Administrator yang dapat menyimpan proses kenaikan kelas.</p>
                    @endif
                </div>
            </div>
        @endif

        @if ($hasil)
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-5 text-sm text-emerald-900">
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

        <div class="rounded-xl border border-zinc-200 bg-white p-5">
            <h3 class="text-base font-semibold text-zinc-900">Riwayat Batch Kenaikan Kelas</h3>

            <div class="mt-3 overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-zinc-50 text-xs uppercase text-zinc-600">
                        <tr>
                            <th class="px-3 py-2">Batch</th>
                            <th class="px-3 py-2">Asal → Tujuan</th>
                            <th class="px-3 py-2">Jumlah</th>
                            <th class="px-3 py-2">Status</th>
                            <th class="px-3 py-2">Waktu</th>
                            <th class="px-3 py-2">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        @forelse ($this->batch as $batch)
                            <tr>
                                <td class="px-3 py-2 font-mono text-xs">#{{ $batch->id }}</td>
                                <td class="px-3 py-2 text-xs">
                                    {{ $batch->tahunAjaranAsal?->nama }} → {{ $batch->tahunAjaranTujuan?->nama }}
                                </td>
                                <td class="px-3 py-2 text-xs">{{ $batch->jumlah_siswa }}</td>
                                <td class="px-3 py-2">
                                    <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $batch->status === 'selesai' ? 'bg-emerald-100 text-emerald-800' : 'bg-zinc-200 text-zinc-700' }}">
                                        {{ $batch->status }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-xs text-zinc-500">{{ $batch->created_at?->format('d/m/Y H:i') }}</td>
                                <td class="px-3 py-2">
                                    @if (auth()->user()?->isAdmin() && $service->bisaDibatalkan($batch))
                                        <button type="button" wire:click="batalkan({{ $batch->id }})"
                                            wire:confirm="Batalkan batch #{{ $batch->id }}? Riwayat kelas baru akan dihapus dan status lama dipulihkan."
                                            class="rounded-lg border border-rose-300 px-2.5 py-1 text-xs font-medium text-rose-700 hover:bg-rose-50">
                                            Batalkan
                                        </button>
                                    @else
                                        <span class="text-xs text-zinc-400">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-3 py-6 text-center text-xs text-zinc-500">Belum ada batch kenaikan kelas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>
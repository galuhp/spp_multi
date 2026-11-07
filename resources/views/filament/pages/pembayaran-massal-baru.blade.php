<x-filament-panels::page>
    @push('styles')
        @vite('resources/css/pembayaran-massal.css')
    @endpush

    @php
        $kelasTersedia = $this->kelasTersedia;
        $kelasTerpilih = $kelasTersedia->firstWhere('id', $this->kelas_id);
        $siswaList = $this->siswa;
        $terpilih = array_map('intval', $this->siswa_ids);
        $jumlahTerpilih = count($terpilih);
        $semuaTerpilih = $siswaList->isNotEmpty() && $jumlahTerpilih === $siswaList->count();
        $modeKompetensi = $this->mode === 'kompetensi';
    @endphp

    <div class="space-y-5">
        {{-- Step 1: mode --}}
        <section class="pm-step">
            <div class="pm-step-header">
                <div>
                    <h3 class="pm-step-title">
                        Pilih Dasar Pengelompokan
                    </h3>
                    <p class="mt-1 text-xs text-gray-500">Tentukan apakah kelas dipilih berdasarkan kompetensi keahlian atau tingkat kelas.</p>
                </div>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-3">
                <label class="pm-choice pm-choice-compact">
                    <input type="radio" wire:model.live="mode" value="kompetensi">
                    <span class="min-w-0">
                        <span class="block text-[13px] font-semibold text-gray-900">Kompetensi Keahlian</span>
                        <span class="mt-0.5 block text-[11px] leading-4 text-gray-500">
                            Tampilkan semua kelas dalam satu kompetensi keahlian, dari semua tingkat.
                        </span>
                    </span>
                </label>

                <label class="pm-choice pm-choice-compact">
                    <input type="radio" wire:model.live="mode" value="tingkat">
                    <span class="min-w-0">
                        <span class="block text-[13px] font-semibold text-gray-900">Tingkat Kelas</span>
                        <span class="mt-0.5 block text-[11px] leading-4 text-gray-500">
                            Tampilkan semua kelas pada satu tingkat, dari semua kompetensi keahlian.
                        </span>
                    </span>
                </label>
            </div>
        </section>

        {{-- Step 2: jurusan / tingkat --}}
        <section class="pm-step">
            <div class="pm-step-header">
                <div>
                    <h3 class="pm-step-title">
                        {{ $modeKompetensi ? 'Pilih Kompetensi Keahlian' : 'Pilih Tingkat Kelas' }}
                    </h3>
                    <p class="mt-1 text-xs text-gray-500">
                        {{ $modeKompetensi
                            ? 'Semua kelas pada kompetensi ini akan muncul pada langkah berikutnya.'
                            : 'Semua kelas pada tingkat ini akan muncul pada langkah berikutnya.' }}
                    </p>
                </div>
            </div>

            @if ($modeKompetensi)
                <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @forelse ($this->jurusanOptions as $jurusan)
                        <label class="pm-choice" wire:key="jurusan-{{ $jurusan->id }}">
                            <input type="radio" wire:model.live="jurusan_id" value="{{ $jurusan->id }}">
                            <span class="min-w-0">
                                <span class="block text-sm font-semibold text-gray-900">{{ $jurusan->nama }}</span>
                                <span class="mt-0.5 block font-mono text-[11px] text-gray-500">{{ $jurusan->kode }}</span>
                            </span>
                        </label>
                    @empty
                        <p class="text-sm text-gray-500">Belum ada data kompetensi keahlian.</p>
                    @endforelse
                </div>
            @else
                <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
                    @foreach ($this->tingkatOptions as $nilai => $label)
                        <label class="pm-choice" wire:key="tingkat-{{ $nilai }}">
                            <input type="radio" wire:model.live="tingkat" value="{{ $nilai }}">
                            <span class="min-w-0">
                                <span class="block text-sm font-semibold text-gray-900">{{ $label }}</span>
                                <span class="mt-0.5 block text-[11px] text-gray-500">Tingkat {{ $nilai }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- Step 3: class --}}
        @if (($modeKompetensi && $this->jurusan_id) || (! $modeKompetensi && $this->tingkat))
            <section class="pm-step">
                <div class="pm-step-header">
                    <div>
                        <h3 class="pm-step-title">
                            Pilih Kelas
                        </h3>
                        <p class="mt-1 text-xs text-gray-500">Daftar siswa akan dimuat otomatis setelah kelas dipilih.</p>
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @forelse ($kelasTersedia as $kelas)
                        <label class="pm-choice" wire:key="kelas-{{ $kelas->id }}">
                            <input type="radio" wire:model.live="kelas_id" value="{{ $kelas->id }}">
                            <span class="min-w-0">
                                <span class="block text-sm font-semibold text-gray-900">{{ $kelas->nama_kelas }}</span>
                                <span class="mt-0.5 block text-xs text-gray-500">{{ $kelas->jurusan?->nama ?? '-' }}</span>
                                <span class="mt-2 flex flex-wrap items-center gap-2 text-[11px]">
                                    <span class="rounded-full bg-gray-100 px-2 py-0.5 font-medium text-gray-600">
                                        {{ $kelas->siswas_count }} siswa aktif
                                    </span>
                                    @if ($kelas->tahunAjaran)
                                        <span class="text-gray-400">{{ $kelas->tahunAjaran->nama }}</span>
                                    @endif
                                </span>
                            </span>
                        </label>
                    @empty
                        <p class="text-sm text-gray-500">Tidak ada kelas yang cocok dengan pilihan tersebut.</p>
                    @endforelse
                </div>
            </section>
        @endif

        {{-- Step 4: students --}}
        @if ($kelasTerpilih)
            <section class="pm-step">
                <div class="pm-step-header">
                    <div>
                        <h3 class="pm-step-title">
                            Pilih Siswa
                        </h3>
                        <p class="mt-1 text-xs text-gray-500">
                            {{ $kelasTerpilih->nama_kelas }}
                            @if ($kelasTerpilih->jurusan)
                                &bull; {{ $kelasTerpilih->jurusan->nama }}
                            @endif
                            @if ($kelasTerpilih->tahunAjaran)
                                &bull; {{ $kelasTerpilih->tahunAjaran->nama }}
                            @endif
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <x-filament::button type="button" color="gray" size="sm" outlined wire:click="pilihSemua">
                            Centang Semua
                        </x-filament::button>
                        <x-filament::button type="button" color="gray" size="sm" outlined wire:click="kosongkan">
                            Kosongkan
                        </x-filament::button>
                    </div>
                </div>

                <div class="mt-4 overflow-x-auto rounded-xl border border-gray-200">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 text-[11px] uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="w-12 px-3 py-2.5 text-center">
                                    <input type="checkbox"
                                        wire:click="toggleSemua"
                                        @checked($semuaTerpilih)
                                        title="Centang / kosongkan semua"
                                        class="size-4 rounded border-gray-300 accent-primary-600">
                                </th>
                                <th class="px-3 py-2.5">NISN</th>
                                <th class="px-3 py-2.5">Nama Siswa</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($siswaList as $murid)
                                <tr wire:key="siswa-{{ $murid->id }}" class="hover:bg-gray-50">
                                    <td class="px-3 py-2.5 text-center">
                                        <input type="checkbox"
                                            wire:model.live="siswa_ids"
                                            value="{{ $murid->id }}"
                                            @checked(in_array($murid->id, $terpilih, true))
                                            class="size-4 rounded border-gray-300 accent-primary-600">
                                    </td>
                                    <td class="px-3 py-2.5 font-mono text-xs text-gray-700">{{ $murid->nisn ?: '-' }}</td>
                                    <td class="px-3 py-2.5 font-medium text-gray-900">{{ $murid->nama }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-3 py-8 text-center text-sm text-gray-500">
                                        Belum ada siswa aktif di kelas ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 flex flex-col gap-3 border-t border-gray-100 pt-4 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm text-gray-600">
                        <strong class="text-gray-900">{{ $jumlahTerpilih }}</strong>
                        dari {{ $siswaList->count() }} siswa dipilih.
                    </p>

                    <x-filament::button type="button" size="sm"
                        wire:click="bukaModal"
                        :disabled="$jumlahTerpilih === 0">
                        Bayar
                    </x-filament::button>
                </div>

                @if ($galat && ! $modal)
                    <div class="mt-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                        {{ $galat }}
                    </div>
                @endif
            </section>
        @endif
    </div>

    {{-- Payment item selection modal --}}
    @if ($modal)
        @php
            $nonSppList = $this->nonSppOptions;
            $nonSppTerpilih = array_map('intval', $this->non_spp_ids);
        @endphp
        <div class="fixed inset-0 z-50 flex items-end justify-center bg-gray-900/60 p-4 sm:items-center"
            wire:key="modal-bulan" role="dialog" aria-modal="true" aria-labelledby="pm-modal-title">
            <div class="w-full max-w-2xl overflow-hidden rounded-2xl bg-white shadow-xl">
                <div class="flex items-start justify-between gap-4 border-b border-gray-200 px-5 py-4">
                    <div>
                        <h3 id="pm-modal-title" class="text-base font-semibold text-gray-900">Pilih Item Pembayaran</h3>
                        <p class="mt-0.5 text-xs text-gray-500">
                            {{ $kelasTerpilih?->nama_kelas ? $kelasTerpilih->nama_kelas.' • ' : '' }}{{ $jumlahTerpilih }} siswa terpilih. Centang bulan SPP dan/atau tarif biaya lain yang akan dibayar.
                        </p>
                    </div>
                    <x-filament::icon-button icon="heroicon-o-x-mark" color="gray" size="sm"
                        label="Tutup" wire:click="tutupModal" />
                </div>

                <div class="max-h-[60vh] space-y-5 overflow-y-auto px-5 py-4">
                    {{-- SPP Bulanan --}}
                    <div class="space-y-2.5">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <span class="text-xs font-semibold uppercase tracking-wide text-gray-700">Bulan SPP</span>
                            <div class="flex flex-wrap items-center gap-2">
                                <x-filament::button type="button" color="gray" size="xs" outlined wire:click="pilihSemuaBulan">
                                    Semua Bulan
                                </x-filament::button>
                                <x-filament::button type="button" color="gray" size="xs" outlined wire:click="kosongkanBulan">
                                    Kosongkan
                                </x-filament::button>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 md:grid-cols-4">
                            @foreach ($this->bulanOptions as $namaBulan)
                                <label class="pm-month" wire:key="bulan-{{ $namaBulan }}">
                                    <input type="checkbox"
                                        wire:model.live="bulan"
                                        value="{{ $namaBulan }}"
                                        @checked(in_array($namaBulan, $this->bulan, true))>
                                    <span class="font-medium">{{ $namaBulan }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Tarif / Pos Biaya Lain (Non-SPP) --}}
                    <div class="space-y-2.5 border-t border-gray-100 pt-4">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <span class="text-xs font-semibold uppercase tracking-wide text-gray-700">Tarif / Biaya Lainnya (Non-SPP)</span>
                            @if ($nonSppList->isNotEmpty())
                                <div class="flex flex-wrap items-center gap-2">
                                    <x-filament::button type="button" color="gray" size="xs" outlined wire:click="pilihSemuaNonSpp">
                                        Semua Tarif
                                    </x-filament::button>
                                    <x-filament::button type="button" color="gray" size="xs" outlined wire:click="kosongkanNonSpp">
                                        Kosongkan
                                    </x-filament::button>
                                </div>
                            @endif
                        </div>

                        @if ($nonSppList->isNotEmpty())
                            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                @foreach ($nonSppList as $tarif)
                                    <label class="pm-month justify-between" wire:key="non-spp-{{ $tarif->id }}">
                                        <span class="flex min-w-0 items-center gap-2">
                                            <input type="checkbox"
                                                wire:model.live="non_spp_ids"
                                                value="{{ $tarif->id }}"
                                                @checked(in_array((int) $tarif->id, $nonSppTerpilih, true))>
                                            <span class="truncate font-medium">
                                                {{ $tarif->kategori }}{{ $tarif->nama ? ' — '.$tarif->nama : '' }}
                                            </span>
                                        </span>
                                        <span class="shrink-0 font-mono text-xs text-gray-500">
                                            Rp {{ number_format((int) $tarif->nominal, 0, ',', '.') }}
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        @else
                            <p class="text-xs text-gray-500">
                                Tidak ada pos biaya lain (Non-SPP) untuk tingkat dan tahun ajaran kelas ini.
                            </p>
                        @endif
                    </div>

                    <p class="text-[11px] leading-relaxed text-gray-500">
                        Tagihan yang sudah lunas atau ditanggung pemerintah untuk seorang siswa akan dilewati otomatis. Siswa yang seluruh item pilihannya sudah lunas tidak disertakan.
                    </p>

                    @if ($galat)
                        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                            {{ $galat }}
                        </div>
                    @endif
                </div>

                <div class="flex flex-col gap-4 border-t border-gray-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <div class="text-[11px] uppercase tracking-wide text-gray-500">Estimasi Total</div>
                        <div class="text-xl font-bold text-gray-900">
                            Rp {{ number_format($totalEstimasi, 0, ',', '.') }}
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2">
                        <x-filament::button type="button" color="gray" size="sm" outlined wire:click="tutupModal">
                            Batal
                        </x-filament::button>
                        <x-filament::button type="button" size="sm" :loading-indicator="false"
                            wire:click="simpan"
                            wire:loading.attr="disabled"
                            wire:target="simpan"
                            :disabled="$this->bulan === [] && $this->non_spp_ids === []">
                            <span wire:loading.remove wire:target="simpan">Simpan &amp; Cetak Kuitansi</span>
                            <span wire:loading wire:target="simpan">Memproses...</span>
                        </x-filament::button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>

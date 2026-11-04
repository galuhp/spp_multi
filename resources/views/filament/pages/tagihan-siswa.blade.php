<x-filament-panels::page>
    @php
        $siswa = $this->siswa;
        $tagihan = $this->tagihanData;
        $isStaff = auth()->user()?->isStaff() ?? false;
    @endphp

    @if($isStaff)
        <div class="fi-section rounded-xl bg-white p-5 shadow-xs ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h3 class="text-base font-semibold leading-6 text-gray-950 dark:text-white">
                        Pilih Siswa untuk Ditinjau
                    </h3>
                    <p class="text-xs text-gray-500">Pratinjau matriks kewajiban dan pembayaran iuran siswa terpilih.</p>
                </div>
                <div class="w-full sm:w-80">
                    <select wire:model.live="selectedSiswaId"
                        class="w-full rounded-lg border-gray-300 text-sm shadow-xs focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                        @foreach(\App\Models\Siswa::with('kelas')->orderBy('nama')->get() as $s)
                            <option value="{{ $s->id }}">{{ $s->nama }} (NISN: {{ $s->nisn }} - {{ $s->kelas?->nama_kelas }})</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    @endif

    @if($siswa)
        <!-- Student Identity Card -->
        <div class="fi-section rounded-xl bg-white p-6 shadow-xs ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Nama Siswa</span>
                    <div class="text-base font-bold text-gray-900 dark:text-white">{{ $siswa->nama }}</div>
                    <div class="text-xs text-gray-500">NISN: {{ $siswa->nisn }} &bull; NIS: {{ $siswa->nis }}</div>
                </div>
                <div>
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Kelas & Jurusan</span>
                    <div class="text-base font-bold text-gray-900 dark:text-white">{{ $siswa->kelas?->nama_kelas }}</div>
                    <div class="text-xs text-gray-500">{{ $siswa->kelas?->jurusan?->nama_jurusan }}</div>
                </div>
                <div>
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Tarif SPP Bulanan</span>
                    <div class="text-base font-bold text-primary-600 dark:text-primary-400">
                        Rp {{ number_format($siswa->spp?->nominal ?? 0, 0, ',', '.') }} / bulan
                    </div>
                    <div class="text-xs text-gray-500">Tahun Ajaran: {{ $siswa->spp?->tahun_ajaran ?? '-' }}</div>
                </div>
                <div>
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Kontak & Alamat</span>
                    <div class="text-xs text-gray-700 dark:text-gray-300">{{ $siswa->no_telp ?: '-' }}</div>
                    <div class="text-xs text-gray-500 truncate">{{ $siswa->alamat ?: '-' }}</div>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="fi-section rounded-xl bg-white p-5 shadow-xs ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <span class="text-xs font-medium uppercase tracking-wider text-emerald-600 dark:text-emerald-400">SPP Bulanan Terbayar</span>
                <div class="mt-2 text-2xl font-bold text-emerald-600 dark:text-emerald-400">
                    Rp {{ number_format($tagihan['totalLunas'], 0, ',', '.') }}
                </div>
            </div>

            <div class="fi-section rounded-xl bg-white p-5 shadow-xs ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <span class="text-xs font-medium uppercase tracking-wider text-rose-600 dark:text-rose-400">Sisa Tagihan SPP</span>
                <div class="mt-2 text-2xl font-bold text-rose-600 dark:text-rose-400">
                    Rp {{ number_format($tagihan['totalBelumLunas'], 0, ',', '.') }}
                </div>
            </div>

            <div class="fi-section rounded-xl bg-white p-5 shadow-xs ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <span class="text-xs font-medium uppercase tracking-wider text-amber-600 dark:text-amber-400">Sisa Tagihan Non-SPP</span>
                <div class="mt-2 text-2xl font-bold text-amber-600 dark:text-amber-400">
                    Rp {{ number_format($tagihan['totalNonSppBelumLunas'], 0, ',', '.') }}
                </div>
            </div>
        </div>

        <!-- Monthly Fee Grid -->
        <div class="fi-section rounded-xl bg-white p-6 shadow-xs ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="mb-4">
                <h3 class="text-base font-semibold leading-6 text-gray-950 dark:text-white">
                    Matriks Pembayaran SPP Bulanan (12 Bulan Tahun Pelajaran)
                </h3>
                <p class="text-xs text-gray-500">Status pelunasan iuran bulanan dari Juli hingga Juni.</p>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
                @foreach($tagihan['bulanStatus'] as $item)
                    <div class="rounded-xl border p-4 text-center transition {{ $item['lunas'] ? 'border-emerald-200 bg-emerald-50/50 dark:border-emerald-900 dark:bg-emerald-950/20' : 'border-gray-200 bg-gray-50/50 dark:border-gray-800 dark:bg-gray-800/30' }}">
                        <div class="text-sm font-bold text-gray-900 dark:text-white">{{ $item['bulan'] }}</div>
                        <div class="text-xs text-gray-500">{{ $item['tahun'] }}</div>
                        <div class="mt-3">
                            @if($item['lunas'])
                                <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-700 dark:bg-emerald-900 dark:text-emerald-300">
                                    Lunas
                                </span>
                                <div class="mt-1 text-xs font-semibold text-emerald-700 dark:text-emerald-400">
                                    Rp {{ number_format($item['pembayaran']->jumlah_bayar, 0, ',', '.') }}
                                </div>
                                @if($item['pembayaran'])
                                    <div class="mt-2">
                                        <a href="{{ route('pembayaran.cetak', $item['pembayaran']->id) }}" target="_blank"
                                            class="inline-block text-[11px] font-medium text-primary-600 hover:underline dark:text-primary-400">
                                            Kwitansi &raquo;
                                        </a>
                                    </div>
                                @endif
                            @else
                                <span class="inline-flex items-center rounded-full bg-rose-100 px-2 py-0.5 text-xs font-semibold text-rose-700 dark:bg-rose-900 dark:text-rose-300">
                                    Belum Lunas
                                </span>
                                <div class="mt-1 text-xs text-gray-500">
                                    Rp {{ number_format($item['nominal'], 0, ',', '.') }}
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Non SPP Fees -->
        @if(count($tagihan['nonSppStatus']) > 0)
            <div class="fi-section rounded-xl bg-white p-6 shadow-xs ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="mb-4">
                    <h3 class="text-base font-semibold leading-6 text-gray-950 dark:text-white">
                        Pos Tagihan Lainnya (Non-SPP)
                    </h3>
                    <p class="text-xs text-gray-500">Uang Gedung, Seragam, Praktik, Ujian dan biaya kegiatan non-bulanan.</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
                        <thead class="border-b border-gray-200 bg-gray-50 text-xs font-semibold uppercase tracking-wider text-gray-700 dark:border-gray-800 dark:bg-gray-800/50 dark:text-gray-300">
                            <tr>
                                <th class="px-4 py-3">Pos Tarif</th>
                                <th class="px-4 py-3">Kategori</th>
                                <th class="px-4 py-3 text-right">Total Kewajiban</th>
                                <th class="px-4 py-3 text-right">Sudah Dibayar</th>
                                <th class="px-4 py-3 text-right">Sisa Tagihan</th>
                                <th class="px-4 py-3 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach($tagihan['nonSppStatus'] as $item)
                                <tr>
                                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                        {{ $item['tarif']->nama_tarif }}
                                    </td>
                                    <td class="px-4 py-3 text-xs">
                                        <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                            {{ $item['tarif']->kategori }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right font-medium">
                                        Rp {{ number_format($item['nominal'], 0, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-semibold text-emerald-600 dark:text-emerald-400">
                                        Rp {{ number_format($item['dibayar'], 0, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-semibold text-rose-600 dark:text-rose-400">
                                        Rp {{ number_format($item['sisa'], 0, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @if($item['lunas'])
                                            <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 dark:bg-emerald-900 dark:text-emerald-300">
                                                Lunas
                                            </span>
                                        @elseif($item['dibayar'] > 0)
                                            <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-700 dark:bg-amber-900 dark:text-amber-300">
                                                Dicicil
                                            </span>
                                        @else
                                            <span class="inline-flex items-center rounded-full bg-rose-100 px-2.5 py-0.5 text-xs font-semibold text-rose-700 dark:bg-rose-900 dark:text-rose-300">
                                                Belum Bayar
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    @else
        <div class="fi-section rounded-xl bg-white p-8 text-center shadow-xs ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <p class="text-sm text-gray-500">Data siswa tidak ditemukan.</p>
        </div>
    @endif
</x-filament-panels::page>

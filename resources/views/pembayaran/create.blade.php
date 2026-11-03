@extends('layouts.app')

@section('title', 'Entri Pembayaran SPP')
@section('header_title', 'Entri Pembayaran SPP')

@section('content')
@php
    $step = $selectedSiswa ? 3 : ($kelasId ? 2 : 1);
    $kelasTerpilih = $kelasList->firstWhere('id', $kelasId);
    $steps = [1 => 'Pilih Kelas', 2 => 'Pilih Siswa', 3 => 'Pilih Bulan & Bayar'];
@endphp

<div class="max-w-4xl mx-auto space-y-5">
    <div class="flex items-center justify-between">
        <div>
            <h3 class="text-xl font-bold text-slate-900 dark:text-white">Form Pembayaran SPP</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Ikuti tiga langkah: kelas, siswa, lalu bulan yang dibayar</p>
        </div>
        <a href="{{ route('pembayaran.index') }}" class="text-xs font-semibold text-slate-600 dark:text-slate-300 hover:text-prism-600 dark:hover:text-prism-300">&larr; Kembali</a>
    </div>

    <!-- Step indicator -->
    <ol class="flex items-center gap-2 text-xs font-semibold">
        @foreach($steps as $no => $label)
            <li class="flex items-center gap-2 {{ $no < 3 ? 'flex-1' : '' }}">
                <span class="w-6 h-6 rounded-full flex items-center justify-center text-[11px] shrink-0
                    {{ $no < $step ? 'bg-emerald-500 text-white' : ($no === $step ? 'bg-prism-600 text-white shadow-sm shadow-prism-500/30' : 'bg-slate-200 dark:bg-slate-800 text-slate-500 dark:text-slate-400') }}">
                    {{ $no < $step ? '✓' : $no }}
                </span>
                <span class="{{ $no === $step ? 'text-slate-900 dark:text-white' : 'text-slate-400 dark:text-slate-500' }} hidden sm:inline">{{ $label }}</span>
                @if($no < 3)
                    <span class="flex-1 h-px {{ $no < $step ? 'bg-emerald-400 dark:bg-emerald-500/50' : 'bg-slate-200 dark:bg-white/10' }}"></span>
                @endif
            </li>
        @endforeach
    </ol>

    @if($selectedSiswa)
        <!-- Compact summary of earlier choices -->
        <div class="prism-card px-5 py-3 flex flex-wrap items-center justify-between gap-3 text-xs">
            <div class="flex items-center gap-2 text-slate-600 dark:text-slate-300">
                <span>Kelas <strong class="text-slate-900 dark:text-white">{{ $selectedSiswa->kelas?->nama_kelas }}</strong></span>
                <span class="text-slate-300 dark:text-slate-600">&rsaquo;</span>
                <span>Siswa <strong class="text-slate-900 dark:text-white">{{ $selectedSiswa->nama }}</strong></span>
            </div>
            <div class="flex items-center gap-3 font-semibold">
                <a href="{{ route('pembayaran.create', ['kelas_id' => $kelasId]) }}" class="text-prism-600 dark:text-prism-300 hover:underline">Ganti siswa</a>
                <a href="{{ route('pembayaran.create') }}" class="text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200">Ganti kelas</a>
            </div>
        </div>
    @else
        <!-- Step 1: choose class -->
        <div class="prism-card p-5">
            <div class="text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-3">1. Pilih Kelas</div>

            @foreach($kelasList->groupBy('tingkat') as $tingkat => $kelasGroup)
                <div class="mb-3 last:mb-0">
                    <div class="text-[11px] font-semibold text-slate-400 dark:text-slate-500 mb-1.5">Kelas {{ $tingkat }}</div>
                    <div class="flex flex-wrap gap-2">
                        @foreach($kelasGroup as $k)
                            <a href="{{ route('pembayaran.create', ['kelas_id' => $k->id]) }}"
                                class="px-3.5 py-2 rounded-xl border text-xs font-semibold transition
                                {{ $kelasId === $k->id ? 'prism-btn-primary border-transparent' : 'bg-white/70 dark:bg-slate-800/60 border-slate-200/80 dark:border-white/10 text-slate-700 dark:text-slate-200 hover:border-prism-400 hover:bg-prism-50/70 dark:hover:bg-prism-500/15' }}">
                                {{ $k->nama_kelas }}
                                <span class="ml-1 font-medium {{ $kelasId === $k->id ? 'text-prism-100' : 'text-slate-400 dark:text-slate-500' }}">{{ $k->siswas_count }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach

            @if($kelasList->isEmpty())
                <p class="text-xs text-slate-400 dark:text-slate-500">Belum ada kelas. Tambahkan kelas terlebih dahulu.</p>
            @endif
        </div>

        <!-- Step 2: choose student -->
        @if($kelasId)
            @php $menunggak = $siswaKelas->where('jumlah_tunggakan', '>', 0)->count(); @endphp
            <div class="prism-card p-5">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
                    <div>
                        <div class="text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">2. Pilih Siswa &mdash; {{ $kelasTerpilih?->nama_kelas }}</div>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                            {{ $siswaKelas->count() }} siswa
                            @if($menunggak > 0)
                                &bull; <span class="text-rose-600 dark:text-rose-400 font-semibold">{{ $menunggak }} siswa punya tunggakan</span>
                            @endif
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <label class="flex items-center gap-1.5 text-xs text-slate-600 dark:text-slate-300 cursor-pointer select-none">
                            <input type="checkbox" id="only-arrears" class="w-3.5 h-3.5 rounded border-slate-300 dark:border-slate-600">
                            Hanya yang menunggak
                        </label>
                        <input type="search" id="siswa-search" placeholder="Cari nama / NISN..." autofocus
                            class="w-44 px-3 py-1.5 prism-input text-xs">
                    </div>
                </div>

                @if($siswaKelas->isEmpty())
                    <p class="py-6 text-center text-xs text-slate-400 dark:text-slate-500">Belum ada siswa di kelas ini.</p>
                @else
                    <div id="siswa-grid" class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        @foreach($siswaKelas as $s)
                            @php
                                $lunasSemua = $s->jumlah_dibayar >= 12;
                                $punyaTunggakan = $s->jumlah_tunggakan > 0;
                            @endphp
                            <a href="{{ route('pembayaran.create', ['siswa_id' => $s->id]) }}"
                                data-search="{{ strtolower($s->nama . ' ' . $s->nisn . ' ' . $s->nis) }}"
                                data-arrears="{{ $punyaTunggakan ? 1 : 0 }}"
                                class="siswa-item p-3 rounded-xl border border-slate-200/80 dark:border-white/10 bg-white/50 dark:bg-slate-800/40 hover:border-prism-400 hover:bg-prism-50/40 dark:hover:bg-prism-500/12 flex items-center justify-between gap-3 transition">
                                <div class="min-w-0">
                                    <div class="font-bold text-sm text-slate-900 dark:text-white truncate">{{ $s->nama }}</div>
                                    <div class="text-[11px] text-slate-400 dark:text-slate-500 font-mono">NISN {{ $s->nisn }}</div>
                                </div>
                                <div class="text-right shrink-0">
                                    @if($lunasSemua)
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 dark:bg-emerald-500/15 text-emerald-700 dark:text-emerald-300">Lunas 12 bulan</span>
                                    @elseif($punyaTunggakan)
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 dark:bg-rose-500/15 text-rose-700 dark:text-rose-300">{{ $s->jumlah_tunggakan }} bln menunggak</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-sky-100 dark:bg-sky-500/15 text-sky-700 dark:text-sky-300">Sampai bulan ini lunas</span>
                                    @endif
                                    <div class="text-[10px] text-slate-400 dark:text-slate-500 mt-0.5">{{ $s->jumlah_dibayar }}/12 dibayar</div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                    <p id="siswa-empty" class="hidden py-6 text-center text-xs text-slate-400 dark:text-slate-500">Tidak ada siswa yang cocok.</p>
                @endif
            </div>
        @endif
    @endif

    <!-- Step 3: choose months and pay -->
    @if($selectedSiswa)
        @php
            $nominal = $selectedSiswa->spp?->nominal ?? 0;
            $jatuhTempo = collect($unpaidMonths)->where('jatuh_tempo', true);
            $rekomendasi = $unpaidMonths[0]['bulan'] ?? null;
            $oldBulan = old('bulan_dibayar', []);
        @endphp

        <div class="prism-card p-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-slate-100 dark:border-white/10">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-xl bg-linear-to-tr from-prism-600 to-violet-500 text-white flex items-center justify-center font-bold shadow-sm">
                        {{ strtoupper(substr($selectedSiswa->nama, 0, 2)) }}
                    </div>
                    <div>
                        <h4 class="font-bold text-slate-900 dark:text-white">{{ $selectedSiswa->nama }}</h4>
                        <div class="text-xs text-slate-500 dark:text-slate-400 font-mono">NISN {{ $selectedSiswa->nisn }} &bull; {{ $selectedSiswa->kelas?->nama_kelas }}</div>
                    </div>
                </div>
                <div class="text-xs text-slate-500 dark:text-slate-400 sm:text-right">
                    TA {{ $selectedSiswa->spp?->tahun_ajaran }}<br>
                    <strong class="text-emerald-700 dark:text-emerald-400 text-sm">{{ $selectedSiswa->spp?->formattedNominal() }}</strong> / bulan
                </div>
            </div>

            <form action="{{ route('pembayaran.store') }}" method="POST" class="pt-5 space-y-5">
                @csrf
                <input type="hidden" name="siswa_id" value="{{ $selectedSiswa->id }}">
                <input type="hidden" name="tahun_dibayar" value="{{ $selectedSiswa->spp?->tahun ?? date('Y') }}">

                @if(empty($unpaidMonths))
                    <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-500/12 border border-emerald-200 dark:border-emerald-400/25 text-emerald-800 dark:text-emerald-200 text-xs font-medium">
                        Semua iuran SPP tahun ajaran ini sudah lunas.
                    </div>
                @else
                    <div>
                        <div class="text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-2">3. Pilih Bulan yang Dibayar <span class="text-rose-500">*</span></div>

                        <!-- Recommendation -->
                        <div class="p-3.5 rounded-xl bg-prism-50/80 dark:bg-prism-500/12 border border-prism-200/70 dark:border-prism-400/25 mb-3">
                            <div class="text-xs text-prism-900 dark:text-prism-100 mb-2">
                                <strong>Rekomendasi:</strong>
                                @if($jatuhTempo->isNotEmpty())
                                    {{ $jatuhTempo->count() }} bulan sudah jatuh tempo
                                    ({{ $jatuhTempo->first()['bulan'] }}@if($jatuhTempo->count() > 1) &ndash; {{ $jatuhTempo->last()['bulan'] }}@endif).
                                @else
                                    tidak ada tunggakan, bulan berikutnya adalah <strong>{{ $rekomendasi }}</strong>.
                                @endif
                            </div>
                            <div class="flex flex-wrap gap-2 text-[11px] font-semibold">
                                <button type="button" onclick="pilihBulan('next', 1)" class="px-3 py-1.5 rounded-lg bg-white dark:bg-slate-800 border border-prism-200 dark:border-prism-400/30 text-prism-700 dark:text-prism-300 hover:bg-prism-100 dark:hover:bg-prism-500/20 cursor-pointer">Bulan berikutnya ({{ $rekomendasi }})</button>
                                @if($jatuhTempo->count() > 1)
                                    <button type="button" onclick="pilihBulan('due')" class="px-3 py-1.5 rounded-lg bg-white dark:bg-slate-800 border border-rose-200 dark:border-rose-400/30 text-rose-700 dark:text-rose-300 hover:bg-rose-50 dark:hover:bg-rose-500/20 cursor-pointer">Semua jatuh tempo ({{ $jatuhTempo->count() }})</button>
                                @endif
                                <button type="button" onclick="pilihBulan('all')" class="px-3 py-1.5 rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-white/12 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 cursor-pointer">Semua belum dibayar ({{ count($unpaidMonths) }})</button>
                                <button type="button" onclick="pilihBulan('none')" class="px-3 py-1.5 rounded-lg text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-white/10 cursor-pointer">Kosongkan</button>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2.5">
                            @foreach($unpaidMonths as $i => $item)
                                <label class="relative p-3 rounded-xl border flex items-start gap-2.5 cursor-pointer select-none transition
                                    {{ $item['jatuh_tempo'] ? 'border-rose-200/80 dark:border-rose-400/25 bg-rose-50/40 dark:bg-rose-500/10 hover:border-rose-400' : 'border-slate-200/80 dark:border-white/10 bg-slate-50/70 dark:bg-slate-800/50 hover:border-prism-400' }}">
                                    <input type="checkbox" name="bulan_dibayar[]" value="{{ $item['bulan'] }}"
                                        data-nominal="{{ $item['nominal'] }}" data-due="{{ $item['jatuh_tempo'] ? 1 : 0 }}"
                                        class="month-checkbox mt-0.5 w-4 h-4 rounded border-slate-300 dark:border-slate-600"
                                        {{ in_array($item['bulan'], $oldBulan, true) || (empty($oldBulan) && $i === 0) ? 'checked' : '' }}
                                        onchange="hitungTotal()">
                                    <div>
                                        <div class="font-bold text-xs text-slate-800 dark:text-slate-100">{{ $item['bulan'] }}</div>
                                        <div class="text-[11px] text-slate-500 dark:text-slate-400">Rp {{ number_format($item['nominal'], 0, ',', '.') }}</div>
                                        @if($i === 0)
                                            <span class="inline-block mt-1 px-1.5 py-0.5 rounded bg-prism-600 text-white text-[9px] font-bold">DISARANKAN</span>
                                        @elseif($item['jatuh_tempo'])
                                            <span class="inline-block mt-1 px-1.5 py-0.5 rounded bg-rose-100 dark:bg-rose-500/20 text-rose-700 dark:text-rose-300 text-[9px] font-bold">JATUH TEMPO</span>
                                        @endif
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="tgl_bayar" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1">Tanggal Pembayaran <span class="text-rose-500">*</span></label>
                            <input type="date" id="tgl_bayar" name="tgl_bayar" value="{{ old('tgl_bayar', date('Y-m-d')) }}" required
                                class="w-full px-3.5 py-2.5 prism-input text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1">Metode Pembayaran <span class="text-rose-500">*</span></label>
                            <div class="grid grid-cols-3 gap-2">
                                @foreach(['Tunai' => 'Tunai', 'Transfer Bank' => 'Transfer', 'QRIS' => 'QRIS'] as $value => $label)
                                    <label class="cursor-pointer">
                                        <input type="radio" name="metode_pembayaran" value="{{ $value }}" class="peer sr-only" {{ old('metode_pembayaran', 'Tunai') === $value ? 'checked' : '' }}>
                                        <span class="block text-center px-2 py-2.5 rounded-xl border border-slate-300/90 dark:border-slate-700 text-xs font-semibold text-slate-600 dark:text-slate-300 peer-checked:bg-prism-600 peer-checked:border-prism-600 peer-checked:text-white hover:border-prism-400 transition">{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div>
                        <label for="catatan" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1">Catatan (Opsional)</label>
                        <input type="text" id="catatan" name="catatan" value="{{ old('catatan') }}" placeholder="Catatan tambahan bila diperlukan"
                            class="w-full px-3.5 py-2.5 prism-input text-sm">
                    </div>

                    <div class="p-4 rounded-2xl bg-prism-50/80 dark:bg-prism-500/12 border border-prism-200/80 dark:border-prism-400/25 flex items-center justify-between">
                        <div>
                            <div class="text-xs font-bold uppercase tracking-wider text-prism-900 dark:text-prism-100">Total Pembayaran</div>
                            <div class="text-xs text-prism-700 dark:text-prism-300 mt-0.5" id="month-count-label">0 bulan dipilih</div>
                        </div>
                        <div class="text-2xl font-black text-prism-900 dark:text-white" id="total-display">Rp 0</div>
                    </div>

                    <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-100 dark:border-white/10">
                        <a href="{{ route('pembayaran.create', ['kelas_id' => $kelasId]) }}" class="px-4 py-2.5 prism-btn-secondary text-xs">Batal</a>
                        <button type="submit" class="px-6 py-2.5 prism-btn-emerald text-xs font-bold">Proses & Simpan Transaksi</button>
                    </div>
                @endif
            </form>
        </div>
    @endif
</div>

<script>
    const rupiah = (n) => 'Rp ' + n.toLocaleString('id-ID');

    function hitungTotal() {
        const checked = document.querySelectorAll('.month-checkbox:checked');
        let total = 0;
        checked.forEach(cb => total += parseInt(cb.dataset.nominal || 0));
        const totalEl = document.getElementById('total-display');
        if (totalEl) {
            totalEl.innerText = rupiah(total);
            document.getElementById('month-count-label').innerText = checked.length + ' bulan dipilih';
        }
    }

    function pilihBulan(mode, limit) {
        document.querySelectorAll('.month-checkbox').forEach((cb, index) => {
            if (mode === 'none') cb.checked = false;
            else if (mode === 'all') cb.checked = true;
            else if (mode === 'due') cb.checked = cb.dataset.due === '1';
            else if (mode === 'next') cb.checked = index < limit;
        });
        hitungTotal();
    }

    const searchInput = document.getElementById('siswa-search');
    const onlyArrears = document.getElementById('only-arrears');

    function filterSiswa() {
        const term = (searchInput?.value || '').trim().toLowerCase();
        const arrearsOnly = onlyArrears?.checked;
        let visible = 0;

        document.querySelectorAll('.siswa-item').forEach(el => {
            const match = el.dataset.search.includes(term) && (!arrearsOnly || el.dataset.arrears === '1');
            el.classList.toggle('hidden', !match);
            if (match) visible++;
        });

        document.getElementById('siswa-empty')?.classList.toggle('hidden', visible > 0);
    }

    searchInput?.addEventListener('input', filterSiswa);
    onlyArrears?.addEventListener('change', filterSiswa);
    hitungTotal();
</script>
@endsection

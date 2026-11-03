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
            <h3 class="text-xl font-bold text-slate-900">Form Pembayaran SPP</h3>
            <p class="text-xs text-slate-500 mt-0.5">Ikuti tiga langkah: kelas, siswa, lalu bulan yang dibayar</p>
        </div>
        <a href="{{ route('pembayaran.index') }}" class="text-xs font-semibold text-slate-600 hover:text-slate-900">&larr; Kembali</a>
    </div>

    <!-- Step indicator -->
    <ol class="flex items-center gap-2 text-xs font-semibold">
        @foreach($steps as $no => $label)
            <li class="flex items-center gap-2 {{ $no < 3 ? 'flex-1' : '' }}">
                <span class="w-6 h-6 rounded-full flex items-center justify-center text-[11px] shrink-0
                    {{ $no < $step ? 'bg-emerald-500 text-white' : ($no === $step ? 'bg-indigo-600 text-white' : 'bg-slate-200 text-slate-500') }}">
                    {{ $no < $step ? '✓' : $no }}
                </span>
                <span class="{{ $no === $step ? 'text-slate-900' : 'text-slate-400' }} hidden sm:inline">{{ $label }}</span>
                @if($no < 3)
                    <span class="flex-1 h-px {{ $no < $step ? 'bg-emerald-300' : 'bg-slate-200' }}"></span>
                @endif
            </li>
        @endforeach
    </ol>

    @if($selectedSiswa)
        <!-- Compact summary of earlier choices -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs px-5 py-3 flex flex-wrap items-center justify-between gap-3 text-xs">
            <div class="flex items-center gap-2 text-slate-600">
                <span>Kelas <strong class="text-slate-900">{{ $selectedSiswa->kelas?->nama_kelas }}</strong></span>
                <span class="text-slate-300">&rsaquo;</span>
                <span>Siswa <strong class="text-slate-900">{{ $selectedSiswa->nama }}</strong></span>
            </div>
            <div class="flex items-center gap-3 font-semibold">
                <a href="{{ route('pembayaran.create', ['kelas_id' => $kelasId]) }}" class="text-indigo-600 hover:text-indigo-800">Ganti siswa</a>
                <a href="{{ route('pembayaran.create') }}" class="text-slate-500 hover:text-slate-800">Ganti kelas</a>
            </div>
        </div>
    @else
        <!-- Step 1: choose class -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5">
            <div class="text-xs font-bold uppercase tracking-wider text-slate-600 mb-3">1. Pilih Kelas</div>

            @foreach($kelasList->groupBy('tingkat') as $tingkat => $kelasGroup)
                <div class="mb-3 last:mb-0">
                    <div class="text-[11px] font-semibold text-slate-400 mb-1.5">Kelas {{ $tingkat }}</div>
                    <div class="flex flex-wrap gap-2">
                        @foreach($kelasGroup as $k)
                            <a href="{{ route('pembayaran.create', ['kelas_id' => $k->id]) }}"
                                class="px-3.5 py-2 rounded-xl border text-xs font-semibold transition
                                {{ $kelasId === $k->id ? 'bg-indigo-600 border-indigo-600 text-white shadow-sm' : 'bg-white border-slate-200 text-slate-700 hover:border-indigo-400 hover:bg-indigo-50' }}">
                                {{ $k->nama_kelas }}
                                <span class="ml-1 font-medium {{ $kelasId === $k->id ? 'text-indigo-100' : 'text-slate-400' }}">{{ $k->siswas_count }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach

            @if($kelasList->isEmpty())
                <p class="text-xs text-slate-400">Belum ada kelas. Tambahkan kelas terlebih dahulu.</p>
            @endif
        </div>

        <!-- Step 2: choose student -->
        @if($kelasId)
            @php $menunggak = $siswaKelas->where('jumlah_tunggakan', '>', 0)->count(); @endphp
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
                    <div>
                        <div class="text-xs font-bold uppercase tracking-wider text-slate-600">2. Pilih Siswa &mdash; {{ $kelasTerpilih?->nama_kelas }}</div>
                        <p class="text-[11px] text-slate-500 mt-0.5">
                            {{ $siswaKelas->count() }} siswa
                            @if($menunggak > 0)
                                &bull; <span class="text-rose-600 font-semibold">{{ $menunggak }} siswa punya tunggakan</span>
                            @endif
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <label class="flex items-center gap-1.5 text-xs text-slate-600 cursor-pointer select-none">
                            <input type="checkbox" id="only-arrears" class="w-3.5 h-3.5 rounded text-indigo-600 border-slate-300">
                            Hanya yang menunggak
                        </label>
                        <input type="search" id="siswa-search" placeholder="Cari nama / NISN..." autofocus
                            class="w-44 px-3 py-1.5 rounded-xl border border-slate-300 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>

                @if($siswaKelas->isEmpty())
                    <p class="py-6 text-center text-xs text-slate-400">Belum ada siswa di kelas ini.</p>
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
                                class="siswa-item p-3 rounded-xl border border-slate-200 hover:border-indigo-400 hover:bg-indigo-50/40 flex items-center justify-between gap-3 transition">
                                <div class="min-w-0">
                                    <div class="font-bold text-sm text-slate-900 truncate">{{ $s->nama }}</div>
                                    <div class="text-[11px] text-slate-400 font-mono">NISN {{ $s->nisn }}</div>
                                </div>
                                <div class="text-right shrink-0">
                                    @if($lunasSemua)
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700">Lunas 12 bulan</span>
                                    @elseif($punyaTunggakan)
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-700">{{ $s->jumlah_tunggakan }} bln menunggak</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-sky-100 text-sky-700">Sampai bulan ini lunas</span>
                                    @endif
                                    <div class="text-[10px] text-slate-400 mt-0.5">{{ $s->jumlah_dibayar }}/12 dibayar</div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                    <p id="siswa-empty" class="hidden py-6 text-center text-xs text-slate-400">Tidak ada siswa yang cocok.</p>
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

        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-slate-100">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-xl bg-indigo-600 text-white flex items-center justify-center font-bold">
                        {{ strtoupper(substr($selectedSiswa->nama, 0, 2)) }}
                    </div>
                    <div>
                        <h4 class="font-bold text-slate-900">{{ $selectedSiswa->nama }}</h4>
                        <div class="text-xs text-slate-500 font-mono">NISN {{ $selectedSiswa->nisn }} &bull; {{ $selectedSiswa->kelas?->nama_kelas }}</div>
                    </div>
                </div>
                <div class="text-xs text-slate-500 sm:text-right">
                    TA {{ $selectedSiswa->spp?->tahun_ajaran }}<br>
                    <strong class="text-emerald-700 text-sm">{{ $selectedSiswa->spp?->formattedNominal() }}</strong> / bulan
                </div>
            </div>

            <form action="{{ route('pembayaran.store') }}" method="POST" class="pt-5 space-y-5">
                @csrf
                <input type="hidden" name="siswa_id" value="{{ $selectedSiswa->id }}">
                <input type="hidden" name="tahun_dibayar" value="{{ $selectedSiswa->spp?->tahun ?? date('Y') }}">

                @if(empty($unpaidMonths))
                    <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-medium">
                        Semua iuran SPP tahun ajaran ini sudah lunas.
                    </div>
                @else
                    <div>
                        <div class="text-xs font-bold uppercase tracking-wider text-slate-600 mb-2">3. Pilih Bulan yang Dibayar <span class="text-rose-500">*</span></div>

                        <!-- Recommendation -->
                        <div class="p-3.5 rounded-xl bg-indigo-50 border border-indigo-100 mb-3">
                            <div class="text-xs text-indigo-900 mb-2">
                                <strong>Rekomendasi:</strong>
                                @if($jatuhTempo->isNotEmpty())
                                    {{ $jatuhTempo->count() }} bulan sudah jatuh tempo
                                    ({{ $jatuhTempo->first()['bulan'] }}@if($jatuhTempo->count() > 1) &ndash; {{ $jatuhTempo->last()['bulan'] }}@endif).
                                @else
                                    tidak ada tunggakan, bulan berikutnya adalah <strong>{{ $rekomendasi }}</strong>.
                                @endif
                            </div>
                            <div class="flex flex-wrap gap-2 text-[11px] font-semibold">
                                <button type="button" onclick="pilihBulan('next', 1)" class="px-3 py-1.5 rounded-lg bg-white border border-indigo-200 text-indigo-700 hover:bg-indigo-100">Bulan berikutnya ({{ $rekomendasi }})</button>
                                @if($jatuhTempo->count() > 1)
                                    <button type="button" onclick="pilihBulan('due')" class="px-3 py-1.5 rounded-lg bg-white border border-rose-200 text-rose-700 hover:bg-rose-50">Semua jatuh tempo ({{ $jatuhTempo->count() }})</button>
                                @endif
                                <button type="button" onclick="pilihBulan('all')" class="px-3 py-1.5 rounded-lg bg-white border border-slate-200 text-slate-700 hover:bg-slate-100">Semua belum dibayar ({{ count($unpaidMonths) }})</button>
                                <button type="button" onclick="pilihBulan('none')" class="px-3 py-1.5 rounded-lg text-slate-500 hover:bg-slate-100">Kosongkan</button>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2.5">
                            @foreach($unpaidMonths as $i => $item)
                                <label class="relative p-3 rounded-xl border flex items-start gap-2.5 cursor-pointer select-none transition
                                    {{ $item['jatuh_tempo'] ? 'border-rose-200 bg-rose-50/40 hover:border-rose-400' : 'border-slate-200 bg-slate-50 hover:border-indigo-400' }}">
                                    <input type="checkbox" name="bulan_dibayar[]" value="{{ $item['bulan'] }}"
                                        data-nominal="{{ $item['nominal'] }}" data-due="{{ $item['jatuh_tempo'] ? 1 : 0 }}"
                                        class="month-checkbox mt-0.5 w-4 h-4 rounded text-indigo-600 border-slate-300"
                                        {{ in_array($item['bulan'], $oldBulan, true) || (empty($oldBulan) && $i === 0) ? 'checked' : '' }}
                                        onchange="hitungTotal()">
                                    <div>
                                        <div class="font-bold text-xs text-slate-800">{{ $item['bulan'] }}</div>
                                        <div class="text-[11px] text-slate-500">Rp {{ number_format($item['nominal'], 0, ',', '.') }}</div>
                                        @if($i === 0)
                                            <span class="inline-block mt-1 px-1.5 py-0.5 rounded bg-indigo-600 text-white text-[9px] font-bold">DISARANKAN</span>
                                        @elseif($item['jatuh_tempo'])
                                            <span class="inline-block mt-1 px-1.5 py-0.5 rounded bg-rose-100 text-rose-700 text-[9px] font-bold">JATUH TEMPO</span>
                                        @endif
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="tgl_bayar" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1">Tanggal Pembayaran <span class="text-rose-500">*</span></label>
                            <input type="date" id="tgl_bayar" name="tgl_bayar" value="{{ old('tgl_bayar', date('Y-m-d')) }}" required
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1">Metode Pembayaran <span class="text-rose-500">*</span></label>
                            <div class="grid grid-cols-3 gap-2">
                                @foreach(['Tunai' => 'Tunai', 'Transfer Bank' => 'Transfer', 'QRIS' => 'QRIS'] as $value => $label)
                                    <label class="cursor-pointer">
                                        <input type="radio" name="metode_pembayaran" value="{{ $value }}" class="peer sr-only" {{ old('metode_pembayaran', 'Tunai') === $value ? 'checked' : '' }}>
                                        <span class="block text-center px-2 py-2.5 rounded-xl border border-slate-300 text-xs font-semibold text-slate-600 peer-checked:bg-indigo-600 peer-checked:border-indigo-600 peer-checked:text-white hover:border-indigo-400 transition">{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div>
                        <label for="catatan" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1">Catatan (Opsional)</label>
                        <input type="text" id="catatan" name="catatan" value="{{ old('catatan') }}" placeholder="Catatan tambahan bila diperlukan"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
                    </div>

                    <div class="p-4 rounded-2xl bg-indigo-50/70 border border-indigo-200 flex items-center justify-between">
                        <div>
                            <div class="text-xs font-bold uppercase tracking-wider text-indigo-900">Total Pembayaran</div>
                            <div class="text-xs text-indigo-700 mt-0.5" id="month-count-label">0 bulan dipilih</div>
                        </div>
                        <div class="text-2xl font-black text-indigo-900" id="total-display">Rp 0</div>
                    </div>

                    <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-100">
                        <a href="{{ route('pembayaran.create', ['kelas_id' => $kelasId]) }}" class="px-4 py-2.5 rounded-xl text-slate-700 bg-slate-100 hover:bg-slate-200 text-xs font-semibold transition">Batal</a>
                        <button type="submit" class="px-6 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition shadow-sm">Proses & Simpan Transaksi</button>
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

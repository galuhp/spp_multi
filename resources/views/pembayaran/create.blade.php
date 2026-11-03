@extends('layouts.app')

@section('title', 'Entri Pembayaran SPP')
@section('header_title', 'Entri Pembayaran SPP')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h3 class="text-xl font-bold text-slate-900">Form Pembayaran SPP</h3>
            <p class="text-xs text-slate-500 mt-0.5">Pilih siswa dan tentukan bulan SPP yang akan dibayarkan</p>
        </div>
        <a href="{{ route('pembayaran.index') }}" class="text-xs font-semibold text-slate-600 hover:text-slate-900">
            &larr; Kembali
        </a>
    </div>

    <!-- Step 1: Select Student Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
        <label for="siswa_select" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
            Pilih Siswa yang Melakukan Pembayaran <span class="text-rose-500">*</span>
        </label>
        <div class="flex flex-col sm:flex-row gap-3">
            <select id="siswa_select" class="flex-1 px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm bg-white" onchange="window.location.href='{{ route('pembayaran.create') }}?siswa_id=' + this.value">
                <option value="">-- Ketik atau Pilih Siswa --</option>
                @foreach($allSiswas as $s)
                    <option value="{{ $s->id }}" {{ $selectedSiswa && $selectedSiswa->id == $s->id ? 'selected' : '' }}>
                        {{ $s->nama }} (NISN: {{ $s->nisn }}) - Kelas {{ $s->kelas?->nama_kelas }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    @if($selectedSiswa)
        <!-- Selected Student Information -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-100">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl bg-indigo-600 text-white flex items-center justify-center font-bold text-lg">
                        {{ strtoupper(substr($selectedSiswa->nama, 0, 2)) }}
                    </div>
                    <div>
                        <h4 class="font-bold text-slate-900 text-base">{{ $selectedSiswa->nama }}</h4>
                        <div class="text-xs text-slate-500 font-mono">NISN: {{ $selectedSiswa->nisn }} &bull; NIS: {{ $selectedSiswa->nis }}</div>
                    </div>
                </div>
                <div class="text-left sm:text-right">
                    <div class="text-xs text-slate-500 font-medium">Kelas: <strong>{{ $selectedSiswa->kelas?->nama_kelas }}</strong></div>
                    <div class="text-xs text-slate-500">Tarif SPP: <strong class="text-emerald-700 text-sm">{{ $selectedSiswa->spp?->formattedNominal() }}</strong> / bulan</div>
                </div>
            </div>

            <!-- Transaction Form -->
            <form action="{{ route('pembayaran.store') }}" method="POST" class="pt-5 space-y-6">
                @csrf
                <input type="hidden" name="siswa_id" value="{{ $selectedSiswa->id }}">
                <input type="hidden" name="tahun_dibayar" value="{{ $selectedSiswa->spp?->tahun ?? date('Y') }}">

                <!-- Month Checklist Section -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700">
                            Pilih Bulan yang Akan Dibayar (Tahun Ajaran {{ $selectedSiswa->spp?->tahun_ajaran }}): <span class="text-rose-500">*</span>
                        </label>
                        <span class="text-xs text-slate-500">Centang 1 atau lebih bulan</span>
                    </div>

                    @if(empty($unpaidMonths))
                        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-medium">
                            Semua iuran SPP untuk tahun ajaran ini sudah lunas!
                        </div>
                    @else
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                            @foreach($unpaidMonths as $item)
                                <label class="p-3 rounded-xl border border-slate-200 hover:border-indigo-400 bg-slate-50 hover:bg-indigo-50/30 flex items-start gap-3 cursor-pointer transition select-none">
                                    <input type="checkbox" name="bulan_dibayar[]" value="{{ $item['bulan'] }}" data-nominal="{{ $item['nominal'] }}" class="month-checkbox mt-1 w-4 h-4 rounded text-indigo-600 border-slate-300 focus:ring-indigo-500" onchange="calculateTotal()">
                                    <div>
                                        <div class="font-bold text-xs text-slate-800">{{ $item['bulan'] }}</div>
                                        <div class="text-[11px] text-slate-500 font-medium">Rp {{ number_format($item['nominal'], 0, ',', '.') }}</div>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- Transaction Details Fields -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                    <div>
                        <label for="tgl_bayar" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1">
                            Tanggal Pembayaran <span class="text-rose-500">*</span>
                        </label>
                        <input type="date" id="tgl_bayar" name="tgl_bayar" value="{{ old('tgl_bayar', date('Y-m-d')) }}" required
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
                    </div>

                    <div>
                        <label for="metode_pembayaran" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1">
                            Metode Pembayaran <span class="text-rose-500">*</span>
                        </label>
                        <select id="metode_pembayaran" name="metode_pembayaran" required
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm bg-white">
                            <option value="Tunai" {{ old('metode_pembayaran') == 'Tunai' ? 'selected' : '' }}>Tunai / Kasir Langsung</option>
                            <option value="Transfer Bank" {{ old('metode_pembayaran') == 'Transfer Bank' ? 'selected' : '' }}>Transfer Bank</option>
                            <option value="QRIS" {{ old('metode_pembayaran') == 'QRIS' ? 'selected' : '' }}>QRIS / E-Wallet</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label for="catatan" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1">
                        Catatan Transaksi (Opsional)
                    </label>
                    <input type="text" id="catatan" name="catatan" value="{{ old('catatan') }}" placeholder="Catatan tambahan bila diperlukan"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
                </div>

                <!-- Total Amount Summary Box -->
                <div class="p-5 rounded-2xl bg-indigo-50/70 border border-indigo-200 flex items-center justify-between">
                    <div>
                        <div class="text-xs font-bold uppercase tracking-wider text-indigo-900">Total Nominal Pembayaran</div>
                        <div class="text-xs text-indigo-700 mt-0.5" id="month-count-label">0 bulan dipilih</div>
                    </div>
                    <div class="text-2xl font-black text-indigo-900" id="total-display">
                        Rp 0
                    </div>
                </div>

                <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-100">
                    <a href="{{ route('pembayaran.index') }}" class="px-4 py-2.5 rounded-xl text-slate-700 bg-slate-100 hover:bg-slate-200 text-xs font-semibold transition">
                        Batal
                    </a>
                    <button type="submit" id="submit-btn" class="px-6 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition shadow-sm">
                        Proses & Simpan Transaksi
                    </button>
                </div>
            </form>
        </div>
    @endif
</div>

<script>
    function calculateTotal() {
        const checkboxes = document.querySelectorAll('.month-checkbox:checked');
        let total = 0;
        let count = 0;

        checkboxes.forEach(cb => {
            total += parseInt(cb.getAttribute('data-nominal') || 0);
            count++;
        });

        document.getElementById('total-display').innerText = 'Rp ' + total.toLocaleString('id-ID');
        document.getElementById('month-count-label').innerText = count + ' bulan dipilih';
    }
</script>
@endsection

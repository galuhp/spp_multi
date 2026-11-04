@extends('layouts.app')

@section('title', 'Dashboard')
@section('header_title', 'Dashboard Utama')

@section('content')
<div class="space-y-6">
    <!-- Welcome Banner -->
    <div class="p-6 rounded-xl bg-zinc-900 text-white shadow-lg flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h3 class="text-xl font-bold tracking-tight">Selamat Datang, {{ auth()->user()->name }}!</h3>
            <p class="text-zinc-300 text-sm mt-1">Anda masuk sebagai <strong>{{ auth()->user()->role->label() }}</strong>. Kelola transaksi pembayaran dan data siswa dengan mudah.</p>
        </div>
        <div class="flex items-center gap-2">
            <x-ui.button as="a" href="{{ route('pembayaran.create') }}" variant="secondary" icon="plus" class="shrink-0">
                Entri Pembayaran SPP
            </x-ui.button>
        </div>
    </div>

    <!-- Statistics Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
        <!-- Penerimaan Hari Ini -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="flex items-center justify-between text-slate-500 mb-2">
                <span class="text-xs font-semibold uppercase tracking-wider">Penerimaan Hari Ini</span>
                <span class="p-2 rounded-xl bg-emerald-50 text-emerald-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <div class="text-xl sm:text-2xl font-bold text-slate-900">
                Rp {{ number_format($nominalHariIni, 0, ',', '.') }}
            </div>
            <div class="text-xs text-slate-500 mt-1">
                {{ $transaksiHariIni }} transaksi tercatat hari ini
            </div>
        </div>

        <!-- Penerimaan Bulan Ini -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="flex items-center justify-between text-slate-500 mb-2">
                <span class="text-xs font-semibold uppercase tracking-wider">Penerimaan Bulan Ini</span>
                <span class="p-2 rounded-xl bg-blue-50 text-blue-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                </span>
            </div>
            <div class="text-xl sm:text-2xl font-bold text-slate-900">
                Rp {{ number_format($nominalBulanIni, 0, ',', '.') }}
            </div>
            <div class="text-xs text-slate-500 mt-1">
                {{ $transaksiBulanIni }} transaksi bulan ini
            </div>
        </div>

        <!-- Total Siswa -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="flex items-center justify-between text-slate-500 mb-2">
                <span class="text-xs font-semibold uppercase tracking-wider">Total Siswa Terdaftar</span>
                <span class="p-2 rounded-xl bg-indigo-50 text-indigo-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                </span>
            </div>
            <div class="text-2xl font-bold text-slate-900">
                {{ $totalSiswa }}
            </div>
            <div class="text-xs text-slate-500 mt-1">
                Tersebar di {{ $totalKelas }} kelas
            </div>
        </div>

        <!-- Total Kelas -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="flex items-center justify-between text-slate-500 mb-2">
                <span class="text-xs font-semibold uppercase tracking-wider">Total Rombel Kelas</span>
                <span class="p-2 rounded-xl bg-purple-50 text-purple-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </span>
            </div>
            <div class="text-2xl font-bold text-slate-900">
                {{ $totalKelas }}
            </div>
            <div class="text-xs text-slate-500 mt-1">
                {{ $totalSpp }} paket tarif SPP aktif
            </div>
        </div>
    </div>

    <!-- Recent Transactions Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-slate-200 flex items-center justify-between">
            <div>
                <h3 class="font-bold text-slate-900 text-base">Transaksi Pembayaran SPP Terkini</h3>
                <p class="text-xs text-slate-500 mt-0.5">Daftar transaksi pembayaran yang baru saja dilakukan</p>
            </div>
            <a href="{{ route('pembayaran.index') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-700">
                Lihat Semua &rarr;
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50/75 text-slate-600 text-xs font-semibold uppercase tracking-wider">
                        <th class="py-3 px-4">No. Transaksi</th>
                        <th class="py-3 px-4">Siswa</th>
                        <th class="py-3 px-4">Kelas</th>
                        <th class="py-3 px-4">Bulan Dibayar</th>
                        <th class="py-3 px-4">Jumlah</th>
                        <th class="py-3 px-4">Tgl. Bayar</th>
                        <th class="py-3 px-4">Petugas</th>
                        <th class="py-3 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($transaksiTerbaru as $trx)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="py-3.5 px-4 font-mono font-medium text-xs text-indigo-700">
                                {{ $trx->kode_transaksi }}
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="font-semibold text-slate-900">{{ $trx->siswa?->nama ?? '-' }}</div>
                                <div class="text-xs text-slate-400 font-mono">NISN: {{ $trx->siswa?->nisn ?? '-' }}</div>
                            </td>
                            <td class="py-3.5 px-4 text-xs font-medium text-slate-600">
                                {{ $trx->siswa?->kelas?->nama_kelas ?? '-' }}
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                                    {{ $trx->bulan_dibayar }} {{ $trx->tahun_dibayar }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 font-bold text-slate-900">
                                {{ $trx->formattedJumlahBayar() }}
                            </td>
                            <td class="py-3.5 px-4 text-xs text-slate-500">
                                {{ $trx->tgl_bayar->format('d/m/Y') }}
                            </td>
                            <td class="py-3.5 px-4 text-xs text-slate-600">
                                {{ $trx->user?->name ?? '-' }}
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <a href="{{ route('pembayaran.show', $trx->id) }}" class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 transition">
                                    Rincian & Kuitansi
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 text-center text-slate-400 text-sm">
                                Belum ada transaksi pembayaran yang tercatat.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

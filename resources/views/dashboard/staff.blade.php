@extends('layouts.app')

@section('title', 'Dashboard')
@section('header_title', 'Dashboard Utama')

@section('content')
<div class="space-y-6">
    <!-- Welcome Banner -->
    <div class="p-6 sm:p-7 rounded-2xl prism-hero text-white flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-white/15 backdrop-blur-md border border-white/20 text-[11px] font-semibold tracking-wide mb-2.5">
                <span class="w-1.5 h-1.5 rounded-full bg-cyan-300"></span>
                Ikhtisar Keuangan Sekolah
            </span>
            <h3 class="text-xl sm:text-2xl font-bold tracking-tight">Selamat Datang, {{ auth()->user()->name }}!</h3>
            <p class="text-prism-100 text-sm mt-1">Anda masuk sebagai <strong>{{ auth()->user()->role->label() }}</strong>. Kelola transaksi pembayaran dan data siswa dengan mudah.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('pembayaran.create') }}" class="px-4 py-2.5 rounded-xl bg-white text-prism-700 hover:bg-prism-50 font-semibold text-sm transition shadow-md shrink-0">
                + Entri Pembayaran SPP
            </a>
        </div>
    </div>

    <!-- Statistics Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
        <!-- Penerimaan Hari Ini -->
        <div class="prism-card p-5 relative overflow-hidden">
            <div class="h-0.5 inset-x-0 top-0 absolute bg-linear-to-r from-emerald-400 to-teal-500"></div>
            <div class="flex items-center justify-between text-slate-500 dark:text-slate-400 mb-2">
                <span class="text-xs font-semibold uppercase tracking-wider">Penerimaan Hari Ini</span>
                <span class="p-2 rounded-xl bg-emerald-50 dark:bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-200/60 dark:border-emerald-400/20">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <div class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white">
                Rp {{ number_format($nominalHariIni, 0, ',', '.') }}
            </div>
            <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                {{ $transaksiHariIni }} transaksi tercatat hari ini
            </div>
        </div>

        <!-- Penerimaan Bulan Ini -->
        <div class="prism-card p-5 relative overflow-hidden">
            <div class="h-0.5 inset-x-0 top-0 absolute bg-linear-to-r from-sky-400 to-prism-500"></div>
            <div class="flex items-center justify-between text-slate-500 dark:text-slate-400 mb-2">
                <span class="text-xs font-semibold uppercase tracking-wider">Penerimaan Bulan Ini</span>
                <span class="p-2 rounded-xl bg-sky-50 dark:bg-sky-500/15 text-sky-600 dark:text-sky-400 border border-sky-200/60 dark:border-sky-400/20">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                </span>
            </div>
            <div class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white">
                Rp {{ number_format($nominalBulanIni, 0, ',', '.') }}
            </div>
            <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                {{ $transaksiBulanIni }} transaksi bulan ini
            </div>
        </div>

        <!-- Total Siswa -->
        <div class="prism-card p-5 relative overflow-hidden">
            <div class="h-0.5 inset-x-0 top-0 absolute bg-linear-to-r from-prism-500 to-violet-500"></div>
            <div class="flex items-center justify-between text-slate-500 dark:text-slate-400 mb-2">
                <span class="text-xs font-semibold uppercase tracking-wider">Total Siswa Terdaftar</span>
                <span class="p-2 rounded-xl bg-prism-50 dark:bg-prism-500/15 text-prism-600 dark:text-prism-300 border border-prism-200/60 dark:border-prism-400/20">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                </span>
            </div>
            <div class="text-2xl font-bold text-slate-900 dark:text-white">
                {{ $totalSiswa }}
            </div>
            <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Tersebar di {{ $totalKelas }} kelas
            </div>
        </div>

        <!-- Total Kelas -->
        <div class="prism-card p-5 relative overflow-hidden">
            <div class="h-0.5 inset-x-0 top-0 absolute bg-linear-to-r from-violet-500 to-fuchsia-500"></div>
            <div class="flex items-center justify-between text-slate-500 dark:text-slate-400 mb-2">
                <span class="text-xs font-semibold uppercase tracking-wider">Total Rombel Kelas</span>
                <span class="p-2 rounded-xl bg-fuchsia-50 dark:bg-fuchsia-500/15 text-fuchsia-600 dark:text-fuchsia-300 border border-fuchsia-200/60 dark:border-fuchsia-400/20">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </span>
            </div>
            <div class="text-2xl font-bold text-slate-900 dark:text-white">
                {{ $totalKelas }}
            </div>
            <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                {{ $totalSpp }} paket tarif SPP aktif
            </div>
        </div>
    </div>

    <!-- Recent Transactions Table -->
    <div class="prism-card overflow-hidden">
        <div class="p-5 border-b border-slate-200/80 dark:border-white/10 flex items-center justify-between">
            <div>
                <h3 class="font-bold text-slate-900 dark:text-white text-base">Transaksi Pembayaran SPP Terkini</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Daftar transaksi pembayaran yang baru saja dilakukan</p>
            </div>
            <a href="{{ route('pembayaran.index') }}" class="text-xs font-semibold text-prism-600 dark:text-prism-300 hover:text-prism-700 dark:hover:text-prism-200">
                Lihat Semua &rarr;
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="prism-thead">
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
                <tbody class="prism-tbody">
                    @forelse($transaksiTerbaru as $trx)
                        <tr class="prism-tr">
                            <td class="py-3.5 px-4 font-mono font-medium text-xs text-prism-700 dark:text-prism-300">
                                {{ $trx->kode_transaksi }}
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="font-semibold text-slate-900 dark:text-slate-100">{{ $trx->siswa?->nama ?? '-' }}</div>
                                <div class="text-xs text-slate-400 dark:text-slate-500 font-mono">NISN: {{ $trx->siswa?->nisn ?? '-' }}</div>
                            </td>
                            <td class="py-3.5 px-4 text-xs font-medium text-slate-600 dark:text-slate-300">
                                {{ $trx->siswa?->kelas?->nama_kelas ?? '-' }}
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-sky-50 dark:bg-sky-500/15 text-sky-700 dark:text-sky-300 border border-sky-200/80 dark:border-sky-400/25">
                                    {{ $trx->bulan_dibayar }} {{ $trx->tahun_dibayar }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 font-bold text-slate-900 dark:text-white">
                                {{ $trx->formattedJumlahBayar() }}
                            </td>
                            <td class="py-3.5 px-4 text-xs text-slate-500 dark:text-slate-400">
                                {{ $trx->tgl_bayar->format('d/m/Y') }}
                            </td>
                            <td class="py-3.5 px-4 text-xs text-slate-600 dark:text-slate-300">
                                {{ $trx->user?->name ?? '-' }}
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <a href="{{ route('pembayaran.show', $trx->id) }}" class="prism-btn-secondary px-2.5 py-1 rounded-lg text-xs">
                                    Rincian & Kuitansi
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 text-center text-slate-400 dark:text-slate-500 text-sm">
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

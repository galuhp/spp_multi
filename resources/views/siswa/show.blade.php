@extends('layouts.app')

@section('title', 'Detail Siswa - ' . $siswa->nama)
@section('header_title', 'Profil & Riwayat SPP Siswa')

@section('content')
<div class="space-y-6">
    <!-- Top Action / Back Bar -->
    <div class="flex items-center justify-between">
        <a href="{{ route('siswa.index') }}" class="text-xs font-semibold text-slate-600 dark:text-slate-300 hover:text-prism-600 dark:hover:text-prism-300 inline-flex items-center gap-1.5">
            &larr; Kembali ke Daftar Siswa
        </a>
        <div class="flex items-center gap-2">
            <a href="{{ route('pembayaran.create', ['siswa_id' => $siswa->id]) }}" class="px-4 py-2 prism-btn-emerald text-xs">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Entri Pembayaran Siswa Ini
            </a>
            <a href="{{ route('siswa.edit', $siswa->id) }}" class="px-3.5 py-2 prism-btn-secondary text-xs">
                Ubah Profil
            </a>
        </div>
    </div>

    <!-- Student Profile Overview Card -->
    <div class="prism-card p-6 flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="flex items-center gap-4">
            <div class="w-16 h-16 rounded-2xl bg-linear-to-tr from-prism-600 via-violet-500 to-cyan-500 text-white flex items-center justify-center font-black text-2xl shadow-md shrink-0">
                {{ strtoupper(substr($siswa->nama, 0, 2)) }}
            </div>
            <div>
                <h3 class="text-xl font-bold text-slate-900 dark:text-white">{{ $siswa->nama }}</h3>
                <div class="text-xs text-slate-500 dark:text-slate-400 mt-1 flex flex-wrap gap-x-4 gap-y-1">
                    <span>NISN: <strong class="font-mono text-slate-700 dark:text-slate-200">{{ $siswa->nisn }}</strong></span>
                    <span>NIS: <strong class="font-mono text-slate-700 dark:text-slate-200">{{ $siswa->nis }}</strong></span>
                    <span>Jenis Kelamin: <strong class="text-slate-700 dark:text-slate-200">{{ $siswa->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }}</strong></span>
                </div>
                <div class="text-xs text-slate-500 dark:text-slate-400 mt-1 flex flex-wrap gap-x-4 gap-y-1">
                    <span>Kelas: <strong class="text-slate-700 dark:text-slate-200">{{ $siswa->kelas?->nama_kelas }}</strong></span>
                    <span>Jurusan: <strong class="text-slate-700 dark:text-slate-200">{{ $siswa->kelas?->jurusan?->nama }}</strong></span>
                    <span>No. Telp: <strong class="text-slate-700 dark:text-slate-200">{{ $siswa->no_telp ?? '-' }}</strong></span>
                </div>
            </div>
        </div>

        <div class="border-t md:border-t-0 md:border-l border-slate-100 dark:border-white/10 pt-4 md:pt-0 md:pl-6 shrink-0">
            <div class="text-xs text-slate-500 dark:text-slate-400 font-semibold uppercase">Besaran Tarif SPP</div>
            <div class="text-2xl font-bold text-prism-700 dark:text-prism-300">{{ $siswa->spp?->formattedNominal() ?? '-' }}</div>
            <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Tahun Ajaran {{ $siswa->spp?->tahun_ajaran }}</div>
            <div class="mt-2 text-xs font-semibold text-emerald-700 dark:text-emerald-400">
                Total Dibayar: Rp {{ number_format($siswa->totalDibayar(), 0, ',', '.') }}
            </div>
        </div>
    </div>

    <!-- Monthly Payment Status Matrix -->
    <div class="prism-card p-6">
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h4 class="font-bold text-slate-900 dark:text-white text-base">Matriks Status Pembayaran Bulanan (TA {{ $siswa->spp?->tahun_ajaran }})</h4>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Pantauan status pembayaran SPP per bulan untuk tahun ajaran yang sedang aktif</p>
            </div>
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Tahun Buku: {{ $activeYear }}</span>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
            @foreach($statusBulan as $item)
                <div class="p-3.5 rounded-xl border {{ $item['lunas'] ? 'bg-emerald-50/70 dark:bg-emerald-500/10 border-emerald-200/80 dark:border-emerald-400/25' : 'bg-slate-50/70 dark:bg-slate-800/40 border-slate-200/80 dark:border-white/10' }} flex flex-col justify-between">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-xs font-bold text-slate-800 dark:text-slate-100">{{ $item['bulan'] }}</span>
                        @if($item['lunas'])
                            <span class="w-5 h-5 rounded-full bg-emerald-500 text-white flex items-center justify-center text-[10px]">
                                ✓
                            </span>
                        @else
                            <span class="w-5 h-5 rounded-full bg-slate-300 dark:bg-slate-700 text-slate-600 dark:text-slate-300 flex items-center justify-center text-[10px]">
                                -
                            </span>
                        @endif
                    </div>

                    <div class="mt-2">
                        @if($item['lunas'])
                            <span class="inline-block text-[11px] font-bold text-emerald-700 dark:text-emerald-300">
                                LUNAS
                            </span>
                            <div class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">
                                {{ $item['pembayaran']->tgl_bayar->format('d/m/Y') }}
                            </div>
                            <a href="{{ route('pembayaran.show', $item['pembayaran']->id) }}" class="mt-2 inline-block text-[10px] font-semibold text-prism-600 dark:text-prism-300 hover:underline">
                                Kuitansi &rarr;
                            </a>
                        @else
                            <span class="inline-block text-[11px] font-bold text-slate-500 dark:text-slate-400">
                                BELUM BAYAR
                            </span>
                            <div class="text-[10px] text-slate-400 dark:text-slate-500 mt-0.5">
                                {{ $siswa->spp?->formattedNominal() }}
                            </div>
                            <a href="{{ route('pembayaran.create', ['siswa_id' => $siswa->id]) }}" class="mt-2 inline-block text-[10px] font-semibold text-emerald-600 dark:text-emerald-400 hover:underline">
                                + Bayar
                            </a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Payment Transaction History -->
    <div class="prism-card overflow-hidden">
        <div class="p-5 border-b border-slate-200/80 dark:border-white/10">
            <h4 class="font-bold text-slate-900 dark:text-white text-base">Riwayat Transaksi Pembayaran Siswa</h4>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Daftar kuitansi pembayaran SPP yang pernah dilakukan oleh siswa ini</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="prism-thead">
                        <th class="py-3 px-4">No. Kuitansi</th>
                        <th class="py-3 px-4">Tgl. Bayar</th>
                        <th class="py-3 px-4">Bulan Dibayar</th>
                        <th class="py-3 px-4">Jumlah Bayar</th>
                        <th class="py-3 px-4">Metode</th>
                        <th class="py-3 px-4">Petugas</th>
                        <th class="py-3 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="prism-tbody">
                    @forelse($riwayatPembayaran as $trx)
                        <tr class="prism-tr">
                            <td class="py-3.5 px-4 font-mono font-medium text-xs text-prism-700 dark:text-prism-300">{{ $trx->kode_transaksi }}</td>
                            <td class="py-3.5 px-4 text-xs">{{ $trx->tgl_bayar->format('d/m/Y') }}</td>
                            <td class="py-3.5 px-4 font-semibold text-slate-900 dark:text-slate-100">{{ $trx->bulan_dibayar }} {{ $trx->tahun_dibayar }}</td>
                            <td class="py-3.5 px-4 font-bold text-emerald-700 dark:text-emerald-400">{{ $trx->formattedJumlahBayar() }}</td>
                            <td class="py-3.5 px-4 text-xs">{{ $trx->metode_pembayaran }}</td>
                            <td class="py-3.5 px-4 text-xs text-slate-600 dark:text-slate-300">{{ $trx->user?->name ?? '-' }}</td>
                            <td class="py-3.5 px-4 text-center">
                                <a href="{{ route('pembayaran.show', $trx->id) }}" class="prism-btn-secondary px-2.5 py-1 rounded-lg text-xs">
                                    Lihat Kuitansi
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400 dark:text-slate-500 text-sm">
                                Siswa ini belum memiliki riwayat transaksi pembayaran SPP.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

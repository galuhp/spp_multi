@extends('layouts.app')

@section('title', 'Detail Siswa - ' . $siswa->nama)
@section('header_title', 'Profil & Riwayat SPP Siswa')

@section('content')
<div class="space-y-6">
    <!-- Top Action / Back Bar -->
    <div class="flex items-center justify-between">
        <a href="{{ route('siswa.index') }}" class="text-xs font-semibold text-slate-600 hover:text-slate-900 inline-flex items-center gap-1.5">
            &larr; Kembali ke Daftar Siswa
        </a>
        <div class="flex items-center gap-2">
            <a href="{{ route('pembayaran.create', ['siswa_id' => $siswa->id]) }}" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs transition shadow-sm inline-flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Entri Pembayaran Siswa Ini
            </a>
            <a href="{{ route('siswa.edit', $siswa->id) }}" class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs transition">
                Ubah Profil
            </a>
        </div>
    </div>

    <!-- Student Profile Overview Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="flex items-center gap-4">
            <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-indigo-600 to-blue-500 text-white flex items-center justify-center font-black text-2xl shadow-md">
                {{ strtoupper(substr($siswa->nama, 0, 2)) }}
            </div>
            <div>
                <h3 class="text-xl font-bold text-slate-900">{{ $siswa->nama }}</h3>
                <div class="text-xs text-slate-500 mt-1 flex flex-wrap gap-x-4 gap-y-1">
                    <span>NISN: <strong class="font-mono text-slate-700">{{ $siswa->nisn }}</strong></span>
                    <span>NIS: <strong class="font-mono text-slate-700">{{ $siswa->nis }}</strong></span>
                    <span>Jenis Kelamin: <strong class="text-slate-700">{{ $siswa->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }}</strong></span>
                </div>
                <div class="text-xs text-slate-500 mt-1 flex flex-wrap gap-x-4 gap-y-1">
                    <span>Kelas: <strong class="text-slate-700">{{ $siswa->kelas?->nama_kelas }}</strong></span>
                    <span>Jurusan: <strong class="text-slate-700">{{ $siswa->kelas?->jurusan?->nama }}</strong></span>
                    <span>No. Telp: <strong class="text-slate-700">{{ $siswa->no_telp ?? '-' }}</strong></span>
                </div>
            </div>
        </div>

        <div class="border-t md:border-t-0 md:border-l border-slate-100 pt-4 md:pt-0 md:pl-6 shrink-0">
            <div class="text-xs text-slate-500 font-semibold uppercase">Besaran Tarif SPP</div>
            <div class="text-2xl font-bold text-indigo-700">{{ $siswa->spp?->formattedNominal() ?? '-' }}</div>
            <div class="text-xs text-slate-500 mt-0.5">Tahun Ajaran {{ $siswa->spp?->tahun_ajaran }}</div>
            <div class="mt-2 text-xs font-semibold text-emerald-700">
                Total Dibayar: Rp {{ number_format($siswa->totalDibayar(), 0, ',', '.') }}
            </div>
        </div>
    </div>

    <!-- Monthly Payment Status Matrix -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h4 class="font-bold text-slate-900 text-base">Matriks Status Pembayaran Bulanan (TA {{ $siswa->spp?->tahun_ajaran }})</h4>
                <p class="text-xs text-slate-500 mt-0.5">Pantauan status pembayaran SPP per bulan untuk tahun ajaran yang sedang aktif</p>
            </div>
            <span class="text-xs font-semibold text-slate-500">Tahun Buku: {{ $activeYear }}</span>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
            @foreach($statusBulan as $item)
                <div class="p-3.5 rounded-xl border {{ $item['lunas'] ? 'bg-emerald-50/70 border-emerald-200' : 'bg-slate-50 border-slate-200' }} flex flex-col justify-between">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-xs font-bold text-slate-800">{{ $item['bulan'] }}</span>
                        @if($item['lunas'])
                            <span class="w-5 h-5 rounded-full bg-emerald-500 text-white flex items-center justify-center text-[10px]">
                                ✓
                            </span>
                        @else
                            <span class="w-5 h-5 rounded-full bg-slate-300 text-slate-600 flex items-center justify-center text-[10px]">
                                -
                            </span>
                        @endif
                    </div>

                    <div class="mt-2">
                        @if($item['lunas'])
                            <span class="inline-block text-[11px] font-bold text-emerald-700">
                                LUNAS
                            </span>
                            <div class="text-[10px] text-slate-500 mt-0.5">
                                {{ $item['pembayaran']->tgl_bayar->format('d/m/Y') }}
                            </div>
                            <a href="{{ route('pembayaran.show', $item['pembayaran']->id) }}" class="mt-2 inline-block text-[10px] font-semibold text-indigo-600 hover:text-indigo-800">
                                Kuitansi &rarr;
                            </a>
                        @else
                            <span class="inline-block text-[11px] font-bold text-slate-500">
                                BELUM BAYAR
                            </span>
                            <div class="text-[10px] text-slate-400 mt-0.5">
                                {{ $siswa->spp?->formattedNominal() }}
                            </div>
                            <a href="{{ route('pembayaran.create', ['siswa_id' => $siswa->id]) }}" class="mt-2 inline-block text-[10px] font-semibold text-emerald-600 hover:text-emerald-800">
                                + Bayar
                            </a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Payment Transaction History -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-slate-200">
            <h4 class="font-bold text-slate-900 text-base">Riwayat Transaksi Pembayaran Siswa</h4>
            <p class="text-xs text-slate-500 mt-0.5">Daftar kuitansi pembayaran SPP yang pernah dilakukan oleh siswa ini</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-slate-600 text-xs font-semibold uppercase">
                        <th class="py-3 px-4">No. Kuitansi</th>
                        <th class="py-3 px-4">Tgl. Bayar</th>
                        <th class="py-3 px-4">Bulan Dibayar</th>
                        <th class="py-3 px-4">Jumlah Bayar</th>
                        <th class="py-3 px-4">Metode</th>
                        <th class="py-3 px-4">Petugas</th>
                        <th class="py-3 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($riwayatPembayaran as $trx)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="py-3.5 px-4 font-mono font-medium text-xs text-indigo-700">{{ $trx->kode_transaksi }}</td>
                            <td class="py-3.5 px-4 text-xs">{{ $trx->tgl_bayar->format('d/m/Y') }}</td>
                            <td class="py-3.5 px-4 font-semibold text-slate-900">{{ $trx->bulan_dibayar }} {{ $trx->tahun_dibayar }}</td>
                            <td class="py-3.5 px-4 font-bold text-emerald-700">{{ $trx->formattedJumlahBayar() }}</td>
                            <td class="py-3.5 px-4 text-xs">{{ $trx->metode_pembayaran }}</td>
                            <td class="py-3.5 px-4 text-xs text-slate-600">{{ $trx->user?->name ?? '-' }}</td>
                            <td class="py-3.5 px-4 text-center">
                                <a href="{{ route('pembayaran.show', $trx->id) }}" class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 transition">
                                    Lihat Kuitansi
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400 text-sm">
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

@extends('layouts.app')

@section('title', 'Detail Siswa - ' . $siswa->nama)
@section('header_title', 'Profil & Riwayat SPP Siswa')

@section('content')
<div class="space-y-6">
    <!-- Top Action / Back Bar -->
    <div class="flex items-center justify-between">
        <a href="{{ route('siswa.index') }}" class="text-xs font-semibold text-zinc-600 hover:text-zinc-900 inline-flex items-center gap-1.5">
            &larr; Kembali ke Daftar Siswa
        </a>
        <div class="flex items-center gap-2">
            <a href="{{ route('pembayaran.create', ['siswa_id' => $siswa->id]) }}" class="px-4 py-2 rounded-xl bg-zinc-900 hover:bg-zinc-800 text-white font-semibold text-xs transition shadow-sm inline-flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Entri Pembayaran Siswa Ini
            </a>
            <a href="{{ route('siswa.edit', $siswa->id) }}" class="px-3.5 py-2 rounded-xl border border-zinc-300 hover:bg-zinc-50 text-zinc-700 font-semibold text-xs transition">
                Ubah Profil
            </a>
        </div>
    </div>

    <!-- Student Profile Overview Card -->
    <div class="bg-white rounded-xl border border-zinc-200 shadow-xs p-6 flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="flex items-center gap-4">
            <div class="w-16 h-16 rounded-xl bg-gradient-to-tr from-indigo-600 to-blue-500 text-white flex items-center justify-center font-black text-2xl shadow-md shrink-0">
                {{ strtoupper(substr($siswa->nama, 0, 2)) }}
            </div>
            <div>
                <h3 class="text-xl font-bold text-zinc-900">{{ $siswa->nama }}</h3>
                <div class="text-xs text-zinc-500 mt-1 flex flex-wrap gap-x-4 gap-y-1">
                    <span>NISN: <strong class="font-mono text-zinc-700">{{ $siswa->nisn }}</strong></span>
                    <span>NIS: <strong class="font-mono text-zinc-700">{{ $siswa->nis }}</strong></span>
                    <span>Jenis Kelamin: <strong class="text-zinc-700">{{ $siswa->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }}</strong></span>
                </div>
                <div class="text-xs text-zinc-500 mt-1 flex flex-wrap gap-x-4 gap-y-1">
                    <span>Kelas: <strong class="text-zinc-700">{{ $siswa->kelas?->nama_kelas }}</strong></span>
                    <span>Jurusan: <strong class="text-zinc-700">{{ $siswa->kelas?->jurusan?->nama }}</strong></span>
                    <span>No. Telp: <strong class="text-zinc-700">{{ $siswa->no_telp ?? '-' }}</strong></span>
                </div>
            </div>
        </div>

        <div class="border-t md:border-t-0 md:border-l border-zinc-200 pt-4 md:pt-0 md:pl-6 shrink-0">
            <div class="text-xs text-zinc-500 font-semibold uppercase">Besaran Tarif SPP</div>
            <div class="text-2xl font-bold text-zinc-900">{{ $siswa->spp?->formattedNominal() ?? '-' }}</div>
            <div class="text-xs text-zinc-500 mt-0.5">Tahun Ajaran {{ $siswa->spp?->tahun_ajaran }}</div>
            <div class="mt-2 text-xs font-semibold text-emerald-700">
                Total Dibayar: Rp {{ number_format($siswa->totalDibayar(), 0, ',', '.') }}
            </div>
        </div>
    </div>

    <!-- Non-SPP Fees Cards (Uang Gedung, Seragam, dll.) -->
    @if(!empty($nonSppTarifs) && $nonSppTarifs->isNotEmpty())
        <div class="bg-white rounded-xl border border-zinc-200 shadow-xs p-6">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h4 class="font-bold text-zinc-900 text-base">Status Biaya Lainnya (Uang Gedung, Seragam, dll.)</h4>
                    <p class="text-xs text-zinc-500 mt-0.5">Pantauan status pembayaran pos di luar iuran bulanan untuk angkatan/tahun {{ $siswa->spp?->tahun_ajaran }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                @foreach($nonSppTarifs as $item)
                    @php
                        $tarif = $item['tarif'];
                        $isLunas = $item['lunas'];
                    @endphp
                    <div class="p-4 rounded-xl border flex flex-col justify-between
                        {{ $isLunas ? 'bg-emerald-50/70 border-emerald-200' : 'bg-zinc-50/70 border-zinc-200' }}">
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold border {{ $tarif->badgeClass() }}">
                                    {{ $tarif->kategori }}
                                </span>
                                @if($isLunas)
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-zinc-900 text-white">
                                        LUNAS
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-500 text-white">
                                        BELUM LUNAS
                                    </span>
                                @endif
                            </div>

                            <div class="font-bold text-sm text-zinc-900 mt-1">
                                {{ $tarif->nama ?: $tarif->kategori }}
                            </div>
                            <div class="text-xs text-zinc-500 mt-2 space-y-1">
                                <div class="flex justify-between">
                                    <span>Total Biaya:</span>
                                    <strong class="text-zinc-800">Rp {{ number_format($item['nominal'], 0, ',', '.') }}</strong>
                                </div>
                                <div class="flex justify-between">
                                    <span>Sudah Dibayar:</span>
                                    <strong class="text-emerald-700">Rp {{ number_format($item['dibayar'], 0, ',', '.') }}</strong>
                                </div>
                                <div class="flex justify-between pt-1 border-t border-zinc-200 font-bold">
                                    <span>Sisa:</span>
                                    <span class="{{ $item['sisa'] > 0 ? 'text-rose-600' : 'text-zinc-400' }}">Rp {{ number_format($item['sisa'], 0, ',', '.') }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 pt-2">
                            <a href="{{ route('pembayaran.create', ['siswa_id' => $siswa->id]) }}" class="block text-center px-3 py-1.5 rounded-lg text-xs font-semibold {{ $isLunas ? 'bg-zinc-100 text-zinc-500' : 'bg-zinc-900 hover:bg-zinc-800 text-white' }}">
                                {{ $isLunas ? 'Lihat / Entri Lagi' : '+ Bayar ' . $tarif->kategori }}
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Monthly Payment Status Matrix -->
    <div class="bg-white rounded-xl border border-zinc-200 shadow-xs p-6">
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h4 class="font-bold text-zinc-900 text-base">Matriks Status Pembayaran Bulanan (TA {{ $siswa->spp?->tahun_ajaran }})</h4>
                <p class="text-xs text-zinc-500 mt-0.5">Pantauan status pembayaran SPP per bulan untuk tahun ajaran yang sedang aktif</p>
            </div>
            <span class="text-xs font-semibold text-zinc-500">Tahun Buku: {{ $activeYear }}</span>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
            @foreach($statusBulan as $item)
                <div class="p-3.5 rounded-xl border {{ $item['lunas'] ? 'bg-emerald-50/70 border-emerald-200' : 'bg-zinc-50/70 border-zinc-200' }} flex flex-col justify-between">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-xs font-bold text-zinc-800">{{ $item['bulan'] }}</span>
                        @if($item['lunas'])
                            <span class="w-5 h-5 rounded-full bg-emerald-500 text-white flex items-center justify-center text-[10px]">
                                ✓
                            </span>
                        @else
                            <span class="w-5 h-5 rounded-full bg-slate-300 text-zinc-600 flex items-center justify-center text-[10px]">
                                -
                            </span>
                        @endif
                    </div>

                    <div class="mt-2">
                        @if($item['lunas'])
                            <span class="inline-block text-[11px] font-bold text-emerald-700">
                                LUNAS
                            </span>
                            <div class="text-[10px] text-zinc-500 mt-0.5">
                                {{ $item['pembayaran']->tgl_bayar->format('d/m/Y') }}
                            </div>
                            <a href="{{ route('pembayaran.show', $item['pembayaran']->id) }}" class="mt-2 inline-block text-[10px] font-semibold text-zinc-900 hover:underline">
                                Kuitansi &rarr;
                            </a>
                        @else
                            <span class="inline-block text-[11px] font-bold text-zinc-500">
                                BELUM BAYAR
                            </span>
                            <div class="text-[10px] text-zinc-400 mt-0.5">
                                {{ $siswa->spp?->formattedNominal() }}
                            </div>
                            <a href="{{ route('pembayaran.create', ['siswa_id' => $siswa->id]) }}" class="mt-2 inline-block text-[10px] font-semibold text-emerald-600 hover:underline">
                                + Bayar
                            </a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Payment Transaction History -->
    <div class="bg-white rounded-xl border border-zinc-200 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-zinc-200">
            <h4 class="font-bold text-zinc-900 text-base">Riwayat Transaksi Pembayaran Siswa</h4>
            <p class="text-xs text-zinc-500 mt-0.5">Daftar kuitansi pembayaran SPP yang pernah dilakukan oleh siswa ini</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-zinc-50 border-b border-zinc-200 text-zinc-700 font-semibold text-xs uppercase tracking-wider">
                        <th class="py-3 px-4">No. Kuitansi</th>
                        <th class="py-3 px-4">Tgl. Bayar</th>
                        <th class="py-3 px-4">Pos Pembayaran</th>
                        <th class="py-3 px-4">Jumlah Bayar</th>
                        <th class="py-3 px-4">Metode</th>
                        <th class="py-3 px-4">Petugas</th>
                        <th class="py-3 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 text-zinc-700">
                    @forelse($riwayatPembayaran as $trx)
                        <tr class="hover:bg-zinc-50 transition">
                            <td class="py-3.5 px-4 font-mono font-medium text-xs text-zinc-900">{{ $trx->kode_transaksi }}</td>
                            <td class="py-3.5 px-4 text-xs">{{ $trx->tgl_bayar->format('d/m/Y') }}</td>
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold border {{ $trx->spp?->badgeClass() }}">
                                        {{ $trx->spp?->kategori ?? 'SPP' }}
                                    </span>
                                    <span class="text-xs font-semibold text-zinc-900">{{ $trx->namaPos() }}</span>
                                </div>
                            </td>
                            <td class="py-3.5 px-4 font-bold text-emerald-700">{{ $trx->formattedJumlahBayar() }}</td>
                            <td class="py-3.5 px-4 text-xs">{{ $trx->metode_pembayaran }}</td>
                            <td class="py-3.5 px-4 text-xs text-zinc-600">{{ $trx->user?->name ?? '-' }}</td>
                            <td class="py-3.5 px-4 text-center">
                                <a href="{{ route('pembayaran.show', $trx->id) }}" class="px-2.5 py-1 rounded-lg border border-zinc-300 hover:bg-zinc-50 text-zinc-700 font-semibold text-xs transition">
                                    Lihat Kuitansi
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-zinc-400 text-sm">
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

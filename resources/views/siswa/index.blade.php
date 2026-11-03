@extends('layouts.app')

@section('title', 'Data Siswa')
@section('header_title', 'Data Induk Siswa')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h3 class="text-xl font-bold text-slate-900 dark:text-white">Daftar Siswa</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Kelola data siswa, penempatan rombel kelas, dan penetapan tarif SPP</p>
        </div>
        <a href="{{ route('siswa.create') }}" class="px-4 py-2.5 prism-btn-primary text-sm self-start">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
            Tambah Siswa Baru
        </a>
    </div>

    <!-- Search & Filter Card -->
    <div class="prism-card p-4">
        <form action="{{ route('siswa.index') }}" method="GET" class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1 relative">
                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 dark:text-slate-500 pointer-events-none">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </span>
                <input type="text" name="search" value="{{ $search }}" placeholder="Cari berdasarkan Nama, NISN, atau NIS..."
                    class="w-full pl-10 pr-3.5 py-2 prism-input text-sm">
            </div>

            <div class="sm:w-60">
                <select name="kelas_id" class="w-full px-3.5 py-2 prism-input text-sm">
                    <option value="">-- Semua Kelas --</option>
                    @foreach($kelasList as $k)
                        <option value="{{ $k->id }}" {{ $kelasId == $k->id ? 'selected' : '' }}>
                            {{ $k->nama_kelas }}
                        </option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="px-4 py-2 prism-btn-primary text-xs shrink-0">
                Filter Data
            </button>

            @if($search || $kelasId)
                <a href="{{ route('siswa.index') }}" class="px-3 py-2 prism-btn-secondary text-xs shrink-0">
                    Reset
                </a>
            @endif
        </form>
    </div>

    <!-- Siswa Table -->
    <div class="prism-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="prism-thead">
                        <th class="py-3 px-4 w-14 text-center">No</th>
                        <th class="py-3 px-4">Nama Siswa</th>
                        <th class="py-3 px-4">NISN / NIS</th>
                        <th class="py-3 px-4">Kelas & Jurusan</th>
                        <th class="py-3 px-4">Tarif SPP</th>
                        <th class="py-3 px-4">No. Telp</th>
                        <th class="py-3 px-4 text-center w-44">Aksi</th>
                    </tr>
                </thead>
                <tbody class="prism-tbody">
                    @forelse($siswas as $index => $item)
                        <tr class="prism-tr">
                            <td class="py-3.5 px-4 text-center text-xs text-slate-400 dark:text-slate-500 font-medium">
                                {{ $siswas->firstItem() + $index }}
                            </td>
                            <td class="py-3.5 px-4">
                                <a href="{{ route('siswa.show', $item->id) }}" class="font-bold text-slate-900 dark:text-white hover:text-prism-600 dark:hover:text-prism-300 transition block">
                                    {{ $item->nama }}
                                </a>
                                <span class="inline-block text-[11px] text-slate-400 dark:text-slate-500 font-medium">
                                    {{ $item->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 font-mono text-xs">
                                <div class="font-bold text-prism-700 dark:text-prism-300">NISN: {{ $item->nisn }}</div>
                                <div class="text-slate-500 dark:text-slate-400">NIS: {{ $item->nis }}</div>
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="font-semibold text-slate-800 dark:text-slate-200">{{ $item->kelas?->nama_kelas ?? '-' }}</div>
                                <div class="text-xs text-slate-400 dark:text-slate-500">{{ $item->kelas?->jurusan?->nama ?? '-' }}</div>
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-emerald-700 dark:text-emerald-400">{{ $item->spp?->formattedNominal() ?? '-' }}</div>
                                <div class="text-xs text-slate-400 dark:text-slate-500">TA {{ $item->spp?->tahun_ajaran }}</div>
                            </td>
                            <td class="py-3.5 px-4 text-xs text-slate-600 dark:text-slate-400">
                                {{ $item->no_telp ?? '-' }}
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <a href="{{ route('pembayaran.create', ['siswa_id' => $item->id]) }}" class="px-2.5 py-1 bg-emerald-50 dark:bg-emerald-500/15 hover:bg-emerald-100 dark:hover:bg-emerald-500/25 text-emerald-700 dark:text-emerald-300 border border-emerald-200/80 dark:border-emerald-400/25 rounded-lg text-xs font-semibold transition" title="Bayar SPP">
                                        Bayar
                                    </a>
                                    <a href="{{ route('siswa.show', $item->id) }}" class="p-1.5 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-white/10 rounded-lg transition" title="Lihat Profil & Tagihan">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </a>
                                    <a href="{{ route('siswa.edit', $item->id) }}" class="p-1.5 text-prism-600 dark:text-prism-300 hover:bg-prism-50 dark:hover:bg-prism-500/15 rounded-lg transition" title="Ubah Data">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </a>
                                    @if(auth()->user()->isAdmin())
                                        <form action="{{ route('siswa.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data siswa ini?');" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1.5 text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-500/15 rounded-lg transition cursor-pointer" title="Hapus Data">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400 dark:text-slate-500 text-sm">
                                Tidak ada data siswa yang sesuai dengan kriteria pencarian.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($siswas->hasPages())
            <div class="p-4 border-t border-slate-100 dark:border-white/10">
                {{ $siswas->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

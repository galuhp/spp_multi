@extends('layouts.app')

@section('title', 'Data Siswa')
@section('header_title', 'Data Induk Siswa')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h3 class="text-xl font-bold text-slate-900">Daftar Siswa</h3>
            <p class="text-xs text-slate-500 mt-1">Kelola data siswa, penempatan rombel kelas, dan penetapan tarif SPP</p>
        </div>
        <x-ui.button as="a" href="{{ route('siswa.create') }}" variant="primary" icon="user-plus" class="self-start">
            Tambah Siswa Baru
        </x-ui.button>
    </div>

    <!-- Search & Filter Card -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
        <form action="{{ route('siswa.index') }}" method="GET" class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1 relative">
                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </span>
                <input type="text" name="search" value="{{ $search }}" placeholder="Cari berdasarkan Nama, NISN, atau NIS..."
                    class="w-full pl-10 pr-3.5 py-2 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
            </div>

            <div class="sm:w-60">
                <select name="kelas_id" class="w-full px-3.5 py-2 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm bg-white">
                    <option value="">-- Semua Kelas --</option>
                    @foreach($kelasList as $k)
                        <option value="{{ $k->id }}" {{ $kelasId == $k->id ? 'selected' : '' }}>
                            {{ $k->nama_kelas }}
                        </option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold transition shrink-0">
                Filter Data
            </button>

            @if($search || $kelasId)
                <a href="{{ route('siswa.index') }}" class="px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition flex items-center justify-center shrink-0">
                    Reset
                </a>
            @endif
        </form>
    </div>

    <!-- Siswa Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-slate-600 text-xs font-semibold uppercase">
                        <th class="py-3 px-4 w-14 text-center">No</th>
                        <th class="py-3 px-4">Nama Siswa</th>
                        <th class="py-3 px-4">NISN / NIS</th>
                        <th class="py-3 px-4">Kelas & Jurusan</th>
                        <th class="py-3 px-4">Tarif SPP</th>
                        <th class="py-3 px-4">No. Telp</th>
                        <th class="py-3 px-4 text-center w-44">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($siswas as $index => $item)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="py-3.5 px-4 text-center text-xs text-slate-400 font-medium">
                                {{ $siswas->firstItem() + $index }}
                            </td>
                            <td class="py-3.5 px-4">
                                <a href="{{ route('siswa.show', $item->id) }}" class="font-bold text-slate-900 hover:text-indigo-600 transition block">
                                    {{ $item->nama }}
                                </a>
                                <span class="inline-block text-[11px] text-slate-400 font-medium">
                                    {{ $item->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 font-mono text-xs">
                                <div class="font-bold text-indigo-700">NISN: {{ $item->nisn }}</div>
                                <div class="text-slate-500">NIS: {{ $item->nis }}</div>
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="font-semibold text-slate-800">{{ $item->kelas?->nama_kelas ?? '-' }}</div>
                                <div class="text-xs text-slate-400">{{ $item->kelas?->jurusan?->nama ?? '-' }}</div>
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-emerald-700">{{ $item->spp?->formattedNominal() ?? '-' }}</div>
                                <div class="text-xs text-slate-400">TA {{ $item->spp?->tahun_ajaran }}</div>
                            </td>
                            <td class="py-3.5 px-4 text-xs text-slate-600">
                                {{ $item->no_telp ?? '-' }}
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <a href="{{ route('pembayaran.create', ['siswa_id' => $item->id]) }}" class="px-2.5 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 rounded-lg text-xs font-semibold transition" title="Bayar SPP">
                                        Bayar
                                    </a>
                                    <a href="{{ route('siswa.show', $item->id) }}" class="p-1.5 text-slate-600 hover:bg-slate-100 rounded-lg transition" title="Lihat Profil & Tagihan">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </a>
                                    <a href="{{ route('siswa.edit', $item->id) }}" class="p-1.5 text-indigo-600 hover:bg-indigo-50 rounded-lg transition" title="Ubah Data">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </a>
                                    @if(auth()->user()->isAdmin())
                                        <form action="{{ route('siswa.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data siswa ini?');" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1.5 text-rose-600 hover:bg-rose-50 rounded-lg transition" title="Hapus Data">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400 text-sm">
                                Tidak ada data siswa yang sesuai dengan kriteria pencarian.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($siswas->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $siswas->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

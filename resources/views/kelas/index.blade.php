@extends('layouts.app')

@section('title', 'Data Kelas')
@section('header_title', 'Data Rombongan Belajar (Kelas)')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h3 class="text-xl font-bold text-slate-900 dark:text-white">Daftar Kelas</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Kelola rombongan belajar dan peminatan kelas siswa</p>
        </div>
        <a href="{{ route('kelas.create') }}" class="px-4 py-2.5 prism-btn-primary text-sm self-start">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Tambah Kelas Baru
        </a>
    </div>

    <div class="prism-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="prism-thead">
                        <th class="py-3 px-4 w-16 text-center">No</th>
                        <th class="py-3 px-4">Nama Kelas</th>
                        <th class="py-3 px-4">Tingkat</th>
                        <th class="py-3 px-4">Kompetensi Keahlian</th>
                        <th class="py-3 px-4 text-center">Jumlah Siswa</th>
                        <th class="py-3 px-4 text-center w-36">Aksi</th>
                    </tr>
                </thead>
                <tbody class="prism-tbody">
                    @forelse($kelasList as $index => $item)
                        <tr class="prism-tr">
                            <td class="py-3.5 px-4 text-center text-xs text-slate-400 dark:text-slate-500 font-medium">
                                {{ $kelasList->firstItem() + $index }}
                            </td>
                            <td class="py-3.5 px-4 font-bold text-slate-900 dark:text-white">
                                {{ $item->nama_kelas }}
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="px-2 py-0.5 rounded-md text-xs font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200/70 dark:border-white/10">
                                    Kelas {{ $item->tingkat }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="font-medium text-slate-800 dark:text-slate-200">{{ $item->jurusan?->nama ?? '-' }}</div>
                                <div class="text-xs text-slate-400 dark:text-slate-500 font-mono">{{ $item->jurusan?->kode }}</div>
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-prism-50 dark:bg-prism-500/15 text-prism-700 dark:text-prism-300 border border-prism-200/70 dark:border-prism-400/25">
                                    {{ $item->siswas_count }} Siswa
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <a href="{{ route('kelas.edit', $item->id) }}" class="p-1.5 text-prism-600 dark:text-prism-300 hover:bg-prism-50 dark:hover:bg-prism-500/15 rounded-lg transition" title="Ubah Data">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </a>
                                    <form action="{{ route('kelas.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus kelas ini?');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-500/15 rounded-lg transition cursor-pointer" title="Hapus Data">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-400 dark:text-slate-500 text-sm">
                                Belum ada data kelas yang terdaftar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($kelasList->hasPages())
            <div class="p-4 border-t border-slate-100 dark:border-white/10">
                {{ $kelasList->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

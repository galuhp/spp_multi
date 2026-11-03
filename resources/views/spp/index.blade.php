@extends('layouts.app')

@section('title', 'Tarif SPP')
@section('header_title', 'Data Tarif & Nominal SPP')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h3 class="text-xl font-bold text-slate-900">Tarif & Periode SPP</h3>
            <p class="text-xs text-slate-500 mt-1">Kelola standar besaran iuran SPP per bulan berdasarkan tahun ajaran</p>
        </div>
        <a href="{{ route('spp.create') }}" class="px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-sm transition shadow-sm inline-flex items-center gap-2 self-start">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Tambah Tarif SPP
        </a>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-slate-600 text-xs font-semibold uppercase">
                        <th class="py-3 px-4 w-16 text-center">No</th>
                        <th class="py-3 px-4">Tahun</th>
                        <th class="py-3 px-4">Tahun Ajaran</th>
                        <th class="py-3 px-4">Nominal / Bulan</th>
                        <th class="py-3 px-4">Keterangan</th>
                        <th class="py-3 px-4 text-center">Siswa Menggunakan</th>
                        <th class="py-3 px-4 text-center w-36">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($spps as $index => $item)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="py-3.5 px-4 text-center text-xs text-slate-400 font-medium">
                                {{ $spps->firstItem() + $index }}
                            </td>
                            <td class="py-3.5 px-4 font-mono font-bold text-xs text-indigo-700">
                                {{ $item->tahun }}
                            </td>
                            <td class="py-3.5 px-4 font-semibold text-slate-900">
                                {{ $item->tahun_ajaran }}
                            </td>
                            <td class="py-3.5 px-4 font-bold text-emerald-700">
                                {{ $item->formattedNominal() }}
                            </td>
                            <td class="py-3.5 px-4 text-xs text-slate-500">
                                {{ $item->keterangan ?? '-' }}
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-100">
                                    {{ $item->siswas_count }} Siswa
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <a href="{{ route('spp.edit', $item->id) }}" class="p-1.5 text-indigo-600 hover:bg-indigo-50 rounded-lg transition" title="Ubah Data">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </a>
                                    <form action="{{ route('spp.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus tarif SPP ini?');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 text-rose-600 hover:bg-rose-50 rounded-lg transition" title="Hapus Data">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400 text-sm">
                                Belum ada data tarif SPP yang terdaftar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($spps->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $spps->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

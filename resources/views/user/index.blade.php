@extends('layouts.app')

@section('title', 'Manajemen Pengguna')
@section('header_title', 'Manajemen Pengguna & Hak Akses')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h3 class="text-xl font-bold text-zinc-900">Pengguna & Hak Akses</h3>
            <p class="text-xs text-zinc-500 mt-1">Kelola akun dan peranan (Administrator, Petugas/Kasir, dan Siswa)</p>
        </div>
        <x-ui.button as="a" href="{{ route('user.create') }}" variant="primary" icon="user-plus" class="self-start">
            Tambah Pengguna Baru
        </x-ui.button>
    </div>

    <!-- Filter by Role Pills -->
    <div class="flex items-center gap-2 overflow-x-auto pb-1 text-xs">
        <a href="{{ route('user.index') }}" class="px-3.5 py-1.5 rounded-xl font-medium transition {{ ! $currentRole ? 'bg-zinc-900 text-white shadow-xs' : 'bg-white text-zinc-600 border border-zinc-200 hover:bg-zinc-100' }}">
            Semua Peran
        </a>
        @foreach($roles as $role)
            <a href="{{ route('user.index', ['role' => $role->value]) }}" class="px-3.5 py-1.5 rounded-xl font-medium transition {{ $currentRole === $role->value ? 'bg-zinc-900 text-white shadow-xs' : 'bg-white text-zinc-600 border border-zinc-200 hover:bg-zinc-100' }}">
                {{ $role->label() }}
            </a>
        @endforeach
    </div>

    <div class="bg-white rounded-xl border border-zinc-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 bg-zinc-50 text-zinc-600 text-xs font-semibold uppercase">
                        <th class="py-3 px-4 w-16 text-center">No</th>
                        <th class="py-3 px-4">Nama Lengkap</th>
                        <th class="py-3 px-4">Username / Akun</th>
                        <th class="py-3 px-4">Email</th>
                        <th class="py-3 px-4">Hak Akses / Peran</th>
                        <th class="py-3 px-4">Tgl. Terdaftar</th>
                        <th class="py-3 px-4 text-center w-36">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 text-zinc-700">
                    @forelse($users as $index => $item)
                        <tr class="hover:bg-zinc-50 transition">
                            <td class="py-3.5 px-4 text-center text-xs text-zinc-400 font-medium">
                                {{ $users->firstItem() + $index }}
                            </td>
                            <td class="py-3.5 px-4 font-semibold text-zinc-900">
                                {{ $item->name }}
                                @if(auth()->id() === $item->id)
                                    <span class="ml-1 text-[10px] text-zinc-900 font-bold">(Akun Anda)</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 font-mono font-medium text-xs text-zinc-700">
                                {{ $item->username }}
                            </td>
                            <td class="py-3.5 px-4 text-xs text-zinc-500">
                                {{ $item->email ?? '-' }}
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $item->role->badgeClass() }}">
                                    {{ $item->role->label() }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-xs text-zinc-500">
                                {{ $item->created_at->format('d/m/Y') }}
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <a href="{{ route('user.edit', $item->id) }}" class="p-1.5 text-zinc-900 hover:bg-zinc-100 rounded-lg transition" title="Ubah Data">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </a>
                                    @if(auth()->id() !== $item->id)
                                        <form action="{{ route('user.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus akun pengguna ini?');" class="inline">
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
                            <td colspan="7" class="py-8 text-center text-zinc-400 text-sm">
                                Tidak ada data pengguna yang ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
            <div class="p-4 border-t border-zinc-200">
                {{ $users->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

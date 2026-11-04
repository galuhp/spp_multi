@extends('layouts.app')

@section('title', 'Data Kelas')
@section('header_title', 'Data Rombongan Belajar (Kelas)')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h3 class="text-xl font-bold text-zinc-900">Daftar Kelas</h3>
            <p class="text-xs text-zinc-500 mt-1">Kelola rombongan belajar dan peminatan kelas siswa</p>
        </div>
        <x-ui.button as="a" href="{{ route('kelas.create') }}" variant="primary" icon="plus" class="self-start">
            Tambah Kelas Baru
        </x-ui.button>
    </div>

    <x-ui.card class="!max-w-none">
        <div class="overflow-x-auto">
            <x-ui.table>
                <x-ui.table.columns>
                    <x-ui.table.head class="w-16 text-center">No</x-ui.table.head>
                    <x-ui.table.head>Tahun Ajaran</x-ui.table.head>
                    <x-ui.table.head>Nama Kelas</x-ui.table.head>
                    <x-ui.table.head>Tingkat</x-ui.table.head>
                    <x-ui.table.head>Kompetensi Keahlian</x-ui.table.head>
                    <x-ui.table.head class="text-center">Jumlah Siswa</x-ui.table.head>
                    <x-ui.table.head class="w-36 text-center">Aksi</x-ui.table.head>
                </x-ui.table.columns>
                <x-ui.table.rows>
                    @forelse($kelasList as $index => $item)
                        <x-ui.table.row>
                            <x-ui.table.cell class="text-center text-xs text-zinc-400 font-medium">
                                {{ $kelasList->firstItem() + $index }}
                            </x-ui.table.cell>
                            <x-ui.table.cell class="text-sm text-zinc-600">
                                {{ $item->tahunAjaran?->nama ?? '-' }}
                            </x-ui.table.cell>
                            <x-ui.table.cell class="font-bold">
                                {{ $item->nama_kelas }}
                            </x-ui.table.cell>
                            <x-ui.table.cell>
                                <x-ui.badge variant="outline">Kelas {{ $item->tingkat }}</x-ui.badge>
                            </x-ui.table.cell>
                            <x-ui.table.cell>
                                <div class="font-medium text-zinc-800">{{ $item->jurusan?->nama ?? '-' }}</div>
                                <div class="text-xs text-zinc-400 font-mono">{{ $item->jurusan?->kode }}</div>
                            </x-ui.table.cell>
                            <x-ui.table.cell class="text-center">
                                <x-ui.badge variant="solid">{{ $item->siswas_count }} Siswa</x-ui.badge>
                            </x-ui.table.cell>
                            <x-ui.table.cell>
                                <div class="flex items-center justify-center gap-1.5">
                                    <x-ui.button as="a" href="{{ route('kelas.edit', $item->id) }}" variant="ghost" size="sm" icon="pencil" />
                                    <form action="{{ route('kelas.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus kelas ini?');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <x-ui.button type="submit" variant="ghost" size="sm" icon="trash" />
                                    </form>
                                </div>
                            </x-ui.table.cell>
                        </x-ui.table.row>
                    @empty
                        <x-ui.table.row>
                            <x-ui.table.cell colspan="7" class="py-8 text-center text-zinc-400 text-sm">
                                Belum ada data kelas yang terdaftar.
                            </x-ui.table.cell>
                        </x-ui.table.row>
                    @endforelse
                </x-ui.table.rows>
            </x-ui.table>
        </div>

        @if($kelasList->hasPages())
            <div class="p-4 border-t border-zinc-200">
                {{ $kelasList->links() }}
            </div>
        @endif
    </x-ui.card>
</div>
@endsection
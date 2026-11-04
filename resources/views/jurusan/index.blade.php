@extends('layouts.app')

@section('title', 'Data Jurusan')
@section('header_title', 'Kompetensi Keahlian (Jurusan)')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h3 class="text-xl font-bold text-zinc-900">Daftar Jurusan / Kompetensi Keahlian</h3>
            <p class="text-xs text-zinc-500 mt-1">Kelola data program studi atau kejuruan yang ada di sekolah</p>
        </div>
        <x-ui.button as="a" href="{{ route('jurusan.create') }}" variant="primary" icon="plus" class="self-start">
            Tambah Jurusan
        </x-ui.button>
    </div>

    <x-ui.card class="!max-w-none">
        <div class="overflow-x-auto">
            <x-ui.table>
                <x-ui.table.columns>
                    <x-ui.table.head class="w-16 text-center">No</x-ui.table.head>
                    <x-ui.table.head>Kode</x-ui.table.head>
                    <x-ui.table.head>Nama Kompetensi Keahlian</x-ui.table.head>
                    <x-ui.table.head>Deskripsi</x-ui.table.head>
                    <x-ui.table.head class="text-center">Jumlah Kelas</x-ui.table.head>
                    <x-ui.table.head class="w-36 text-center">Aksi</x-ui.table.head>
                </x-ui.table.columns>
                <x-ui.table.rows>
                    @forelse($jurusans as $index => $item)
                        <x-ui.table.row>
                            <x-ui.table.cell class="text-center text-xs text-zinc-400 font-medium">
                                {{ $jurusans->firstItem() + $index }}
                            </x-ui.table.cell>
                            <x-ui.table.cell class="font-mono font-bold text-xs">
                                {{ $item->kode }}
                            </x-ui.table.cell>
                            <x-ui.table.cell class="font-semibold">
                                {{ $item->nama }}
                            </x-ui.table.cell>
                            <x-ui.table.cell class="text-xs text-zinc-500 max-w-xs truncate">
                                {{ $item->deskripsi ?? '-' }}
                            </x-ui.table.cell>
                            <x-ui.table.cell class="text-center">
                                <x-ui.badge variant="outline">{{ $item->kelas_count }} Rombel</x-ui.badge>
                            </x-ui.table.cell>
                            <x-ui.table.cell>
                                <div class="flex items-center justify-center gap-1.5">
                                    <x-ui.button as="a" href="{{ route('jurusan.edit', $item->id) }}" variant="ghost" size="sm" icon="pencil" />
                                    <form action="{{ route('jurusan.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus jurusan ini?');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <x-ui.button type="submit" variant="ghost" size="sm" icon="trash" />
                                    </form>
                                </div>
                            </x-ui.table.cell>
                        </x-ui.table.row>
                    @empty
                        <x-ui.table.row>
                            <x-ui.table.cell colspan="6" class="py-8 text-center text-zinc-400 text-sm">
                                Belum ada data jurusan yang terdaftar.
                            </x-ui.table.cell>
                        </x-ui.table.row>
                    @endforelse
                </x-ui.table.rows>
            </x-ui.table>
        </div>

        @if($jurusans->hasPages())
            <div class="p-4 border-t border-zinc-200">
                {{ $jurusans->links() }}
            </div>
        @endif
    </x-ui.card>
</div>
@endsection
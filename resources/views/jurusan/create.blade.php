@extends('layouts.app')

@section('title', 'Tambah Jurusan')
@section('header_title', 'Tambah Kompetensi Keahlian')

@section('content')
<div class="max-w-2xl mx-auto">
    <x-ui.card class="!max-w-none !p-6 sm:!p-8">
        <div class="flex items-center justify-between pb-5 mb-5 border-b border-zinc-200">
            <div>
                <h3 class="font-bold text-zinc-900 text-lg">Form Tambah Jurusan</h3>
                <p class="text-xs text-zinc-500 mt-0.5">Lengkapi data jurusan / keahlian baru</p>
            </div>
            <a href="{{ route('jurusan.index') }}" class="text-xs font-semibold text-zinc-600 hover:text-zinc-900">
                &larr; Kembali
            </a>
        </div>

        <form action="{{ route('jurusan.store') }}" method="POST" class="space-y-4">
            @csrf

            <div>
                <label for="kode" class="block text-sm font-medium text-zinc-700 mb-1.5">
                    Kode Jurusan <span class="text-rose-500">*</span>
                </label>
                <x-ui.input id="kode" name="kode" value="{{ old('kode') }}" required
                    placeholder="Contoh: RPL, TKJ, AKL" class="font-mono uppercase" />
            </div>

            <div>
                <label for="nama" class="block text-sm font-medium text-zinc-700 mb-1.5">
                    Nama Kompetensi Keahlian <span class="text-rose-500">*</span>
                </label>
                <x-ui.input id="nama" name="nama" value="{{ old('nama') }}" required
                    placeholder="Contoh: Rekayasa Perangkat Lunak" />
            </div>

            <div>
                <label for="deskripsi" class="block text-sm font-medium text-zinc-700 mb-1.5">
                    Deskripsi / Keterangan
                </label>
                <x-ui.textarea id="deskripsi" name="deskripsi" rows="3"
                    placeholder="Keterangan singkat mengenai keahlian atau program studi">{{ old('deskripsi') }}</x-ui.textarea>
            </div>

            <div class="pt-4 flex items-center justify-end gap-3 border-t border-zinc-200">
                <x-ui.button as="a" href="{{ route('jurusan.index') }}" variant="secondary">Batal</x-ui.button>
                <x-ui.button type="submit" variant="primary">Simpan Data</x-ui.button>
            </div>
        </form>
    </x-ui.card>
</div>
@endsection
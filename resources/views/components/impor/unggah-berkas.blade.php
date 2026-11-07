@props([
    'model',
    'berkas' => null,
    'selesai' => false,
    'label' => 'berkas Excel / CSV',
    'petunjuk' => 'Format .xlsx, .xls, atau .csv · Maksimal 5 MB',
])

@php
    $pesanError = $errors->first($model);
    $idPetunjuk = $model.'-petunjuk';
    $idError = $model.'-error';
    $namaBerkasAwal = is_object($berkas) && method_exists($berkas, 'getClientOriginalName')
        ? $berkas->getClientOriginalName()
        : null;
    $ukuranBerkasAwal = null;

    if (is_object($berkas) && method_exists($berkas, 'getSize')) {
        $ukuranBerkas = $berkas->getSize();
        $ukuranBerkasAwal = $ukuranBerkas >= 1048576
            ? number_format($ukuranBerkas / 1048576, 2, ',', '.').' MB'
            : number_format($ukuranBerkas / 1024, 0, ',', '.').' KB';
    }
@endphp

<div
    wire:key="{{ $model }}-{{ $selesai ? 'selesai' : 'aktif' }}"
    data-nama-berkas="{{ $namaBerkasAwal }}"
    data-ukuran-berkas="{{ $ukuranBerkasAwal }}"
    x-data="{
        namaBerkas: null,
        ukuranBerkas: null,
        mengunggah: false,
        progres: 0,
        menyeret: false,
        pilihBerkas(event) {
            const file = event.target.files[0] ?? null;

            this.namaBerkas = file ? file.name : null;
            this.ukuranBerkas = file
                ? (file.size >= 1048576
                    ? (file.size / 1048576).toFixed(2).replace('.', ',') + ' MB'
                    : Math.max(1, Math.round(file.size / 1024)) + ' KB')
                : null;
            this.progres = 0;
        },
        akhiriSeret(event) {
            if (! this.$el.contains(event.relatedTarget)) {
                this.menyeret = false;
            }
        },
    }"
    x-init="namaBerkas = $el.dataset.namaBerkas || null; ukuranBerkas = $el.dataset.ukuranBerkas || null"
    x-on:livewire-upload-start="mengunggah = true; progres = 0"
    x-on:livewire-upload-progress="progres = $event.detail.progress ?? 0"
    x-on:livewire-upload-finish="mengunggah = false; progres = 100"
    x-on:livewire-upload-error="mengunggah = false"
    x-on:livewire-upload-cancel="mengunggah = false"
    class="space-y-2"
>
    <label
        for="{{ $model }}"
        data-impor-dropzone
        x-bind:data-menyeret="menyeret ? 'true' : 'false'"
        x-on:dragenter.prevent="menyeret = true"
        x-on:dragover.prevent="menyeret = true"
        x-on:dragleave.prevent="akhiriSeret($event)"
        x-on:drop="menyeret = false"
        @class([
            'impor-dropzone group relative flex cursor-pointer flex-col items-center justify-center gap-3 overflow-hidden rounded-2xl border-2 border-dashed px-5 py-8 text-center transition-colors duration-150 has-[:focus-visible]:border-primary-500 has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-primary-200 dark:has-[:focus-visible]:ring-primary-500/20',
            'border-slate-300 bg-slate-50/60 hover:border-primary-400 hover:bg-primary-50/40 dark:border-gray-700 dark:bg-white/[0.03] dark:hover:border-primary-500/60 dark:hover:bg-primary-500/5' => ! $pesanError,
            'border-rose-300 bg-rose-50/40 dark:border-rose-500/40 dark:bg-rose-500/[0.06]' => (bool) $pesanError,
        ])
    >
        <input
            id="{{ $model }}"
            type="file"
            wire:model="{{ $model }}"
            accept=".xlsx,.xls,.csv"
            class="absolute inset-0 z-10 h-full w-full cursor-pointer opacity-0"
            x-on:change="pilihBerkas($event)"
            aria-describedby="{{ $pesanError ? $idPetunjuk.' '.$idError : $idPetunjuk }}"
            @if ($pesanError) aria-invalid="true" @endif
        />

        <span
            class="flex size-12 items-center justify-center rounded-full bg-white text-primary-600 shadow-sm ring-1 ring-slate-200 transition group-hover:scale-105 group-hover:bg-primary-600 group-hover:text-white group-hover:ring-primary-600 motion-reduce:group-hover:scale-100 dark:bg-gray-800 dark:text-primary-400 dark:ring-gray-700 dark:group-hover:bg-primary-500 dark:group-hover:text-white"
            aria-hidden="true"
        >
            <x-heroicon-o-cloud-arrow-up class="size-6" x-show="! mengunggah" />
            <x-heroicon-o-arrow-path class="size-6 animate-spin motion-reduce:animate-none" x-show="mengunggah" x-cloak />
        </span>

        <span class="w-full min-w-0 space-y-1">
            <span class="block truncate text-sm font-semibold text-slate-900 dark:text-white" x-show="! namaBerkas">
                Klik atau seret {{ $label }} ke sini
            </span>

            <span
                class="block truncate text-sm font-semibold text-primary-700 dark:text-primary-400"
                x-show="namaBerkas"
                x-text="namaBerkas"
                x-cloak
            ></span>

            <span id="{{ $idPetunjuk }}" class="block text-xs text-slate-500 dark:text-gray-400" x-show="! namaBerkas">
                {{ $petunjuk }}
            </span>

            <span class="block text-xs text-slate-500 dark:text-gray-400" x-show="namaBerkas" x-cloak>
                <span class="font-medium text-emerald-600 dark:text-emerald-400" x-text="ukuranBerkas"></span>
                <span aria-hidden="true">&middot;</span>
                <span x-show="mengunggah">mengunggah <span x-text="progres"></span>%</span>
                <span x-show="! mengunggah">klik untuk mengganti berkas</span>
            </span>
        </span>

        @if ($pesanError)
            <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-3 py-1 text-[11px] font-semibold text-rose-700 ring-1 ring-rose-200/80 dark:bg-rose-500/10 dark:text-rose-400 dark:ring-rose-500/20">
                <x-heroicon-o-exclamation-circle class="size-3.5" aria-hidden="true" />
                Berkas perlu diperiksa
            </span>
        @else
            <span
                class="inline-flex items-center gap-1.5 rounded-full bg-white px-3 py-1 text-[11px] font-medium text-slate-600 ring-1 ring-slate-200/80 dark:bg-white/5 dark:text-gray-300 dark:ring-white/10"
                x-show="! namaBerkas"
            >
                <x-heroicon-o-document-text class="size-3.5" aria-hidden="true" />
                XLSX &middot; XLS &middot; CSV
            </span>

            <span
                class="inline-flex items-center gap-1.5 rounded-full bg-primary-50 px-3 py-1 text-[11px] font-semibold text-primary-700 ring-1 ring-primary-200/80 dark:bg-primary-500/10 dark:text-primary-300 dark:ring-primary-500/20"
                x-show="namaBerkas && mengunggah"
                x-cloak
            >
                <x-heroicon-o-arrow-path class="size-3.5 animate-spin motion-reduce:animate-none" aria-hidden="true" />
                Mengunggah berkas...
            </span>

            <span
                class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-[11px] font-semibold text-emerald-700 ring-1 ring-emerald-200/80 dark:bg-emerald-500/10 dark:text-emerald-400 dark:ring-emerald-500/20"
                x-show="namaBerkas && ! mengunggah"
                x-cloak
            >
                <x-heroicon-o-check-circle class="size-3.5" aria-hidden="true" />
                Berkas siap diimpor
            </span>
        @endif

        <span class="pointer-events-none absolute inset-x-0 bottom-0 h-1 overflow-hidden bg-transparent" x-show="mengunggah" x-cloak aria-hidden="true">
            <span class="block h-full bg-primary-500 transition-[width] duration-150 motion-reduce:transition-none" x-bind:style="`width: ${progres}%`"></span>
        </span>
    </label>

    <span
        class="sr-only"
        role="status"
        aria-live="polite"
        x-text="mengunggah ? 'Mengunggah berkas...' : (namaBerkas ? 'Berkas siap diimpor.' : '')"
    ></span>

    @if ($pesanError)
        <p
            id="{{ $idError }}"
            class="flex items-start gap-1.5 text-xs text-rose-600 dark:text-rose-400"
            aria-live="polite"
        >
            <x-heroicon-o-exclamation-circle class="mt-0.5 size-3.5 shrink-0" aria-hidden="true" />
            <span>{{ $pesanError }}</span>
        </p>
    @endif
</div>

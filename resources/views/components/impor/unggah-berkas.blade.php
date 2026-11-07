@props([
    'model',
    'berkas' => null,
    'selesai' => false,
    'label' => 'berkas Excel / CSV',
    'petunjuk' => 'Format .xlsx, .xls, atau .csv · Maksimal 5 MB',
])

@php
    $pesanError = $errors->first($model);
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
        pilihBerkas(event) {
            const file = event.target.files[0] ?? null;

            this.namaBerkas = file ? file.name : null;
            this.ukuranBerkas = file
                ? (file.size >= 1048576
                    ? (file.size / 1048576).toFixed(2).replace('.', ',') + ' MB'
                    : Math.max(1, Math.round(file.size / 1024)) + ' KB')
                : null;
        },
    }"
    x-init="namaBerkas = $el.dataset.namaBerkas || null; ukuranBerkas = $el.dataset.ukuranBerkas || null"
    class="space-y-2"
>
    <label
        for="{{ $model }}"
        class="group relative flex cursor-pointer flex-col items-center justify-center gap-3 overflow-hidden rounded-xl border-2 border-dashed border-slate-300 bg-slate-50/70 px-5 py-7 text-center transition hover:border-primary-400 hover:bg-primary-50/40 has-[:focus-visible]:border-primary-500 has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-primary-200 dark:border-gray-700 dark:bg-white/[0.03] dark:hover:border-primary-500/60 dark:hover:bg-primary-500/5 dark:has-[:focus-visible]:ring-primary-500/20"
    >
        <input
            id="{{ $model }}"
            type="file"
            wire:model="{{ $model }}"
            accept=".xlsx,.xls,.csv"
            class="absolute inset-0 z-10 h-full w-full cursor-pointer opacity-0"
            x-on:change="pilihBerkas($event)"
            @if ($pesanError) aria-invalid="true" aria-describedby="{{ $model }}-error" @endif
        />

        <span class="flex size-12 items-center justify-center rounded-full bg-white text-primary-600 shadow-sm ring-1 ring-slate-200 transition group-hover:scale-105 group-hover:bg-primary-600 group-hover:text-white group-hover:ring-primary-600 dark:bg-gray-800 dark:text-primary-400 dark:ring-gray-700 dark:group-hover:bg-primary-500 dark:group-hover:text-white">
            <x-heroicon-o-cloud-arrow-up class="size-6" aria-hidden="true" />
        </span>

        <span class="space-y-1">
            <span class="block text-sm font-semibold text-slate-900 dark:text-white" x-show="! namaBerkas">
                Klik atau seret {{ $label }} ke sini
            </span>

            <span
                class="block max-w-full truncate text-sm font-semibold text-primary-700 dark:text-primary-400"
                x-show="namaBerkas"
                x-text="namaBerkas"
                x-cloak
            ></span>

            <span class="block text-xs text-slate-500 dark:text-gray-400" x-show="! namaBerkas">
                {{ $petunjuk }}
            </span>

            <span
                class="block text-xs font-medium text-emerald-600 dark:text-emerald-400"
                x-show="namaBerkas"
                x-text="ukuranBerkas"
                x-cloak
            ></span>
        </span>

        <span
            class="inline-flex items-center gap-1.5 rounded-full bg-white px-3 py-1 text-[11px] font-medium text-slate-600 ring-1 ring-slate-200/80 dark:bg-white/5 dark:text-gray-300 dark:ring-white/10"
            x-show="! namaBerkas"
        >
            <x-heroicon-o-document-text class="size-3.5" aria-hidden="true" />
            XLSX · XLS · CSV
        </span>

        <span
            class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-[11px] font-semibold text-emerald-700 ring-1 ring-emerald-200/80 dark:bg-emerald-500/10 dark:text-emerald-400 dark:ring-emerald-500/20"
            x-show="namaBerkas"
            x-cloak
        >
            <x-heroicon-o-check-circle class="size-3.5" aria-hidden="true" />
            Berkas siap diimpor
        </span>
    </label>

    @if ($pesanError)
        <p
            id="{{ $model }}-error"
            class="flex items-start gap-1.5 text-xs text-rose-600 dark:text-rose-400"
            aria-live="polite"
        >
            <x-heroicon-o-exclamation-circle class="mt-0.5 size-3.5 shrink-0" aria-hidden="true" />
            <span>{{ $pesanError }}</span>
        </p>
    @endif
</div>

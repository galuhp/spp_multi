@props([
    'judul',
    'deskripsi' => null,
    'ikon' => 'heroicon-o-document-arrow-up',
    'templateUrl' => null,
    'templateLabel' => 'Unduh Template',
])

<section {{ $attributes->class('fi-section overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-xs dark:border-white/10 dark:bg-gray-900') }}>
    <header class="flex flex-col gap-3 border-b border-slate-200/80 px-5 py-4 sm:flex-row sm:items-start sm:justify-between dark:border-white/10">
        <div class="flex min-w-0 items-start gap-3">
            <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-primary-50 text-primary-600 ring-1 ring-primary-100 dark:bg-primary-500/10 dark:text-primary-400 dark:ring-primary-500/20">
                <x-dynamic-component :component="$ikon" class="size-5" aria-hidden="true" />
            </span>

            <div class="min-w-0">
                <h3 class="text-base font-semibold text-slate-900 dark:text-white">{{ $judul }}</h3>

                @if ($deskripsi)
                    <p class="mt-0.5 text-xs leading-relaxed text-slate-500 dark:text-gray-400">
                        {{ $deskripsi }}
                    </p>
                @endif
            </div>
        </div>

        @if ($templateUrl)
            <x-filament::button tag="a" href="{{ $templateUrl }}" color="gray" size="sm" icon="heroicon-o-arrow-down-tray" class="w-full shrink-0 sm:w-auto">
                {{ $templateLabel }}
            </x-filament::button>
        @endif
    </header>

    <div class="p-5">
        {{ $slot }}
    </div>
</section>

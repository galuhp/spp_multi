@props([
    'variant' => 'primary',
    'icon' => 'printer',
    'href' => null,
    'type' => 'button',
])

@php
    $classes = [
        'inline-flex cursor-pointer items-center justify-center gap-2 rounded-lg border px-4 py-2 text-xs font-semibold shadow-sm transition focus:outline-none focus-visible:ring-2 focus-visible:ring-zinc-400 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
        'border-transparent bg-zinc-900 text-white hover:bg-zinc-800' => $variant === 'primary',
        'border-zinc-300 bg-white text-zinc-700 hover:bg-zinc-50' => $variant === 'secondary',
    ];
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>
        @if (filled($icon))
            <x-dynamic-component :component="'heroicon-o-'.$icon" class="size-4 shrink-0" />
        @endif
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->class($classes) }}>
        @if (filled($icon))
            <x-dynamic-component :component="'heroicon-o-'.$icon" class="size-4 shrink-0" />
        @endif
        {{ $slot }}
    </button>
@endif

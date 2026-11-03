@props(['size' => 'md'])

@php
    $dimensions = match ($size) {
        'sm' => 'w-9 h-9 rounded-xl',
        'lg' => 'w-16 h-16 rounded-2xl',
        default => 'w-10 h-10 rounded-xl',
    };
    $iconDimensions = match ($size) {
        'sm' => 'w-5 h-5',
        'lg' => 'w-9 h-9',
        default => 'w-6 h-6',
    };
@endphp

<div {{ $attributes->merge([
    'class' => "$dimensions relative inline-flex items-center justify-center text-white shrink-0 shadow-prism-glow overflow-hidden",
]) }} style="background: linear-gradient(135deg, var(--color-prism-cyan), var(--color-prism-600) 52%, var(--color-prism-magenta));">
    <span class="absolute inset-0 bg-linear-to-b from-white/25 to-transparent pointer-events-none"></span>
    <svg class="{{ $iconDimensions }} relative drop-shadow-xs" viewBox="0 0 24 24" fill="none" aria-hidden="true">
        <path d="M12 2.5L21 18.5H3L12 2.5Z" stroke="currentColor" stroke-width="1.9" stroke-linejoin="round" fill="currentColor" fill-opacity="0.16"/>
        <path d="M12 2.5L15.5 18.5M12 2.5L8.5 18.5M6.2 13H17.8" stroke="currentColor" stroke-width="1.35" stroke-linecap="round" stroke-opacity="0.85"/>
    </svg>
</div>

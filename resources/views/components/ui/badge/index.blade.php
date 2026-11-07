@props([
    'iconVariant' => 'micro',
    'iconAfter' => null,
    'variant' => 'solid',
    'pill' => false,
    'color' => null,
    'size' => null,
    'icon' => null,
])
@php
    if ($variant === 'outline') {
        $colorClasses = match ($color) {
            default => 'text-slate-700 dark:text-slate-300 bg-slate-50 dark:bg-white/5 border-slate-200 dark:border-white/15 border',
            'red', 'rose' => 'text-rose-700 dark:text-rose-400 bg-rose-50 dark:bg-rose-400/10 border-rose-200 dark:border-rose-400/30 border',
            'orange' => 'text-orange-700 dark:text-orange-400 bg-orange-50 dark:bg-orange-400/10 border-orange-200 dark:border-orange-400/30 border',
            'amber' => 'text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-400/10 border-amber-200 dark:border-amber-400/30 border',
            'yellow' => 'text-yellow-800 dark:text-yellow-400 bg-yellow-50 dark:bg-yellow-400/10 border-yellow-200 dark:border-yellow-400/30 border',
            'lime' => 'text-lime-800 dark:text-lime-400 bg-lime-50 dark:bg-lime-400/10 border-lime-200 dark:border-lime-400/30 border',
            'green', 'emerald' => 'text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-400/10 border-emerald-200 dark:border-emerald-400/30 border',
            'teal' => 'text-teal-700 dark:text-teal-400 bg-teal-50 dark:bg-teal-400/10 border-teal-200 dark:border-teal-400/30 border',
            'cyan' => 'text-cyan-700 dark:text-cyan-400 bg-cyan-50 dark:bg-cyan-400/10 border-cyan-200 dark:border-cyan-400/30 border',
            'sky' => 'text-sky-700 dark:text-sky-400 bg-sky-50 dark:bg-sky-400/10 border-sky-200 dark:border-sky-400/30 border',
            'blue' => 'text-blue-700 dark:text-blue-400 bg-blue-50 dark:bg-blue-400/10 border-blue-200 dark:border-blue-400/30 border',
            'indigo' => 'text-indigo-700 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-400/10 border-indigo-200 dark:border-indigo-400/30 border',
            'violet' => 'text-violet-700 dark:text-violet-400 bg-violet-50 dark:bg-violet-400/10 border-violet-200 dark:border-violet-400/30 border',
            'purple' => 'text-purple-700 dark:text-purple-400 bg-purple-50 dark:bg-purple-400/10 border-purple-200 dark:border-purple-400/30 border',
            'fuchsia' => 'text-fuchsia-700 dark:text-fuchsia-400 bg-fuchsia-50 dark:bg-fuchsia-400/10 border-fuchsia-200 dark:border-fuchsia-400/30 border',
            'pink' => 'text-pink-700 dark:text-pink-400 bg-pink-50 dark:bg-pink-400/10 border-pink-200 dark:border-pink-400/30 border',
        };
    } else {
        $colorClasses = match ($color) {
            default => 'text-slate-700 dark:text-slate-200 bg-slate-100 dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700',
            'red', 'rose' => 'text-rose-700 dark:text-rose-300 bg-rose-50 dark:bg-rose-500/15 border border-rose-200/80 dark:border-rose-500/30',
            'orange' => 'text-orange-700 dark:text-orange-300 bg-orange-50 dark:bg-orange-500/15 border border-orange-200/80 dark:border-orange-500/30',
            'amber', 'yellow' => 'text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-500/15 border border-amber-200/80 dark:border-amber-500/30',
            'lime' => 'text-lime-700 dark:text-lime-300 bg-lime-50 dark:bg-lime-500/15 border border-lime-200/80 dark:border-lime-500/30',
            'green', 'emerald' => 'text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-500/15 border border-emerald-200/80 dark:border-emerald-500/30',
            'teal' => 'text-teal-700 dark:text-teal-300 bg-teal-50 dark:bg-teal-500/15 border border-teal-200/80 dark:border-teal-500/30',
            'cyan' => 'text-cyan-700 dark:text-cyan-300 bg-cyan-50 dark:bg-cyan-500/15 border border-cyan-200/80 dark:border-cyan-500/30',
            'sky' => 'text-sky-700 dark:text-sky-300 bg-sky-50 dark:bg-sky-500/15 border border-sky-200/80 dark:border-sky-500/30',
            'blue' => 'text-blue-700 dark:text-blue-300 bg-blue-50 dark:bg-blue-500/15 border border-blue-200/80 dark:border-blue-500/30',
            'indigo' => 'text-indigo-700 dark:text-indigo-300 bg-indigo-50 dark:bg-indigo-500/15 border border-indigo-200/80 dark:border-indigo-500/30',
            'violet' => 'text-violet-700 dark:text-violet-300 bg-violet-50 dark:bg-violet-500/15 border border-violet-200/80 dark:border-violet-500/30',
            'purple' => 'text-purple-700 dark:text-purple-300 bg-purple-50 dark:bg-purple-500/15 border border-purple-200/80 dark:border-purple-500/30',
            'fuchsia' => 'text-fuchsia-700 dark:text-fuchsia-300 bg-fuchsia-50 dark:bg-fuchsia-500/15 border border-fuchsia-200/80 dark:border-fuchsia-500/30',
            'pink' => 'text-pink-700 dark:text-pink-300 bg-pink-50 dark:bg-pink-500/15 border border-pink-200/80 dark:border-pink-500/30',
        };
    }

    $classes = [
        'inline-flex items-center font-semibold whitespace-nowrap gap-x-1',
        $colorClasses,
        match ($size) {
            'sm' => 'text-xs py-0.5',
            'lg' => 'text-sm py-1.5',
            default => 'text-xs py-0.5',
        },
        match ($pill) {
            true => 'rounded-full px-3',
            default => 'rounded-md px-2.5',
        },
    ];

    $iconClasses = [
        'size-4' => $iconVariant === 'outline',
    ];

@endphp


<x-ui.button.abstract 
    {{ $attributes->class(Arr::toCssClasses($classes)) }}
    data-slot="badge"
>

    @if (is_string($icon) && $icon !== '')
        <x-ui.icon :name="$icon" :variant="$iconVariant" class="{{ Arr::toCssClasses($iconClasses) }}"
            data-slot="badge-icon" />
    @else
        {{ $icon }}
    @endif


    {{ $slot }}

    @if ($iconAfter)
        @if (is_string($iconAfter))
            <x-ui.icon :name="$iconAfter" :variant="$iconVariant" class="{{ Arr::toCssClasses($iconClasses) }}"
                data-slot="badge-icon:after" />
        @else
            {{ $iconAfter }}
        @endif
    @endif
</x-ui.button.abstract>

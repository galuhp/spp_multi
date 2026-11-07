@props(['size' => 'md'])

@php
    $variantClasses = match ($size) {
        'xs' => 'max-w-xs',
        'sm' => 'max-w-sm',
        'md' => 'max-w-md',
        'lg' => 'max-w-lg',
        'xl' => 'max-w-xl',
        '2xl' =>  'max-w-2xl',
    };
    
    $classes = [
        'fi-section bg-white dark:bg-gray-900 border border-slate-200/80 dark:border-white/10 shadow-xs',
        '[:where(&)]:p-5 [:where(&)]:rounded-xl',
        $variantClasses
    ];

@endphp

<div {{ $attributes->class(Arr::toCssClasses($classes)) }}>
    {{ $slot }}
</div>
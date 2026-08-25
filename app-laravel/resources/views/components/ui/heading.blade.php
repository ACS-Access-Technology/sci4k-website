@props([
    'level' => 2,
    'size' => 'md',
])

@php
    $sizes = [
        'xl' => 'text-xl font-semibold',
        'lg' => 'text-lg font-semibold',
        'md' => 'text-base font-semibold',
    ];

    $tag = 'h'.$level;
@endphp

<{{ $tag }} {{ $attributes->class(['text-zinc-900 dark:text-white', $sizes[$size] ?? $sizes['md']]) }}>{{ $slot }}</{{ $tag }}>

@props([
    'variant' => 'primary',
    'type' => 'button',
])

@php
    $variants = [
        'primary' => 'bg-accent text-accent-foreground hover:opacity-90 border border-transparent',
        'danger' => 'bg-red-600 text-white hover:bg-red-700 border border-transparent',
        'outline' => 'bg-transparent text-zinc-800 dark:text-white border border-zinc-300 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-800',
        'ghost' => 'bg-transparent text-zinc-600 dark:text-zinc-300 border border-transparent hover:bg-zinc-100 dark:hover:bg-zinc-800',
        'filled' => 'bg-zinc-100 text-zinc-800 dark:bg-zinc-800 dark:text-white border border-transparent hover:bg-zinc-200 dark:hover:bg-zinc-700',
    ];
@endphp

<button
    type="{{ $type }}"
    {{ $attributes->class([
        'inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2 text-sm font-medium',
        'transition-colors cursor-pointer disabled:cursor-not-allowed disabled:opacity-50',
        'focus:outline-hidden focus:ring-2 focus:ring-accent focus:ring-offset-2 focus:ring-offset-accent-foreground',
        $variants[$variant] ?? $variants['primary'],
    ]) }}
>
    {{ $slot }}
</button>

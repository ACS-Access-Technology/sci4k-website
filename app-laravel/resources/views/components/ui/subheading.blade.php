@props([
    'size' => 'md',
])

<p {{ $attributes->class(['text-zinc-500 dark:text-zinc-400', $size === 'lg' ? 'text-base' : 'text-sm']) }}>
    {{ $slot }}
</p>

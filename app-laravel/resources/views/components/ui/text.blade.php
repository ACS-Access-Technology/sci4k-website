@props([
    'variant' => null,
])

<p {{ $attributes->class([
    'text-sm',
    $variant === 'subtle' ? 'text-zinc-500 dark:text-zinc-400' : 'text-zinc-600 dark:text-zinc-300',
]) }}>
    {{ $slot }}
</p>

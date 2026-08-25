@props([
    'href' => '#',
])

<a
    href="{{ $href }}"
    {{ $attributes->class('text-accent underline-offset-4 hover:underline font-medium') }}
>
    {{ $slot }}
</a>

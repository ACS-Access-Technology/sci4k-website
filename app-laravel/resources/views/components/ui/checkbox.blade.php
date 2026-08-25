@props([
    'label' => null,
    'checked' => false,
])

@php
    $name = $attributes->get('name');
    $inputId = 'field-'.($name ? preg_replace('/[^a-zA-Z0-9_-]/', '-', $name) : uniqid());
@endphp

<label for="{{ $inputId }}" class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300 cursor-pointer w-fit">
    <input
        id="{{ $inputId }}"
        type="checkbox"
        value="1"
        @if ($checked) checked @endif
        {{ $attributes->class('size-4 rounded border-zinc-300 text-accent focus:ring-2 focus:ring-accent dark:border-zinc-700') }}
    />
    {{ $label }}
</label>

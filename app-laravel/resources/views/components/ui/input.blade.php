@props([
    'label' => null,
    'type' => 'text',
    'viewable' => false,
])

@php
    $name = $attributes->get('name') ?? $attributes->get('wire:model');
    $inputId = 'field-'.($name ? preg_replace('/[^a-zA-Z0-9_-]/', '-', $name) : uniqid());
@endphp

<div
    class="grid gap-2"
    @if ($viewable) x-data="{ visible: false }" @endif
>
    @if ($label)
        <label for="{{ $inputId }}" class="text-sm font-medium leading-tight text-zinc-800 dark:text-white">
            {{ $label }}
        </label>
    @endif

    <div class="relative">
        <input
            id="{{ $inputId }}"
            @if ($viewable)
                x-bind:type="visible ? 'text' : 'password'"
            @else
                type="{{ $type }}"
            @endif
            {{ $attributes->except(['label', 'type', 'viewable'])->class([
                'w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-xs',
                'placeholder:text-zinc-400',
                'focus:outline-hidden focus:ring-2 focus:ring-accent focus:ring-offset-2 focus:ring-offset-accent-foreground',
                'dark:border-zinc-700 dark:bg-zinc-900 dark:text-white',
                'pr-10' => $viewable,
            ]) }}
        />

        @if ($viewable)
            <button
                type="button"
                x-on:click="visible = !visible"
                class="absolute inset-y-0 end-0 flex items-center pe-3 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200"
                tabindex="-1"
            >
                <x-icons.eye x-show="!visible" />
                <x-icons.eye-slash x-show="visible" x-cloak />
                <span class="sr-only">{{ __('Show password') }}</span>
            </button>
        @endif
    </div>

    @if ($name)
        @error($name)
            <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    @endif
</div>

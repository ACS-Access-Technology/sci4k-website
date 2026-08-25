@props([
    'variant' => 'danger',
    'heading' => null,
])

<div {{ $attributes->class('rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900 dark:bg-red-950/50 dark:text-red-300') }}>
    {{ $heading }}
</div>

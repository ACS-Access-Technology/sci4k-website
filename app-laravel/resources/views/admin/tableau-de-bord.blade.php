<x-layouts::app :title="__('Tableau de bord')">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold">{{ __('Administration SCI4K') }}</h1>
        <x-bascule-langue />
    </div>
    <p class="mt-2 text-sm text-zinc-500">
        {{ __('Connecté en tant que :nom.', ['nom' => auth()->user()->name]) }}
    </p>
</x-layouts::app>

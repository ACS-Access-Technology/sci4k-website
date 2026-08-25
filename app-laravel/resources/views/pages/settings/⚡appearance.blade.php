<?php

use Livewire\Component;
use Livewire\Attributes\Title;

new #[Title('Appearance settings')] class extends Component {
    //
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-ui.heading level="2" class="sr-only">{{ __('Appearance settings') }}</x-ui.heading>

    <x-pages::settings.layout :heading="__('Appearance')" :subheading="__('Update the appearance settings for your account')">
        <div x-data class="inline-flex rounded-lg border border-zinc-200 p-1 dark:border-zinc-700">
            <button
                type="button"
                x-on:click="$store.appearance.set('light')"
                :class="$store.appearance.value === 'light' ? 'bg-zinc-100 dark:bg-white/10 text-zinc-900 dark:text-white' : 'text-zinc-500 dark:text-zinc-400'"
                class="rounded-md px-3 py-1.5 text-sm font-medium cursor-pointer"
            >
                {{ __('Light') }}
            </button>
            <button
                type="button"
                x-on:click="$store.appearance.set('dark')"
                :class="$store.appearance.value === 'dark' ? 'bg-zinc-100 dark:bg-white/10 text-zinc-900 dark:text-white' : 'text-zinc-500 dark:text-zinc-400'"
                class="rounded-md px-3 py-1.5 text-sm font-medium cursor-pointer"
            >
                {{ __('Dark') }}
            </button>
            <button
                type="button"
                x-on:click="$store.appearance.set('system')"
                :class="$store.appearance.value === 'system' ? 'bg-zinc-100 dark:bg-white/10 text-zinc-900 dark:text-white' : 'text-zinc-500 dark:text-zinc-400'"
                class="rounded-md px-3 py-1.5 text-sm font-medium cursor-pointer"
            >
                {{ __('System') }}
            </button>
        </div>
    </x-pages::settings.layout>
</section>

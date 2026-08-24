<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800" x-data="{ mobileNavOpen: false }">
        <header class="flex items-center gap-4 border-b border-zinc-200 bg-zinc-50 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-900">
            <button type="button" x-on:click="mobileNavOpen = ! mobileNavOpen" class="text-zinc-600 lg:hidden dark:text-zinc-300">
                <x-icons.bars />
                <span class="sr-only">{{ __('Toggle menu') }}</span>
            </button>

            <x-app-logo href="{{ route('dashboard') }}" wire:navigate />

            <nav class="max-lg:hidden">
                <a
                    href="{{ route('dashboard') }}"
                    wire:navigate
                    @class([
                        'rounded-lg px-3 py-2 text-sm font-medium',
                        'bg-zinc-200/70 text-zinc-900 dark:bg-white/10 dark:text-white' => request()->routeIs('dashboard'),
                        'text-zinc-600 hover:bg-zinc-200/50 dark:text-zinc-300 dark:hover:bg-white/5' => ! request()->routeIs('dashboard'),
                    ])
                >
                    {{ __('Dashboard') }}
                </a>
            </nav>

            <div class="flex-1"></div>

            <nav class="hidden items-center gap-1 lg:flex">
                <a href="https://github.com/laravel/livewire-starter-kit" target="_blank" title="{{ __('Repository') }}" class="rounded-lg p-2 text-zinc-600 hover:bg-zinc-200/50 dark:text-zinc-300 dark:hover:bg-white/5">
                    <x-icons.folder-git-2 />
                    <span class="sr-only">{{ __('Repository') }}</span>
                </a>
                <a href="https://laravel.com/docs/starter-kits#livewire" target="_blank" title="{{ __('Documentation') }}" class="rounded-lg p-2 text-zinc-600 hover:bg-zinc-200/50 dark:text-zinc-300 dark:hover:bg-white/5">
                    <x-icons.book-open-text />
                    <span class="sr-only">{{ __('Documentation') }}</span>
                </a>
            </nav>

            <x-desktop-user-menu />
        </header>

        {{-- Mobile menu --}}
        <nav x-show="mobileNavOpen" x-cloak class="border-b border-zinc-200 bg-zinc-50 p-3 lg:hidden dark:border-zinc-700 dark:bg-zinc-900">
            <a
                href="{{ route('dashboard') }}"
                wire:navigate
                @class([
                    'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium',
                    'bg-zinc-200/70 text-zinc-900 dark:bg-white/10 dark:text-white' => request()->routeIs('dashboard'),
                    'text-zinc-600 hover:bg-zinc-200/50 dark:text-zinc-300 dark:hover:bg-white/5' => ! request()->routeIs('dashboard'),
                ])
            >
                <x-icons.layout-grid />
                {{ __('Dashboard') }}
            </a>
        </nav>

        {{ $slot }}

        @persist('toast')
            <x-ui.toast />
        @endpersist

        @livewireScripts
    </body>
</html>

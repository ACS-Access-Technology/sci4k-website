<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800" x-data="{ mobileNavOpen: false }">
        <div class="flex min-h-screen">
            {{-- Desktop sidebar --}}
            <aside class="hidden lg:flex lg:w-64 lg:shrink-0 lg:flex-col border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center justify-between p-4">
                    <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                </div>

                <nav class="flex-1 space-y-1 px-3">
                    <p class="px-2 pb-1 text-xs text-zinc-400">{{ __('Platform') }}</p>

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

                    <a
                        href="{{ route('admin.articles.liste') }}"
                        wire:navigate
                        @class([
                            'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium',
                            'bg-zinc-200/70 text-zinc-900 dark:bg-white/10 dark:text-white' => request()->routeIs('admin.articles.*'),
                            'text-zinc-600 hover:bg-zinc-200/50 dark:text-zinc-300 dark:hover:bg-white/5' => ! request()->routeIs('admin.articles.*'),
                        ])
                    >
                        <x-icons.layout-grid />
                        {{ __('Articles') }}
                    </a>

                    <a
                        href="{{ route('admin.services.liste') }}"
                        wire:navigate
                        @class([
                            'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium',
                            'bg-zinc-200/70 text-zinc-900 dark:bg-white/10 dark:text-white' => request()->routeIs('admin.services.*'),
                            'text-zinc-600 hover:bg-zinc-200/50 dark:text-zinc-300 dark:hover:bg-white/5' => ! request()->routeIs('admin.services.*'),
                        ])
                    >
                        <x-icons.layout-grid />
                        {{ __('Services') }}
                    </a>

                    <a
                        href="{{ route('admin.faq.liste') }}"
                        wire:navigate
                        @class([
                            'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium',
                            'bg-zinc-200/70 text-zinc-900 dark:bg-white/10 dark:text-white' => request()->routeIs('admin.faq.*'),
                            'text-zinc-600 hover:bg-zinc-200/50 dark:text-zinc-300 dark:hover:bg-white/5' => ! request()->routeIs('admin.faq.*'),
                        ])
                    >
                        <x-icons.layout-grid />
                        {{ __('FAQ') }}
                    </a>
                </nav>

                <nav class="space-y-1 px-3 pb-3">
                    <a
                        href="https://github.com/laravel/livewire-starter-kit"
                        target="_blank"
                        class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-zinc-600 hover:bg-zinc-200/50 dark:text-zinc-300 dark:hover:bg-white/5"
                    >
                        <x-icons.folder-git-2 />
                        {{ __('Repository') }}
                    </a>
                    <a
                        href="https://laravel.com/docs/starter-kits#livewire"
                        target="_blank"
                        class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-zinc-600 hover:bg-zinc-200/50 dark:text-zinc-300 dark:hover:bg-white/5"
                    >
                        <x-icons.book-open-text />
                        {{ __('Documentation') }}
                    </a>
                </nav>

                <div class="border-t border-zinc-200 p-3 dark:border-zinc-700">
                    <x-desktop-user-menu />
                </div>
            </aside>

            {{-- Mobile off-canvas sidebar --}}
            <div
                x-show="mobileNavOpen"
                x-cloak
                class="fixed inset-0 z-40 lg:hidden"
            >
                <div class="fixed inset-0 bg-black/50" x-on:click="mobileNavOpen = false"></div>

                <aside class="relative flex h-full w-64 flex-col border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="flex items-center justify-between p-4">
                        <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />

                        <button type="button" x-on:click="mobileNavOpen = false" class="text-zinc-500 hover:text-zinc-800 dark:hover:text-white">
                            <x-icons.x-mark />
                            <span class="sr-only">{{ __('Close menu') }}</span>
                        </button>
                    </div>

                    <nav class="flex-1 space-y-1 px-3">
                        <p class="px-2 pb-1 text-xs text-zinc-400">{{ __('Platform') }}</p>

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

                        <a
                            href="{{ route('admin.articles.liste') }}"
                            wire:navigate
                            @class([
                                'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium',
                                'bg-zinc-200/70 text-zinc-900 dark:bg-white/10 dark:text-white' => request()->routeIs('admin.articles.*'),
                                'text-zinc-600 hover:bg-zinc-200/50 dark:text-zinc-300 dark:hover:bg-white/5' => ! request()->routeIs('admin.articles.*'),
                            ])
                        >
                            <x-icons.layout-grid />
                            {{ __('Articles') }}
                        </a>

                        <a
                            href="{{ route('admin.services.liste') }}"
                            wire:navigate
                            @class([
                                'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium',
                                'bg-zinc-200/70 text-zinc-900 dark:bg-white/10 dark:text-white' => request()->routeIs('admin.services.*'),
                                'text-zinc-600 hover:bg-zinc-200/50 dark:text-zinc-300 dark:hover:bg-white/5' => ! request()->routeIs('admin.services.*'),
                            ])
                        >
                            <x-icons.layout-grid />
                            {{ __('Services') }}
                        </a>

                        <a
                            href="{{ route('admin.faq.liste') }}"
                            wire:navigate
                            @class([
                                'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium',
                                'bg-zinc-200/70 text-zinc-900 dark:bg-white/10 dark:text-white' => request()->routeIs('admin.faq.*'),
                                'text-zinc-600 hover:bg-zinc-200/50 dark:text-zinc-300 dark:hover:bg-white/5' => ! request()->routeIs('admin.faq.*'),
                            ])
                        >
                            <x-icons.layout-grid />
                            {{ __('FAQ') }}
                        </a>
                    </nav>

                    <nav class="space-y-1 px-3 pb-3">
                        <a href="https://github.com/laravel/livewire-starter-kit" target="_blank" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-zinc-600 hover:bg-zinc-200/50 dark:text-zinc-300 dark:hover:bg-white/5">
                            <x-icons.folder-git-2 />
                            {{ __('Repository') }}
                        </a>
                        <a href="https://laravel.com/docs/starter-kits#livewire" target="_blank" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-zinc-600 hover:bg-zinc-200/50 dark:text-zinc-300 dark:hover:bg-white/5">
                            <x-icons.book-open-text />
                            {{ __('Documentation') }}
                        </a>
                    </nav>
                </aside>
            </div>

            {{-- min-w-0 est indispensable : un element flex ne descend pas
                 sous la largeur intrinseque de son contenu tant qu'il garde
                 min-width:auto. Sans cela un tableau large pousse toute la
                 page, qui defile alors horizontalement, et l'overflow-x-auto
                 pose sur le tableau lui-meme reste sans effet. --}}
            <div class="flex min-h-screen min-w-0 flex-1 flex-col">
                {{-- Mobile top bar --}}
                <header class="flex items-center justify-between border-b border-zinc-200 bg-zinc-50 p-3 lg:hidden dark:border-zinc-700 dark:bg-zinc-900">
                    <button type="button" x-on:click="mobileNavOpen = true" class="text-zinc-600 dark:text-zinc-300">
                        <x-icons.bars />
                        <span class="sr-only">{{ __('Open menu') }}</span>
                    </button>

                    <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />

                    <x-desktop-user-menu :show-name="false" />
                </header>

                {{ $slot }}
            </div>
        </div>

        @persist('toast')
            <x-ui.toast />
        @endpersist

        @livewireScripts
    </body>
</html>

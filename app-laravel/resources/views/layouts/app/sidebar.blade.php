<!DOCTYPE html>
{{-- Pas de class="dark" ici : le script de partials/head.blade.php la pose
     avant le premier rendu, selon le choix garde en memoire ou le reglage du
     poste. L'ecrire en dur faisait annoncer « sombre » a chaque page servie,
     y compris a un visiteur ayant choisi le clair. --}}
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800" x-data="{ mobileNavOpen: false }">
        <div class="flex min-h-screen">
            {{-- Desktop sidebar --}}
            {{-- « sticky top-0 h-screen » borne la barre a la hauteur de la
                 fenetre. Sans cela elle grandit avec son contenu, la page
                 entiere devient le seul element qui defile, et faire defiler la
                 barre emporte l'ecran de travail avec elle — signale par le
                 client. Le defilement propre est pose sur la liste des entrees,
                 juste en dessous : le logo et le pied de barre restent en
                 place. --}}
            <aside class="hidden lg:sticky lg:top-0 lg:flex lg:h-screen lg:w-64 lg:shrink-0 lg:flex-col border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center justify-between p-4">
                    <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                </div>

                <nav class="min-h-0 flex-1 space-y-1 overflow-y-auto px-3">
                    @include('layouts.app.partials.navigation-laterale')
                </nav>

                <div class="space-y-3 border-t border-zinc-200 p-3 dark:border-zinc-700">
                    <x-bascule-theme />
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

                    <nav class="min-h-0 flex-1 space-y-1 overflow-y-auto px-3">
                        @include('layouts.app.partials.navigation-laterale')
                    </nav>

                    <div class="border-t border-zinc-200 p-3 dark:border-zinc-700">
                        <x-bascule-theme />
                    </div>
                </aside>
            </div>

            {{-- min-w-0 est indispensable : un element flex ne descend pas
                 sous la largeur intrinseque de son contenu tant qu'il garde
                 min-width:auto. Sans cela un tableau large pousse toute la
                 page, qui defile alors horizontalement, et l'overflow-x-auto
                 pose sur le tableau lui-meme reste sans effet. --}}
            <div class="flex min-h-screen min-w-0 flex-1 flex-col">
                {{-- Mobile top bar --}}
                {{-- Barre du haut : elle porte la recherche transverse, que la
                     maquette place sur chaque ecran. Chaque liste a deja sa
                     propre recherche, mais elle suppose de savoir OU se trouve
                     ce qu'on cherche — or on se souvient d'un titre, pas de la
                     famille a laquelle il appartient. --}}
                <header class="hidden items-center gap-4 border-b border-zinc-200 bg-zinc-50 px-6 py-3 lg:flex dark:border-zinc-700 dark:bg-zinc-900">
                    <livewire:admin.recherche-globale />
                </header>

                <header class="flex items-center justify-between border-b border-zinc-200 bg-zinc-50 p-3 lg:hidden dark:border-zinc-700 dark:bg-zinc-900">
                    <button type="button" x-on:click="mobileNavOpen = true" class="text-zinc-600 dark:text-zinc-300">
                        <x-icons.bars />
                        <span class="sr-only">{{ __('Open menu') }}</span>
                    </button>

                    <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />

                    <x-desktop-user-menu :show-name="false" />
                </header>

                {{-- min-w-0 sur le conteneur du contenu pour la meme raison que
                     plus haut : un tableau large ne doit pas pousser la page. --}}
                <div class="min-w-0 flex-1">
                    {{ $slot }}
                </div>

                {{-- Pied du panneau, comme la maquette. L'annee suit l'horloge
                     plutot que d'etre figee : la page se serait trompee dans
                     quatre mois. --}}
                <footer class="mt-8 flex flex-wrap items-center justify-between gap-3 border-t border-zinc-200 px-6 py-4 text-xs text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                    <span>{{ __('© :annee SCI4K — Panneau d’administration.', ['annee' => now()->year]) }}</span>

                    <nav class="flex items-center gap-4">
                        <a href="{{ route('home') }}" target="_blank" class="hover:underline">{{ __('Accueil') }}</a>
                        <a href="{{ route('faq.index') }}" target="_blank" class="hover:underline">{{ __('FAQ') }}</a>
                        <a href="{{ route('profile.edit') }}" wire:navigate class="hover:underline">{{ __('Mon profil') }}</a>
                    </nav>
                </footer>
            </div>
        </div>

        @persist('toast')
            <x-ui.toast />
        @endpersist

        @livewireScripts
    </body>
</html>

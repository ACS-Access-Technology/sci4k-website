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
            <aside class="hidden lg:flex lg:w-64 lg:shrink-0 lg:flex-col border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center justify-between p-4">
                    <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                </div>

                <nav class="flex-1 space-y-1 px-3">
                    <p class="px-2 pb-1 text-xs text-zinc-400">{{ __('Platform') }}</p>


                    <x-admin.lien-lateral route="dashboard" :intitule="__('Tableau de bord')" />
                    <x-admin.lien-lateral route="admin.articles.liste" motif="admin.articles.*" :intitule="__('Articles')" />
                    <x-admin.lien-lateral route="admin.services.liste" motif="admin.services.*" :intitule="__('Services')" />
                    <x-admin.lien-lateral route="admin.faq.liste" motif="admin.faq.*" :intitule="__('FAQ')" />
                    <x-admin.lien-lateral route="admin.rubriques-faq.liste" motif="admin.rubriques-faq.*" :intitule="__('Rubriques de la FAQ')" />
                    <x-admin.lien-lateral route="admin.temoignages.liste" motif="admin.temoignages.*" :intitule="__('Témoignages')" />
                    <x-admin.lien-lateral route="admin.partenaires.liste" motif="admin.partenaires.*" :intitule="__('Partenaires')" />
                    <x-admin.lien-lateral route="admin.equipe.liste" motif="admin.equipe.*" :intitule="__('Équipe')" />
                    <x-admin.lien-lateral route="admin.valeurs" :intitule="__('Valeurs')" />
                    <x-admin.lien-lateral route="admin.chiffres-cles" :intitule="__('Chiffres clés')" />
                    <x-admin.lien-lateral route="admin.etapes-processus" :intitule="__('Étapes du processus')" />
                    <x-admin.lien-lateral route="admin.encarts.liste" motif="admin.encarts.*" :intitule="__('Encarts')" />
                    <x-admin.lien-lateral route="admin.images-de-fond.liste" motif="admin.images-de-fond.*" :intitule="__('Images de fond')" />
                    <x-admin.lien-lateral route="admin.reglages-de-section.liste" motif="admin.reglages-de-section.*" :intitule="__('En-têtes de section')" />
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

                    <nav class="flex-1 space-y-1 px-3">
                        <p class="px-2 pb-1 text-xs text-zinc-400">{{ __('Platform') }}</p>


                        <x-admin.lien-lateral route="dashboard" :intitule="__('Tableau de bord')" />
                        <x-admin.lien-lateral route="admin.articles.liste" motif="admin.articles.*" :intitule="__('Articles')" />
                        <x-admin.lien-lateral route="admin.services.liste" motif="admin.services.*" :intitule="__('Services')" />
                        <x-admin.lien-lateral route="admin.faq.liste" motif="admin.faq.*" :intitule="__('FAQ')" />
                        <x-admin.lien-lateral route="admin.rubriques-faq.liste" motif="admin.rubriques-faq.*" :intitule="__('Rubriques de la FAQ')" />
                        <x-admin.lien-lateral route="admin.temoignages.liste" motif="admin.temoignages.*" :intitule="__('Témoignages')" />
                        <x-admin.lien-lateral route="admin.partenaires.liste" motif="admin.partenaires.*" :intitule="__('Partenaires')" />
                        <x-admin.lien-lateral route="admin.equipe.liste" motif="admin.equipe.*" :intitule="__('Équipe')" />
                        <x-admin.lien-lateral route="admin.valeurs" :intitule="__('Valeurs')" />
                        <x-admin.lien-lateral route="admin.chiffres-cles" :intitule="__('Chiffres clés')" />
                        <x-admin.lien-lateral route="admin.etapes-processus" :intitule="__('Étapes du processus')" />
                        <x-admin.lien-lateral route="admin.encarts.liste" motif="admin.encarts.*" :intitule="__('Encarts')" />
                        <x-admin.lien-lateral route="admin.images-de-fond.liste" motif="admin.images-de-fond.*" :intitule="__('Images de fond')" />
                        <x-admin.lien-lateral route="admin.reglages-de-section.liste" motif="admin.reglages-de-section.*" :intitule="__('En-têtes de section')" />
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
                        <a href="{{ route('appearance.edit') }}" wire:navigate class="hover:underline">{{ __('Réglages') }}</a>
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

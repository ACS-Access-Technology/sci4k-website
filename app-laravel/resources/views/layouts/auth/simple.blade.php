<!DOCTYPE html>
{{-- Pas de class="dark" ici : le script de partials/head.blade.php la pose
     avant le premier rendu, selon le choix garde en memoire ou le reglage du
     poste. L'ecrire en dur faisait annoncer « sombre » a chaque page servie,
     y compris a un visiteur ayant choisi le clair. --}}
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white antialiased dark:bg-linear-to-b dark:from-neutral-950 dark:to-neutral-900">
        <div class="bg-background flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10">
            <div class="flex w-full max-w-sm flex-col gap-2">
                {{-- Le nom est ECRIT, et non seulement lu par un lecteur
                     d'ecran : le logo du site est une photographie, illisible
                     a 36 px. Meme traitement que l'en-tete du site public et
                     que la barre laterale — un visuel, puis le nom. --}}
                <a href="{{ route('home') }}" class="mb-2 flex flex-col items-center gap-2 font-medium" wire:navigate>
                    <span class="flex size-14 items-center justify-center overflow-hidden rounded-xl">
                        <x-app-logo-icon class="size-14" />
                    </span>
                    <span class="text-lg font-bold tracking-tight text-zinc-900 dark:text-white">
                        {{ config('app.name', 'SCI4K') }}
                    </span>
                </a>
                <div class="flex flex-col gap-6">
                    {{ $slot }}
                </div>
            </div>
        </div>

        @persist('toast')
            <x-ui.toast />
        @endpersist

        @livewireScripts
    </body>
</html>

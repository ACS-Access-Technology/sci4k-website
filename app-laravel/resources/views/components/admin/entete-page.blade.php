@props([
    'titre',
    'fil' => [],       // fil d'Ariane : ['Accueil' => route(...), 'Articles' => null]
    'resume' => null,  // ligne de contexte sous le fil, ex. « 52 articles publiés »
])

{{--
    En-tete commun a tous les ecrans d'administration.

    Reprend la disposition de la maquette : fil d'Ariane, titre, ligne de
    contexte a gauche, actions a droite. Le balisage est en Tailwind — la
    feuille du gabarit NexLink qui habille les maquettes est sous licence
    commerciale non acquise et n'est pas reprise.
--}}
<div class="flex flex-wrap items-start justify-between gap-4">
    <div class="min-w-0">
        <h1 class="text-2xl font-semibold tracking-tight text-zinc-900 dark:text-white">{{ $titre }}</h1>

        @if ($fil)
            <nav aria-label="{{ __('Fil de navigation') }}" class="mt-1 flex flex-wrap items-center gap-1.5 text-sm text-zinc-500">
                @foreach ($fil as $intitule => $lien)
                    @if (! $loop->first)
                        <span aria-hidden="true" class="text-zinc-300 dark:text-zinc-600">/</span>
                    @endif

                    @if ($lien)
                        <a href="{{ $lien }}" wire:navigate class="hover:text-zinc-800 hover:underline dark:hover:text-zinc-200">{{ $intitule }}</a>
                    @else
                        <span aria-current="page" class="text-zinc-700 dark:text-zinc-300">{{ $intitule }}</span>
                    @endif
                @endforeach
            </nav>
        @endif

        @if ($resume)
            <p class="mt-1 text-sm text-zinc-500">{{ $resume }}</p>
        @endif
    </div>

    @if (isset($actions))
        <div class="flex flex-wrap items-center gap-3">{{ $actions }}</div>
    @endif
</div>

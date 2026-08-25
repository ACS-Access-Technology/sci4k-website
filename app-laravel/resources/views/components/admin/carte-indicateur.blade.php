@props([
    'valeur',
    'intitule',
    'ton' => 'zinc',      // zinc | ambre | vert | bleu
    'icone' => 'document',
])

@php
    $tons = [
        'zinc' => 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300',
        'ambre' => 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
        'vert' => 'bg-green-100 text-green-700 dark:bg-green-950 dark:text-green-300',
        'bleu' => 'bg-sky-100 text-sky-700 dark:bg-sky-950 dark:text-sky-300',
    ];

    // Les chiffres sont espaces par milliers, a la francaise : « 18 420 » se
    // lit du premier coup d'oeil, « 18420 » demande un effort.
    $affiche = is_numeric($valeur)
        ? number_format((float) $valeur, 0, ',', "\u{202F}")
        : $valeur;
@endphp

{{--
    Carte d'indicateur, reprise de la disposition des maquettes : pastille
    d'icone, grand nombre, intitule discret.

    Aucune variation en pourcentage n'est affichee : les maquettes en montrent
    (« +8 % »), mais rien en base ne permet de comparer a une periode
    precedente. Afficher un chiffre inventé serait pire que ne rien afficher.
--}}
<div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
    <span class="inline-flex h-11 w-11 items-center justify-center rounded-lg {{ $tons[$ton] ?? $tons['zinc'] }}">
        <x-admin.icone :nom="$icone" />
    </span>

    <p class="mt-4 text-3xl font-semibold tabular-nums tracking-tight text-zinc-900 dark:text-white">{{ $affiche }}</p>
    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $intitule }}</p>
</div>

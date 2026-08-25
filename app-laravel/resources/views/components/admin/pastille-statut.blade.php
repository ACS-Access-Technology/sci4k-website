@props(['statut'])

@php
    /*
     * Une seule table pour les trois etats, partagee par tous les ecrans : le
     * jour ou un statut change de couleur, il change partout.
     *
     * Le libelle ne repose pas que sur la couleur — un daltonien lit le mot,
     * pas la teinte.
     */
    $etats = [
        'publie' => ['libelle' => __('Publié'), 'classes' => 'bg-green-100 text-green-800 dark:bg-green-950 dark:text-green-200'],
        'brouillon' => ['libelle' => __('Brouillon'), 'classes' => 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-200'],
        'archive' => ['libelle' => __('Archivé'), 'classes' => 'bg-zinc-200 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200'],
    ];

    $etat = $etats[$statut] ?? ['libelle' => $statut, 'classes' => $etats['archive']['classes']];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-md px-2.5 py-1 text-xs font-medium '.$etat['classes']]) }}>
    {{ $etat['libelle'] }}
</span>

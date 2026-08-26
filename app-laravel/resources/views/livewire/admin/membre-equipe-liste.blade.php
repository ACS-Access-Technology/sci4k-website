@include('livewire.admin.partials.bloc-liste', [
    'titre' => __('Équipe'),
    'fil' => [
        __('Accueil') => route('dashboard'),
        __('Contenu') => null,
        __('Équipe') => null,
    ],
    'colonnes' => ['nom' => __('Nom'), 'fonction' => __('Fonction'), 'etiquette' => __('Étiquette')],
    'cellule' => fn ($element, $cle) => match ($cle) {
        // Le portrait accompagne le nom plutôt que d'occuper sa propre colonne :
        // c'est la même information, et une colonne de plus aurait poussé la
        // fonction hors de l'écran sur un portable.
        'nom' => '<span class="flex items-center gap-3">'
            .($element->photo
                ? '<img src="'.e(asset($element->photo)).'" alt="" loading="lazy" class="size-9 shrink-0 rounded-full object-cover">'
                : '<span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-zinc-200 text-xs font-medium text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">'
                    .e(mb_strtoupper(mb_substr(trim(preg_replace('/^(M\.|Mme)\s*/u', '', $element->nom)), 0, 1))).'</span>')
            .'<span>'.e($element->nom).'</span></span>',
        'fonction' => e($element->fonction($langue)),
        'etiquette' => e($element->etiquette($langue) ?: '—'),
        default => '',
    },
    'nomLisible' => fn ($element) => $element->nom,
    'routeEdition' => 'admin.equipe.edition',
    'routeCreation' => 'admin.equipe.creation',
    'libelleCreation' => __('Nouveau membre'),
    'placeholder' => __('Un nom, une fonction…'),
    'messageVide' => __('Aucun membre ne correspond à votre recherche.'),
])

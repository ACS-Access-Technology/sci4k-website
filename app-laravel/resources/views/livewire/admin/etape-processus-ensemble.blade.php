@php($champReglage = 'mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950')

@include('livewire.admin.partials.edition-groupee', [
    'titre' => __("Processus d'accompagnement"),
    'sousTitre' => __('Bloc « Comment nous travaillons avec vous » de la page Services'),
    'fil' => [
        __('Accueil') => route('dashboard'),
        __('Blocs du site') => null,
        __('Processus') => null,
    ],
    'champs' => ['titre' => __('Titre'), 'texte' => __('Description')],
    'intituleRang' => __('Étape'),
    'libelleAjout' => __('Ajouter une étape'),
    'reglagesSupplementaires' => view('livewire.admin.partials.reglage-mise-en-page', [
        'champ' => $champReglage,
        'valeur' => $reglages['mise_en_page'] ?? 'frise',
        'peutEcrire' => $peutEcrire,
    ])->render(),
])

@include('livewire.admin.partials.edition-groupee', [
    'titre' => __('Valeurs et engagements'),
    'sousTitre' => __('Bloc « Les engagements de SCI4K » de la page Présentation'),
    'fil' => [
        __('Accueil') => route('dashboard'),
        __('Blocs du site') => null,
        __('Valeurs') => null,
    ],
    'champs' => ['titre' => __('Titre'), 'texte' => __('Description')],
    'colonnes' => 2,
    'intituleRang' => __('Valeur'),
    'libelleAjout' => __('Ajouter une valeur'),
])

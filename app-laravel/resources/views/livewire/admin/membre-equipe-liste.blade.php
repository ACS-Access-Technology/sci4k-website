@include('livewire.admin.partials.bloc-liste', [
    'titre' => __('Équipe'),
    'fil' => [
        __('Accueil') => route('dashboard'),
        __('Contenu') => null,
        __('Équipe') => null,
    ],
    'colonnes' => ['nom' => __('Nom'), 'fonction' => __('Fonction'), 'etiquette' => __('Étiquette')],
    'cellule' => fn ($element, $cle) => match ($cle) {
        'nom' => e($element->nom),
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

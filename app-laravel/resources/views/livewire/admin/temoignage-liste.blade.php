@include('livewire.admin.partials.bloc-liste', [
    'titre' => __('Témoignages'),
    'fil' => [
        __('Accueil') => route('dashboard'),
        __('Contenu') => null,
        __('Témoignages') => null,
    ],
    'colonnes' => ['auteur' => __('Auteur'), 'citation' => __('Témoignage'), 'note' => __('Note')],
    'cellule' => fn ($element, $cle) => match ($cle) {
        'auteur' => e($element->auteur),
        'citation' => e(Str::limit($element->citation($langue), 80)),
        'note' => str_repeat('★', $element->note),
        default => '',
    },
    'nomLisible' => fn ($element) => $element->auteur,
    'routeEdition' => 'admin.temoignages.edition',
    'routeCreation' => 'admin.temoignages.creation',
    'libelleCreation' => __('Nouveau témoignage'),
    'placeholder' => __('Un auteur, un mot du témoignage…'),
    'messageVide' => __('Aucun témoignage ne correspond à votre recherche.'),
])

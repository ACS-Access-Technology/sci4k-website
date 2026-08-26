@include('livewire.admin.partials.ensemble-fige', [
    'titre' => __('Valeurs'),
    'fil' => [
        __('Accueil') => route('dashboard'),
        __('Contenu') => null,
        __('Valeurs') => null,
    ],
    'champs' => ['titre' => __('Titre'), 'texte' => __('Description')],
    'intituleRang' => __('Valeur'),
])

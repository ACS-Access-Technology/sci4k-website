@include('livewire.admin.partials.ensemble-fige', [
    'titre' => __('Étapes du processus'),
    'fil' => [
        __('Accueil') => route('dashboard'),
        __('Contenu') => null,
        __('Étapes du processus') => null,
    ],
    'champs' => ['titre' => __('Titre'), 'texte' => __('Description')],
    'intituleRang' => __('Étape'),
])

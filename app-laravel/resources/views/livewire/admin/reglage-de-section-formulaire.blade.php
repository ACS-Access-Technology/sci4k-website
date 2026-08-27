@include('livewire.admin.partials.bloc-formulaire', [
    'intitule' => __('En-tête de section'),
    'routeListe' => 'admin.reglages-de-section.liste',
    'fil' => [
        __('Accueil') => route('dashboard'),
        __('En-têtes de section') => route('admin.reglages-de-section.liste'),
        ($estCreation ? __('Nouveau') : __('Modifier')) => null,
    ],
])

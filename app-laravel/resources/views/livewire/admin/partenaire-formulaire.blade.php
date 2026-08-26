@include('livewire.admin.partials.bloc-formulaire', [
    'intitule' => __('Partenaire'),
    'routeListe' => 'admin.partenaires.liste',
    'fil' => [
        __('Accueil') => route('dashboard'),
        __('Partenaires') => route('admin.partenaires.liste'),
        ($estCreation ? __('Nouveau') : __('Modifier')) => null,
    ],
])

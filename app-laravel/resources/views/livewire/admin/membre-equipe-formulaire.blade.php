@include('livewire.admin.partials.bloc-formulaire', [
    'intitule' => __('Membre'),
    'routeListe' => 'admin.equipe.liste',
    'fil' => [
        __('Accueil') => route('dashboard'),
        __('Équipe') => route('admin.equipe.liste'),
        ($estCreation ? __('Nouveau') : __('Modifier')) => null,
    ],
])

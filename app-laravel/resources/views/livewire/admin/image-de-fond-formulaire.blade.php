@include('livewire.admin.partials.bloc-formulaire', [
    'intitule' => __('Image de fond'),
    'routeListe' => 'admin.images-de-fond.liste',
    'fil' => [
        __('Accueil') => route('dashboard'),
        __('Images de fond') => route('admin.images-de-fond.liste'),
        ($estCreation ? __('Nouveau') : __('Modifier')) => null,
    ],
])

@include('livewire.admin.partials.bloc-formulaire', [
    'intitule' => __('Témoignage'),
    'routeListe' => 'admin.temoignages.liste',
    'fil' => [
        __('Accueil') => route('dashboard'),
        __('Témoignages') => route('admin.temoignages.liste'),
        ($estCreation ? __('Nouveau') : __('Modifier')) => null,
    ],
])

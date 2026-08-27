@include('livewire.admin.partials.bloc-formulaire', [
    'intitule' => __('Encart'),
    'routeListe' => 'admin.encarts.liste',
    'fil' => [
        __('Accueil') => route('dashboard'),
        __('Encarts') => route('admin.encarts.liste'),
        ($estCreation ? __('Nouveau') : __('Modifier')) => null,
    ],
])

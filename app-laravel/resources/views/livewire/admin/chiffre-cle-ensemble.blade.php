@include('livewire.admin.partials.ensemble-fige', [
    'titre' => __('Chiffres clés'),
    'fil' => [
        __('Accueil') => route('dashboard'),
        __('Contenu') => null,
        __('Chiffres clés') => null,
    ],
    'champs' => ['intitule' => __('Libellé')],
    'champsSimples' => ['valeur' => __('Nombre affiché')],
    'intituleRang' => __('Chiffre'),
])

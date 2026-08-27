@php($champReglage = 'mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950')

{{-- L'aperçu se construit depuis les valeurs EN COURS DE SAISIE et non depuis
     la base : c'est le seul endroit où l'on voit qu'un suffixe manque ou qu'un
     libellé déborde, avant d'enregistrer. --}}
@php($apercu = collect($lignes)->map(fn ($ligne) => view('livewire.admin.partials.apercu-chiffre', [
    'valeur' => ($ligne['valeur'] ?? '0').($ligne['suffixe'] ?? ''),
    'intitule' => $ligne['intitule_'.$langueActive] ?? '',
    'visible' => (bool) ($ligne['visible'] ?? false),
])->render())->implode(''))

@include('livewire.admin.partials.edition-groupee', [
    'titre' => __('Chiffres clés'),
    'sousTitre' => __("Compteurs animés de la page d'accueil"),
    'fil' => [
        __('Accueil') => route('dashboard'),
        __('Blocs du site') => null,
        __('Chiffres clés') => null,
    ],
    'apercu' => $apercu,
    'champs' => ['intitule' => __('Libellé affiché')],
    'champsSimples' => [
        'valeur' => ['intitule' => __('Valeur'), 'type' => 'number'],
        'suffixe' => [
            'intitule' => __('Suffixe'),
            'aide' => __('Par exemple « % ». Le compteur anime la valeur, puis pose le suffixe.'),
        ],
        'note_interne' => [
            'intitule' => __('Note interne'),
            'aide' => __("Ne s'affiche jamais sur le site : elle dit d'où vient le chiffre."),
        ],
    ],
    'colonnes' => 2,
    'intituleRang' => __('Compteur'),
    'libelleAjout' => __('Ajouter un compteur'),
    'reglagesSupplementaires' => view('livewire.admin.partials.reglage-animation', [
        'champ' => $champReglage,
        'peutEcrire' => $peutEcrire,
    ])->render(),
])

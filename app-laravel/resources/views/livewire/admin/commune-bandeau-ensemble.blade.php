@php($champReglage = 'mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950')

{{-- L'aperçu se construit depuis les valeurs EN COURS DE SAISIE, comme celui
     des chiffres clés : c'est le seul endroit où l'on voit ce que donnera un
     séparateur ou une casse avant d'enregistrer.

     La liste est répétée DEUX FOIS, comme sur le site : le bandeau défile en
     boucle, et une seule série laisserait un blanc à chaque tour. --}}
@php($nomsSaisis = collect($lignes)
    ->filter(fn ($ligne) => ($ligne['visible'] ?? '') !== '' && trim($ligne['nom'] ?? '') !== '')
    ->map(fn ($ligne) => trim($ligne['nom']))
    ->values())

@php($casse = $reglages['casse'] ?? 'majuscules')
@php($separateur = $reglages['separateur'] ?? '·')

@php($apercu = view('livewire.admin.partials.apercu-bandeau', [
    'noms' => $nomsSaisis,
    'casse' => $casse,
    'separateur' => $separateur,
    'fond' => $reglages['fond'] ?? 'sombre',
])->render())

@include('livewire.admin.partials.edition-groupee', [
    'titre' => __('Banderole des communes'),
    'sousTitre' => __("Bandeau défilant placé entre la bannière et les services, sur l'accueil"),
    'fil' => [
        __('Accueil') => route('dashboard'),
        __('Blocs du site') => null,
        __('Banderole') => null,
    ],
    'apercu' => $apercu,
    // Le bandeau est UNE bande pleine largeur, pas une grille de cartes.
    'apercuPleineLargeur' => true,
    // Il n'affiche ni titre ni chapo sur le site : les proposer aurait fait
    // saisir un texte que rien ne rend.
    'enteteAffichee' => false,
    'champs' => [],
    'champsSimples' => [
        'nom' => [
            'intitule' => __('Commune'),
            'aide' => __("Le nom s'écrit tel qu'il se dit : il n'est pas traduit."),
        ],
    ],
    'colonnes' => 3,
    'intituleRang' => __('Commune'),
    'libelleAjout' => __('Ajouter une commune'),
    'reglagesSupplementaires' => view('livewire.admin.partials.reglage-bandeau', [
        'champ' => $champReglage,
        'peutEcrire' => $peutEcrire,
    ])->render(),
])

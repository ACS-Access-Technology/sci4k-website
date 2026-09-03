@include('livewire.admin.partials.edition-groupee', [
    'titre' => __("Catégories d'articles"),
    'sousTitre' => __('Vocabulaire du filtre « Catégorie » de la page Actualités'),
    'fil' => [
        __('Accueil') => route('dashboard'),
        __('Blocs du site') => null,
        __('Catégories') => null,
    ],
    'champs' => ['nom' => __('Nom')],
    'colonnes' => 3,
    'intituleRang' => __('Catégorie'),
    'libelleAjout' => __('Ajouter une catégorie'),
    // Le filtre public propose TOUTES les categories de la table : le modele
    // n'a pas de colonne « visible », et une case sans effet aurait menti.
    'visibiliteAffichee' => false,
    // Ces categories ne portent l'en-tete d'aucune section du site.
    'enteteAffichee' => false,
])

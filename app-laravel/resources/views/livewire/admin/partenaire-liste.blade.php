@include('livewire.admin.partials.bloc-liste', [
    'titre' => __('Partenaires'),
    'fil' => [
        __('Accueil') => route('dashboard'),
        __('Contenu') => null,
        __('Partenaires') => null,
    ],
    'colonnes' => ['nom' => __('Nom'), 'logo' => __('Logo'), 'site' => __('Site')],
    'cellule' => fn ($element, $cle) => match ($cle) {
        'nom' => e($element->nom),
        'logo' => $element->logo
            ? '<img src="'.e(asset($element->logo)).'" alt="" loading="lazy" class="h-8 w-auto max-w-24 object-contain">'
            : '<span class="text-xs text-zinc-500">'.e(__('Aucun')).'</span>',
        // Deux des sept partenaires n'ont pas de site : leur logo n'est alors
        // pas un lien sur la page publique, et la colonne le dit.
        'site' => $element->aUnSite()
            ? e($element->site)
            : '<span class="text-xs text-zinc-500">'.e(__('Sans site')).'</span>',
        default => '',
    },
    'nomLisible' => fn ($element) => $element->nom,
    'creationPermise' => true,
    'libelleCreation' => __('Nouveau partenaire'),
    'placeholder' => __("Un nom d'organisation…"),
    'messageVide' => __('Aucun partenaire ne correspond à votre recherche.'),
])

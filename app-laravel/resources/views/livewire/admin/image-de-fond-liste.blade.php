{{-- Ni création ni suppression : le slug correspond à une variable CSS. Une
     image créée ne serait servie par aucune règle, une image supprimée
     laisserait la section sans fond. --}}
@include('livewire.admin.partials.bloc-liste', [
    'titre' => __('Images de fond'),
    'fil' => [
        __('Accueil') => route('dashboard'),
        __('Contenu') => null,
        __('Images de fond') => null,
    ],
    'colonnes' => [
        'slug' => __('Emplacement'),
        'apercu' => __('Aperçu'),
        'alt' => __('Texte de remplacement'),
    ],
    'cellule' => fn ($element, $cle) => match ($cle) {
        'slug' => e($element->slug),
        'apercu' => '<img src="'.e(asset($element->fichier)).'" alt="" loading="lazy" class="h-11 w-16 rounded object-cover">',
        'alt' => e($element->texteAlternatif($langue) ?: '—'),
        default => '',
    },
    'nomLisible' => fn ($element) => $element->slug,
    'routeEdition' => 'admin.images-de-fond.edition',
    'placeholder' => __('Un emplacement…'),
    'messageVide' => __('Aucune image ne correspond à votre recherche.'),
])

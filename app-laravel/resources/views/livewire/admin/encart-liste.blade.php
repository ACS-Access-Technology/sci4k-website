{{-- Ni bouton de création ni bouton de suppression : le slug d'un encart
     désigne l'endroit du site qui l'affiche. Un encart créé ne s'afficherait
     nulle part, un encart supprimé laisserait un vide. Le partiel omet les
     deux dès que 'routeCreation' est absente. --}}
@include('livewire.admin.partials.bloc-liste', [
    'titre' => __('Annonces & Actions'),
    'fil' => [
        __('Accueil') => route('dashboard'),
        __('Contenu') => null,
        __('Annonces & Actions') => null,
    ],
    'colonnes' => ['slug' => __('Emplacement'), 'titre' => __('Titre'), 'bouton' => __('Bouton')],
    'cellule' => fn ($element, $cle) => match ($cle) {
        'slug' => e($element->slug),
        'titre' => e($element->titre($langue)),
        'bouton' => e($element->libelleBouton($langue) ?: '—'),
        default => '',
    },
    'nomLisible' => fn ($element) => $element->titre($langue),
    'routeEdition' => 'admin.encarts.edition',
    'placeholder' => __('Un titre, un texte…'),
    'messageVide' => __('Aucun encart ne correspond à votre recherche.'),
])

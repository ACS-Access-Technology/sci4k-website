@php($tous = \App\Models\Temoignage::all())

@include('livewire.admin.partials.bloc-liste', [
    'statistiques' => [
        ['intitule' => __('Témoignages'), 'valeur' => $tous->count()],
        [
            'intitule' => __('Affichés sur le site'),
            'valeur' => $tous->where('visible', true)->count(),
            'detail' => $tous->where('visible', false)->count() > 0
                ? trans_choice(':nombre masqué|:nombre masqués', $tous->where('visible', false)->count(), ['nombre' => $tous->where('visible', false)->count()])
                : __('Aucun masqué'),
            'ton' => $tous->where('visible', false)->count() > 0 ? 'alerte' : 'neutre',
        ],
        [
            'intitule' => __('Note moyenne'),
            // Arrondie au dixieme : afficher « 4,666666 » ne dit rien de plus
            // que « 4,7 » et se lit moins bien.
            'valeur' => $tous->count() ? number_format($tous->avg('note'), 1, ',', ' ').' / 5' : '—',
            'detail' => trans_choice(':nombre note à 5 étoiles|:nombre notes à 5 étoiles', $tous->where('note', 5)->count(), ['nombre' => $tous->where('note', 5)->count()]),
        ],
    ],
    'titre' => __('Témoignages'),
    'fil' => [
        __('Accueil') => route('dashboard'),
        __('Contenu') => null,
        __('Témoignages') => null,
    ],
    'colonnes' => ['auteur' => __('Auteur'), 'citation' => __('Témoignage'), 'note' => __('Note')],
    'cellule' => fn ($element, $cle) => match ($cle) {
        'auteur' => e($element->auteur),
        'citation' => e(Str::limit($element->citation($langue), 80)),
        // Les cinq etoiles sont toujours rendues : celles qui manquent restent
        // en gris. N'afficher que les pleines laissait deviner la note sur
        // combien, et une note de 3 ressemblait a une note de 3 sur 3.
        'note' => '<span class="whitespace-nowrap text-base" title="'.e($element->note).'/5" aria-label="'
            .e(__(':note sur 5', ['note' => $element->note])).'">'
            .'<span class="text-amber-500">'.str_repeat('★', $element->note).'</span>'
            .'<span class="text-zinc-300 dark:text-zinc-600">'.str_repeat('★', max(0, 5 - $element->note)).'</span>'
            .'</span>',
        default => '',
    },
    'nomLisible' => fn ($element) => $element->auteur,
    'creationPermise' => true,
    'libelleCreation' => __('Nouveau témoignage'),
    'placeholder' => __('Un auteur, un mot du témoignage…'),
    'messageVide' => __('Aucun témoignage ne correspond à votre recherche.'),
])

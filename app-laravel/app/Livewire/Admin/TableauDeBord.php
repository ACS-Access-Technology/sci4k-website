<?php

namespace App\Livewire\Admin;

use App\Models\Article;
use App\Models\MembreEquipe;
use App\Models\Partenaire;
use App\Models\QuestionFaq;
use App\Models\Service;
use App\Models\Temoignage;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/*
 * Tableau de bord de l'administration.
 *
 * Il n'affichait que les quatre rectangles hachures du starter kit : un ecran
 * d'accueil qui ne dit rien de l'etat du site, et que l'editeur traverse sans
 * le lire.
 *
 * Il repond maintenant aux questions qu'on se pose en arrivant : combien de
 * contenu, qu'est-ce qui est masque du site, et qu'ai-je touche en dernier.
 * Le compte des elements MASQUES est le plus utile des trois — c'est le seul
 * qui signale un oubli, un bloc retire « le temps de » et jamais remis.
 */
#[Layout('layouts.app')]
class TableauDeBord extends Component
{
    /**
     * Les familles de contenu, avec leur compte et leur ecran.
     *
     * @return list<array<string, mixed>>
     */
    protected function familles(): array
    {
        return [
            [
                'intitule' => __('Articles'),
                'total' => Article::count(),
                'masques' => Article::where('statut', 'brouillon')->count(),
                'motMasque' => __('en brouillon'),
                'route' => 'admin.articles.liste',
            ],
            [
                'intitule' => __('Services'),
                'total' => Service::count(),
                'masques' => Service::where('visible', false)->count(),
                'motMasque' => __('masqués'),
                'route' => 'admin.services.liste',
            ],
            [
                'intitule' => __('Questions de FAQ'),
                'total' => QuestionFaq::count(),
                'masques' => QuestionFaq::where('visible', false)->count(),
                'motMasque' => __('masquées'),
                'route' => 'admin.faq.liste',
            ],
            [
                'intitule' => __('Témoignages'),
                'total' => Temoignage::count(),
                'masques' => Temoignage::where('visible', false)->count(),
                'motMasque' => __('masqués'),
                'route' => 'admin.temoignages.liste',
            ],
            [
                'intitule' => __('Partenaires'),
                'total' => Partenaire::count(),
                'masques' => Partenaire::where('visible', false)->count(),
                'motMasque' => __('masqués'),
                'route' => 'admin.partenaires.liste',
            ],
            [
                'intitule' => __('Équipe'),
                'total' => MembreEquipe::count(),
                'masques' => MembreEquipe::where('visible', false)->count(),
                'motMasque' => __('masqués'),
                'route' => 'admin.equipe.liste',
            ],
        ];
    }

    /**
     * Les derniers contenus touches, toutes familles confondues.
     *
     * Les six familles n'ont pas de table commune : la liste est assemblee en
     * PHP plutot que par une union SQL, qui aurait exige des colonnes de meme
     * nom partout et fige les modeles les uns aux autres.
     */
    protected function dernieresModifications()
    {
        $recents = collect();

        $sources = [
            [Article::class, 'titre_fr', __('Article'), 'admin.articles.edition'],
            [Service::class, 'nom_fr', __('Service'), 'admin.services.edition'],
            [QuestionFaq::class, 'question_fr', __('Question'), 'admin.faq.edition'],
            [Temoignage::class, 'auteur', __('Témoignage'), 'admin.temoignages.edition'],
            [MembreEquipe::class, 'nom', __('Membre'), 'admin.equipe.edition'],
        ];

        foreach ($sources as [$modele, $colonne, $famille, $route]) {
            foreach ($modele::query()->latest('updated_at')->limit(5)->get() as $element) {
                $recents->push([
                    'famille' => $famille,
                    'intitule' => (string) $element->$colonne,
                    'quand' => $element->updated_at,
                    'route' => $route,
                    'element' => $element,
                ]);
            }
        }

        return $recents->sortByDesc('quand')->take(8)->values();
    }

    public function render(): View
    {
        return view('livewire.admin.tableau-de-bord', [
            'familles' => $this->familles(),
            'recents' => $this->dernieresModifications(),
            'langue' => app()->getLocale(),
        ])->title(__('Tableau de bord'));
    }
}

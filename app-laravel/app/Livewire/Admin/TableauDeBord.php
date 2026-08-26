<?php

namespace App\Livewire\Admin;

use App\Models\Article;
use App\Models\ChiffreCle;
use App\Models\Encart;
use App\Models\EtapeProcessus;
use App\Models\ImageDeFond;
use App\Models\MembreEquipe;
use App\Models\Partenaire;
use App\Models\QuestionFaq;
use App\Models\Service;
use App\Models\Temoignage;
use App\Models\Valeur;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/*
 * Tableau de bord de l'administration.
 *
 * Reprend la disposition de backoffice/dashboard.html : quatre tuiles, deux
 * panneaux cote a cote, puis l'activite recente.
 *
 * Deux ecarts assumes avec la maquette, et ils tiennent au meme motif — ne
 * jamais afficher un chiffre qu'on ne mesure pas :
 *
 *   - la maquette compte des « biens en ligne » et une « repartition des
 *     biens ». Le catalogue est au lot 3. Ces deux emplacements servent donc
 *     ce qui existe : le contenu editorial et sa repartition ;
 *   - elle affiche des « visiteurs aujourd'hui » et une frequentation sur
 *     douze mois. Rien ne compte les visites. Plutot qu'un graphique invente,
 *     l'emplacement porte ce qui demande une action — un panneau qui sert a
 *     quelque chose vaut mieux qu'une courbe decorative.
 *
 * Les variations en pourcentage de la maquette demanderaient un historique que
 * personne ne conserve. Chaque tuile annonce a la place ce qui a ete AJOUTE ce
 * mois-ci, qui se mesure vraiment.
 */
#[Layout('layouts.app')]
class TableauDeBord extends Component
{
    /**
     * Les quatre tuiles de tete.
     *
     * @return list<array<string, mixed>>
     */
    protected function tuiles(): array
    {
        $debutDuMois = now()->startOfMonth();

        return [
            [
                'intitule' => __('Articles publiés'),
                'valeur' => Article::where('statut', 'publie')->count(),
                'ajoutes' => Article::where('created_at', '>=', $debutDuMois)->count(),
                'ton' => 'primaire',
                'route' => 'admin.articles.liste',
            ],
            [
                'intitule' => __('Services en ligne'),
                'valeur' => Service::where('visible', true)->count(),
                'ajoutes' => Service::where('created_at', '>=', $debutDuMois)->count(),
                'ton' => 'succes',
                'route' => 'admin.services.liste',
            ],
            [
                'intitule' => __('Questions de FAQ'),
                'valeur' => QuestionFaq::where('visible', true)->count(),
                'ajoutes' => QuestionFaq::where('created_at', '>=', $debutDuMois)->count(),
                'ton' => 'info',
                'route' => 'admin.faq.liste',
            ],
            [
                'intitule' => __('Éléments masqués'),
                'valeur' => $this->totalMasque(),
                'ajoutes' => 0,
                'ton' => 'alerte',
                'route' => null,
            ],
        ];
    }

    /** Tout ce qui existe en base mais ne s'affiche pas sur le site. */
    protected function totalMasque(): int
    {
        return Article::where('statut', 'brouillon')->count()
            + Service::where('visible', false)->count()
            + QuestionFaq::where('visible', false)->count()
            + Temoignage::where('visible', false)->count()
            + Partenaire::where('visible', false)->count()
            + MembreEquipe::where('visible', false)->count();
    }

    /**
     * La repartition du contenu, famille par famille.
     *
     * Remplace la « repartition des biens » de la maquette, le catalogue
     * n'existant pas encore. Les barres se lisent les unes par rapport aux
     * autres : le maximum donne l'echelle.
     *
     * @return list<array<string, mixed>>
     */
    protected function repartition(): array
    {
        $familles = [
            [__('Articles'), Article::count(), 'admin.articles.liste'],
            [__('Questions de FAQ'), QuestionFaq::count(), 'admin.faq.liste'],
            [__('Images de fond'), ImageDeFond::count(), 'admin.images-de-fond.liste'],
            [__('Services'), Service::count(), 'admin.services.liste'],
            [__('Partenaires'), Partenaire::count(), 'admin.partenaires.liste'],
            [__('Équipe'), MembreEquipe::count(), 'admin.equipe.liste'],
            [__('Témoignages'), Temoignage::count(), 'admin.temoignages.liste'],
            [__('Valeurs'), Valeur::count(), 'admin.valeurs'],
            [__('Étapes du processus'), EtapeProcessus::count(), 'admin.etapes-processus'],
            [__('Chiffres clés'), ChiffreCle::count(), 'admin.chiffres-cles'],
            [__('Encarts'), Encart::count(), 'admin.encarts.liste'],
        ];

        $maximum = max(1, max(array_column($familles, 1)));

        return collect($familles)
            ->sortByDesc(fn ($famille) => $famille[1])
            ->map(fn ($famille) => [
                'intitule' => $famille[0],
                'total' => $famille[1],
                'part' => (int) round($famille[1] / $maximum * 100),
                'route' => $famille[2],
            ])
            ->values()
            ->all();
    }

    /**
     * Ce qui demande une action, et pourquoi.
     *
     * Occupe l'emplacement des « taches prioritaires » de la maquette, mais
     * les taches y sont DEDUITES de l'etat reel plutot que saisies a la main :
     * une liste de taches qu'il faut penser a cocher se desynchronise du site
     * en une semaine.
     *
     * @return list<array<string, mixed>>
     */
    protected function aTraiter(): array
    {
        $aFaire = [];

        $brouillons = Article::where('statut', 'brouillon')->count();

        if ($brouillons > 0) {
            $aFaire[] = [
                'texte' => trans_choice(':nombre article en brouillon|:nombre articles en brouillon', $brouillons, ['nombre' => $brouillons]),
                'detail' => __('Invisible sur le site tant qu’il n’est pas publié.'),
                'route' => 'admin.articles.liste',
            ];
        }

        $masques = $this->totalMasque() - $brouillons;

        if ($masques > 0) {
            $aFaire[] = [
                'texte' => trans_choice(':nombre élément masqué|:nombre éléments masqués', $masques, ['nombre' => $masques]),
                'detail' => __('Retirés du site sans être supprimés.'),
                'route' => null,
            ];
        }

        // Un texte francais sans son equivalent anglais laisse la version
        // anglaise du site incomplete, sans que rien ne le signale.
        $sansAnglais = Service::where(fn ($r) => $r->whereNull('description_en')->orWhere('description_en', ''))->count()
            + QuestionFaq::where(fn ($r) => $r->whereNull('reponse_en')->orWhere('reponse_en', ''))->count()
            + Temoignage::where(fn ($r) => $r->whereNull('citation_en')->orWhere('citation_en', ''))->count();

        if ($sansAnglais > 0) {
            $aFaire[] = [
                'texte' => trans_choice(':nombre texte sans version anglaise|:nombre textes sans version anglaise', $sansAnglais, ['nombre' => $sansAnglais]),
                'detail' => __('La version anglaise du site affichera le français.'),
                'route' => null,
            ];
        }

        $sansPhoto = MembreEquipe::where(fn ($r) => $r->whereNull('photo')->orWhere('photo', ''))->count();

        if ($sansPhoto > 0) {
            $aFaire[] = [
                'texte' => trans_choice(':nombre membre sans photo|:nombre membres sans photo', $sansPhoto, ['nombre' => $sansPhoto]),
                'detail' => __('Leur vignette affiche une initiale.'),
                'route' => 'admin.equipe.liste',
            ];
        }

        return $aFaire;
    }

    /**
     * Les derniers contenus touches, toutes familles confondues.
     *
     * Les familles n'ont pas de table commune : la liste est assemblee en PHP
     * plutot que par une union SQL, qui aurait exige des colonnes de meme nom
     * partout et fige les modeles les uns aux autres.
     */
    protected function activiteRecente()
    {
        $recents = collect();

        $sources = [
            [Article::class, 'titre_fr', __('Article'), 'admin.articles.edition'],
            [Service::class, 'nom_fr', __('Service'), 'admin.services.edition'],
            [QuestionFaq::class, 'question_fr', __('Question'), 'admin.faq.edition'],
            [Temoignage::class, 'auteur', __('Témoignage'), 'admin.temoignages.edition'],
            [MembreEquipe::class, 'nom', __('Membre'), 'admin.equipe.edition'],
            [Partenaire::class, 'nom', __('Partenaire'), 'admin.partenaires.edition'],
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
            'tuiles' => $this->tuiles(),
            'repartition' => $this->repartition(),
            'aTraiter' => $this->aTraiter(),
            'recents' => $this->activiteRecente(),
            'langue' => app()->getLocale(),
        ])->title(__('Tableau de bord'));
    }
}

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
use App\Models\Tache;
use App\Models\Temoignage;
use App\Models\Valeur;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/*
 * Tableau de bord de l'administration.
 *
 * Reprend la disposition de backoffice/dashboard.html : quatre tuiles, deux
 * panneaux, puis activite recente, taches et ce qui demande une action.
 *
 * Trois panneaux de la maquette attendent leur lot — frequentation,
 * repartition des biens, messages de contact. Ils gardent leur place et
 * annoncent ce qu'ils porteront : un emplacement vide laisse croire a un
 * oubli, un graphique invente serait pire.
 */
#[Layout('layouts.app')]
class TableauDeBord extends Component
{
    /** Texte de la tache en cours de saisie. */
    public string $nouvelleTache = '';

    /** Echeance facultative de la tache en cours de saisie. */
    public string $nouvelleEcheance = '';

    protected function peutEcrire(): bool
    {
        return (bool) auth()->user()?->hasAnyRole(['administrateur', 'editeur']);
    }

    /* ------------------------------------------------------------ tuiles */

    /**
     * Les quatre tuiles de tete.
     *
     * @return list<array<string, mixed>>
     */
    protected function tuiles(): array
    {
        return [
            [
                'intitule' => __('Articles publiés'),
                'valeur' => Article::where('statut', 'publie')->count(),
                'variation' => $this->variationMensuelle(Article::class),
                'icone' => 'document',
                'ton' => 'primaire',
                'route' => 'admin.articles.liste',
            ],
            [
                'intitule' => __('Services en ligne'),
                'valeur' => Service::where('visible', true)->count(),
                'variation' => $this->variationMensuelle(Service::class),
                'icone' => 'grille',
                'ton' => 'succes',
                'route' => 'admin.services.liste',
            ],
            [
                'intitule' => __('Questions de FAQ'),
                'valeur' => QuestionFaq::where('visible', true)->count(),
                'variation' => $this->variationMensuelle(QuestionFaq::class),
                'icone' => 'question',
                'ton' => 'info',
                'route' => 'admin.faq.liste',
            ],
            [
                'intitule' => __('Éléments masqués'),
                'valeur' => $this->totalMasque(),
                'variation' => null,
                'icone' => 'oeil-barre',
                'ton' => 'alerte',
                'route' => null,
            ],
        ];
    }

    /**
     * La variation du mois en cours par rapport au precedent, en pourcentage.
     *
     * La maquette affiche « ↑ 12 % » sans dire de quoi. Ici c'est le nombre
     * d'elements CREES qu'on compare, la seule chose que les dates en base
     * permettent de mesurer — aucun historique des totaux n'est conserve.
     *
     * Renvoie null quand la comparaison n'aurait pas de sens : sans creation le
     * mois dernier, toute croissance vaudrait « +∞ % ».
     *
     * @return array{sens: string, pourcentage: int}|null
     */
    protected function variationMensuelle(string $modele): ?array
    {
        $debutMois = now()->startOfMonth();
        $debutMoisPrecedent = now()->subMonthNoOverflow()->startOfMonth();

        $ceMois = $modele::where('created_at', '>=', $debutMois)->count();
        $moisDernier = $modele::whereBetween('created_at', [$debutMoisPrecedent, $debutMois])->count();

        if ($moisDernier === 0) {
            return null;
        }

        $variation = (int) round(($ceMois - $moisDernier) / $moisDernier * 100);

        return [
            'sens' => $variation >= 0 ? 'hausse' : 'baisse',
            'pourcentage' => abs($variation),
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

    /* ------------------------------------------------------- repartition */

    /**
     * La repartition du contenu, famille par famille.
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

    /* ------------------------------------------------------------ taches */

    /** Les taches de l'utilisateur, les non terminees d'abord. */
    protected function taches()
    {
        return Tache::where('user_id', auth()->id())
            ->orderBy('terminee')
            ->orderByRaw('echeance is null')
            ->orderBy('echeance')
            ->orderBy('id')
            ->get();
    }

    public function ajouterTache(): void
    {
        abort_unless($this->peutEcrire(), 403);

        $this->validate([
            'nouvelleTache' => ['required', 'string', 'max:255'],
            'nouvelleEcheance' => ['nullable', 'date'],
        ], attributes: [
            'nouvelleTache' => __('la tâche'),
            'nouvelleEcheance' => __("l'échéance"),
        ]);

        Tache::create([
            'user_id' => auth()->id(),
            'texte' => $this->nouvelleTache,
            'echeance' => $this->nouvelleEcheance ?: null,
            'ordre' => Tache::where('user_id', auth()->id())->max('ordre') + 1,
        ]);

        $this->nouvelleTache = '';
        $this->nouvelleEcheance = '';
    }

    public function basculerTache(int $id): void
    {
        abort_unless($this->peutEcrire(), 403);

        // Restreint a l'utilisateur : l'identifiant vient du navigateur, et
        // rien n'empeche d'y mettre celui de la tache d'un collegue.
        $tache = Tache::where('user_id', auth()->id())->findOrFail($id);

        $tache->update(['terminee' => ! $tache->terminee]);
    }

    public function supprimerTache(int $id): void
    {
        abort_unless($this->peutEcrire(), 403);

        Tache::where('user_id', auth()->id())->findOrFail($id)->delete();
    }

    /* -------------------------------------------------------- a traiter */

    /**
     * Ce qui demande une action, DEDUIT de l'etat reel.
     *
     * Complete les taches saisies plutot que de s'y substituer : « 3 articles
     * en brouillon » se deduit et se perime tout seul, « rappeler le notaire
     * jeudi » se saisit et ne se devine pas.
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

    /* ------------------------------------------------------- activite */

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
            [Article::class, 'titre_fr', __('Article'), 'admin.articles.edition', 'document'],
            [Service::class, 'nom_fr', __('Service'), 'admin.services.edition', 'grille'],
            [QuestionFaq::class, 'question_fr', __('Question'), 'admin.faq.edition', 'question'],
            [Temoignage::class, 'auteur', __('Témoignage'), 'admin.temoignages.edition', 'guillemets'],
            [MembreEquipe::class, 'nom', __('Membre'), 'admin.equipe.edition', 'personne'],
            [Partenaire::class, 'nom', __('Partenaire'), 'admin.partenaires.edition', 'grille'],
        ];

        foreach ($sources as [$modele, $colonne, $famille, $route, $icone]) {
            foreach ($modele::query()->latest('updated_at')->limit(5)->get() as $element) {
                $recents->push([
                    'famille' => $famille,
                    'icone' => $icone,
                    'intitule' => (string) $element->$colonne,
                    'quand' => $element->updated_at,
                    'route' => $route,
                    'element' => $element,
                ]);
            }
        }

        return $recents->sortByDesc('quand')->take(6)->values();
    }

    /**
     * Les panneaux de la maquette qui attendent leur lot.
     *
     * @return list<array<string, string>>
     */
    protected function aVenir(): array
    {
        return [
            [
                'titre' => __('Fréquentation'),
                'texte' => __("Visiteurs et demandes sur douze mois. Demande un suivi de fréquentation, qui n'est pas encore posé."),
            ],
            [
                'titre' => __('Répartition des biens'),
                'texte' => __('Maisons, appartements, terrains, bureaux. Arrive avec le catalogue des biens immobiliers.'),
            ],
            [
                'titre' => __('Messages'),
                'texte' => __("Demandes de visite et formulaires de contact. Arrive avec la réception des messages du site."),
            ],
        ];
    }

    public function render(): View
    {
        return view('livewire.admin.tableau-de-bord', [
            'tuiles' => $this->tuiles(),
            'repartition' => $this->repartition(),
            'taches' => $this->taches(),
            'aTraiter' => $this->aTraiter(),
            'recents' => $this->activiteRecente(),
            'aVenir' => $this->aVenir(),
            'peutEcrire' => $this->peutEcrire(),
            'langue' => app()->getLocale(),
        ])->title(__('Tableau de bord'));
    }
}

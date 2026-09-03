<?php

namespace App\Livewire\Admin;

use App\Models\ActiviteJournalisee;
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
                'route' => 'admin.pages.actualites',
            ],
            [
                'intitule' => __('Services en ligne'),
                'valeur' => Service::where('visible', true)->count(),
                'variation' => $this->variationMensuelle(Service::class),
                'icone' => 'grille',
                'ton' => 'succes',
                'route' => 'admin.pages.services',
            ],
            [
                'intitule' => __('Questions de FAQ'),
                'valeur' => QuestionFaq::where('visible', true)->count(),
                'variation' => $this->variationMensuelle(QuestionFaq::class),
                'icone' => 'question',
                'ton' => 'info',
                'route' => 'admin.pages.faq',
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
            [__('Articles'), Article::count(), 'admin.pages.actualites'],
            [__('Questions de FAQ'), QuestionFaq::count(), 'admin.pages.faq'],
            [__('Images de fond'), ImageDeFond::count(), 'admin.pages.accueil'],
            [__('Services'), Service::count(), 'admin.pages.services'],
            [__('Partenaires'), Partenaire::count(), 'admin.pages.accueil'],
            [__('Équipe'), MembreEquipe::count(), 'admin.pages.presentation'],
            [__('Témoignages'), Temoignage::count(), 'admin.pages.accueil'],
            [__('Valeurs'), Valeur::count(), 'admin.pages.presentation'],
            [__('Étapes du processus'), EtapeProcessus::count(), 'admin.pages.services'],
            [__('Chiffres clés'), ChiffreCle::count(), 'admin.pages.accueil'],
            [__('Encarts'), Encart::count(), 'admin.pages.accueil'],
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
                'route' => 'admin.pages.actualites',
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
                'route' => 'admin.pages.presentation',
            ];
        }

        return $aFaire;
    }

    /* ------------------------------------------------------- activite */

    /**
     * Les dernieres actions faites depuis l'administration.
     *
     * Lues dans le JOURNAL, et non plus deduites du champ `updated_at` de
     * chaque famille. L'ancienne version ne pouvait dire ni ce qui s'etait
     * passe — creation, modification, publication — ni qui l'avait fait, et
     * elle perdait toute trace d'un element supprime. Un enregistrement sans
     * changement reel suffisait aussi a faire remonter un contenu en tete.
     */
    protected function activiteRecente()
    {
        return ActiviteJournalisee::recentes()->limit(6)->get();
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
                'texte' => __('Demandes de visite et formulaires de contact. Arrive avec la réception des messages du site.'),
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

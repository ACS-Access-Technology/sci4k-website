<?php

namespace App\Livewire\Admin;

use App\Models\Article;
use App\Models\Categorie;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

/*
 * Tableau d'administration des articles.
 *
 * Contrairement a la liste publique, il montre les brouillons : c'est l'ecran
 * de travail des editeurs.
 */
#[Layout('layouts.app')]
class ArticleListe extends Component
{
    use WithPagination;

    /**
     * L'ecran est-il rendu A L'INTERIEUR d'un autre ?
     *
     * Vrai quand « Pages du site → Actualités » l'embarque dans son module
     * Articles. L'en-tete de page disparait alors — la page qui l'accueille
     * porte le sien — et les liens d'edition cedent la place a un formulaire
     * ouvert sur place, pour ne pas faire sortir l'editeur.
     *
     * Ce composant n'herite pas de ListeOrdonnable : il porte donc lui-meme
     * ce que la classe mere apporte aux autres, comme BienListe.
     */
    public bool $embarque = true;

    /** Formulaire ouvert sur place : null, 'creation', ou l'identifiant edite. */
    public null|int|string $formulaireOuvert = null;

    public string $recherche = '';

    public string $categorieId = '';

    public string $statut = '';

    protected function peutEcrire(): bool
    {
        return (bool) auth()->user()?->hasAnyRole(['administrateur', 'editeur']);
    }

    public function ouvrirCreation(): void
    {
        abort_unless($this->peutEcrire(), 403);

        $this->formulaireOuvert = 'creation';
    }

    public function ouvrirEdition(int $id): void
    {
        abort_unless($this->peutEcrire(), 403);

        // L'identifiant vient du navigateur : on verifie qu'il designe bien un
        // article avant de le passer au formulaire.
        abort_unless(Article::whereKey($id)->exists(), 404);

        $this->formulaireOuvert = $id;
    }

    #[\Livewire\Attributes\On('bloc-enregistre')]
    #[\Livewire\Attributes\On('bloc-annule')]
    public function fermerFormulaire(): void
    {
        $this->formulaireOuvert = null;
    }

    /** Revenir a la premiere page des qu'un filtre change. */
    public function updating($nom): void
    {
        if (in_array($nom, ['recherche', 'categorieId', 'statut'], true)) {
            $this->resetPage();
        }
    }

    /**
     * Supprime un article, et avec lui sa couverture televersee.
     *
     * Le controle de role est refait ici : la route protege l'ecran, pas
     * l'action. Un lecteur peut ouvrir la liste, donc atteindre ce composant,
     * et rien ne l'empecherait d'en appeler la methode sans cette ligne.
     */
    public function supprimer(int $id): void
    {
        abort_unless(auth()->user()?->hasAnyRole(['administrateur', 'editeur']), 403);

        $article = Article::findOrFail($id);

        // Seuls les fichiers deposes par l'administration sont effaces. Les
        // couvertures du site statique sont la source du site public.
        if ($article->couvertureTeleversee()) {
            Storage::disk('public')->delete(substr($article->image_source, strlen('storage/')));
        }

        $titre = $article->titre(app()->getLocale());
        $article->delete();

        session()->flash('message', __('Article supprimé : :titre', ['titre' => $titre]));
    }

    public function render(): View
    {
        $langue = app()->getLocale();

        $articles = Article::query()
            ->with('categorie')
            // Un redacteur ne voit que ses propres articles. La restriction est
            // posee sur la REQUETE et non sur l'affichage : masquer des lignes
            // deja chargees les laisse dans la reponse envoyee au navigateur.
            ->when(auth()->user()?->limiteASesArticles(), fn ($r) => $r->where('auteur_id', auth()->id()))
            // La recherche porte sur les deux langues : un editeur anglophone
            // doit retrouver un article dont seul le titre anglais lui parle.
            ->when($this->recherche !== '', fn ($r) => $r->where(function ($q) {
                $q->where('titre_fr', 'like', '%'.$this->recherche.'%')
                    ->orWhere('titre_en', 'like', '%'.$this->recherche.'%');
            }))
            ->when($this->categorieId !== '', fn ($r) => $r->where('categorie_id', $this->categorieId))
            ->when($this->statut !== '', fn ($r) => $r->where('statut', $this->statut))
            ->orderByDesc('date_publication')
            ->paginate(20);

        // Les indicateurs portent sur TOUS les articles, pas sur la page
        // filtree : une carte qui changerait au gre des filtres ne mesurerait
        // plus rien.
        $parStatut = Article::query()
            ->selectRaw('statut, COUNT(*) as nombre')
            ->groupBy('statut')
            ->pluck('nombre', 'statut');

        return view('livewire.admin.article-liste', [
            'articles' => $articles,
            'categories' => Categorie::orderBy('ordre')->get(),
            'langue' => $langue,
            'peutEcrire' => $this->peutEcrire(),
            // Le MODELE et non l'identifiant : ArticleFormulaire attend un
            // Article dans son mount(), et le liage de route ne joue que pour
            // un composant de pleine page.
            'articleEnEdition' => is_int($this->formulaireOuvert) ? Article::find($this->formulaireOuvert) : null,
            'indicateurs' => [
                'publies' => (int) ($parStatut['publie'] ?? 0),
                'brouillons' => (int) ($parStatut['brouillon'] ?? 0),
                'archives' => (int) ($parStatut['archive'] ?? 0),
                'vues' => (int) Article::sum('vues'),
            ],
        ])->title(__('Articles'));
    }
}

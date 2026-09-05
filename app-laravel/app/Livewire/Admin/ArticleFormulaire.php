<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\RemplitParTraduction;
use App\Models\Article;
use App\Models\Categorie;
use App\Services\Traduction\Traducteur;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

/*
 * Formulaire de creation et d'edition d'un article.
 *
 * DEUX MECANISMES DE LANGUE SE CROISENT ICI, ET ILS SONT INDEPENDANTS :
 *
 *   - le bouton FR/EN de l'en-tete pilote la langue de l'INTERFACE, via
 *     app()->getLocale() ;
 *   - les onglets « Français » et « English » pilotent la langue du CONTENU
 *     saisi, via $langueActive.
 *
 * Leur seul point de rencontre est l'etat initial : l'onglet ouvert au
 * chargement suit la langue de l'interface, par simple confort. Ensuite ils ne
 * se parlent plus — on peut lire l'interface en anglais tout en redigeant la
 * version francaise. Les confondre est le defaut le plus probable de cet
 * ecran ; c'etait consigne au plan avant meme de l'ecrire.
 */
#[Layout('layouts.app')]
class ArticleFormulaire extends Component
{
    use RemplitParTraduction;
    use WithFileUploads;

    /**
     * Le formulaire est-il rendu A L'INTERIEUR d'une liste ?
     *
     * Ce composant n'herite pas de FormulaireDeBloc : il porte donc lui-meme
     * ce que la classe mere apporte aux autres — en-tete masquee, « Annuler »
     * qui referme, et enregistrement qui previent la liste au lieu de
     * rediriger. Voir BienFormulaire::$embarque.
     */
    public bool $embarque = true;

    public ?Article $article = null;

    /** Fichier choisi dans le navigateur, pas encore enregistre. */
    public $couverture = null;

    /** Chemin de la couverture actuelle, tel qu'il sera ecrit en base. */
    /**
     * Verrouille pour la meme raison qu'ServiceFormulaire::$imageActuelle :
     * propriete d'etat, jamais saisie, mais utilisee comme chemin d'effacement.
     */
    #[Locked]
    public ?string $couvertureActuelle = null;

    /** L'editeur a demande le retrait de la couverture existante. */
    public bool $couvertureARetirer = false;

    public string $slug = '';

    public string $categorieId = '';

    public string $datePublication = '';

    public string $statut = 'brouillon';

    /** Les commentaires sont-ils ouverts sous cet article ? */
    public bool $commentairesOuverts = true;

    public string $titreFr = '';

    public string $titreEn = '';

    public string $resumeFr = '';

    public string $resumeEn = '';

    public string $contenuFr = '';

    public string $contenuEn = '';

    public string $metaTitreFr = '';

    public string $metaTitreEn = '';

    public string $metaDescriptionFr = '';

    public string $metaDescriptionEn = '';

    /** Langue du contenu en cours de saisie — sans rapport avec celle de l'interface. */
    public string $langueActive = 'fr';

    public function mount(?Article $article = null): void
    {
        $this->langueActive = app()->getLocale();

        // Un redacteur ne modifie QUE ses propres articles. Sans ce controle,
        // la description de son role — « ses propres contenus » — n'aurait ete
        // qu'une phrase dans un panneau, et il aurait edite ceux de tout le
        // monde.
        if ($article?->exists && auth()->user()?->limiteASesArticles()
            && $article->auteur_id !== auth()->id()) {
            abort(403);
        }

        if (! $article?->exists) {
            $this->datePublication = now()->format('Y-m-d');

            return;
        }

        $this->article = $article;
        $this->couvertureActuelle = $article->image_source;
        $this->slug = $article->slug;
        $this->categorieId = (string) $article->categorie_id;
        $this->datePublication = $article->date_publication->format('Y-m-d');
        $this->statut = $article->statut;
        $this->commentairesOuverts = (bool) $article->commentaires_ouverts;
        $this->titreFr = $article->titre_fr;
        $this->titreEn = $article->titre_en;
        $this->resumeFr = $article->resume_fr;
        $this->resumeEn = $article->resume_en;
        $this->contenuFr = $article->contenu_fr;
        $this->contenuEn = $article->contenu_en;
        $this->metaTitreFr = $article->meta_titre_fr ?? '';
        $this->metaTitreEn = $article->meta_titre_en ?? '';
        $this->metaDescriptionFr = $article->meta_description_fr ?? '';
        $this->metaDescriptionEn = $article->meta_description_en ?? '';
    }

    protected function rules(): array
    {
        return [
            // Le format du slug est impose : il devient une adresse publique, et
            // un espace ou un accent produirait un lien casse que rien ne
            // signalerait avant le premier clic depuis le site.
            'slug' => [
                'required', 'string', 'max:190', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('articles', 'slug')->ignore($this->article?->id),
            ],
            'categorieId' => ['required', 'exists:categories,id'],
            'datePublication' => ['required', 'date'],
            'statut' => ['required', 'in:brouillon,publie'],
            'commentairesOuverts' => ['boolean'],
            'titreFr' => ['required', 'string', 'max:190'],
            'titreEn' => ['required', 'string', 'max:190'],
            'resumeFr' => ['required', 'string'],
            'resumeEn' => ['required', 'string'],
            'contenuFr' => ['required', 'string'],
            'contenuEn' => ['required', 'string'],
            'metaDescriptionFr' => ['nullable', 'string', 'max:160'],
            'metaDescriptionEn' => ['nullable', 'string', 'max:160'],
            'couverture' => ['nullable', 'image', 'max:4096'],
        ];
    }

    /** Valide le fichier des son choix, sans attendre l'enregistrement. */
    public function updatedCouverture(): void
    {
        $this->validateOnly('couverture');
    }

    /**
     * Retire la couverture. Le fichier n'est efface qu'a l'enregistrement :
     * tant que l'editeur n'a pas valide, il peut encore changer d'avis.
     */
    public function supprimerCouverture(): void
    {
        $this->couverture = null;
        $this->couvertureARetirer = true;
    }

    protected function messages(): array
    {
        return [
            'slug.regex' => __("L'identifiant d'adresse n'accepte que des minuscules, des chiffres et des traits d'union, par exemple : acd-securiser-terrain."),
        ];
    }

    public function enregistrer(): void
    {
        // La route protege l'ecran, pas l'action : Livewire ne rejoue pas le
        // middleware de role sur /livewire/update — sa liste de middlewares
        // persistants ne contient que ceux d'authentification du framework.
        // Une page laissee ouverte par un editeur retrograde en lecteur
        // continuerait sinon d'enregistrer.
        abort_unless((bool) auth()->user()?->hasAnyRole(['administrateur', 'editeur', 'redacteur']), 403);

        // Le compte est lu UNE FOIS, et sans `?->` : la ligne ci-dessus abandonne
        // deja pour qui n'est pas connecte — `auth()->user()` y vaut null, le
        // `(bool)` vaut false, et abort_unless coupe. Passe ce point, le compte
        // NE PEUT PAS etre nul, et le `?->` protegeait contre un cas impossible.
        $compte = auth()->user();

        // Meme controle qu'au montage, rejoue ici : l'ecran a pu rester ouvert
        // pendant qu'un editeur reprenait l'article a son compte.
        if ($this->article?->exists && $compte->limiteASesArticles()
            && $this->article->auteur_id !== $compte->id) {
            abort(403);
        }

        // Le redacteur ne PUBLIE pas : c'est tout l'objet de son role. Le champ
        // est deja masque dans le gabarit, mais `statut` est une propriete
        // publique — le navigateur en fixe la valeur, et une propriete masquee
        // reste accessible depuis /livewire/update.
        if (! $compte->peutPublier()) {
            $this->statut = 'brouillon';
        }

        // Avant la validation, et non apres : les champs remplis par traduction
        // doivent satisfaire les regles « required » comme s'ils avaient ete
        // saisis. Si la traduction echoue, la validation reprend la main et
        // designe les champs restes vides.
        $this->remplirParTraductionCeQuiEstVide();

        $this->validate();

        $couverture = $this->resoudreCouverture();

        $donnees = [
            'image_source' => $couverture,
            'slug' => $this->slug,
            'categorie_id' => $this->categorieId,
            'date_publication' => $this->datePublication,
            'statut' => $this->statut,
            // L'auteur est pose a la CREATION seulement : reprendre un article
            // pour y corriger une virgule ne doit pas en changer la signature.
            'auteur_id' => $this->article->auteur_id ?? auth()->id(),
            'titre_fr' => $this->titreFr,
            'titre_en' => $this->titreEn,
            'resume_fr' => $this->resumeFr,
            'resume_en' => $this->resumeEn,
            'contenu_fr' => $this->contenuFr,
            'contenu_en' => $this->contenuEn,
            'meta_titre_fr' => $this->metaTitreFr ?: null,
            'meta_titre_en' => $this->metaTitreEn ?: null,
            'meta_description_fr' => $this->metaDescriptionFr ?: null,
            'meta_description_en' => $this->metaDescriptionEn ?: null,
            'commentaires_ouverts' => $this->commentairesOuverts,
        ];

        if ($this->article) {
            $this->article->update($donnees);
        } else {
            $this->article = Article::create($donnees);
        }

        $this->dispatch('toast', message: __('Article enregistré.'), variant: 'success');

        // On ne redirige pas : on previent la liste, qui se referme.
        // Rediriger ferait quitter l'ecran de page au milieu d'une
        // modification — et depuis le retrait des ecrans par type de
        // contenu, il n'y a plus d'adresse a soi vers laquelle revenir.
        $this->dispatch('bloc-enregistre');
    }

    /**
     * Champs traduisibles de l'article, consommes par RemplitParTraduction.
     *
     * @return list<string>
     */
    protected function champsTraduisibles(): array
    {
        return ['titre', 'resume', 'contenu', 'metaDescription'];
    }

    /**
     * Determine le chemin de couverture a enregistrer, et fait le menage.
     *
     * L'ancien fichier n'est efface que s'il vient de l'administration. Les
     * douze couvertures reprises du site vivent dans public/images/, deposees
     * par tools/sync-frontoffice.sh depuis frontoffice/ : les effacer
     * detruirait la source du site public, et le prochain lancement du script
     * les remettrait, ce qui rendrait le defaut incomprehensible.
     */
    protected function resoudreCouverture(): ?string
    {
        $ancienne = $this->couvertureActuelle;

        if ($this->couverture) {
            $chemin = $this->couverture->store('actualites', 'public');
            $this->effacerSiTeleversee($ancienne);
            $this->couvertureActuelle = 'storage/'.$chemin;

            return $this->couvertureActuelle;
        }

        if ($this->couvertureARetirer) {
            $this->effacerSiTeleversee($ancienne);
            $this->couvertureActuelle = null;
            $this->couvertureARetirer = false;

            return null;
        }

        return $ancienne;
    }

    protected function effacerSiTeleversee(?string $chemin): void
    {
        if ($chemin && str_starts_with($chemin, Article::DOSSIER_COUVERTURES.'/')) {
            Storage::disk('public')->delete(substr($chemin, strlen('storage/')));
        }
    }

    public function render(): View
    {
        return view('livewire.admin.article-formulaire', [
            'categories' => Categorie::orderBy('ordre')->get(),
            'langue' => app()->getLocale(),
            'traductionActive' => app(Traducteur::class)->disponible(),
        ])->title($this->article ? __('Modifier') : __('Nouvel article'));
    }
}

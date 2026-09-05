<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\PorteDesImagesDeFond;
use App\Livewire\Concerns\PorteDesTextesDeBloc;
use App\Livewire\Concerns\PorteUnEnteteDeSection;
use App\Models\ReglageDeSection;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * La page Actualites, geree depuis un seul ecran.
 *
 * Cinquieme ecran de la refonte, meme principe que les quatre precedents : un
 * module par bloc de la page publique, dans l'ordre ou le visiteur les voit,
 * et l'ancien ecran ENTIER embarque partout ou un module pilote une
 * collection.
 *
 * La barre de recherche de /actualites filtre cote navigateur, sur le texte,
 * la date et la CATEGORIE. Seule cette derniere est de la donnee : le champ
 * libre et les deux dates n'ont rien a regler. Le module Filtres ne porte donc
 * que les categories — qui n'etaient modifiables nulle part jusqu'ici.
 */
#[Layout('layouts.app')]
class PageActualites extends Component
{
    use PorteDesImagesDeFond;
    use PorteDesTextesDeBloc;
    use PorteUnEnteteDeSection;

    /**
     * Les quatre modules de la page, dans l'ordre du site.
     *
     * @return array<string, array<string, mixed>>
     */
    /**
     * Les libelles du formulaire de recherche.
     *
     * Ils etaient ecrits en dur dans la vue et traduits par __() : aucun ecran
     * ne les exposait. Les CATEGORIES proposees etaient bien modifiables, mais
     * pas les intitules au-dessus des champs.
     */
    public const TEXTES_DES_FILTRES = [
        'libelle_recherche' => ['intitule' => 'Libellé du champ de recherche', 'defaut' => 'Rechercher'],
        'exemple_recherche' => ['intitule' => 'Exemple dans le champ de recherche', 'defaut' => 'Titre, mot-clé…'],
        'libelle_categorie' => ['intitule' => 'Libellé du filtre « catégorie »', 'defaut' => 'Catégorie'],
        'choix_toutes_categories' => ['intitule' => 'Choix « toutes les catégories »', 'defaut' => 'Toutes'],
        'libelle_du' => ['intitule' => 'Libellé de la date de début', 'defaut' => 'Du'],
        'libelle_au' => ['intitule' => 'Libellé de la date de fin', 'defaut' => 'Au'],
        'libelle_bouton' => ['intitule' => 'Libellé du bouton', 'defaut' => 'Rechercher'],
        'aucun_resultat' => [
            'intitule' => 'Message quand aucun article ne correspond',
            'defaut' => 'Aucune actualité ne correspond à votre recherche.',
            'long' => true,
        ],
        'libelle_pagination' => ['intitule' => 'Libellé de la pagination (lecteurs d’écran)', 'defaut' => 'Pagination des actualités'],
    ];

    /** Les textes propres a la page d'un article. */
    public const TEXTES_DE_L_ARTICLE = [
        'lien_retour' => ['intitule' => 'Lien de retour à la liste', 'defaut' => 'Retour aux actualités'],
        'partage_facebook' => ['intitule' => 'Libellé du partage Facebook', 'defaut' => 'Facebook'],
        'partage_whatsapp' => ['intitule' => 'Libellé du partage WhatsApp', 'defaut' => 'WhatsApp'],
        'partage_linkedin' => ['intitule' => 'Libellé du partage LinkedIn', 'defaut' => 'LinkedIn'],
        'partage_x' => ['intitule' => 'Libellé du partage X/Twitter', 'defaut' => 'X/Twitter'],
        'partage_lien' => ['intitule' => 'Libellé « copier le lien »', 'defaut' => 'Copier le lien'],
    ];

    /**
     * Les textes du bloc de commentaires.
     *
     * Le module qui les porte pilote deja la moderation ; ses textes sont ceux
     * que le LECTEUR voit sous l'article, et non ceux du backoffice.
     */
    public const TEXTES_DES_COMMENTAIRES = [
        'titre_formulaire' => ['intitule' => 'Titre du formulaire', 'defaut' => 'Laisser un commentaire'],
        'aucun_commentaire' => [
            'intitule' => 'Message quand il n’y a aucun commentaire',
            'defaut' => 'Aucun commentaire pour le moment. Soyez le premier à réagir.',
            'long' => true,
        ],
        'libelle_repondre' => ['intitule' => 'Libellé du bouton « répondre »', 'defaut' => 'Répondre'],
        'libelle_annuler_reponse' => ['intitule' => 'Libellé du bouton « annuler la réponse »', 'defaut' => 'Annuler la réponse'],
        'en_reponse_a' => ['intitule' => 'Mention « en réponse à » (:nom sera remplacé)', 'defaut' => 'En réponse à :nom'],
        'libelle_nom' => ['intitule' => 'Libellé du champ « nom »', 'defaut' => 'Votre nom *'],
        'libelle_email' => ['intitule' => 'Libellé du champ « e-mail »', 'defaut' => 'Votre e-mail *'],
        'aide_email' => ['intitule' => 'Précision sous le champ « e-mail »', 'defaut' => 'Il ne sera pas affiché.'],
        'libelle_message' => ['intitule' => 'Libellé du champ « commentaire »', 'defaut' => 'Votre commentaire *'],
        'libelle_bouton' => ['intitule' => 'Libellé du bouton d’envoi', 'defaut' => 'Publier mon commentaire'],
        'commentaires_fermes' => [
            'intitule' => 'Message quand les commentaires sont fermés',
            'defaut' => 'Les commentaires sont fermés sur cet article.',
            'long' => true,
        ],
    ];

    /**
     * Le libelle du bouton de l'appel a l'action.
     *
     * Le commentaire du module affirmait qu'il etait « fixe ». Il l'etait en
     * effet — ecrit en dur dans DEUX vues — ce qui n'est pas une raison, mais
     * la description du defaut.
     */
    public const TEXTES_DE_L_APPEL = [
        'libelle_bouton' => ['intitule' => 'Libellé du bouton', 'defaut' => 'Contacter SCI4K'],
    ];

    public function modules(): array
    {
        return [
            'banniere' => [
                'intitule' => __('Bannière'),
                'resume' => __('Étiquette, titre, accroche et image de fond.'),
                'section' => 'news.page',
                'fond' => 'banniere-actualites',
                // Ce que la page annonce d'elle-meme aux moteurs. C'etait
                // ecrit en dur en tete de la vue.
                'textes' => self::referencement(
                    'Actualités',
                    'Conseils et actualités immobilières à Abidjan : foncier, marché, gestion locative. Les actualités de SCI4K.',
                ),
            ],
            'filtres' => [
                'intitule' => __('Filtres'),
                'resume' => __('Les catégories proposées, et les libellés du formulaire de recherche.'),
                // Le formulaire de recherche n'affiche aucun en-tete : lui
                // offrir un titre et une accroche aurait fait saisir un texte
                // que rien ne rend. Il porte en revanche ses LIBELLES, qui
                // etaient ecrits en dur dans la vue.
                'section' => 'news.filters',
                'champsEntete' => [],
                'textes' => self::TEXTES_DES_FILTRES,
            ],
            'articles' => [
                'intitule' => __('Articles'),
                'resume' => __('Les articles publiés, et les textes de la page d’un article.'),
                // La grille n'a pas davantage d'en-tete de section. La PAGE
                // d'un article, elle, porte son lien de retour et ses boutons
                // de partage, tous figes jusqu'ici.
                'section' => 'news.article',
                'champsEntete' => [],
                'textes' => self::TEXTES_DE_L_ARTICLE,
            ],
            'commentaires' => [
                'intitule' => __('Commentaires'),
                'resume' => __('Ce que les lecteurs écrivent, et les textes du bloc sous l’article.'),
                // Les commentaires ne sont pas un bloc de la page Actualités
                // mais de chaque article. Ils sont ici parce que c'est ou
                // l'editeur les cherchera, et parce qu'aucun autre ecran ne
                // porte les articles.
                'section' => 'news.comments',
                'champsEntete' => [],
                'textes' => self::TEXTES_DES_COMMENTAIRES,
            ],
            'appel' => [
                'intitule' => __('Appel à l’action'),
                'resume' => __('Le bloc de contact placé sous la liste et sous chaque article.'),
                'section' => 'news.cta',
                // Le bloc n'affiche ni etiquette ni image.
                'champsEntete' => ['titre', 'chapo'],
                'textes' => self::TEXTES_DE_L_APPEL,
            ],
        ];
    }

    public string $module = 'banniere';

    public string $langueActive = 'fr';

    public array $entete = [];

    public ?string $message = null;

    protected function peutEcrire(): bool
    {
        return (bool) auth()->user()?->hasAnyRole(['administrateur', 'editeur']);
    }

    public function mount(): void
    {
        abort_unless(auth()->user()?->hasAnyRole(['administrateur', 'editeur', 'redacteur', 'lecteur']), 403);

        $this->langueActive = app()->getLocale();
        $this->charger();
    }

    public function ouvrir(string $module): void
    {
        abort_unless(array_key_exists($module, $this->modules()), 404);

        $this->module = $module;
        $this->message = null;
        $this->resetValidation();
        $this->charger();
    }

    public function moduleCourant(): array
    {
        return $this->modules()[$this->module] ?? $this->modules()['banniere'];
    }

    /**
     * Les champs d'en-tete que porte le module ouvert.
     *
     * La declaration des modules l'annonçait — « champsEntete » — sans que
     * personne ne la lise : les trois champs etaient ecrits en dur ici. Les
     * trois modules qui ne portent que des textes n'affichent aucun en-tete.
     *
     * @return list<string>
     */
    protected function champsDeLEntete(): array
    {
        return $this->moduleCourant()['champsEntete'] ?? self::CHAMPS_ENTETE;
    }

    protected function charger(): void
    {
        $this->entete = [];
        $this->textes = [];

        $slug = $this->moduleCourant()['section'] ?? null;

        if (! $slug) {
            return;
        }

        $section = ReglageDeSection::where('slug', $slug)->first();

        foreach ($this->champsDeLEntete() as $champ) {
            $this->entete[$champ.'_fr'] = (string) ($section?->{$champ.'_fr'} ?? '');
            $this->entete[$champ.'_en'] = (string) ($section?->{$champ.'_en'} ?? '');
        }

        $this->chargerLesTextes($section);
    }

    protected function rules(): array
    {
        return $this->reglesDeLEntete() + $this->reglesDesTextes();
    }

    /**
     * Intitules lisibles, pour que le message de validation ne cite pas
     * « textes.libelle_bouton_fr ».
     */
    protected function validationAttributes(): array
    {
        return $this->intitulesDesTextes();
    }

    public function enregistrer(): void
    {
        abort_unless($this->peutEcrire(), 403);

        $description = $this->moduleCourant();
        $slug = $description['section'] ?? null;
        abort_unless($slug !== null, 404);

        $this->validate();

        $section = ReglageDeSection::firstOrNew(['slug' => $slug]);
        // Les cles viennent du navigateur : seules celles que
        // l'ecran declare sont ecrites. Voir le trait.
        $section->fill($this->enteteFiltree());

        // poserLesTextes() ne retient que les cles declarees par le module et
        // POSE sans enregistrer : le save() qui suit les porte en base.
        $this->poserLesTextes($section);

        $section->save();

        $this->charger();
        $this->message = __('Module enregistré.');
        $this->dispatch('toast',
            message: __(':module enregistré.', ['module' => $description['intitule']]),
            variant: 'success');
    }

    /**
     * L'ecran complet a embarquer dans le module ouvert, s'il y en a un.
     *
     * @return array{composant: string, intitule: string, parametres?: array}|null
     */
    public function ecranEmbarque(): ?array
    {
        return [
            'filtres' => ['composant' => 'admin.categorie-ensemble', 'intitule' => __("Catégories d'articles")],
            'articles' => ['composant' => 'admin.article-liste', 'intitule' => __('Articles')],
            'commentaires' => ['composant' => 'admin.commentaire-liste', 'intitule' => __('Commentaires')],
        ][$this->module] ?? null;
    }


    public function render(): View
    {
        return view('livewire.admin.page-actualites', [
            'modules' => $this->modules(),
            'description' => $this->moduleCourant(),
            'ecranEmbarque' => $this->ecranEmbarque(),
            'images' => $this->imagesDuModule(),
            'peutEcrire' => $this->peutEcrire(),
        ])->title(__('Page Actualités'));
    }
}

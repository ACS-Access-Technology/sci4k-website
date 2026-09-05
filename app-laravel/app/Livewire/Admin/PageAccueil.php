<?php

namespace App\Livewire\Admin;

use App\Models\CommuneDuBandeau;
use App\Models\Encart;
use App\Livewire\Concerns\PorteDesImagesDeFond;
use App\Livewire\Concerns\PorteDesTextesDeBloc;
use App\Livewire\Concerns\PorteUnEnteteDeSection;
use App\Models\ReglageDeSection;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * L'accueil du site, gere depuis un seul ecran.
 *
 * PREMIER ECRAN DE LA REFONTE : une page d'administration par page publique,
 * au lieu d'un rangement par type de contenu. Ce que montre l'accueil venait
 * jusqu'ici de HUIT ecrans differents — en-tetes de section, chiffres cles,
 * banderole des communes, services, annonces & actions, temoignages,
 * partenaires, images de fond. Pour changer le titre du hero puis son image,
 * il fallait en traverser deux, et rien n'indiquait lequel.
 *
 * Les anciens ecrans restent en place et continuent de fonctionner : les deux
 * lisent et ecrivent LES MEMES tables. Aucune donnee n'est dupliquee, aucune
 * migration n'est necessaire, et une modification faite d'un cote se voit de
 * l'autre.
 *
 * L'ORDRE DES MODULES N'EST PAS MODIFIABLE, et l'ecran ne propose donc aucune
 * poignee de deplacement. Il est fixe dans le gabarit public accueil.blade.php
 * — offrir un glisser-deposer qui ne changerait rien serait un ecran menteur
 * de plus. Le jour ou l'ordre viendra de la base, la poignee aura un sens.
 */
#[Layout('layouts.app')]
class PageAccueil extends Component
{
    use PorteDesImagesDeFond;
    use PorteDesTextesDeBloc;
    use PorteUnEnteteDeSection;

    /** Les textes du hero qui ne sont ni un titre ni un bouton. */
    public const TEXTES_DU_HERO = [
        'aria_defilement' => ['intitule' => 'Flèche de défilement — description', 'defaut' => 'Faire défiler vers le contenu'],
        'libelle_defilement' => ['intitule' => 'Mot affiché sous la flèche', 'defaut' => 'Défilez'],
    ];

    /** Le lien de chaque carte d'article. */
    public const TEXTES_DES_ARTICLES = [
        'libelle_lien' => ['intitule' => 'Lien sous chaque article', 'defaut' => "Lire l'article"],
    ];

    /** La note d'un avis, lue par les lecteurs d'ecran. */
    public const TEXTES_DES_TEMOIGNAGES = [
        'aria_note' => ['intitule' => 'Note d’un avis (:note sera remplacé)', 'defaut' => ':note sur 5'],
    ];

    /** La bulle d'aide d'un logo de partenaire. */
    public const TEXTES_DES_PARTENAIRES = [
        'titre_lien' => ['intitule' => 'Bulle d’aide d’un logo (:nom sera remplacé)', 'defaut' => 'Ouvrir le site de :nom'],
    ];

    /**
     * Les huit modules de l'accueil, dans l'ordre ou le visiteur les voit.
     *
     * Chaque entree decrit ce que le module pilote, et d'ou vient la donnee —
     * l'ecran l'affiche, pour qu'un editeur sache ce qu'il touche.
     *
     * @return array<string, array<string, mixed>>
     */
    public function modules(): array
    {
        return [
            'hero' => [
                'intitule' => __('Hero'),
                'resume' => __('Étiquette, titre, accroche, boutons, chiffres clés et image de fond.'),
                'section' => 'home.hero',
                'fond' => 'accueil-hero',
                'ancre' => '#accueil',
                // Les textes propres au hero, ET ce que la page annonce
                // d'elle-meme aux moteurs — deux lignes ecrites en dur en tete
                // de la vue. Une seule cle « textes » par module : deux
                // ecraseraient l'une l'autre en silence.
                'textes' => self::TEXTES_DU_HERO + self::referencement(
                    'Votre propriété, notre priorité',
                    'Société Civile Immobilière à Abidjan : achat, vente, location, construction et gestion de patrimoine immobilier.',
                ),
            ],
            'bandeau' => [
                'intitule' => __('Bande déroulante'),
                'resume' => __('Communes défilantes sous le hero, et leur apparence.'),
                'section' => CommuneDuBandeau::SECTION,
                // Ni etiquette ni accroche : la banderole n'affiche que les
                // communes. Les proposer aurait ete offrir deux champs dont
                // rien n'aurait jamais montre le contenu.
                'champsEntete' => [],
                'ancre' => null,
            ],
            'services' => [
                'intitule' => __('Services'),
                'resume' => __('En-tête du bloc et services mis en avant.'),
                'section' => 'home.services',
                'ancre' => '#services',
            ],
            'annonce' => [
                'intitule' => __('Annonce'),
                'resume' => __('Encart promotionnel affiché après les services.'),
                'encart' => 'accueil.annonce',
                'ancre' => '#encart-accueil',
            ],
            'cta' => [
                'intitule' => __("Bandeau d'appel à l'action"),
                'resume' => __('Bloc pleine largeur avant les actualités.'),
                'encart' => 'accueil',
                'fond' => 'cta',
                'ancre' => null,
            ],
            'articles' => [
                'intitule' => __('Articles'),
                'resume' => __('En-tête du bloc. Les trois articles affichés sont les plus récents publiés.'),
                'section' => 'home.articles',
                'ancre' => '#articles',
                'textes' => self::TEXTES_DES_ARTICLES,
            ],
            'temoignages' => [
                'intitule' => __('Avis clients'),
                'resume' => __('En-tête du bloc, avis affichés et image de fond.'),
                'section' => 'home.testimonials',
                'fond' => 'temoignages',
                'ancre' => null,
                'textes' => self::TEXTES_DES_TEMOIGNAGES,
            ],
            'partenaires' => [
                'intitule' => __('Partenaires'),
                'resume' => __('En-tête du bloc et logos affichés.'),
                'section' => 'home.partners',
                'ancre' => null,
                'textes' => self::TEXTES_DES_PARTENAIRES,
            ],
        ];
    }

    /** Module ouvert. Contraint a la liste ci-dessus. */
    public string $module = 'hero';

    /** Langue du CONTENU saisi, sans rapport avec celle de l'interface. */
    public string $langueActive = 'fr';

    /** Valeurs de l'en-tete de section du module ouvert. */
    public array $entete = [];

    /** Reglages d'apparence de la bande deroulante. */
    public array $bandeau = [];

    /**
     * Les cles d'apparence de la banderole, et celles des deux boutons du hero.
     *
     * Elles sont ecrites ICI, a cote du filtre qui s'en sert : une liste
     * entretenue loin de son filtre finit par diverger, et c'est la divergence
     * qui rouvre le trou.
     *
     * @var list<string>
     */
    public const CLES_DU_BANDEAU = ['fond', 'separateur', 'casse'];

    /** @var list<string> */
    public const CLES_DES_BOUTONS = [
        'bouton1_libelle_fr', 'bouton1_libelle_en', 'bouton1_cible',
        'bouton2_libelle_fr', 'bouton2_libelle_en', 'bouton2_cible',
    ];

    /** Libelles et cibles des deux boutons du hero. */
    public array $boutons = [];

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

    /** Description du module ouvert. */
    public function moduleCourant(): array
    {
        return $this->modules()[$this->module] ?? $this->modules()['hero'];
    }

    /**
     * Recharge les champs du module ouvert depuis la base.
     *
     * Les valeurs sont relues a chaque changement de module plutot que toutes
     * chargees d'un coup : huit modules, c'est huit fois plus d'etat expose au
     * navigateur, et autant de champs qu'une validation devrait couvrir.
     */
    protected function charger(): void
    {
        $description = $this->moduleCourant();
        $this->entete = [];
        $this->bandeau = [];
        $this->boutons = [];
        $this->textes = [];

        if ($slug = $description['section'] ?? null) {
            $section = ReglageDeSection::where('slug', $slug)->first();

            $this->chargerLesTextes($section);

            foreach (['etiquette', 'titre', 'chapo'] as $champ) {
                $this->entete[$champ.'_fr'] = (string) ($section?->{$champ.'_fr'} ?? '');
                $this->entete[$champ.'_en'] = (string) ($section?->{$champ.'_en'} ?? '');
            }

            if ($this->module === 'hero') {
                foreach (['bouton1', 'bouton2'] as $bouton) {
                    $this->boutons[$bouton.'_libelle_fr'] = (string) ($section?->option($bouton.'_libelle_fr') ?? '');
                    $this->boutons[$bouton.'_libelle_en'] = (string) ($section?->option($bouton.'_libelle_en') ?? '');
                    $this->boutons[$bouton.'_cible'] = (string) ($section?->option($bouton.'_cible') ?? '');
                }
            }

            if ($this->module === 'bandeau') {
                $this->bandeau = [
                    'fond' => (string) ($section?->option('fond', 'sombre') ?? 'sombre'),
                    'separateur' => (string) ($section?->option('separateur', '·') ?? '·'),
                    'casse' => (string) ($section?->option('casse', 'majuscules') ?? 'majuscules'),
                ];
            }
        }

        // Rien a charger pour un encart : son propre formulaire est embarque
        // dans le module, et c'est lui qui lit et ecrit ses champs.
    }

    protected function rules(): array
    {
        $regles = [];

        foreach (['etiquette', 'titre', 'chapo'] as $champ) {
            $regles['entete.'.$champ.'_fr'] = ['nullable', 'string', 'max:500'];
            $regles['entete.'.$champ.'_en'] = ['nullable', 'string', 'max:500'];
        }

        foreach (['bouton1', 'bouton2'] as $bouton) {
            $regles['boutons.'.$bouton.'_libelle_fr'] = ['nullable', 'string', 'max:80'];
            $regles['boutons.'.$bouton.'_libelle_en'] = ['nullable', 'string', 'max:80'];
            $regles['boutons.'.$bouton.'_cible'] = ['nullable', 'string', 'max:190'];
        }

        $regles['bandeau.fond'] = ['nullable', 'in:sombre,clair'];
        $regles['bandeau.separateur'] = ['nullable', 'string', 'max:5'];
        $regles['bandeau.casse'] = ['nullable', 'in:majuscules,normale'];

        return $regles + $this->reglesDesTextes();
    }

    /**
     * Intitules lisibles, pour que le message de validation ne cite pas
     * « textes.libelle_lien_fr ».
     */
    protected function validationAttributes(): array
    {
        return $this->intitulesDesTextes();
    }

    public function enregistrer(): void
    {
        abort_unless($this->peutEcrire(), 403);

        $this->validate();

        $description = $this->moduleCourant();

        if ($slug = $description['section'] ?? null) {
            $section = ReglageDeSection::firstOrNew(['slug' => $slug]);
            // Les cles viennent du navigateur : seules celles que
        // l'ecran declare sont ecrites. Voir le trait.
        $section->fill($this->enteteFiltree());

            // poserLesTextes() ne retient que les cles declarees par le module
            // et POSE sans enregistrer : le save() qui suit les porte en base.
            $this->poserLesTextes($section);

            $section->save();

            // poserOptions() ne fait que POSER les valeurs sur le modele : il
            // n'enregistre pas. Sans ce second save(), l'apparence de la
            // banderole se perdait sans un mot.
            //
            // `$bandeau` et `$boutons` sont des proprietes publiques, dont le
            // navigateur fixe le contenu CLES COMPRISES : seules celles que
            // l'ecran declare sont versees dans le sac JSON.
            if ($this->module === 'bandeau') {
                $declarees = $this->optionsFiltrees($this->bandeau, self::CLES_DU_BANDEAU);

                if ($declarees !== []) {
                    $section->poserOptions($declarees);
                    $section->save();
                }
            }

            if ($this->module === 'hero') {
                $declarees = $this->optionsFiltrees($this->boutons, self::CLES_DES_BOUTONS);

                if ($declarees !== []) {
                    $section->poserOptions($declarees);
                    $section->save();
                }
            }
        }

        $this->charger();
        $this->message = __('Module enregistré.');
        $this->dispatch('toast', message: __(':module enregistré.', ['module' => $description['intitule']]), variant: 'success');
    }

    /** Le fond du module ouvert, s'il en a un. */

    /**
     * L'ecran complet a embarquer dans le module ouvert, s'il y en a un.
     *
     * Chaque module qui pilote une collection affiche l'ancien ecran ENTIER —
     * statistiques, recherche, reordonnancement, actions — plutot qu'un
     * resume renvoyant ailleurs. C'est la condition pour que les ecrans par
     * type de contenu puissent disparaitre sans rien perdre.
     *
     * @return array{composant: string, intitule: string}|null
     */
    public function ecranEmbarque(): ?array
    {
        // Les deux encarts embarquent leur PROPRE formulaire, et non une copie
        // de leurs champs : lui seul sait televerser une image, la remplacer et
        // faire le menage de l'ancienne. La recopier ici avait donne un module
        // d'annonce sans visuel.
        if ($slug = $this->moduleCourant()['encart'] ?? null) {
            return [
                'composant' => 'admin.encart-formulaire',
                'intitule' => $this->moduleCourant()['intitule'],
                'parametres' => [
                    'element' => Encart::firstOrCreate(['slug' => $slug]),
                    'embarque' => true,
                ],
            ];
        }

        return [
            'hero' => ['composant' => 'admin.chiffre-cle-ensemble', 'intitule' => __('Chiffres clés')],
            'bandeau' => ['composant' => 'admin.commune-bandeau-ensemble', 'intitule' => __('Communes défilantes')],
            'services' => ['composant' => 'admin.service-liste', 'intitule' => __('Services')],
            'temoignages' => ['composant' => 'admin.temoignage-liste', 'intitule' => __('Avis clients')],
            'partenaires' => ['composant' => 'admin.partenaire-liste', 'intitule' => __('Partenaires')],
        ][$this->module] ?? null;
    }

    public function render(): View
    {
        return view('livewire.admin.page-accueil', [
            'modules' => $this->modules(),
            'description' => $this->moduleCourant(),
            'ecranEmbarque' => $this->ecranEmbarque(),
            'images' => $this->imagesDuModule(),
            // Les trois articles que l'accueil affichera : ils ne se choisissent
            // pas, ce sont les plus recents publies. L'ecran les montre pour
            // que l'editeur sache ce qui sortira, sans laisser croire qu'il
            // peut les selectionner ici.
            'articles' => $this->module === 'articles'
                ? \App\Models\Article::publies()->latest('date_publication')->limit(3)->get()
                : collect(),
            'peutEcrire' => $this->peutEcrire(),
        ])->title(__("Page d'accueil"));
    }
}

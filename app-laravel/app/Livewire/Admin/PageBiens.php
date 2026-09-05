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
 * La page Biens immobiliers, geree depuis un seul ecran.
 *
 * Troisieme ecran de la refonte, meme principe que l'accueil et la
 * presentation : un module par bloc de la page publique, dans l'ordre ou le
 * visiteur les voit, et l'ancien ecran ENTIER embarque partout ou un module
 * pilote une collection.
 *
 * Trois modules seulement, la page en ayant trois : la banniere, le panneau de
 * filtres, et le catalogue lui-meme.
 *
 * Le module Filtres embarque l'ecran des referentiels. Ce n'est pas un
 * rapprochement de circonstance : les cinq familles qu'il edite SONT le
 * vocabulaire des filtres de /biens et des fiches de bien — types, zones,
 * tranches de pieces, tranches de surface, statuts juridiques. Les modifier
 * ailleurs obligeait a deviner ou l'on agissait.
 */
#[Layout('layouts.app')]
class PageBiens extends Component
{
    use PorteDesImagesDeFond;
    use PorteDesTextesDeBloc;
    use PorteUnEnteteDeSection;

    /**
     * Les libelles du panneau de recherche.
     *
     * Ils etaient ecrits en dur dans la vue et traduits par __() : aucun ecran
     * ne les exposait. Le vocabulaire des filtres — types, zones, tranches —
     * etait deja modifiable depuis les referentiels ; les INTITULES au-dessus
     * de chaque liste, non.
     */
    public const TEXTES_DES_FILTRES = [
        'libelle_type' => ['intitule' => 'Libellé du filtre « type »', 'defaut' => 'Type de bien'],
        'choix_tous_types' => ['intitule' => 'Choix « tous les types »', 'defaut' => 'Tous les types'],
        'libelle_zone' => ['intitule' => 'Libellé du filtre « localité »', 'defaut' => 'Localité'],
        'choix_toutes_zones' => ['intitule' => 'Choix « toutes les zones »', 'defaut' => 'Toutes les zones'],
        'libelle_pieces' => ['intitule' => 'Libellé du filtre « pièces »', 'defaut' => 'Nombre de Pièces'],
        'choix_toutes_pieces' => ['intitule' => 'Choix « toutes pièces »', 'defaut' => 'Toutes pièces'],
        'libelle_surface' => ['intitule' => 'Libellé du filtre « surface »', 'defaut' => 'Surface (m²)'],
        'choix_toutes_surfaces' => ['intitule' => 'Choix « toutes surfaces »', 'defaut' => 'Toutes surfaces'],
        'libelle_bouton' => ['intitule' => 'Libellé du bouton de recherche', 'defaut' => 'Rechercher le bien idéal'],
        'onglet_tous' => ['intitule' => 'Onglet « tous »', 'defaut' => 'Tous'],
    ];

    /**
     * Les textes de la grille de resultats.
     *
     * « Fermer » n'y figure PAS : il ferme aussi la fenetre d'un service sur la
     * page Services. Il est dit une seule fois, sur l'ecran « Menus », avec le
     * reste de l'habillage du site.
     */
    public const TEXTES_DU_CATALOGUE = [
        'pastille_vendu' => ['intitule' => 'Pastille « vendu »', 'defaut' => 'Vendu'],
        'libelle_fiche' => ['intitule' => 'Lien vers la fiche', 'defaut' => 'Voir la fiche'],
        'aucun_resultat' => [
            'intitule' => 'Message quand aucun bien ne correspond',
            'defaut' => 'Aucun bien ne correspond à votre recherche.',
            'long' => true,
        ],
        'titre_description' => ['intitule' => 'Titre du bloc de description', 'defaut' => 'Description intégrale du bien'],
    ];

    /**
     * Les libelles des caracteristiques d'un bien.
     *
     * Ils servent DEUX FOIS : dans la fenetre qui s'ouvre depuis le catalogue,
     * et sur la fiche complete. Les declarer une seule fois est deliberé — les
     * dire deux fois aurait laisse les deux versions diverger, et l'editeur
     * aurait corrige l'une en croyant avoir corrige les deux.
     */
    public const TEXTES_DE_LA_FICHE = [
        'libelle_type' => ['intitule' => 'Libellé « type »', 'defaut' => 'Type'],
        'libelle_surface' => ['intitule' => 'Libellé « surface »', 'defaut' => 'Surface'],
        'libelle_pieces' => ['intitule' => 'Libellé « pièces »', 'defaut' => 'Pièces'],
        'libelle_chambres' => ['intitule' => 'Libellé « chambres »', 'defaut' => 'Chambres'],
        'libelle_salles_eau' => ['intitule' => 'Libellé « salles d’eau »', 'defaut' => "Salles d'eau"],
        'libelle_statut_juridique' => ['intitule' => 'Libellé « statut juridique »', 'defaut' => 'Statut juridique'],
        'libelle_numero_titre' => ['intitule' => 'Libellé « numéro de titre »', 'defaut' => 'Numéro de titre'],
        'titre_equipements' => ['intitule' => 'Titre du bloc « équipements »', 'defaut' => 'Équipements'],
        'lien_retour' => ['intitule' => 'Lien de retour au catalogue', 'defaut' => 'Retour au catalogue'],
        'titre_meme_zone' => ['intitule' => 'Titre du bloc « biens voisins »', 'defaut' => 'Dans la même zone'],
    ];

    /**
     * Les textes du formulaire de demande de visite.
     *
     * Lui aussi apparait deux fois — dans la fenetre du catalogue et sur la
     * fiche — et pour la meme raison il n'est declare qu'une fois.
     */
    public const TEXTES_DE_LA_VISITE = [
        'titre' => ['intitule' => 'Titre du bloc', 'defaut' => 'Demander une visite'],
        'accroche' => [
            'intitule' => 'Phrase d’introduction',
            'defaut' => 'Laissez vos coordonnées : un conseiller vous rappelle pour convenir d’un créneau.',
            'long' => true,
        ],
        'libelle_nom' => ['intitule' => 'Libellé du champ « nom »', 'defaut' => 'Nom complet'],
        'libelle_telephone' => ['intitule' => 'Libellé du champ « téléphone »', 'defaut' => 'Téléphone'],
        'libelle_email' => ['intitule' => 'Libellé du champ « e-mail »', 'defaut' => 'E-mail'],
        'libelle_creneau' => ['intitule' => 'Libellé du champ « créneau »', 'defaut' => 'Créneau souhaité'],
        'libelle_precisions' => ['intitule' => 'Libellé du champ « précisions »', 'defaut' => 'Précisions'],
        'libelle_bouton' => ['intitule' => 'Libellé du bouton', 'defaut' => 'Envoyer ma demande'],
        'confirmation' => [
            'intitule' => 'Message de confirmation',
            'defaut' => 'Votre demande est enregistrée. Un conseiller vous rappelle sous 24 heures ouvrées.',
            'long' => true,
        ],
    ];

    /**
     * Les cinq modules de la page, dans l'ordre du site.
     *
     * @return array<string, array<string, mixed>>
     */
    public function modules(): array
    {
        return [
            'banniere' => [
                'intitule' => __('Bannière'),
                'resume' => __('Étiquette, titre, accroche et image de fond.'),
                'section' => 'biens.page',
                'fond' => 'banniere-biens',
            ],
            'filtres' => [
                'intitule' => __('Filtres'),
                'resume' => __('Titre du panneau et vocabulaire des filtres.'),
                'section' => 'biens.filters',
                // Ni etiquette ni accroche : le panneau n'affiche que son
                // titre. Les proposer aurait ete offrir deux champs dont rien
                // n'aurait jamais montre le contenu.
                'champsEntete' => ['titre'],
                'textes' => self::TEXTES_DES_FILTRES,
            ],
            'catalogue' => [
                'intitule' => __('Catalogue'),
                'resume' => __('Les biens publiés, et les textes de la grille de résultats.'),
                // Le catalogue n'affichait aucun en-tete et n'avait donc pas de
                // section. Il lui en faut une desormais : c'est la que vivent
                // les textes de la grille.
                'section' => 'biens.catalog',
                'champsEntete' => [],
                'textes' => self::TEXTES_DU_CATALOGUE,
            ],
            'fiche' => [
                'intitule' => __('Fiche d’un bien'),
                'resume' => __('Libellés des caractéristiques, communs à la fenêtre du catalogue et à la fiche.'),
                'section' => 'biens.detail',
                'champsEntete' => [],
                'textes' => self::TEXTES_DE_LA_FICHE,
            ],
            'visite' => [
                'intitule' => __('Demande de visite'),
                'resume' => __('Tous les textes du formulaire de rendez-vous.'),
                'section' => 'biens.visit',
                'champsEntete' => [],
                'textes' => self::TEXTES_DE_LA_VISITE,
            ],
        ];
    }

    /** Module ouvert. Contraint a la liste ci-dessus. */
    public string $module = 'banniere';

    /** Langue du CONTENU saisi, sans rapport avec celle de l'interface. */
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
     * La declaration des modules l'annonçait deja — « champsEntete » — sans que
     * personne ne la lise : les trois champs etaient ecrits en dur ici. Le
     * panneau de filtres n'affiche que son titre, et les trois modules de
     * textes n'affichent aucun en-tete du tout.
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

        $slug = $this->moduleCourant()['section'] ?? null;
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
            message: __(':module enregistré.', ['module' => $this->moduleCourant()['intitule']]),
            variant: 'success');
    }

    /**
     * L'ecran complet a embarquer dans le module ouvert, s'il y en a un.
     *
     * @return array{composant: string, intitule: string}|null
     */
    public function ecranEmbarque(): ?array
    {
        return [
            'filtres' => ['composant' => 'admin.referentiels', 'intitule' => __('Vocabulaire des filtres')],
            'catalogue' => ['composant' => 'admin.bien-liste', 'intitule' => __('Biens')],
        ][$this->module] ?? null;
    }


    public function render(): View
    {
        return view('livewire.admin.page-biens', [
            'modules' => $this->modules(),
            'description' => $this->moduleCourant(),
            'ecranEmbarque' => $this->ecranEmbarque(),
            'images' => $this->imagesDuModule(),
            'peutEcrire' => $this->peutEcrire(),
        ])->title(__('Page Biens immobiliers'));
    }
}

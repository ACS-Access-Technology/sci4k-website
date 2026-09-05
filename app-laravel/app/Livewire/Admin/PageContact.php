<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\PorteDesTextesDeBloc;
use App\Livewire\Concerns\PorteDesImagesDeFond;
use App\Livewire\Concerns\PorteUnEnteteDeSection;
use App\Models\Parametre;
use App\Models\ReglageDeSection;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * La page Contact, geree depuis un seul ecran.
 *
 * Dernier ecran de la refonte, meme principe que les cinq precedents : un
 * module par bloc de la page publique, dans l'ordre ou le visiteur les voit.
 *
 * Deux particularites.
 *
 * Les coordonnees et les coordonnees GPS ne sont pas des sections mais des
 * REGLAGES — les memes cles Parametre qu'edite l'ecran « Configuration ». Leur
 * declaration est reprise de cet ecran plutot que recopiee : intitules, types
 * et regles restent dits a un seul endroit. Leur ecriture reste reservee aux
 * administrateurs, comme la-bas : les ouvrir aux editeurs depuis cet ecran
 * aurait elargi un acces par la bande.
 *
 * Les messages recus ne sont pas un bloc de la page, mais ce qu'elle produit :
 * le formulaire les enregistre avant d'ouvrir WhatsApp. Ils ont donc leur
 * module, en dernier — sans quoi il faudrait quitter l'ecran pour lire ce que
 * la page a rapporte.
 */
#[Layout('layouts.app')]
class PageContact extends Component
{
    use PorteDesImagesDeFond;
    use PorteUnEnteteDeSection;
    use PorteDesTextesDeBloc;

    /**
     * Les textes du formulaire de contact.
     *
     * Ils etaient ecrits en dur dans la vue et traduits par __() : aucun ecran
     * ne les exposait. L'ordre est celui du bloc sur le site.
     *
     * `sujets` est une liste, une par ligne : la valeur choisie est recopiee
     * telle quelle dans le message WhatsApp et dans le message enregistre, si
     * bien que l'intitule EST la valeur. Une ligne par sujet suit la forme
     * deja retenue pour l'adresse postale et les horaires.
     */
    public const TEXTES_DU_FORMULAIRE = [
        'libelle_nom' => ['intitule' => 'Libellé du champ « nom »', 'defaut' => 'Nom complet *'],
        'exemple_nom' => ['intitule' => 'Exemple dans le champ « nom »', 'defaut' => 'Ex: Jean Kouassi'],
        'libelle_telephone' => ['intitule' => 'Libellé du champ « téléphone »', 'defaut' => 'Téléphone *'],
        'exemple_telephone' => ['intitule' => 'Exemple dans le champ « téléphone »', 'defaut' => '+225 07 00 00 00 00'],
        'libelle_email' => ['intitule' => 'Libellé du champ « email »', 'defaut' => 'Adresse Email *'],
        'exemple_email' => ['intitule' => 'Exemple dans le champ « email »', 'defaut' => 'j.kouassi@email.com'],
        'libelle_sujet' => ['intitule' => 'Libellé du champ « sujet »', 'defaut' => 'Sujet de votre demande'],
        'sujets' => [
            'intitule' => 'Sujets proposés (un par ligne)',
            'defaut' => "Achat de bien / terrain\nVente / Estimation de bien\nLocation d'un bien\nGestion locative & Administration\nProjet de Construction\nQuestion Foncier / ACD\nAutre demande",
            'long' => true,
        ],
        'libelle_message' => ['intitule' => 'Libellé du champ « message »', 'defaut' => 'Votre message *'],
        'exemple_message' => [
            'intitule' => 'Exemple dans le champ « message »',
            'defaut' => 'Précisez les détails de votre projet, le quartier souhaité, le budget approximatif...',
            'long' => true,
        ],
        'libelle_bouton' => ['intitule' => 'Libellé du bouton', 'defaut' => 'Envoyer mon message'],
        'confirmation' => [
            'intitule' => 'Message de confirmation',
            'defaut' => "Votre message est prêt : la conversation WhatsApp s'ouvre dans un nouvel onglet. Appuyez sur Envoyer pour le transmettre à SCI4K.",
            'long' => true,
        ],
    ];

    /**
     * Les intitules du cadre de coordonnees.
     *
     * Ils etaient ecrits en dur dans la vue et traduits par __() : les
     * VALEURS — adresse, telephone, horaires — etaient bien modifiables depuis
     * la configuration, mais pas les titres qui les coiffent.
     */
    public const TEXTES_DES_COORDONNEES = [
        'titre_adresse' => ['intitule' => 'Titre au-dessus de l’adresse', 'defaut' => 'Siège Social'],
        'titre_telephone' => ['intitule' => 'Titre au-dessus du téléphone', 'defaut' => 'Téléphone & WhatsApp'],
        'titre_email' => ['intitule' => 'Titre au-dessus de l’e-mail', 'defaut' => 'Email'],
        'titre_horaires' => ['intitule' => 'Titre au-dessus des horaires', 'defaut' => "Horaires d'ouverture"],
    ];

    /** Les textes du bloc de la carte. */
    public const TEXTES_DE_LA_CARTE = [
        'libelle_lien' => ['intitule' => 'Lien vers Google Maps', 'defaut' => 'Ouvrir dans Google Maps'],
        'titre_cadre' => ['intitule' => 'Description de la carte (:nom sera remplacé)', 'defaut' => 'Localisation de :nom'],
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
                'section' => 'contact.page',
                'fond' => 'banniere-contact',
                // Ce que la page annonce d'elle-meme aux moteurs. C'etait
                // ecrit en dur en tete de la vue.
                'textes' => self::referencement(
                    'Contact',
                    'Contactez SCI4K à Abidjan : achat, vente, location, construction et gestion de patrimoine immobilier.',
                ),
            ],
            'formulaire' => [
                'intitule' => __('Formulaire'),
                'resume' => __('En-tête du bloc, libellés des champs et sujets proposés.'),
                'section' => 'contact.form',
                // Le bloc n'affiche pas d'etiquette au-dessus de son titre.
                'champsEntete' => ['titre', 'chapo'],
                'textes' => self::TEXTES_DU_FORMULAIRE,
            ],
            'coordonnees' => [
                'intitule' => __('Coordonnées'),
                'resume' => __('Adresse, téléphone, e-mail et horaires affichés à côté du formulaire.'),
                // Le cadre n'affiche pas d'en-tete, mais il porte les
                // INTITULES au-dessus de chaque coordonnee : il lui faut donc
                // une section, et c'est son absence qui les laissait figes.
                'section' => 'contact.info',
                'champsEntete' => [],
                'reglages' => ['adresse_postale', 'telephone', 'email_public', 'horaires'],
                'textes' => self::TEXTES_DES_COORDONNEES,
                // Le fond du cadre : .info-box le pose sous son voile sombre.
                'fond' => 'info-box',
            ],
            'carte' => [
                'intitule' => __('Carte'),
                'resume' => __('Titre du bloc et point affiché sur la carte.'),
                'section' => 'contact.map',
                // La carte n'affiche que son titre : ni etiquette ni accroche
                // n'ont d'emplacement sur le site.
                'champsEntete' => ['titre'],
                'reglages' => ['coordonnees_carte'],
                'textes' => self::TEXTES_DE_LA_CARTE,
            ],
            'messages' => [
                'intitule' => __('Messages reçus'),
                'resume' => __('Ce que le formulaire a rapporté.'),
                'section' => null,
            ],
        ];
    }

    public string $module = 'banniere';

    public string $langueActive = 'fr';

    public array $entete = [];

    /** Valeurs des reglages du module ouvert, par cle Parametre. */
    public array $reglages = [];

    public ?string $message = null;

    protected function peutEcrire(): bool
    {
        return (bool) auth()->user()?->hasAnyRole(['administrateur', 'editeur']);
    }

    /**
     * Les reglages Parametre restent reserves aux administrateurs.
     *
     * L'ecran « Configuration » en fait autant, et pour la meme raison : ces
     * cles sortent du contenu editorial. Les ouvrir ici aurait elargi un acces
     * par la bande, sans que personne ne l'ait decide.
     */
    protected function peutReglerLeSite(): bool
    {
        return (bool) auth()->user()?->hasRole('administrateur');
    }

    /**
     * La declaration des reglages, reprise de l'ecran « Configuration ».
     *
     * Intitules, types et regles y sont dits une fois : les recopier ici
     * aurait fait deux verites pour une meme cle, qui auraient diverge.
     *
     * @return array<string, array<string, mixed>>
     */
    public function reglagesDuModule(): array
    {
        $cles = $this->moduleCourant()['reglages'] ?? [];

        if ($cles === []) {
            return [];
        }

        $declaration = (new Configuration)->onglets()['contact']['champs'] ?? [];

        return array_intersect_key($declaration, array_flip($cles));
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
     * personne ne la lise : les trois champs etaient ecrits en dur plus bas.
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
        $this->reglages = [];

        foreach (array_keys($this->reglagesDuModule()) as $cle) {
            $this->reglages[$cle] = (string) (Parametre::lire($cle, '') ?? '');
        }

        $slug = $this->moduleCourant()['section'] ?? null;
        $section = $slug ? ReglageDeSection::where('slug', $slug)->first() : null;

        $this->chargerLesTextes($section);

        if (! $slug) {
            return;
        }

        foreach ($this->champsDeLEntete() as $champ) {
            $this->entete[$champ.'_fr'] = (string) ($section?->{$champ.'_fr'} ?? '');
            $this->entete[$champ.'_en'] = (string) ($section?->{$champ.'_en'} ?? '');
        }
    }

    protected function rules(): array
    {
        $regles = $this->reglesDeLEntete();

        foreach ($this->reglagesDuModule() as $cle => $decrit) {
            $regles['reglages.'.$cle] = $decrit['regles'] ?? ['nullable', 'string', 'max:300'];
        }

        return $regles + $this->reglesDesTextes();
    }

    protected function validationAttributes(): array
    {
        $intitules = $this->intitulesDesTextes();

        foreach ($this->reglagesDuModule() as $cle => $decrit) {
            $intitules['reglages.'.$cle] = $decrit['intitule'];
        }

        return $intitules;
    }

    public function enregistrer(): void
    {
        abort_unless($this->peutEcrire(), 403);

        $description = $this->moduleCourant();
        $slug = $description['section'] ?? null;
        $reglages = $this->reglagesDuModule();

        // Un module qui ne porte ni section ni reglage n'a rien a enregistrer.
        abort_unless($slug !== null || $reglages !== [], 404);

        // Les reglages touchent a la configuration du site, pas au contenu :
        // le controle est refait ici, la ou l'ecriture a lieu.
        abort_unless($reglages === [] || $this->peutReglerLeSite(), 403);

        $this->validate();

        if ($slug !== null) {
            $section = ReglageDeSection::firstOrNew(['slug' => $slug]);
            // Les cles viennent du navigateur : seules celles que
        // l'ecran declare sont ecrites. Voir le trait.
        $section->fill($this->enteteFiltree());

            // poserLesTextes() ne retient que les cles declarees par le module
            // et POSE sans enregistrer : le save() qui suit les porte en base.
            $this->poserLesTextes($section);

            $section->save();
        }

        foreach (array_keys($reglages) as $cle) {
            Parametre::poser($cle, $this->reglages[$cle] ?? '', 'contact');
        }

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
            'messages' => ['composant' => 'admin.message-liste', 'intitule' => __('Messages de contact')],
        ][$this->module] ?? null;
    }


    public function render(): View
    {
        return view('livewire.admin.page-contact', [
            'modules' => $this->modules(),
            'description' => $this->moduleCourant(),
            'ecranEmbarque' => $this->ecranEmbarque(),
            'images' => $this->imagesDuModule(),
            'peutEcrire' => $this->peutEcrire(),
            'reglagesDuModule' => $this->reglagesDuModule(),
            'peutReglerLeSite' => $this->peutReglerLeSite(),
        ])->title(__('Page Contact'));
    }
}

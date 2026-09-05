<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\PorteDesTextesDeBloc;
use App\Models\EntreeDeMenu;
use App\Models\Parametre;
use App\Models\ReglageDeSection;
use App\Models\Service;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Navigation de l'en-tete et colonnes du pied de page.
 *
 * Trois menus editables sur un seul ecran, comme sur la maquette. La structure
 * est celle des referentiels — plusieurs collections, chacune avec son rang —
 * et pour la meme raison : EditionGroupee ne porte qu'une collection.
 */
#[Layout('layouts.app')]
class Menus extends Component
{
    use PorteDesTextesDeBloc;

    /**
     * La section ou vivent les textes de l'en-tete, du pied et des boutons
     * flottants. Ils apparaissent sur TOUTES les pages du site.
     */
    public const SECTION = 'site.chrome';

    /**
     * Les textes de l'habillage du site.
     *
     * Ils etaient ecrits en dur dans les trois partiels et traduits par __() :
     * aucun ecran ne les exposait. Beaucoup ne se lisent qu'a la souris ou au
     * lecteur d'ecran — un intitule de bouton, une bulle d'aide — mais un
     * texte qu'un visiteur peut entendre est un texte que l'agence doit
     * pouvoir changer.
     *
     * Ils sont ici et non dans « Configuration » : cet ecran gouverne deja
     * l'en-tete et le pied, et ces mots s'affichent a cote des menus qu'il
     * edite. Les mettre ailleurs aurait oblige a chercher.
     */
    public const TEXTES_DU_SITE = [
        // --- en-tete ---
        'aria_logo' => ['intitule' => 'Description du logo (lecteurs d’écran, :site sera remplacé)', 'defaut' => 'Logo :site'],
        'aria_theme' => ['intitule' => 'Bouton de thème — description', 'defaut' => 'Basculer mode sombre / clair'],
        'titre_theme' => ['intitule' => 'Bouton de thème — bulle d’aide', 'defaut' => 'Mode sombre / clair'],
        'aria_langue' => ['intitule' => 'Bouton de langue — description', 'defaut' => 'Changer de langue'],
        'aria_menu_mobile' => ['intitule' => 'Bouton du menu mobile — description', 'defaut' => 'Menu Mobile'],
        // --- pied de page ---
        'exemple_newsletter' => ['intitule' => 'Exemple dans le champ de la lettre d’information', 'defaut' => 'Votre adresse email'],
        'aria_newsletter' => ['intitule' => 'Bouton d’inscription — description', 'defaut' => "S'inscrire à la newsletter"],
        'titre_navigation' => ['intitule' => 'Titre de la colonne « Navigation »', 'defaut' => 'Navigation'],
        'titre_services' => ['intitule' => 'Titre de la colonne « Services »', 'defaut' => 'Nos Services'],
        'titre_contact' => ['intitule' => 'Titre de la colonne « Contact »', 'defaut' => 'Nous contacter'],
        'libelle_telephone' => ['intitule' => 'Mention devant le téléphone', 'defaut' => 'Tél:'],
        'libelle_email' => ['intitule' => 'Mention devant l’e-mail', 'defaut' => 'Email:'],
        // --- mots communs a plusieurs pages ---
        // « Fermer » ferme la fiche d'un bien ET la fenetre d'un service ;
        // « Annonce » coiffe l'encart de l'accueil ET celui des services. Les
        // declarer page par page aurait cree trois champs pour un meme mot,
        // qu'un editeur aurait corriges un par un — ou pas.
        'libelle_fermer' => ['intitule' => 'Libellé des boutons de fermeture', 'defaut' => 'Fermer'],
        'libelle_annonce' => ['intitule' => 'Mention au-dessus des encarts publicitaires', 'defaut' => 'Annonce'],
        // --- boutons flottants ---
        'aria_whatsapp' => ['intitule' => 'Bouton WhatsApp — description', 'defaut' => 'Discuter sur WhatsApp'],
        'aria_chat' => ['intitule' => 'Bouton de chat — description', 'defaut' => 'Ouvrir le chat en ligne'],
        'titre_chat' => ['intitule' => 'Bouton de chat — bulle d’aide', 'defaut' => 'Chat en ligne'],
        'aria_joindre' => ['intitule' => 'Bouton « nous joindre » — description', 'defaut' => 'Nous joindre'],
    ];

    /**
     * Le trait interroge d'ordinaire le module ouvert. Cet ecran n'a pas de
     * modules : il porte une seule liste de textes.
     */
    protected function textesDeclares(): array
    {
        return self::TEXTES_DU_SITE;
    }

    /**
     * Les entrees en cours d'edition, par menu puis par cle.
     *
     * @var array<string, array<int|string, array<string, string>>>
     */
    public array $entrees = [];

    /** Identifiants retires, effaces a l'enregistrement. */
    public array $aSupprimer = [];

    /** Compteur des entrees ajoutees, par menu. */
    public array $compteurNeuf = [];

    /** Langue du contenu saisi — sans rapport avec celle de l'interface. */
    public string $langueActive = 'fr';

    protected function peutEcrire(): bool
    {
        return (bool) auth()->user()?->hasRole('administrateur');
    }

    public function mount(): void
    {
        abort_unless($this->peutEcrire(), 403);

        $this->langueActive = app()->getLocale();
        $this->charger();
    }

    /** (Re)lit les trois menus et les textes du site depuis la base. */
    protected function charger(): void
    {
        $this->chargerLesTextes(ReglageDeSection::where('slug', self::SECTION)->first());

        foreach (array_keys(EntreeDeMenu::menus()) as $menu) {
            $this->entrees[$menu] = [];
            $this->compteurNeuf[$menu] = 0;

            foreach (EntreeDeMenu::duMenu($menu)->ordonnees()->get() as $entree) {
                $this->entrees[$menu][$entree->id] = [
                    'libelle_fr' => (string) $entree->libelle_fr,
                    'libelle_en' => (string) $entree->libelle_en,
                    'cible' => (string) $entree->cible,
                    'visible' => $entree->visible ? '1' : '',
                ];
            }
        }
    }

    public function ajouter(string $menu): void
    {
        abort_unless($this->peutEcrire(), 403);
        abort_unless(EntreeDeMenu::menuConnu($menu), 404);

        $cle = 'neuf-'.(++$this->compteurNeuf[$menu]);

        $this->entrees[$menu][$cle] = [
            'libelle_fr' => '',
            'libelle_en' => '',
            'cible' => '',
            'visible' => '1',
        ];
    }

    public function retirer(string $menu, int|string $cle): void
    {
        abort_unless($this->peutEcrire(), 403);
        abort_unless(EntreeDeMenu::menuConnu($menu), 404);

        unset($this->entrees[$menu][$cle]);

        if (is_int($cle) || ctype_digit((string) $cle)) {
            $this->aSupprimer[] = (int) $cle;
        }
    }

    protected function rules(): array
    {
        $regles = [];

        foreach ($this->entrees as $menu => $entrees) {
            foreach (array_keys($entrees) as $cle) {
                $regles["entrees.$menu.$cle.libelle_fr"] = ['required', 'string', 'max:120'];
                $regles["entrees.$menu.$cle.libelle_en"] = ['nullable', 'string', 'max:120'];
                $regles["entrees.$menu.$cle.visible"] = ['nullable'];

                // Ce que vaut une cible acceptable est defini par le MODELE,
                // et interroge ici. Une seconde definition dans la validation
                // aurait pu diverger de celle qui rend le lien — c'est-a-dire
                // laisser passer a la saisie ce que le rendu refuse ensuite,
                // ou l'inverse, bien plus grave.
                $regles["entrees.$menu.$cle.cible"] = [
                    'required', 'string', 'max:255',
                    function (string $attribut, mixed $valeur, callable $echec) {
                        if (! EntreeDeMenu::cibleAcceptable(is_string($valeur) ? $valeur : null)) {
                            $echec(__("La cible doit être un chemin du site commençant par « / », une adresse http(s) complète, ou un nom de route de l'application."));
                        }
                    },
                ];
            }
        }

        return $regles + $this->reglesDesTextes();
    }

    protected function validationAttributes(): array
    {
        $intitules = $this->intitulesDesTextes();
        $menus = EntreeDeMenu::menus();

        foreach ($this->entrees as $menu => $entrees) {
            foreach (array_keys($entrees) as $rang => $cle) {
                $nom = $menus[$menu]['intitule'] ?? $menu;

                $intitules["entrees.$menu.$cle.libelle_fr"] = __('libellé français — :menu, entrée :rang', ['menu' => $nom, 'rang' => $rang + 1]);
                $intitules["entrees.$menu.$cle.libelle_en"] = __('libellé anglais — :menu, entrée :rang', ['menu' => $nom, 'rang' => $rang + 1]);
                $intitules["entrees.$menu.$cle.cible"] = __('cible — :menu, entrée :rang', ['menu' => $nom, 'rang' => $rang + 1]);
            }
        }

        return $intitules;
    }

    public function enregistrer(): void
    {
        abort_unless($this->peutEcrire(), 403);

        // Les cles de premier niveau viennent du navigateur au meme titre que
        // les valeurs. Un menu forge creerait des entrees qu'aucun gabarit
        // n'afficherait plus jamais.
        $this->entrees = array_intersect_key($this->entrees, EntreeDeMenu::menus());

        if ($this->rules() !== []) {
            $this->validate();
        }

        foreach ($this->entrees as $menu => $entrees) {
            // Relecture depuis la base en filtrant sur le menu reel : un
            // identifiant emprunte a un autre menu ne doit pas y etre deplace.
            $connus = EntreeDeMenu::query()
                ->where('menu', $menu)
                ->whereIn('id', array_filter(array_keys($entrees), 'is_numeric'))
                ->get()
                ->keyBy('id');

            $rang = 0;

            foreach ($entrees as $cle => $entree) {
                $donnees = [
                    'menu' => $menu,
                    'libelle_fr' => $entree['libelle_fr'],
                    'libelle_en' => $entree['libelle_en'] ?: null,
                    'cible' => trim($entree['cible']),
                    'visible' => (bool) ($entree['visible'] ?? false),
                    'ordre' => ++$rang,
                ];

                if (isset($connus[$cle])) {
                    $connus[$cle]->update($donnees);

                    continue;
                }

                if (! is_numeric($cle)) {
                    EntreeDeMenu::create($donnees);
                }
            }
        }

        if ($this->aSupprimer) {
            EntreeDeMenu::query()->whereIn('id', $this->aSupprimer)->delete();
            $this->aSupprimer = [];
        }

        // poserLesTextes() ne retient que les cles declarees et POSE sans
        // enregistrer : le save() qui suit les porte en base.
        $section = ReglageDeSection::firstOrNew(['slug' => self::SECTION]);
        $this->poserLesTextes($section);
        $section->save();

        $this->charger();

        $this->dispatch('toast', message: __('Menus enregistrés.'), variant: 'success');
    }

    public function render(): View
    {
        return view('livewire.admin.menus', [
            'menus' => EntreeDeMenu::menus(),
            // Les deux colonnes automatiques du pied, montrees pour que
            // l'ecran corresponde a la maquette sans laisser croire qu'on les
            // edite ici.
            'servicesDuPied' => Service::visibles()->ordonnees()->get(),
            'coordonnees' => [
                __('Adresse') => Parametre::lire('adresse_postale'),
                __('Téléphone') => Parametre::lire('telephone'),
                __('E-mail') => Parametre::lire('email_public'),
            ],
        ])->title(__('Menus du site'));
    }
}

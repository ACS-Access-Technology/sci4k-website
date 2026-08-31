<?php

namespace App\Livewire\Admin;

use App\Models\EntreeDeMenu;
use App\Models\Parametre;
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

    /** (Re)lit les trois menus depuis la base. */
    protected function charger(): void
    {
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

        return $regles;
    }

    protected function validationAttributes(): array
    {
        $intitules = [];
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

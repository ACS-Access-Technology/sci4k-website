<?php

namespace App\Livewire\Admin;

use App\Models\Categorie;
use App\Models\Referentiel;
use App\Models\RubriqueFaq;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Valeurs des listes deroulantes du site public.
 *
 * Cinq familles editees d'un seul ecran, comme sur la maquette. C'est proche
 * d'EditionGroupee — memes ajouts, memes retraits, meme bouton unique — mais
 * cette abstraction porte UNE collection, la ou celle-ci en porte cinq dont
 * chacune a son propre rang. La plier a cinq familles aurait complique les
 * trois ecrans qui s'en servent deja pour un seul usage.
 */
#[Layout('layouts.app')]
class Referentiels extends Component
{
    /**
     * Les lignes en cours d'edition, par famille puis par cle.
     *
     * Une ligne ajoutee mais pas encore enregistree porte une cle « neuf-N » :
     * elle n'a pas d'identifiant, et lui en inventer un risquerait de heurter
     * celui d'une ligne reelle.
     *
     * @var array<string, array<int|string, array<string, string>>>
     */
    public array $lignes = [];

    /** Identifiants retires, effaces a l'enregistrement. */
    public array $aSupprimer = [];

    /** Compteur des lignes ajoutees, par famille. */
    public array $compteurNeuf = [];

    /** Langue du contenu saisi — sans rapport avec celle de l'interface. */
    public string $langueActive = 'fr';

    protected function peutEcrire(): bool
    {
        // Meme raisonnement que pour la configuration : ces valeurs pilotent
        // les filtres publics et les fiches de biens. Les modifier n'est pas
        // rediger du contenu.
        return (bool) auth()->user()?->hasRole('administrateur');
    }

    public function mount(): void
    {
        abort_unless($this->peutEcrire(), 403);

        $this->langueActive = app()->getLocale();
        $this->charger();
    }

    /**
     * (Re)lit les cinq familles depuis la base.
     *
     * Appele au montage et apres enregistrement. Apres enregistrement, il sert
     * surtout a remplacer les cles « neuf-N » par les identifiants reels : les
     * garder ferait recreer les memes lignes au second enregistrement.
     */
    protected function charger(): void
    {
        foreach (array_keys(Referentiel::familles()) as $famille) {
            $this->lignes[$famille] = [];
            $this->compteurNeuf[$famille] = 0;

            foreach (Referentiel::deLaFamille($famille)->ordonnees()->get() as $element) {
                $this->lignes[$famille][$element->id] = [
                    'valeur' => (string) $element->valeur,
                    'libelle_fr' => (string) $element->libelle_fr,
                    'libelle_en' => (string) $element->libelle_en,
                    'visible' => $element->visible ? '1' : '',
                ];
            }
        }
    }

    public function ajouter(string $famille): void
    {
        abort_unless($this->peutEcrire(), 403);
        // La famille vient du navigateur : sans ce controle, un nom forge
        // creerait une famille fantome que plus aucun ecran n'afficherait.
        abort_unless(Referentiel::familleConnue($famille), 404);

        $cle = 'neuf-'.(++$this->compteurNeuf[$famille]);

        $this->lignes[$famille][$cle] = [
            'valeur' => '',
            'libelle_fr' => '',
            'libelle_en' => '',
            'visible' => '1',
        ];
    }

    public function retirer(string $famille, int|string $cle): void
    {
        abort_unless($this->peutEcrire(), 403);
        abort_unless(Referentiel::familleConnue($famille), 404);

        unset($this->lignes[$famille][$cle]);

        // Une ligne jamais enregistree n'a rien a effacer en base.
        if (is_int($cle) || ctype_digit((string) $cle)) {
            $this->aSupprimer[] = (int) $cle;
        }
    }

    protected function rules(): array
    {
        $regles = [];

        foreach ($this->lignes as $famille => $lignes) {
            foreach (array_keys($lignes) as $cle) {
                $regles["lignes.$famille.$cle.valeur"] = ['required', 'string', 'max:60', 'regex:/^[a-z0-9-]+$/'];
                $regles["lignes.$famille.$cle.libelle_fr"] = ['required', 'string', 'max:120'];
                $regles["lignes.$famille.$cle.libelle_en"] = ['nullable', 'string', 'max:120'];
                $regles["lignes.$famille.$cle.visible"] = ['nullable'];
            }
        }

        return $regles;
    }

    protected function messages(): array
    {
        $messages = [];

        foreach ($this->lignes as $famille => $lignes) {
            foreach (array_keys($lignes) as $cle) {
                $messages["lignes.$famille.$cle.valeur.regex"] =
                    __('La valeur technique ne peut contenir que des minuscules, des chiffres et des tirets.');
            }
        }

        return $messages;
    }

    protected function validationAttributes(): array
    {
        $intitules = [];
        $familles = Referentiel::familles();

        foreach ($this->lignes as $famille => $lignes) {
            foreach (array_keys($lignes) as $rang => $cle) {
                $nom = $familles[$famille]['intitule'] ?? $famille;

                $intitules["lignes.$famille.$cle.valeur"] = __('valeur technique — :famille, ligne :rang', ['famille' => $nom, 'rang' => $rang + 1]);
                $intitules["lignes.$famille.$cle.libelle_fr"] = __('libellé français — :famille, ligne :rang', ['famille' => $nom, 'rang' => $rang + 1]);
                $intitules["lignes.$famille.$cle.libelle_en"] = __('libellé anglais — :famille, ligne :rang', ['famille' => $nom, 'rang' => $rang + 1]);
            }
        }

        return $intitules;
    }

    public function enregistrer(): void
    {
        // La route protege l'ecran, pas l'action.
        abort_unless($this->peutEcrire(), 403);

        // Rien a valider quand toutes les familles sont vides — apres le
        // retrait de la derniere valeur, par exemple. Livewire refuse un jeu
        // de regles vide, et l'administrateur aurait vu une erreur technique
        // la ou il venait simplement de tout retirer.
        if ($this->rules() !== []) {
            $this->validate();
        }

        // Les CLES de premier niveau viennent du navigateur au meme titre que
        // les valeurs : `lignes` est une propriete publique. Une famille forgee
        // creerait des lignes qu'aucun ecran n'afficherait plus jamais, et que
        // seul un acces direct a la base permettrait de retrouver. On ne
        // conserve donc que les familles declarees.
        $this->lignes = array_intersect_key($this->lignes, Referentiel::familles());

        // Une valeur technique en double dans une famille rendrait le filtre
        // non deterministe. La base l'interdit ; la verifier ici permet de le
        // DIRE plutot que de laisser remonter une erreur SQL.
        foreach ($this->lignes as $famille => $lignes) {
            $valeurs = array_map(fn ($ligne) => $ligne['valeur'] ?? '', $lignes);

            if (count($valeurs) !== count(array_unique($valeurs))) {
                $this->addError('lignes.'.$famille, __('Deux valeurs techniques identiques dans « :famille ».', [
                    'famille' => Referentiel::familles()[$famille]['intitule'] ?? $famille,
                ]));

                return;
            }
        }

        foreach ($this->lignes as $famille => $lignes) {
            // Les elements sont relus depuis la base : les identifiants
            // viennent du navigateur, et l'un d'eux pourrait designer une
            // ligne d'une AUTRE famille, ou une ligne disparue depuis
            // l'ouverture de l'ecran.
            $connus = Referentiel::query()
                ->where('famille', $famille)
                ->whereIn('id', array_filter(array_keys($lignes), 'is_numeric'))
                ->get()
                ->keyBy('id');

            $rang = 0;

            foreach ($lignes as $cle => $ligne) {
                $donnees = [
                    'famille' => $famille,
                    'valeur' => $ligne['valeur'],
                    'libelle_fr' => $ligne['libelle_fr'],
                    'libelle_en' => $ligne['libelle_en'] ?: null,
                    'visible' => (bool) ($ligne['visible'] ?? false),
                    // Le rang suit l'ordre d'affichage, famille par famille.
                    'ordre' => ++$rang,
                ];

                if (isset($connus[$cle])) {
                    $connus[$cle]->update($donnees);

                    continue;
                }

                if (! is_numeric($cle)) {
                    Referentiel::create($donnees);
                }
            }
        }

        if ($this->aSupprimer) {
            Referentiel::query()->whereIn('id', $this->aSupprimer)->delete();
            $this->aSupprimer = [];
        }

        $this->charger();

        session()->flash('succes', __('Référentiels enregistrés.'));
    }

    public function render(): View
    {
        return view('livewire.admin.referentiels', [
            'familles' => Referentiel::familles(),
            // Les deux familles qui vivent ailleurs, affichees en lecture avec
            // un renvoi vers leur ecran : les dupliquer ici aurait cree deux
            // sources pour la meme information.
            'categoriesArticles' => Categorie::orderBy('ordre')->get(),
            'rubriquesFaq' => RubriqueFaq::orderBy('ordre')->get(),
        ])->title(__('Référentiels'));
    }
}

<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\RemplitParTraduction;
use App\Services\Traduction\Traducteur;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Ecran des petits ensembles edites d'un bloc.
 *
 * Valeurs (4), chiffres cles (3), etapes du processus (4) : tous les elements
 * cote a cote, un seul bouton d'enregistrement, ni creation ni suppression. La
 * maquette values-list.html decrit exactement cela, et le cadrage en donne le
 * motif : un tableau pagine avec recherche et filtres pour quatre lignes est
 * une machinerie qui coute a ecrire, a tester et surtout a utiliser — trois
 * clics pour changer un mot.
 *
 * Chaque ensemble ne declare que son modele et ses champs. Le bilingue, la
 * validation, le controle de role et le remplissage par traduction sont ici.
 */
#[Layout('layouts.app')]
abstract class EnsembleFige extends Component
{
    use RemplitParTraduction;

    /**
     * Les lignes en cours d'edition, indexees par identifiant.
     *
     * Forme : [id => ['titre_fr' => …, 'titre_en' => …, …]]
     *
     * @var array<int, array<string, string>>
     */
    public array $lignes = [];

    /** Langue du contenu saisi — sans rapport avec celle de l'interface. */
    public string $langueActive = 'fr';

    /** Classe du modele porte par cet ecran. */
    abstract protected function modele(): string;

    /**
     * Champs bilingues, par prefixe : ['titre', 'texte'] designe les colonnes
     * titre_fr, titre_en, texte_fr et texte_en.
     *
     * @return list<string>
     */
    abstract protected function champsBilingues(): array;

    /**
     * Champs qui n'ont pas de version par langue, avec leurs regles.
     *
     * @return array<string, string|list<string>>
     */
    protected function champsSimples(): array
    {
        return [];
    }

    /** Vue Blade de l'ecran. */
    abstract protected function vue(): string;

    /** Titre affiche dans l'en-tete et l'onglet. */
    abstract protected function titre(): string;

    /** Regles appliquees a chaque champ bilingue. */
    protected function reglesDuChamp(string $champ): array
    {
        return ['required', 'string'];
    }

    protected function peutEcrire(): bool
    {
        return (bool) auth()->user()?->hasAnyRole(['administrateur', 'editeur']);
    }

    public function mount(): void
    {
        $this->langueActive = app()->getLocale();

        foreach ($this->elements() as $element) {
            $ligne = [];

            foreach ($this->champsBilingues() as $champ) {
                $ligne[$champ.'_fr'] = (string) $element->{$champ.'_fr'};
                $ligne[$champ.'_en'] = (string) $element->{$champ.'_en'};
            }

            foreach (array_keys($this->champsSimples()) as $champ) {
                $ligne[$champ] = (string) $element->$champ;
            }

            $this->lignes[$element->id] = $ligne;
        }
    }

    /** Les elements de l'ensemble, dans leur ordre d'affichage. */
    protected function elements()
    {
        return ($this->modele())::query()->orderBy('ordre')->orderBy('id')->get();
    }

    protected function rules(): array
    {
        $regles = [];

        foreach (array_keys($this->lignes) as $id) {
            foreach ($this->champsBilingues() as $champ) {
                $regles["lignes.$id.{$champ}_fr"] = $this->reglesDuChamp($champ);
                $regles["lignes.$id.{$champ}_en"] = $this->reglesDuChamp($champ);
            }

            foreach ($this->champsSimples() as $champ => $regle) {
                $regles["lignes.$id.$champ"] = $regle;
            }
        }

        return $regles;
    }

    /**
     * Intitules lisibles, pour que le message de validation ne cite pas
     * « lignes.7.titre_fr ».
     */
    protected function validationAttributes(): array
    {
        $intitules = [];

        foreach (array_keys($this->lignes) as $rang => $id) {
            foreach ($this->champsBilingues() as $champ) {
                $intitules["lignes.$id.{$champ}_fr"] = __(':champ (français) — élément :rang', ['champ' => $champ, 'rang' => $rang + 1]);
                $intitules["lignes.$id.{$champ}_en"] = __(':champ (anglais) — élément :rang', ['champ' => $champ, 'rang' => $rang + 1]);
            }
        }

        return $intitules;
    }

    /**
     * Champs traduisibles au sens du trait : aucun.
     *
     * Les textes de cet ecran vivent dans un tableau, pas dans des proprietes
     * {prefixe}Fr. Le remplissage passe donc par completerCouple(), que le
     * trait expose pour ce cas.
     *
     * @return list<string>
     */
    protected function champsTraduisibles(): array
    {
        return [];
    }

    public function enregistrer(): void
    {
        // La route protege l'ecran, pas l'action : Livewire ne rejoue pas le
        // middleware de role sur /livewire/update.
        abort_unless($this->peutEcrire(), 403);

        // Avant la validation : un champ rempli par traduction doit satisfaire
        // la regle « required » comme s'il avait ete saisi.
        foreach ($this->lignes as $id => $ligne) {
            foreach ($this->champsBilingues() as $champ) {
                [$fr, $en] = $this->completerCouple($ligne[$champ.'_fr'] ?? '', $ligne[$champ.'_en'] ?? '');

                $this->lignes[$id][$champ.'_fr'] = $fr;
                $this->lignes[$id][$champ.'_en'] = $en;
            }
        }

        $this->validate();

        // Les elements sont relus depuis la base : les identifiants viennent du
        // navigateur, et l'un d'eux pourrait designer une ligne d'une autre
        // table ou une ligne disparue depuis l'ouverture de l'ecran.
        $connus = ($this->modele())::query()->whereIn('id', array_keys($this->lignes))->get()->keyBy('id');

        foreach ($this->lignes as $id => $ligne) {
            $connus->get($id)?->update($ligne);
        }

        session()->flash('message', __('Modifications enregistrées.'));
    }

    public function render(): View
    {
        return view($this->vue(), [
            'elements' => $this->elements(),
            'langue' => app()->getLocale(),
            'peutEcrire' => $this->peutEcrire(),
            'traductionActive' => app(Traducteur::class)->disponible(),
        ])->title($this->titre());
    }
}

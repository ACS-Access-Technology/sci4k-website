<?php

namespace App\Livewire\Admin;

use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Ecran de liste commun aux collections ordonnables du lot 2.
 *
 * Les sept blocs partagent recherche, filtre de visibilite, reordonnancement,
 * bascule de visibilite et suppression. Chaque entite ne declare que son
 * modele et les colonnes ou porte la recherche.
 *
 * Le controle de role est refait dans chaque methode d'ecriture : la route
 * protege l'ecran, pas l'action, et un lecteur peut atteindre le composant.
 */
#[Layout('layouts.app')]
abstract class ListeOrdonnable extends Component
{
    /**
     * L'ecran est-il rendu A L'INTERIEUR d'un autre ?
     *
     * Vrai quand un ecran de page — « Pages du site → Accueil » — embarque
     * cette liste dans l'un de ses modules. Le corps reste identique : mêmes
     * statistiques, meme recherche, meme reordonnancement. Seul l'en-tete de
     * page disparait, un titre et un fil d'Ariane n'ayant pas de sens au
     * milieu d'une autre page.
     *
     * Embarquer plutot que reecrire : la refonte du backoffice rassemble les
     * ecrans par page publique, et dupliquer le corps de chaque liste aurait
     * cree deux versions a corriger a chaque defaut.
     */
    public bool $embarque = true;

    /**
     * Formulaire ouvert SUR PLACE : null, 'creation', ou l'identifiant edite.
     *
     * N'a de sens qu'embarque. Sur son propre ecran, la liste continue de
     * renvoyer vers les adresses d'edition, qui restent utiles — un lien se
     * partage, se met en favori et s'ouvre dans un onglet.
     */
    public null|int|string $formulaireOuvert = null;

    /**
     * Le composant Livewire du formulaire de ce bloc.
     *
     * Null quand la liste n'a pas de formulaire a ouvrir sur place ; elle
     * retombe alors sur ses liens.
     */
    protected function composantFormulaire(): ?string
    {
        return null;
    }

    /**
     * Sous quel NOM le formulaire attend son modele.
     *
     * Les formulaires nes de FormulaireDeBloc l'appellent « element ».
     * ServiceFormulaire, qui n'en herite pas, l'appelle « service » : lui
     * passer « element » revenait a ne rien lui passer du tout, et il ouvrait
     * son formulaire de CREATION a chaque demande de modification. Le nom est
     * donc declare, et non suppose.
     */
    protected function parametreDuFormulaire(): string
    {
        return 'element';
    }

    public function ouvrirCreation(): void
    {
        abort_unless($this->peutEcrire(), 403);

        $this->formulaireOuvert = 'creation';
    }

    public function ouvrirEdition(int $id): void
    {
        abort_unless($this->peutEcrire(), 403);

        // L'identifiant vient du navigateur : on verifie qu'il designe bien une
        // ligne de CE bloc avant de le passer au formulaire.
        abort_unless(($this->modele())::whereKey($id)->exists(), 404);

        $this->formulaireOuvert = $id;
    }

    #[On('bloc-enregistre')]
    #[On('bloc-annule')]
    public function fermerFormulaire(): void
    {
        $this->formulaireOuvert = null;
    }

    public string $recherche = '';

    /** '' | 'visibles' | 'masques' */
    public string $visibilite = '';

    /** Classe du modele porte par cet ecran. */
    abstract protected function modele(): string;

    /** Colonnes ou porte la recherche, dans les deux langues. */
    abstract protected function colonnesRecherchees(): array;

    /** Vue Blade de l'ecran. */
    abstract protected function vue(): string;

    /** Titre affiche dans l'en-tete et l'onglet. */
    abstract protected function titre(): string;

    protected function peutEcrire(): bool
    {
        return (bool) auth()->user()?->hasAnyRole(['administrateur', 'editeur']);
    }

    public function updating($nom): void
    {
        if (in_array($nom, ['recherche', 'visibilite'], true) && method_exists($this, 'resetPage')) {
            $this->resetPage();
        }
    }

    /**
     * Reecrit l'ordre d'affichage a partir de la liste reçue du navigateur.
     *
     * Le reordonnancement n'est accepte que s'il porte sur la collection
     * ENTIERE. reordonner() renumerote « en repartant de 1 » : applique a un
     * sous-ensemble — ce que produit un glisser-deposer pendant qu'un filtre
     * est actif —, il donnerait aux lignes affichees des rangs deja tenus par
     * les lignes cachees. L'ordre public deviendrait alors celui que le tri
     * produit sur des rangs en doublon, jamais celui que l'editeur a choisi,
     * et sans le moindre signal.
     *
     * La vue retire deja la poignee des qu'un filtre est pose ; ce controle
     * est la garde de derniere ligne, Livewire exposant la methode au
     * navigateur quoi qu'affiche la vue.
     */
    public function reordonner(array $ids): void
    {
        abort_unless($this->peutEcrire(), 403);

        $recus = count(array_unique($ids));

        if ($recus !== ($this->modele())::query()->count()) {
            $this->dispatch('toast', message: __('Le tri n’a pas été enregistré : rechargez la liste complète.'), variant: 'error');

            return;
        }

        ($this->modele())::reordonner($ids);
        $this->dispatch('toast', message: __('Ordre enregistré.'), variant: 'success');
    }

    public function basculerVisibilite(int $id): void
    {
        abort_unless($this->peutEcrire(), 403);

        $element = ($this->modele())::findOrFail($id);
        $element->update(['visible' => ! $element->visible]);
        $this->dispatch('toast', message: $element->visible ? __('Élément rendu visible.') : __('Élément masqué.'), variant: 'success');
    }

    /**
     * Cette collection accepte-t-elle la suppression ?
     *
     * Le point d'extension avait ete retire au lot 2a, son unique utilisateur
     * ayant disparu : une garde toujours vraie, dont la branche fausse n'etait
     * couverte par aucun test, ne donnait plus d'assurance. Il revient parce
     * que trois collections du lot 2b en ont un vrai besoin — reglages de
     * section, encarts et images de fond, dont le slug designe un emplacement
     * du site. En supprimer un ne retire rien : la page cherche toujours ce
     * nom, et ne trouve plus rien a afficher.
     *
     * Il est declare ici plutot que par trois surcharges identiques de
     * supprimer(), et sa branche fausse est desormais eprouvee.
     */
    protected function suppressionPermise(): bool
    {
        return true;
    }

    /**
     * Suppression simple. Une collection dont le retrait touche autre chose
     * qu'elle-meme surcharge cette methode plutot que de retirer le bouton :
     * Livewire expose au navigateur toute methode publique du composant, si
     * bien qu'une vue sans bouton reste appelable. Voir ServiceListe, qui
     * efface le fichier d'image avec le service.
     */
    public function supprimer(int $id): void
    {
        abort_unless($this->suppressionPermise(), 403);
        abort_unless($this->peutEcrire(), 403);

        ($this->modele())::findOrFail($id)->delete();

        $this->dispatch('toast', message: __('Élément supprimé.'), variant: 'success');
    }

    /** Les elements de l'ecran, filtres et ordonnes. */
    /**
     * Relations a precharger, pour que la vue n'emette pas une requete par
     * ligne. Chaque collection declare les siennes ; par defaut, aucune.
     *
     * @return list<string>
     */
    protected function relationsAPrecharger(): array
    {
        return [];
    }

    protected function elements(): Collection
    {
        return ($this->modele())::query()
            ->with($this->relationsAPrecharger())
            ->when($this->recherche !== '', function ($r) {
                $r->where(function ($q) {
                    foreach ($this->colonnesRecherchees() as $colonne) {
                        $q->orWhere($colonne, 'like', '%'.$this->recherche.'%');
                    }
                });
            })
            ->when($this->visibilite === 'visibles', fn ($r) => $r->where('visible', true))
            ->when($this->visibilite === 'masques', fn ($r) => $r->where('visible', false))
            ->ordonnees()
            ->get();
    }

    public function render()
    {
        return view($this->vue(), [
            'elements' => $this->elements(),
            'langue' => app()->getLocale(),
            'peutEcrire' => $this->peutEcrire(),
            // L'edition sur place n'est proposee qu'embarquee, et seulement si
            // ce bloc declare un formulaire.
            'composantFormulaire' => $this->embarque ? $this->composantFormulaire() : null,
            // Le MODELE et non l'identifiant : le formulaire attend un Model
            // dans son mount(), et le liage de route ne joue que pour un
            // composant de pleine page.
            'elementEnEdition' => is_int($this->formulaireOuvert)
                ? ($this->modele())::find($this->formulaireOuvert)
                : null,
            'parametreDuFormulaire' => $this->parametreDuFormulaire(),
        ])->title($this->titre());
    }
}

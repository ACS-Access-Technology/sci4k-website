<?php

namespace App\Livewire\Admin;

use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Layout;
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

    public function reordonner(array $ids): void
    {
        abort_unless($this->peutEcrire(), 403);

        ($this->modele())::reordonner($ids);
    }

    public function basculerVisibilite(int $id): void
    {
        abort_unless($this->peutEcrire(), 403);

        $element = ($this->modele())::findOrFail($id);
        $element->update(['visible' => ! $element->visible]);
    }

    public function supprimer(int $id): void
    {
        abort_unless($this->peutEcrire(), 403);

        ($this->modele())::findOrFail($id)->delete();

        session()->flash('message', __('Élément supprimé.'));
    }

    /** Les elements de l'ecran, filtres et ordonnes. */
    protected function elements(): Collection
    {
        return ($this->modele())::query()
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
        ])->title($this->titre());
    }
}

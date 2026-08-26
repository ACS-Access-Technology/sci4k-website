<?php

namespace App\Livewire\Admin;

use App\Models\ReglageDeSection;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/*
 * Liste des en-tetes de section du site.
 *
 * N'herite PAS de ListeOrdonnable, contrairement aux six collections. Un
 * en-tete de section n'a ni rang d'affichage ni visibilite : il appartient a
 * sa section, laquelle est a une place fixe dans sa page. Lui preter un
 * glisser-deposer et un bouton « masquer » aurait offert deux commandes sans
 * effet, et impose deux colonnes que rien n'aurait lues.
 *
 * Reste la recherche, utile : vingt-trois sections tiennent mal dans un coup
 * d'oeil.
 */
#[Layout('layouts.app')]
class ReglageDeSectionListe extends Component
{
    public string $recherche = '';

    protected function peutEcrire(): bool
    {
        return (bool) auth()->user()?->hasAnyRole(['administrateur', 'editeur']);
    }

    public function render(): View
    {
        $elements = ReglageDeSection::query()
            ->when($this->recherche !== '', function ($requete) {
                $requete->where(function ($q) {
                    foreach (['slug', 'titre_fr', 'titre_en', 'chapo_fr', 'chapo_en'] as $colonne) {
                        $q->orWhere($colonne, 'like', '%'.$this->recherche.'%');
                    }
                });
            })
            ->orderBy('slug')
            ->get();

        return view('livewire.admin.reglage-de-section-liste', [
            'elements' => $elements,
            'langue' => app()->getLocale(),
            'peutEcrire' => $this->peutEcrire(),
        ])->title(__('En-têtes de section'));
    }
}

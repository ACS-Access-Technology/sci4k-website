<?php

namespace App\Livewire\Admin;

use App\Models\ActiviteJournalisee;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Journal complet des actions faites depuis l'administration.
 *
 * Le tableau de bord n'en montre que les six dernieres. « Tout afficher »
 * menait jusqu'ici a la liste des ARTICLES, alors que le panneau resume seize
 * familles : il montrait moins que ce qu'il annonçait.
 *
 * Ouvert a tous les roles qui entrent dans l'administration, en LECTURE seule.
 * Un journal qu'on peut effacer ne sert a rien ; il n'a donc aucune action.
 */
#[Layout('layouts.app')]
class JournalActivite extends Component
{
    use WithPagination;

    /** Filtre d'action, ou '' pour toutes. */
    public string $action = '';

    /** Filtre d'auteur, ou '' pour tous. */
    public string $auteur = '';

    public function updating($nom): void
    {
        // Changer un filtre ramene a la premiere page : rester en page 4 d'une
        // liste qui n'en compte plus qu'une affiche un vide trompeur.
        if (in_array($nom, ['action', 'auteur'], true)) {
            $this->resetPage();
        }
    }

    public function render(): View
    {
        $lignes = ActiviteJournalisee::query()
            ->when($this->action !== '', fn ($r) => $r->where('action', $this->action))
            ->when($this->auteur !== '', fn ($r) => $r->where('user_id', $this->auteur))
            ->recentes()
            ->paginate(30);

        return view('livewire.admin.journal-activite', [
            'lignes' => $lignes,
            'actions' => [
                ActiviteJournalisee::CREATION => __('Créations'),
                ActiviteJournalisee::MODIFICATION => __('Modifications'),
                ActiviteJournalisee::PUBLICATION => __('Publications'),
                ActiviteJournalisee::SUPPRESSION => __('Suppressions'),
            ],
            // Seuls les comptes qui ont REELLEMENT agi sont proposes : lister
            // tous les comptes remplirait le filtre de choix sans resultat.
            'auteurs' => User::query()
                ->whereIn('id', ActiviteJournalisee::query()->whereNotNull('user_id')->distinct()->pluck('user_id'))
                ->orderBy('name')
                ->get(),
        ])->title(__('Journal des activités'));
    }
}

<?php

namespace App\Livewire\Admin;

use App\Models\Commentaire;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Moderation des commentaires.
 *
 * Les commentaires paraissent sans attendre : cet ecran n'est donc pas un sas
 * d'entree mais un poste de surveillance. Il ouvre par defaut sur ceux que le
 * filtre a mis DE COTE — les seuls qui reclament une decision — le reste
 * n'etant consultable qu'a la demande.
 *
 * Embarque dans « Pages du site → Actualités », comme les autres collections.
 */
#[Layout('layouts.app')]
class CommentaireListe extends Component
{
    use WithPagination;

    public bool $embarque = true;

    /** '' | 'publie' | 'en_attente' | 'rejete' */
    public string $statut = Commentaire::EN_ATTENTE;

    public string $recherche = '';

    public ?string $message = null;

    public function updating($nom): void
    {
        if (in_array($nom, ['statut', 'recherche'], true)) {
            $this->resetPage();
        }
    }

    protected function peutModerer(): bool
    {
        return (bool) auth()->user()?->hasAnyRole(['administrateur', 'editeur']);
    }

    /**
     * Change le statut d'un commentaire.
     *
     * Le controle de role est refait ICI : la route protege l'ecran, pas
     * l'action, et Livewire ne rejoue pas ses middlewares sur
     * /livewire/update. Un lecteur peut atteindre ce composant.
     */
    public function changerLeStatut(int $id, string $statut): void
    {
        abort_unless($this->peutModerer(), 403);
        abort_unless(array_key_exists($statut, Commentaire::statuts()), 404);

        $commentaire = Commentaire::findOrFail($id);
        $commentaire->update(['statut' => $statut]);

        $this->message = match ($statut) {
            Commentaire::PUBLIE => __('Commentaire publié.'),
            Commentaire::REJETE => __('Commentaire retiré du site.'),
            default => __('Commentaire remis en attente.'),
        };

        $this->dispatch('toast', message: $this->message, variant: 'success');
    }

    /**
     * Supprime definitivement un commentaire, et ses reponses avec lui.
     *
     * La cascade est posee en base : une reponse dont la question a disparu
     * n'aurait plus de sens.
     */
    public function supprimer(int $id): void
    {
        abort_unless($this->peutModerer(), 403);

        Commentaire::findOrFail($id)->delete();

        $this->message = __('Commentaire supprimé.');
        $this->dispatch('toast', message: $this->message, variant: 'success');
    }

    public function render(): View
    {
        $commentaires = Commentaire::query()
            ->with(['article', 'parent'])
            ->when($this->statut !== '', fn ($r) => $r->where('statut', $this->statut))
            ->when($this->recherche !== '', function ($r) {
                $motif = '%'.trim($this->recherche).'%';
                $r->where(fn ($q) => $q->where('auteur', 'like', $motif)
                    ->orWhere('email', 'like', $motif)
                    ->orWhere('message', 'like', $motif));
            })
            ->latest()
            ->paginate(20);

        return view('livewire.admin.commentaire-liste', [
            'commentaires' => $commentaires,
            'statuts' => Commentaire::statuts(),
            'peutModerer' => $this->peutModerer(),
            'langue' => app()->getLocale(),
            // Le compte des commentaires EN ATTENTE, quel que soit le filtre
            // affiche : c'est le chiffre qui dit s'il reste du travail.
            'enAttente' => Commentaire::aModerer()->count(),
        ])->title(__('Commentaires'));
    }
}

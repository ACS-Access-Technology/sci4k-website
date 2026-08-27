<?php

namespace App\Livewire\Admin;

use App\Models\DemandeDeVisite;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Rendez-vous demandes depuis les fiches de biens.
 *
 * Derniere page de la maquette, et la seule qui ait ete reportee : chaque
 * ligne porte une reference de bien, qui n'existait pas avant ce lot.
 */
#[Layout('layouts.app')]
class DemandeDeVisiteListe extends Component
{
    use WithPagination;

    public string $recherche = '';

    public string $statut = '';

    public string $assigne = '';

    public ?string $message = null;

    public function updating($nom): void
    {
        if (in_array($nom, ['recherche', 'statut', 'assigne'], true)) {
            $this->resetPage();
        }
    }

    protected function peutEcrire(): bool
    {
        return (bool) auth()->user()?->hasAnyRole(['administrateur', 'editeur']);
    }

    public function changerLeStatut(int $id, string $statut): void
    {
        abort_unless($this->peutEcrire(), 403);
        abort_unless(DemandeDeVisite::statutConnu($statut), 404);

        $demande = DemandeDeVisite::findOrFail($id);
        // Affectation directe : `statut` est hors du `fillable`, le point
        // d'entree public ecrivant sur ce meme modele.
        $demande->statut = $statut;
        $demande->save();

        $this->message = __('Statut mis à jour.');
    }

    public function assigner(int $id, string $utilisateur): void
    {
        abort_unless($this->peutEcrire(), 403);

        $demande = DemandeDeVisite::findOrFail($id);

        if ($utilisateur === '') {
            $demande->assigne_a = null;
            $demande->save();
            $this->message = __('Demande désassignée.');

            return;
        }

        $compte = User::whereKey((int) $utilisateur)
            ->whereHas('roles', fn ($r) => $r->whereIn('name', ['administrateur', 'editeur', 'redacteur']))
            ->first();

        abort_unless($compte, 404);

        $demande->assigne_a = $compte->id;
        $demande->save();

        $this->message = __('Visite confiée à :nom.', ['nom' => $compte->name]);
    }

    public function supprimer(int $id): void
    {
        abort_unless($this->peutEcrire(), 403);

        DemandeDeVisite::findOrFail($id)->delete();
        $this->message = __('Demande supprimée.');
    }

    public function render(): View
    {
        $langue = app()->getLocale();
        $taux = DemandeDeVisite::tauxDeConcretisation();

        $demandes = DemandeDeVisite::query()
            ->with(['bien', 'assigne'])
            ->when($this->recherche !== '', function ($r) {
                $motif = '%'.trim($this->recherche).'%';
                $r->where(fn ($q) => $q->where('nom', 'like', $motif)
                    ->orWhere('telephone', 'like', $motif)
                    ->orWhere('bien_intitule', 'like', $motif));
            })
            ->when($this->statut !== '', fn ($r) => $r->where('statut', $this->statut))
            ->when($this->assigne !== '', fn ($r) => $r->where('assigne_a', $this->assigne))
            ->recentes()
            ->paginate(25);

        return view('livewire.admin.demande-de-visite-liste', [
            'demandes' => $demandes,
            'langue' => $langue,
            'peutEcrire' => $this->peutEcrire(),
            'statuts' => DemandeDeVisite::statuts(),
            'collaborateurs' => User::query()
                ->whereHas('roles', fn ($r) => $r->whereIn('name', ['administrateur', 'editeur', 'redacteur']))
                ->orderBy('name')->get(),
            'statistiques' => [
                [
                    'intitule' => __('Demandes ce mois'),
                    'valeur' => DemandeDeVisite::where('created_at', '>=', now()->startOfMonth())->count(),
                ],
                [
                    'intitule' => __('À confirmer'),
                    'valeur' => DemandeDeVisite::aConfirmer()->count(),
                    'ton' => DemandeDeVisite::aConfirmer()->count() > 0 ? 'alerte' : 'neutre',
                ],
                [
                    'intitule' => __('Réalisées'),
                    'valeur' => DemandeDeVisite::where('statut', DemandeDeVisite::REALISEE)->count(),
                ],
                [
                    'intitule' => __('Taux de concrétisation'),
                    // Le tiret plutot que « 0 % » : rien n'est encore sorti du
                    // statut « a confirmer », il n'y a donc rien a mesurer.
                    'valeur' => $taux === null ? '—' : $taux.' %',
                ],
            ],
        ])->title(__('Demandes de visite'));
    }
}

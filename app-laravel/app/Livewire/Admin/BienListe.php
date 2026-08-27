<?php

namespace App\Livewire\Admin;

use App\Models\Bien;
use App\Models\Referentiel;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Catalogue des biens.
 *
 * Les cinq filtres sont EXACTEMENT ceux de la page publique — memes familles,
 * memes valeurs techniques. C'est tout l'objet du referentiel : un bien
 * retrouve ici par un filtre doit l'etre par le meme filtre cote visiteur.
 */
#[Layout('layouts.app')]
class BienListe extends Component
{
    use WithPagination;

    public string $recherche = '';

    public string $type = '';

    public string $offre = '';

    public string $zone = '';

    public string $pieces = '';

    public string $surface = '';

    public string $statut = '';

    public string $tri = 'recent';

    public ?string $message = null;

    /** @var list<string> */
    protected array $filtres = ['recherche', 'type', 'offre', 'zone', 'pieces', 'surface', 'statut', 'tri'];

    public function updating($nom): void
    {
        if (in_array($nom, $this->filtres, true)) {
            $this->resetPage();
        }
    }

    public function reinitialiser(): void
    {
        $this->reset($this->filtres);
        $this->tri = 'recent';
        $this->resetPage();
    }

    protected function peutEcrire(): bool
    {
        return (bool) auth()->user()?->hasAnyRole(['administrateur', 'editeur']);
    }

    public function supprimer(int $id): void
    {
        abort_unless($this->peutEcrire(), 403);

        $bien = Bien::findOrFail($id);
        $titre = $bien->titre_fr;

        // Les photos partent avec lui : la contrainte les efface en cascade,
        // et les fichiers sont retires du disque avant.
        foreach ($bien->photos as $photo) {
            if ($chemin = $photo->cheminEffacable()) {
                Storage::disk('public')->delete($chemin);
            }
        }

        $bien->delete();

        $this->message = __('Bien « :titre » supprimé.', ['titre' => $titre]);
    }

    public function render(): View
    {
        $langue = app()->getLocale();

        $biens = Bien::query()
            ->with('photos')
            ->when($this->recherche !== '', function ($r) {
                $motif = '%'.trim($this->recherche).'%';
                $r->where(fn ($q) => $q->where('titre_fr', 'like', $motif)
                    ->orWhere('titre_en', 'like', $motif)
                    ->orWhere('reference', 'like', $motif)
                    ->orWhere('quartier', 'like', $motif));
            })
            ->when($this->type !== '', fn ($r) => $r->where('type', $this->type))
            ->when($this->offre !== '', fn ($r) => $r->where('offre', $this->offre))
            ->when($this->zone !== '', fn ($r) => $r->where('zone', $this->zone))
            ->when($this->pieces !== '', fn ($r) => $r->deLaTrancheDePieces($this->pieces))
            ->when($this->surface !== '', fn ($r) => $r->deLaTrancheDeSurface($this->surface))
            ->when($this->statut !== '', fn ($r) => $r->where('statut', $this->statut))
            ->when($this->tri === 'prix_croissant', fn ($r) => $r->orderByRaw('prix IS NULL')->orderBy('prix'))
            ->when($this->tri === 'prix_decroissant', fn ($r) => $r->orderByRaw('prix IS NULL')->orderByDesc('prix'))
            ->when($this->tri === 'surface', fn ($r) => $r->orderByRaw('COALESCE(surface_habitable, surface_terrain) DESC'))
            ->when($this->tri === 'recent', fn ($r) => $r->ordonnes())
            ->paginate(20);

        return view('livewire.admin.bien-liste', [
            'biens' => $biens,
            'langue' => $langue,
            'peutEcrire' => $this->peutEcrire(),
            'types' => Referentiel::deLaFamille('types_de_bien')->ordonnees()->get(),
            'zones' => Referentiel::deLaFamille('zones')->ordonnees()->get(),
            'tranchesPieces' => Referentiel::deLaFamille('tranches_pieces')->ordonnees()->get(),
            'tranchesSurface' => Referentiel::deLaFamille('tranches_surface')->ordonnees()->get(),
            'offres' => Bien::offres(),
            'statuts' => Bien::statuts(),
            'statistiques' => [
                ['intitule' => __('Biens au catalogue'), 'valeur' => Bien::count()],
                ['intitule' => __('En ligne'), 'valeur' => Bien::publies()->count()],
                [
                    'intitule' => __('Brouillons'),
                    'valeur' => Bien::where('statut', Bien::BROUILLON)->count(),
                    'ton' => Bien::where('statut', Bien::BROUILLON)->count() > 0 ? 'alerte' : 'neutre',
                ],
                [
                    'intitule' => __('Sans photo'),
                    'valeur' => Bien::doesntHave('photos')->count(),
                    // Les six biens repris du site n'en ont aucune : c'est un
                    // etat de depart normal, pas une anomalie a signaler en
                    // rouge.
                    'detail' => __('Illustration dessinée en attendant'),
                ],
            ],
        ])->title(__('Biens immobiliers'));
    }
}

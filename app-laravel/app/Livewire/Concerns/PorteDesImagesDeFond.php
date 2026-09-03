<?php

namespace App\Livewire\Concerns;

use App\Models\ImageDeFond;
use Illuminate\Support\Collection;

/**
 * Les images de fond qu'un module d'ecran de page porte.
 *
 * Un module en declare une sous la cle `fond`, ou plusieurs sous `fonds` — les
 * six tuiles de services ont chacune la leur.
 *
 * Toute ligne visible de la table devient une variable CSS `--img-{slug}` sur
 * le site public (voir AppServiceProvider::variablesImagesDeFond). Une image
 * qu'aucun module ne declare est donc AFFICHEE par le site sans etre
 * modifiable nulle part : c'est ce qui est arrive a neuf d'entre elles au
 * retrait de l'ecran « Images de fond ». Le test d'audit compare les deux
 * listes pour que le trou ne se recreuse pas.
 */
trait PorteDesImagesDeFond
{
    /**
     * Les images du module ouvert, dans l'ordre de leur declaration.
     *
     * @return Collection<int, ImageDeFond>
     */
    public function imagesDuModule(): Collection
    {
        $description = $this->moduleCourant();

        $slugs = $description['fonds'] ?? array_filter([$description['fond'] ?? null]);

        if ($slugs === []) {
            return collect();
        }

        $trouvees = ImageDeFond::whereIn('slug', $slugs)->get()->keyBy('slug');

        // L'ordre suit la DECLARATION, pas la base : l'editeur relit son ecran
        // dans l'ordre ou il a pense les images.
        return collect($slugs)
            ->map(fn (string $slug) => $trouvees->get($slug))
            ->filter()
            ->values();
    }
}

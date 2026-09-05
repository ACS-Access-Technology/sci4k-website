<?php

namespace App\Livewire\Admin;

use App\Models\ImageDeFond;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Mediatheque extends Component
{
    public string $recherche = '';

    public string $type = '';

    public ?string $selection = null;

    public function ouvrir(string $chemin): void
    {
        abort_unless($this->imageConnue($chemin), 404);
        $this->selection = $chemin;
    }

    public function fermer(): void
    {
        $this->selection = null;
    }

    /**
     * Les images de fond qui n'appartiennent a AUCUNE page.
     *
     * Le pied de page s'affiche sur toutes, les pages d'erreur sur aucune :
     * ni l'un ni l'autre n'a d'ecran de page ou se ranger. Elles echouaient
     * donc entre deux chaises au retrait de l'ecran « Images de fond » — le
     * site les affichait, plus rien ne les modifiait.
     *
     * La mediatheque les accueille : c'est deja l'ecran de ce qui n'est a
     * aucune page, et il reste ouvert aux editeurs, la ou la configuration est
     * reservee aux administrateurs.
     *
     * @return Collection<int, ImageDeFond>
     */
    public const FONDS_SANS_PAGE = ['footer', 'erreur'];

    protected function fondsGlobaux(): Collection
    {
        $trouvees = ImageDeFond::whereIn('slug', self::FONDS_SANS_PAGE)
            ->get()
            ->keyBy('slug');

        return collect(self::FONDS_SANS_PAGE)
            ->map(fn (string $slug) => $trouvees->get($slug))
            ->filter()
            ->values();
    }

    /** @return list<array{chemin: string, nom: string, extension: string, taille: string}> */
    protected function images(): array
    {
        $extensions = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'];
        $dossiers = [
            'images' => public_path('images'),
            'storage' => storage_path('app/public'),
        ];

        $images = collect();

        foreach ($dossiers as $prefixe => $racine) {
            if (! File::isDirectory($racine)) {
                continue;
            }

            $images = $images->merge(collect(File::allFiles($racine))
                ->filter(fn ($fichier) => in_array(strtolower($fichier->getExtension()), $extensions, true))
                ->map(function ($fichier) use ($racine, $prefixe) {
                    $cheminRelatif = str_replace(DIRECTORY_SEPARATOR, '/', ltrim(str_replace($racine, '', $fichier->getPathname()), DIRECTORY_SEPARATOR));
                    $cheminPublic = $prefixe === 'storage' ? 'storage/'.$cheminRelatif : 'images/'.$cheminRelatif;

                    return [
                        'chemin' => $cheminPublic,
                        'nom' => $fichier->getFilename(),
                        'extension' => strtolower($fichier->getExtension()),
                        'taille' => number_format($fichier->getSize() / 1024, 0, ',', ' ').' Ko',
                        'source' => $prefixe,
                    ];
                }));
        }

        return $images
            ->filter(fn ($image) => $this->recherche === '' || str_contains(strtolower($image['nom']), strtolower($this->recherche)))
            ->filter(fn ($image) => $this->type === '' || $image['extension'] === $this->type)
            ->sortBy('nom', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }

    protected function imageConnue(string $chemin): bool
    {
        return collect($this->images())->contains('chemin', $chemin);
    }

    public function render(): View
    {
        return view('livewire.admin.mediatheque', [
            'images' => $this->images(),
            'total' => count($this->images()),
            'fondsGlobaux' => $this->fondsGlobaux(),
        ])->title(__('Médiathèque'));
    }
}

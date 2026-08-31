<?php

namespace App\Livewire\Admin;

use Illuminate\Contracts\View\View;
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
        ])->title(__('Médiathèque'));
    }
}

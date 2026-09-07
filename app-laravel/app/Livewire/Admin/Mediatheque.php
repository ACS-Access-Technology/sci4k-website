<?php

namespace App\Livewire\Admin;

use App\Models\ImageDeFond;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\FileAttributes;
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

        // Deux sources, et deux façons de les lire.
        //
        // Les visuels des maquettes sont deposes dans public/images au moment
        // de la construction : ce sont des fichiers du depot, lisibles la ou
        // l'application tourne, y compris sur une plateforme au disque en
        // lecture seule.
        //
        // Les fichiers TELEVERSES, eux, passent par Storage::disk('public'), et
        // c'est ce qui compte ici : ce disque vit sur le serveur ou dans un
        // stockage objet selon la configuration. Les lire par leur chemin
        // physique rendait cet ecran vide des que le disque partait ailleurs.
        $images = collect($this->imagesDuDepot($extensions))
            ->merge($this->imagesTeleversees($extensions));

        return $images
            ->filter(fn ($image) => $this->recherche === '' || str_contains(strtolower($image['nom']), strtolower($this->recherche)))
            ->filter(fn ($image) => $this->type === '' || $image['extension'] === $this->type)
            ->sortBy('nom', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }

    /**
     * Les visuels livres avec le depot, deposes dans public/images.
     *
     * @param  list<string>  $extensions
     * @return list<array<string, string>>
     */
    protected function imagesDuDepot(array $extensions): array
    {
        $racine = public_path('images');

        if (! File::isDirectory($racine)) {
            return [];
        }

        return collect(File::allFiles($racine))
            ->filter(fn ($fichier) => in_array(strtolower($fichier->getExtension()), $extensions, true))
            ->map(fn ($fichier) => [
                'chemin' => 'images/'.str_replace(DIRECTORY_SEPARATOR, '/', ltrim(str_replace($racine, '', $fichier->getPathname()), DIRECTORY_SEPARATOR)),
                'nom' => $fichier->getFilename(),
                'extension' => strtolower($fichier->getExtension()),
                'taille' => $this->enKilooctets($fichier->getSize()),
                'source' => 'images',
            ])
            ->values()
            ->all();
    }

    /**
     * Les fichiers televerses depuis le backoffice, lus sur le disque configure.
     *
     * Un seul listing plutot qu'une taille demandee fichier par fichier : sur un
     * stockage objet, chaque appel est un aller-retour reseau.
     *
     * @param  list<string>  $extensions
     * @return list<array<string, string>>
     */
    protected function imagesTeleversees(array $extensions): array
    {
        $disque = Storage::disk('public');

        // instanceof plutot que isFile() : le second filtre a l'execution mais
        // laisse le type a StorageAttributes, qui ne porte pas fileSize().
        return collect($disque->getDriver()->listContents('', true)->toArray())
            ->filter(fn ($element) => $element instanceof FileAttributes)
            ->filter(fn ($element) => in_array(strtolower(pathinfo($element->path(), PATHINFO_EXTENSION)), $extensions, true))
            ->map(fn (FileAttributes $element) => [
                'chemin' => 'storage/'.$element->path(),
                'nom' => basename($element->path()),
                'extension' => strtolower(pathinfo($element->path(), PATHINFO_EXTENSION)),
                'taille' => $this->enKilooctets($element->fileSize() ?? 0),
                'source' => 'storage',
            ])
            ->values()
            ->all();
    }

    protected function enKilooctets(int $octets): string
    {
        return number_format($octets / 1024, 0, ',', ' ').' Ko';
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

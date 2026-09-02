<?php

namespace App\Livewire\Admin;

use App\Models\Service;
use Illuminate\Support\Facades\Storage;

class ServiceListe extends ListeOrdonnable
{
    protected function modele(): string
    {
        return Service::class;
    }

    protected function colonnesRecherchees(): array
    {
        return ['nom_fr', 'nom_en', 'accroche_fr', 'accroche_en'];
    }

    /** Ouvert sur place quand la liste est embarquee dans un ecran de page. */
    protected function composantFormulaire(): ?string
    {
        return 'admin.service-formulaire';
    }

    /** ServiceFormulaire n'herite pas de FormulaireDeBloc : son modele s'appelle « service ». */
    protected function parametreDuFormulaire(): string
    {
        return 'service';
    }

    protected function vue(): string
    {
        return 'livewire.admin.service-liste';
    }

    protected function titre(): string
    {
        return __('Services');
    }

    /** La colonne « Catégorie » declencherait sinon une requete par ligne. */
    protected function relationsAPrecharger(): array
    {
        return ['categorie'];
    }

    /**
     * Retire le service, et le fichier de son image avec lui.
     *
     * L'image televersee part avec le service, sans quoi son fichier resterait
     * sur le disque sans plus rien pour le designer. Il n'est efface qu'apres
     * la suppression en base : un fichier detruit ne se rejoue pas, alors
     * qu'une ligne restee en base se resupprime. Un visuel repris du site
     * statique, lui, n'est jamais touche — son fichier vit dans frontoffice/
     * et sert encore les pages que le service ne porte pas.
     *
     * Depuis que la FAQ a ses propres rubriques, un service ne porte plus de
     * questions : il n'y a donc plus rien a proteger de ce cote.
     */
    public function supprimer(int $id): void
    {
        abort_unless($this->peutEcrire(), 403);

        $service = Service::findOrFail($id);
        $image = $service->cheminEffaçable();

        $service->delete();

        if ($image) {
            Storage::disk('public')->delete($image);
        }

        session()->flash('message', __('Service supprimé.'));
    }
}

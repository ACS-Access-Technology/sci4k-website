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

    protected function vue(): string
    {
        return 'livewire.admin.service-liste';
    }

    protected function titre(): string
    {
        return __('Services');
    }

    /**
     * Retire le service, apres deux verifications que l'abstrait ne fait pas.
     *
     * La cle etrangere questions_faq.service_id est en RESTRICT, comme
     * partout dans le projet : sans le premier controle, l'appel remonterait
     * une QueryException et l'editeur verrait une page 500 au lieu d'une
     * phrase lui disant quoi faire.
     *
     * Le fichier d'une image televersee est efface avec le service, sans quoi
     * il resterait sur le disque sans plus rien pour le designer. Un visuel
     * repris du site statique, lui, n'est jamais touche : son fichier vit
     * dans frontoffice/ et sert encore les pages que le service ne porte pas.
     */
    public function supprimer(int $id): void
    {
        abort_unless($this->peutEcrire(), 403);

        $service = Service::findOrFail($id);

        if ($service->questionsFaq()->exists()) {
            session()->flash('erreur', __('Ce service porte des questions de FAQ. Retirez-les ou rattachez-les à un autre service avant de le supprimer.'));

            return;
        }

        if ($service->imageTeleversee()) {
            Storage::disk('public')->delete(substr($service->image_source, strlen('storage/')));
        }

        $service->delete();

        session()->flash('message', __('Service supprimé.'));
    }
}

<?php

namespace App\Livewire\Admin;

use App\Models\Service;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
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
     * Compte les questions de FAQ pour que la confirmation les annonce.
     *
     * Le chiffre est le seul moyen pour l'editeur de savoir qu'il detruit
     * aussi du contenu de FAQ : sans lui, la boite de confirmation ne parle
     * que du service.
     */
    protected function elements(): Collection
    {
        return parent::elements()->loadCount('questionsFaq');
    }

    /**
     * Retire le service et ce qui ne survit pas sans lui.
     *
     * La cle etrangere questions_faq.service_id est en RESTRICT, comme partout
     * dans le projet : les questions doivent donc partir AVANT le service,
     * faute de quoi l'appel remonterait une QueryException et l'editeur
     * verrait une page 500. Les deux effacements tiennent dans une seule
     * transaction : interrompus a mi-chemin, ils laisseraient des questions
     * sans service, que la FAQ publique ne sait pas afficher.
     *
     * Le fichier d'une image televersee part avec le service, sans quoi il
     * resterait sur le disque sans plus rien pour le designer. Il n'est efface
     * qu'apres la transaction : un fichier detruit ne se rejoue pas, alors
     * qu'une ligne restee en base se resupprime. Un visuel repris du site
     * statique, lui, n'est jamais touche — son fichier vit dans frontoffice/
     * et sert encore les pages que le service ne porte pas.
     */
    public function supprimer(int $id): void
    {
        abort_unless($this->peutEcrire(), 403);

        $service = Service::findOrFail($id);
        $questions = $service->questionsFaq()->count();

        DB::transaction(function () use ($service) {
            $service->questionsFaq()->delete();
            $service->delete();
        });

        if ($service->imageTeleversee()) {
            Storage::disk('public')->delete(substr($service->image_source, strlen('storage/')));
        }

        session()->flash('message', $questions === 0
            ? __('Service supprimé.')
            : trans_choice('Service supprimé, avec :nombre question de FAQ.|Service supprimé, avec ses :nombre questions de FAQ.', $questions, ['nombre' => $questions]));
    }
}

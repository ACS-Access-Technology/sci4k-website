<?php

namespace App\Livewire\Admin;

use App\Models\Service;

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

    /** Les six services correspondent aux six metiers et a la navigation du
     *  site : en retirer un touche la structure des pages publiques, ce qui
     *  releve d'un developpement, pas d'une saisie. */
    protected function suppressionPermise(): bool
    {
        return false;
    }
}

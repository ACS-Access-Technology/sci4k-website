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
}

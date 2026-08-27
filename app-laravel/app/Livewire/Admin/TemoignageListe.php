<?php

namespace App\Livewire\Admin;

use App\Models\Temoignage;

/*
 * Les temoignages affiches sur l'accueil.
 */
class TemoignageListe extends ListeOrdonnable
{
    protected function modele(): string
    {
        return Temoignage::class;
    }

    protected function colonnesRecherchees(): array
    {
        return ['auteur', 'citation_fr', 'citation_en', 'role_fr', 'role_en'];
    }

    protected function vue(): string
    {
        return 'livewire.admin.temoignage-liste';
    }

    protected function titre(): string
    {
        return __('Témoignages');
    }
}

<?php

namespace App\Livewire\Admin;

use App\Models\MembreEquipe;

/*
 * Les membres de l'equipe, affiches sur la page de presentation.
 */
class MembreEquipeListe extends ListeOrdonnable
{
    protected function modele(): string
    {
        return MembreEquipe::class;
    }

    protected function colonnesRecherchees(): array
    {
        return ['nom', 'fonction_fr', 'fonction_en', 'biographie_fr', 'biographie_en'];
    }

    protected function vue(): string
    {
        return 'livewire.admin.membre-equipe-liste';
    }

    protected function titre(): string
    {
        return __('Équipe');
    }
}

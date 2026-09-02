<?php

namespace App\Livewire\Admin;

use App\Models\Partenaire;

/*
 * Les partenaires affiches sur l'accueil.
 */
class PartenaireListe extends ListeOrdonnable
{
    protected function modele(): string
    {
        return Partenaire::class;
    }

    protected function colonnesRecherchees(): array
    {
        return ['nom'];
    }

    /** Ouvert sur place quand la liste est embarquee dans un ecran de page. */
    protected function composantFormulaire(): ?string
    {
        return 'admin.partenaire-formulaire';
    }

    protected function vue(): string
    {
        return 'livewire.admin.partenaire-liste';
    }

    protected function titre(): string
    {
        return __('Partenaires');
    }
}

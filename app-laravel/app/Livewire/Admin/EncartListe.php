<?php

namespace App\Livewire\Admin;

use App\Models\Encart;

/*
 * Les encarts d'appel a l'action.
 */
class EncartListe extends ListeOrdonnable
{
    protected function modele(): string
    {
        return Encart::class;
    }

    protected function colonnesRecherchees(): array
    {
        return ['titre_fr', 'titre_en', 'texte_fr', 'texte_en'];
    }

    protected function vue(): string
    {
        return 'livewire.admin.encart-liste';
    }

    protected function titre(): string
    {
        return __('Annonces & Actions');
    }

    /** Le slug designe l'endroit du site qui affiche l'encart : un encart cree ne s'afficherait nulle part, un encart supprime laisserait un vide. */
    protected function suppressionPermise(): bool
    {
        return false;
    }
}

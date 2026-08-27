<?php

namespace App\Livewire\Admin;

use App\Models\ImageDeFond;

/*
 * Les images de fond des sections du site.
 *
 * Le slug reprend la variable CSS qui les sert : c'est lui qui relie une image
 * a l'endroit qui l'affiche.
 */
class ImageDeFondListe extends ListeOrdonnable
{
    protected function modele(): string
    {
        return ImageDeFond::class;
    }

    protected function colonnesRecherchees(): array
    {
        return ['slug', 'texte_alternatif_fr', 'texte_alternatif_en'];
    }

    protected function vue(): string
    {
        return 'livewire.admin.image-de-fond-liste';
    }

    protected function titre(): string
    {
        return __('Images de fond');
    }

    /** Le slug correspond a une variable CSS : une image creee ne serait servie par aucune regle, une image supprimee laisserait la section sans fond. */
    protected function suppressionPermise(): bool
    {
        return false;
    }
}

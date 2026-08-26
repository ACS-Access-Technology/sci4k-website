<?php

namespace App\Livewire\Admin;

use App\Models\Valeur;

/*
 * Les quatre valeurs de la page de presentation.
 *
 * Leur nombre suit la maquette, qui les dispose en grille de quatre : en
 * ajouter une casserait la mise en page sans que rien ne le signale.
 */
class ValeurEnsemble extends EnsembleFige
{
    protected function modele(): string
    {
        return Valeur::class;
    }

    protected function champsBilingues(): array
    {
        return ['titre', 'texte'];
    }

    protected function vue(): string
    {
        return 'livewire.admin.valeur-ensemble';
    }

    protected function titre(): string
    {
        return __('Valeurs');
    }
}

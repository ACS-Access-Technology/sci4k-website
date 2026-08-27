<?php

namespace App\Livewire\Admin;

use App\Models\Valeur;

/*
 * Les valeurs affichees sur la page de presentation.
 *
 * La maquette values-list.html les montre en grille, chacune avec son icone,
 * sa visibilite et son rang, plus un bouton d'ajout. Le panneau de reglages
 * edite l'en-tete de la section « about.values », deja porte par
 * ReglageDeSection : une seconde table aurait dit la meme chose.
 */
class ValeurEnsemble extends EditionGroupee
{
    protected function modele(): string
    {
        return Valeur::class;
    }

    protected function champsBilingues(): array
    {
        return ['titre', 'texte'];
    }

    protected function sectionReglee(): ?string
    {
        return 'about.values';
    }

    protected function vue(): string
    {
        return 'livewire.admin.valeur-ensemble';
    }

    protected function titre(): string
    {
        return __('Valeurs et engagements');
    }
}

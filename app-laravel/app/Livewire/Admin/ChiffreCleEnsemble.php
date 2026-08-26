<?php

namespace App\Livewire\Admin;

use App\Models\ChiffreCle;

/*
 * Les trois chiffres du bandeau d'accueil.
 */
class ChiffreCleEnsemble extends EnsembleFige
{
    protected function modele(): string
    {
        return ChiffreCle::class;
    }

    protected function champsBilingues(): array
    {
        return ['intitule'];
    }

    protected function champsSimples(): array
    {
        // Le nombre que le compteur anime jusqu'a lui. Borne haut : au-dela,
        // l'animation defile plus longtemps que le visiteur ne regarde.
        return ['valeur' => ['required', 'integer', 'min:0', 'max:100000']];
    }

    protected function vue(): string
    {
        return 'livewire.admin.chiffre-cle-ensemble';
    }

    protected function titre(): string
    {
        return __('Chiffres clés');
    }
}

<?php

namespace App\Livewire\Admin;

use App\Models\CommuneDuBandeau;

/*
 * Le bandeau defilant de communes, sur la page d'accueil.
 *
 * Un seul champ par ligne — le nom — et trois reglages d'apparence. C'est
 * exactement ce pour quoi EditionGroupee a ete ecrit au lot 2 : tous les
 * elements cote a cote, un seul bouton d'enregistrement, et un panneau de
 * reglages qui edite l'en-tete de section correspondante.
 *
 * Aucun champ bilingue : les noms de communes sont des noms propres.
 */
class CommuneBandeauEnsemble extends EditionGroupee
{
    protected function modele(): string
    {
        return CommuneDuBandeau::class;
    }

    protected function champsBilingues(): array
    {
        return [];
    }

    protected function champsSimples(): array
    {
        return ['nom' => ['required', 'string', 'max:80']];
    }

    protected function sectionReglee(): ?string
    {
        return CommuneDuBandeau::SECTION;
    }

    /**
     * Les trois reglages d'apparence de la maquette.
     *
     * Ils vivent dans les options de l'en-tete de section, comme la duree
     * d'animation des chiffres cles et la mise en page du processus : une
     * colonne par reglage aurait impose une migration a chaque nouveau, pour
     * des donnees que seul l'affichage consulte.
     */
    protected function optionsDuBloc(): array
    {
        return [
            'fond' => 'sombre',
            'separateur' => '·',
            'casse' => 'majuscules',
        ];
    }

    protected function vue(): string
    {
        return 'livewire.admin.commune-bandeau-ensemble';
    }

    protected function titre(): string
    {
        return __('Banderole des communes');
    }
}

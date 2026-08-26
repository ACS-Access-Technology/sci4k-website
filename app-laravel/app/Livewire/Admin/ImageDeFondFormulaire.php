<?php

namespace App\Livewire\Admin;

use App\Models\ImageDeFond;

/*
 * Edition d'une image de fond.
 *
 * Le slug est fige : il correspond a une variable CSS, et le changer
 * detacherait l'image de l'endroit qui la sert.
 */
class ImageDeFondFormulaire extends FormulaireDeBloc
{
    protected function modele(): string
    {
        return ImageDeFond::class;
    }

    protected function champs(): array
    {
        return [
            'slug' => [
                'intitule' => __("Identifiant d'emplacement"),
                'type' => 'fige',
                'regles' => ['required', 'string', 'max:190', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
                'aide' => __('Correspond à la variable CSS qui sert cette image. Définitif.'),
            ],
            'texte_alternatif' => [
                'intitule' => __('Texte de remplacement'),
                'type' => 'texte',
                'bilingue' => true,
                'regles' => ['nullable', 'string', 'max:255'],
                'aide' => __("Lu par les lecteurs d'écran quand l'image ne s'affiche pas."),
            ],
        ];
    }

    protected function fichierGere(): ?array
    {
        return ['fichier', 'fonds'];
    }

    protected function vue(): string
    {
        return 'livewire.admin.image-de-fond-formulaire';
    }

    protected function routeListe(): string
    {
        return 'admin.images-de-fond.liste';
    }

    protected function intitule(): string
    {
        return __('Image de fond');
    }

    /** Le slug correspond a une variable CSS : une image creee ne serait servie par aucune regle, une image supprimee laisserait la section sans fond. */
    protected function creationPermise(): bool
    {
        return false;
    }

    /**
     * Le liage de route a besoin d'un type instanciable : Laravel ne sait pas
     * resoudre un Model abstrait depuis l'adresse. Chaque formulaire declare
     * donc son modele et delegue a preparer() — PHP interdisant a une classe
     * fille de restreindre le type d'un parametre herite.
     */
    public function mount(?ImageDeFond $element = null): void
    {
        $this->preparer($element);
    }

    protected function descriptionDuFichier(): array
    {
        return [
            'intitule' => __('Fichier de fond'),
            'aide' => __('1920 × 800 px minimum. Un voile sombre est appliqué automatiquement.'),
            'forme' => 'rectangle',
        ];
    }
}

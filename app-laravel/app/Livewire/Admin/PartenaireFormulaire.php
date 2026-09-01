<?php

namespace App\Livewire\Admin;

use App\Models\Partenaire;

/*
 * Edition d'un partenaire.
 *
 * Aucun champ traduisible : un nom d'organisation, un logo, une adresse. Le
 * site est facultatif — deux des sept partenaires repris n'en ont pas, et leur
 * logo n'est alors pas presente comme un lien.
 */
class PartenaireFormulaire extends FormulaireDeBloc
{
    protected function modele(): string
    {
        return Partenaire::class;
    }

    protected function champs(): array
    {
        return [
            'nom' => [
                'intitule' => __('Nom'),
                'type' => 'texte',
                'regles' => ['required', 'string', 'max:190'],
            ],
            'site' => [
                'intitule' => __('Site officiel'),
                'type' => 'url',
                'regles' => ['nullable', 'url', 'max:255'],
                'aide' => __("Facultatif. Sans adresse, le logo n'est pas cliquable."),
            ],
        ];
    }

    protected function fichierGere(): ?array
    {
        return ['logo', 'partenaires'];
    }

    protected function vue(): string
    {
        return 'livewire.admin.partenaire-formulaire';
    }

    protected function routeListe(): string
    {
        return 'admin.partenaires.liste';
    }

    protected function intitule(): string
    {
        return __('Partenaire');
    }

    /**
     * Le liage de route a besoin d'un type instanciable : Laravel ne sait pas
     * resoudre un Model abstrait depuis l'adresse. Chaque formulaire declare
     * donc son modele et delegue a preparer() — PHP interdisant a une classe
     * fille de restreindre le type d'un parametre herite.
     */
    public function mount(?Partenaire $element = null): void
    {
        $this->preparer($element);
    }

    /**
     * Pas de recadrage sur un logo de partenaire.
     *
     * Deux raisons, et la seconde est la pire. Un logo n'a pas de proportions
     * negociables — le contraindre dans un carre l'etire ou le rogne, alors
     * que le site l'affiche a hauteur libre. Et le recadrage rend un JPEG,
     * sans couche alpha : le fond transparent que l'aide reclame juste en
     * dessous ressortait noir.
     */
    protected function recadrageDuFichier(): bool
    {
        return false;
    }

    protected function descriptionDuFichier(): array
    {
        return [
            'intitule' => __('Logo du partenaire'),
            'aide' => __('Fond transparent de préférence, le logo étant posé sur la couleur de la page.'),
            'forme' => 'rectangle',
        ];
    }
}

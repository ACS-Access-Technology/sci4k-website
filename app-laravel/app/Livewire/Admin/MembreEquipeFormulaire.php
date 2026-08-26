<?php

namespace App\Livewire\Admin;

use App\Models\MembreEquipe;

/*
 * Edition d'un membre de l'equipe.
 *
 * Le nom n'a qu'une colonne : c'est un nom propre.
 */
class MembreEquipeFormulaire extends FormulaireDeBloc
{
    protected function modele(): string
    {
        return MembreEquipe::class;
    }

    protected function champs(): array
    {
        return [
            'nom' => [
                'intitule' => __('Nom'),
                'type' => 'texte',
                'regles' => ['required', 'string', 'max:190'],
                'aide' => __('Nom propre : il ne se traduit pas.'),
            ],
            'etiquette' => [
                'intitule' => __('Étiquette'),
                'type' => 'texte',
                'bilingue' => true,
                'regles' => ['nullable', 'string', 'max:190'],
                'aide' => __('Affichée sur la vignette, par exemple « Direction ».'),
            ],
            'fonction' => [
                'intitule' => __('Fonction'),
                'type' => 'texte',
                'bilingue' => true,
                'regles' => ['required', 'string', 'max:190'],
            ],
            'biographie' => [
                'intitule' => __('Présentation'),
                'type' => 'zone',
                'bilingue' => true,
                'regles' => ['nullable', 'string', 'max:2000'],
            ],
            'linkedin' => [
                'intitule' => __('LinkedIn'),
                'type' => 'url',
                'regles' => ['nullable', 'url', 'max:255'],
            ],
            'email' => [
                'intitule' => __('Adresse e-mail'),
                'type' => 'email',
                'regles' => ['nullable', 'email', 'max:190'],
            ],
        ];
    }

    protected function fichierGere(): ?array
    {
        return ['photo', 'equipe'];
    }

    protected function vue(): string
    {
        return 'livewire.admin.membre-equipe-formulaire';
    }

    protected function routeListe(): string
    {
        return 'admin.equipe.liste';
    }

    protected function intitule(): string
    {
        return __('Membre');
    }

    /**
     * Le liage de route a besoin d'un type instanciable : Laravel ne sait pas
     * resoudre un Model abstrait depuis l'adresse. Chaque formulaire declare
     * donc son modele et delegue a preparer() — PHP interdisant a une classe
     * fille de restreindre le type d'un parametre herite.
     */
    public function mount(?MembreEquipe $element = null): void
    {
        $this->preparer($element);
    }
}

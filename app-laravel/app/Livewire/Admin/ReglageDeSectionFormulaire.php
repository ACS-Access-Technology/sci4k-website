<?php

namespace App\Livewire\Admin;

use App\Models\ReglageDeSection;

/*
 * Edition de l'en-tete d'une section.
 *
 * Le slug identifie la section : il ne se modifie pas, la page qui lit cet
 * en-tete le cherche par ce nom.
 */
class ReglageDeSectionFormulaire extends FormulaireDeBloc
{
    protected function modele(): string
    {
        return ReglageDeSection::class;
    }

    protected function champs(): array
    {
        return [
            'slug' => [
                'intitule' => __('Section'),
                'type' => 'fige',
                'regles' => ['required', 'string', 'max:190'],
                'aide' => __('Identifie la section du site. Définitif.'),
            ],
            'etiquette' => [
                'intitule' => __('Étiquette'),
                'type' => 'texte',
                'bilingue' => true,
                'regles' => ['nullable', 'string', 'max:190'],
                'aide' => __('Petit texte affiché au-dessus du titre.'),
            ],
            'titre' => [
                'intitule' => __('Titre'),
                'type' => 'texte',
                'bilingue' => true,
                'regles' => ['nullable', 'string', 'max:255'],
            ],
            'chapo' => [
                'intitule' => __('Chapô'),
                'type' => 'zone',
                'bilingue' => true,
                'regles' => ['nullable', 'string', 'max:2000'],
            ],
        ];
    }

    protected function vue(): string
    {
        return 'livewire.admin.reglage-de-section-formulaire';
    }

    protected function routeListe(): string
    {
        return 'admin.reglages-de-section.liste';
    }

    protected function intitule(): string
    {
        return __('En-tête de section');
    }

    /** Les sections sont celles du site : en ajouter une ne l'afficherait nulle part, en retirer une laisserait la page sans en-tete. */
    protected function creationPermise(): bool
    {
        return false;
    }

    /** La table des en-tetes de section n'a pas de colonne `visible`. */
    protected function gereLaVisibilite(): bool
    {
        return false;
    }

    /**
     * Le liage de route a besoin d'un type instanciable : Laravel ne sait pas
     * resoudre un Model abstrait depuis l'adresse. Chaque formulaire declare
     * donc son modele et delegue a preparer() — PHP interdisant a une classe
     * fille de restreindre le type d'un parametre herite.
     */
    public function mount(?ReglageDeSection $element = null): void
    {
        $this->preparer($element);
    }
}

<?php

namespace App\Livewire\Admin;

use App\Models\Temoignage;

/*
 * Edition d'un temoignage.
 *
 * L'auteur et ses initiales n'ont qu'une seule colonne : ce sont des noms
 * propres, identiques dans les deux langues.
 */
class TemoignageFormulaire extends FormulaireDeBloc
{
    protected function modele(): string
    {
        return Temoignage::class;
    }

    protected function champs(): array
    {
        return [
            'auteur' => [
                'intitule' => __('Auteur'),
                'type' => 'texte',
                'regles' => ['required', 'string', 'max:190'],
                'aide' => __('Nom propre : il ne se traduit pas.'),
            ],
            'initiales' => [
                'intitule' => __('Initiales'),
                'type' => 'texte',
                'regles' => ['nullable', 'string', 'max:8'],
                'aide' => __('Affichées dans la pastille ronde, à défaut de photo.'),
            ],
            'note' => [
                'intitule' => __('Note sur 5'),
                'type' => 'nombre',
                'regles' => ['required', 'integer', 'min:1', 'max:5'],
            ],
            'citation' => [
                'intitule' => __('Témoignage'),
                'type' => 'zone',
                'bilingue' => true,
                'regles' => ['required', 'string', 'max:2000'],
            ],
            'role' => [
                'intitule' => __('Fonction ou quartier'),
                'type' => 'texte',
                'bilingue' => true,
                'regles' => ['nullable', 'string', 'max:190'],
            ],
        ];
    }

    protected function vue(): string
    {
        return 'livewire.admin.temoignage-formulaire';
    }


    protected function intitule(): string
    {
        return __('Témoignage');
    }

    /**
     * Le liage de route a besoin d'un type instanciable : Laravel ne sait pas
     * resoudre un Model abstrait depuis l'adresse. Chaque formulaire declare
     * donc son modele et delegue a preparer() — PHP interdisant a une classe
     * fille de restreindre le type d'un parametre herite.
     */
    public function mount(?Temoignage $element = null): void
    {
        $this->preparer($element);
    }
}

<?php

namespace App\Livewire\Admin;

use App\Models\Encart;

/*
 * Edition d'un encart.
 *
 * Le slug designe l'endroit du site qui affiche l'encart : il ne se modifie
 * pas apres coup, la page qui le lit le cherche par ce nom.
 */
class EncartFormulaire extends FormulaireDeBloc
{
    protected function modele(): string
    {
        return Encart::class;
    }

    protected function champs(): array
    {
        return [
            'slug' => [
                'intitule' => __("Identifiant d'emplacement"),
                'type' => 'fige',
                'regles' => ['required', 'string', 'max:190', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
                'aide' => __("Désigne l'endroit du site qui affiche cet encart. Définitif."),
            ],
            'etiquette' => [
                'intitule' => __('Étiquette'),
                'type' => 'texte',
                'bilingue' => true,
                'regles' => ['nullable', 'string', 'max:190'],
            ],
            'titre' => [
                'intitule' => __('Titre'),
                'type' => 'texte',
                'bilingue' => true,
                'regles' => ['required', 'string', 'max:255'],
            ],
            'texte' => [
                'intitule' => __('Texte'),
                'type' => 'zone',
                'bilingue' => true,
                'regles' => ['nullable', 'string', 'max:2000'],
            ],
            'libelle_bouton' => [
                'intitule' => __('Libellé du bouton'),
                'type' => 'texte',
                'bilingue' => true,
                'regles' => ['nullable', 'string', 'max:120'],
            ],
            'cible_bouton' => [
                'intitule' => __('Cible du bouton'),
                'type' => 'texte',
                'regles' => ['nullable', 'string', 'max:255'],
                'aide' => __('Adresse vers laquelle le bouton conduit.'),
            ],
            'diffusion_de' => ['intitule' => __('Début de diffusion'), 'type' => 'texte', 'regles' => ['nullable', 'date']],
            'diffusion_a' => ['intitule' => __('Fin de diffusion'), 'type' => 'texte', 'regles' => ['nullable', 'date', 'after_or_equal:valeurs.diffusion_de']],
        ];
    }

    protected function fichierGere(): ?array
    {
        return ['image_source', 'encarts'];
    }

    protected function vue(): string
    {
        return 'livewire.admin.encart-formulaire';
    }

    protected function routeListe(): string
    {
        return 'admin.encarts.liste';
    }

    protected function intitule(): string
    {
        return __('Encart');
    }

    /** Le slug designe l'endroit du site qui affiche l'encart : un encart cree ne s'afficherait nulle part, un encart supprime laisserait un vide. */
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
    public function mount(?Encart $element = null): void
    {
        $this->preparer($element);
    }

    protected function descriptionDuFichier(): array
    {
        return [
            'intitule' => __("Image de l'encart"),
            'aide' => __('Format large : elle occupe toute la largeur de la banderole.'),
            'forme' => 'rectangle',
        ];
    }
}

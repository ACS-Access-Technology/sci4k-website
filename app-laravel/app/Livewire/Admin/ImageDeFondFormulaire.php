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
                'aide' => $this->visuelEnLigne()
                    ? __("Désigne l'emplacement de cette illustration dans la page. Définitif.")
                    : __('Correspond à la variable CSS qui sert cette image. Définitif.'),
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

    /**
     * Pas de recadrage sur cet ecran.
     *
     * L'etape de recadrage rend un CARRE. Elle est juste pour un portrait
     * rond ou une vignette ; elle ne l'est ni pour un fond de section, dont
     * la consigne demande 1920 × 800, ni pour les illustrations de la page
     * Presentation, affichees a leurs proportions d'origine. Un fond ramene
     * au carre est recadre deux fois : une fois ici, une fois par le
     * navigateur qui le retaille pour couvrir la section.
     */
    protected function recadrageDuFichier(): bool
    {
        return false;
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

    /**
     * Cette entree est-elle une illustration posee dans le HTML, et non un
     * fond CSS ? Voir ImageDeFond::VISUELS_EN_LIGNE.
     */
    protected function visuelEnLigne(): bool
    {
        return $this->element instanceof ImageDeFond && $this->element->estVisuelEnLigne();
    }

    /**
     * La consigne suit l'emplacement, au lieu d'annoncer la meme chose a tous.
     *
     * « 1920 × 800 px, un voile sombre est applique » est vrai d'un fond de
     * section et faux d'une illustration de la page Presentation : aucun voile
     * n'y est pose, et l'image est affichee a sa taille. La donner partout
     * aurait fait de cet ecran un ecran menteur de plus.
     */
    protected function descriptionDuFichier(): array
    {
        if ($this->visuelEnLigne()) {
            return [
                'intitule' => __('Illustration'),
                'aide' => __("Affichée telle quelle dans la page, sans voile ni recadrage. 1200 px de large suffisent."),
                'forme' => 'rectangle',
            ];
        }

        return [
            'intitule' => __('Fichier de fond'),
            'aide' => __('1920 × 800 px minimum. Un voile sombre est appliqué automatiquement.'),
            'forme' => 'rectangle',
        ];
    }
}

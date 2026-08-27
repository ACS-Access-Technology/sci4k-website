<?php

namespace App\Livewire\Admin;

use App\Models\ChiffreCle;

/*
 * Les compteurs animes du bandeau d'accueil.
 *
 * Le suffixe est un champ a part et non une partie du libelle : « 98 » et
 * « 98 % » ne sont pas le meme nombre, et le compteur anime jusqu'a la valeur
 * avant de poser le suffixe. Les melanger aurait fait defiler « 9, 98, 98 % ».
 *
 * La note interne ne s'affiche jamais sur le site : elle dit d'ou vient le
 * chiffre — « Depuis la creation en 2015 » —, ce qu'aucun autre champ ne peut
 * porter sans se retrouver publie.
 */
class ChiffreCleEnsemble extends EditionGroupee
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
        return [
            // Borne haut : au-dela, l'animation defile plus longtemps que le
            // visiteur ne regarde.
            'valeur' => ['required', 'integer', 'min:0', 'max:100000'],
            'suffixe' => ['nullable', 'string', 'max:16'],
            'note_interne' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function sectionReglee(): ?string
    {
        return 'home.hero';
    }

    protected function optionsDuBloc(): array
    {
        return [
            'animer' => true,
            'duree_animation' => 2000,
        ];
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

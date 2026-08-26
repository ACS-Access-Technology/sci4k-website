<?php

namespace App\Livewire\Admin;

use App\Models\EtapeProcessus;

/*
 * Les etapes de la methode, affichees sur /services.
 *
 * Elles etaient ecrites en dur dans la vue jusqu'au lot 2b. Le panneau de
 * reglages edite l'en-tete de la section « services.process » et sa mise en
 * page — la maquette propose la frise horizontale ou la liste verticale.
 */
class EtapeProcessusEnsemble extends EditionGroupee
{
    protected function modele(): string
    {
        return EtapeProcessus::class;
    }

    protected function champsBilingues(): array
    {
        return ['titre', 'texte'];
    }

    protected function sectionReglee(): ?string
    {
        return 'services.process';
    }

    protected function optionsDuBloc(): array
    {
        return ['mise_en_page' => 'frise'];
    }

    protected function vue(): string
    {
        return 'livewire.admin.etape-processus-ensemble';
    }

    protected function titre(): string
    {
        return __("Processus d'accompagnement");
    }
}

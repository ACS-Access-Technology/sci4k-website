<?php

namespace App\Livewire\Admin;

use App\Models\EtapeProcessus;

/*
 * Les quatre etapes de la methode, affichees sur /services.
 *
 * Elles etaient ecrites en dur dans la vue depuis le lot 2a : cet ecran les
 * rend editables sans qu'il faille reporter la page.
 */
class EtapeProcessusEnsemble extends EnsembleFige
{
    protected function modele(): string
    {
        return EtapeProcessus::class;
    }

    protected function champsBilingues(): array
    {
        return ['titre', 'texte'];
    }

    protected function vue(): string
    {
        return 'livewire.admin.etape-processus-ensemble';
    }

    protected function titre(): string
    {
        return __('Étapes du processus');
    }
}

<?php

namespace App\Livewire\Admin;

use App\Models\QuestionFaq;

/*
 * Ecran de liste de la FAQ.
 *
 * Contrairement aux services, la FAQ accepte la creation et la suppression :
 * ajouter ou retirer une question ne touche aucune structure des pages
 * publiques. Ce composant ne surcharge donc pas suppressionPermise().
 */
class FaqListe extends ListeOrdonnable
{
    protected function modele(): string
    {
        return QuestionFaq::class;
    }

    protected function colonnesRecherchees(): array
    {
        return ['question_fr', 'question_en', 'reponse_fr', 'reponse_en'];
    }

    protected function vue(): string
    {
        return 'livewire.admin.faq-liste';
    }

    protected function titre(): string
    {
        return __('FAQ');
    }
}

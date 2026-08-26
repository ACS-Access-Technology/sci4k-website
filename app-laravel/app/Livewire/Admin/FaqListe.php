<?php

namespace App\Livewire\Admin;

use App\Models\QuestionFaq;

/*
 * Ecran de liste de la FAQ.
 *
 * Retirer une question ne touche rien d'autre qu'elle-meme : ce composant se
 * contente donc du supprimer() de l'abstrait, la ou ServiceListe doit le
 * surcharger pour proteger les questions rattachees et le fichier d'image.
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

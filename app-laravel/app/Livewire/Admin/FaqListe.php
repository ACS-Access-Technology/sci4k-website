<?php

namespace App\Livewire\Admin;

use App\Models\QuestionFaq;
use Illuminate\Database\Eloquent\Collection;

/*
 * Ecran de liste de la FAQ.
 *
 * Retirer une question ne touche rien d'autre qu'elle-meme : ce composant se
 * contente donc du supprimer() de l'abstrait, la ou ServiceListe doit le
 * surcharger pour effacer le fichier d'image du service.
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

    /** Ouvert sur place quand la liste est embarquee dans un ecran de page. */
    protected function composantFormulaire(): ?string
    {
        return 'admin.faq-formulaire';
    }

    /** FaqFormulaire n'herite pas de FormulaireDeBloc : son modele s'appelle « question ». */
    protected function parametreDuFormulaire(): string
    {
        return 'question';
    }

    protected function vue(): string
    {
        return 'livewire.admin.faq-liste';
    }

    protected function titre(): string
    {
        return __('FAQ');
    }

    protected function relationsAPrecharger(): array
    {
        return ['rubrique'];
    }

    /**
     * Les questions dans l'ordre ou le site les montre.
     *
     * Le rang d'une question est relatif a sa rubrique : deux questions de
     * rubriques differentes portent couramment le rang 1. Le tri a plat de
     * l'abstrait entrelacait donc les rubriques — premiere question de chaque
     * rubrique, puis toutes les deuxiemes — alors que la page publique groupe
     * d'abord. L'ecran annoncait ainsi un ordre qui n'etait celui de personne,
     * et le pied du tableau promettait un glisser-deposer « pour changer
     * l'ordre d'affichage sur le site » que l'ecran ne refletait pas.
     *
     * La rubrique est prechargee par relationsAPrecharger() : sans cela, la
     * colonne du meme nom declenche une requete par ligne.
     */
    protected function elements(): Collection
    {
        // Le tri passe par une cle CALCULEE plutot que par une fermeture typee :
        // la methode mere rend une collection de Model, et lui promettre des
        // QuestionFaq serait mentir sur ce qu'elle rend vraiment. La cle, elle,
        // ne demande rien de plus que ce que la collection contient.
        return parent::elements()
            ->sortBy([
                fn ($question) => $question->rubrique->ordre,
                fn ($question) => $question->ordre,
                fn ($question) => $question->id,
            ])
            ->values();
    }
}

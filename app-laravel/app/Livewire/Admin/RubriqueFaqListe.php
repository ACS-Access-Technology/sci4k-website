<?php

namespace App\Livewire\Admin;

use App\Models\RubriqueFaq;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/*
 * Ecran de liste des rubriques de la FAQ.
 *
 * Une rubrique se cree depuis le formulaire d'une question, la ou le besoin
 * apparait. Cet ecran existe pour ce que la creation a la volee ne couvre pas :
 * renommer une rubrique mal saisie, la deplacer, la masquer, la retirer. Sans
 * lui, la premiere faute de frappe serait definitive.
 */
class RubriqueFaqListe extends ListeOrdonnable
{
    protected function modele(): string
    {
        return RubriqueFaq::class;
    }

    protected function colonnesRecherchees(): array
    {
        return ['nom_fr', 'nom_en'];
    }

    /** Ouvert sur place quand la liste est embarquee dans un ecran de page. */
    protected function composantFormulaire(): ?string
    {
        return 'admin.rubrique-faq-formulaire';
    }

    /** RubriqueFaqFormulaire n'herite pas de FormulaireDeBloc : son modele s'appelle « rubrique ». */
    protected function parametreDuFormulaire(): string
    {
        return 'rubrique';
    }

    protected function vue(): string
    {
        return 'livewire.admin.rubrique-faq-liste';
    }

    protected function titre(): string
    {
        return __('Rubriques de la FAQ');
    }

    /**
     * Compte les questions de chaque rubrique.
     *
     * Le chiffre dit a l'editeur ce qu'une rubrique contient avant qu'il ne
     * tente de la supprimer, et evite une requete par ligne a l'affichage.
     */
    protected function elements(): Collection
    {
        return parent::elements()->loadCount('questions');
    }

    /**
     * Retire la rubrique, a condition qu'elle ne porte plus de question.
     *
     * La cle etrangere est en RESTRICT, comme partout dans le projet : sans ce
     * controle, l'appel remonterait une QueryException et l'editeur verrait une
     * page 500. Le refus plutot que la cascade, contrairement aux services :
     * une rubrique n'est qu'un titre de groupe, alors que ses questions sont du
     * contenu redige. Les detruire parce qu'on renomme un classement serait
     * hors de proportion — le journal garde trace de l'arbitrage inverse pris
     * pour les services, ou le service EST le contenu.
     */
    public function supprimer(int $id): void
    {
        abort_unless($this->peutEcrire(), 403);

        $rubrique = RubriqueFaq::findOrFail($id);
        $questions = $rubrique->questions()->count();

        if ($questions > 0) {
            session()->flash('erreur', trans_choice(
                'Cette rubrique porte :nombre question. Déplacez-la dans une autre rubrique avant de supprimer celle-ci.|Cette rubrique porte :nombre questions. Déplacez-les dans une autre rubrique avant de supprimer celle-ci.',
                $questions,
                ['nombre' => $questions],
            ));

            return;
        }

        DB::transaction(fn () => $rubrique->delete());

        session()->flash('message', __('Rubrique supprimée.'));
    }
}

<?php

namespace App\Models\Concerns;

use App\Models\ActiviteJournalisee;

/**
 * Inscrit au journal les creations, modifications et suppressions.
 *
 * Pose sur les modeles dont l'activite interesse le tableau de bord. Le
 * tableau de bord DEDUISAIT auparavant cette activite du champ `updated_at` :
 * il ne pouvait donc dire ni ce qui s'etait passe, ni qui l'avait fait, et il
 * perdait toute trace d'un element supprime.
 *
 * Trois precautions, chacune contre un journal bavard ou trompeur :
 *
 *   - un enregistrement SANS changement reel n'ecrit rien. Ouvrir une fiche et
 *     la refermer n'est pas une modification, et faisait pourtant remonter le
 *     contenu en tete de liste ;
 *   - passer un article de brouillon a publie s'inscrit comme une PUBLICATION
 *     et non comme une modification : c'est le seul changement que le visiteur
 *     du site remarque ;
 *   - l'intitule est recopie, pas reference. Une ligne doit rester lisible
 *     apres la suppression de ce qu'elle decrit.
 */
trait JournaliseSesChangements
{
    public static function bootJournaliseSesChangements(): void
    {
        static::created(fn ($modele) => $modele->inscrireAuJournal(ActiviteJournalisee::CREATION));

        static::updated(function ($modele) {
            $modifies = array_keys($modele->getChanges());

            // `updated_at` bouge a chaque sauvegarde, meme sans changement de
            // fond : le compter aurait rendu la precaution inutile.
            $modifies = array_diff($modifies, ['updated_at']);

            if ($modifies === []) {
                return;
            }

            $devientPublie = in_array('statut', $modifies, true)
                && $modele->statut === 'publie';

            $modele->inscrireAuJournal(
                $devientPublie ? ActiviteJournalisee::PUBLICATION : ActiviteJournalisee::MODIFICATION,
            );
        });

        static::deleted(fn ($modele) => $modele->inscrireAuJournal(ActiviteJournalisee::SUPPRESSION));
    }

    /**
     * Comment ce contenu se nomme dans le journal.
     *
     * Les modeles qui n'ont pas de colonne `titre_fr` ou `nom` redefinissent
     * cette methode. Le repli sur l'identifiant vaut mieux qu'une ligne vide :
     * elle dit au moins de quoi on parle.
     */
    public function intituleJournal(): string
    {
        foreach (['titre_fr', 'nom_fr', 'question_fr', 'nom', 'auteur', 'libelle_fr', 'slug', 'name'] as $colonne) {
            $valeur = $this->getAttribute($colonne);

            if (is_string($valeur) && trim($valeur) !== '') {
                return trim($valeur);
            }
        }

        return '#'.$this->getKey();
    }

    /** Ecrit une ligne du journal. */
    public function inscrireAuJournal(string $action): void
    {
        $auteur = auth()->user();

        ActiviteJournalisee::create([
            'user_id' => $auteur?->getKey(),
            // Le nom est recopie pour la meme raison que l'intitule : le compte
            // peut disparaitre, la ligne doit rester lisible.
            'auteur_nom' => $auteur?->name,
            'action' => $action,
            'sujet_type' => static::class,
            'sujet_id' => $this->getKey(),
            'sujet_intitule' => mb_substr($this->intituleJournal(), 0, 190),
        ]);
    }
}

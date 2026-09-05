<?php

namespace App\Livewire\Admin;

use App\Models\Categorie;
use Illuminate\Validation\ValidationException;

/*
 * Les categories d'articles.
 *
 * Elles n'etaient modifiables NULLE PART : l'ecran des referentiels les
 * montrait en lecture avec un renvoi vers la liste des articles, qui ne les
 * edite pas davantage. Le vocabulaire du filtre de /actualites etait donc fige
 * depuis le seeder. La refonte le rendait intenable — « Pages du site →
 * Actualités » promet de tout modifier sur place — d'ou cet ecran.
 *
 * Une categorie n'a pas de colonne « visible » : le filtre public liste
 * toutes celles de la table. La case a cocher est donc masquee, sans quoi
 * l'editeur en decocherait une sans effet.
 */
class CategorieEnsemble extends EditionGroupee
{
    protected function modele(): string
    {
        return Categorie::class;
    }

    protected function champsBilingues(): array
    {
        return ['nom'];
    }

    protected function vue(): string
    {
        return 'livewire.admin.categorie-ensemble';
    }

    protected function titre(): string
    {
        return __("Catégories d'articles");
    }

    /**
     * Le rang gouverne l'ordre des options du filtre public, pas un en-tete de
     * section : les categories ne sont le titre d'aucun bloc.
     */
    protected function sectionReglee(): ?string
    {
        return null;
    }

    /**
     * Retirer une categorie encore utilisee est refuse.
     *
     * Les articles ET les services la referencent par cle etrangere
     * contrainte. Laisser passer le retrait ferait echouer l'enregistrement
     * sur une erreur SQL brute, apres coup, sans dire laquelle des lignes
     * posait probleme — et en perdant au passage toutes les autres
     * modifications de l'ecran.
     */
    public function retirer(int|string $cle): void
    {
        abort_unless($this->peutEcrire(), 403);

        // Une ligne jamais enregistree ne peut rien retenir.
        if (is_numeric($cle) && $categorie = Categorie::find((int) $cle)) {
            $contenus = $categorie->nombreDeContenus();

            if ($contenus > 0) {
                $this->addError('lignes.'.$cle.'.nom_fr', __(
                    'Impossible de retirer « :nom » : :nombre contenu(s) y sont rattachés. Déplacez-les d’abord vers une autre catégorie.',
                    ['nom' => $categorie->nom(app()->getLocale()), 'nombre' => $contenus],
                ));

                return;
            }
        }

        parent::retirer($cle);
    }

    /**
     * Le meme refus, refait a l'instant de l'effacement.
     *
     * Celui de `retirer()` ne tient que si le retrait est passe par la : la
     * liste des identifiants a effacer est une propriete publique, que le
     * navigateur fixe. Sans ce second controle, une categorie encore rattachee
     * a des articles arrivait jusqu'au DELETE, la contrainte de cle etrangere
     * la refusait, et l'editeur recevait une erreur 500 apres que le reste de
     * l'ecran avait deja ete enregistre.
     *
     * @param  list<int>  $ids
     */
    protected function verifierLesSuppressions(array $ids): void
    {
        if ($ids === []) {
            return;
        }

        $messages = [];

        foreach (Categorie::query()->whereIn('id', $ids)->get() as $categorie) {
            if (($contenus = $categorie->nombreDeContenus()) > 0) {
                $messages[] = __(
                    'Impossible de retirer « :nom » : :nombre contenu(s) y sont rattachés. Déplacez-les d’abord vers une autre catégorie.',
                    ['nom' => $categorie->nom(app()->getLocale()), 'nombre' => $contenus],
                );
            }
        }

        if ($messages !== []) {
            throw ValidationException::withMessages(['aSupprimer' => $messages]);
        }
    }
}

<?php

namespace App\Livewire\Concerns;

use App\Services\Traduction\Traducteur;

/**
 * Remplit automatiquement la langue laissee vide d'un formulaire bilingue.
 *
 * Extrait d'ArticleFormulaire (lot 1) pour que ServiceFormulaire, puis la FAQ,
 * ne recopient pas le meme mecanisme : une copie suffit deja a heberger le
 * defaut, trois l'auraient rendu certain a corriger partout a la fois.
 *
 * La classe utilisatrice declare uniquement la liste de ses champs
 * traduisibles via champsTraduisibles() ; chaque prefixe y designe une paire
 * de proprietes publiques {prefixe}Fr et {prefixe}En.
 */
trait RemplitParTraduction
{
    /**
     * Prefixes des paires de proprietes {prefixe}Fr / {prefixe}En a remplir
     * par traduction. Par exemple ['titre', 'resume', 'contenu'] pour un
     * article.
     *
     * @return list<string>
     */
    abstract protected function champsTraduisibles(): array;

    /**
     * Remplit la langue manquante par traduction automatique.
     *
     * REGLE UNIQUE, ARBITREE AVEC LE CLIENT : on ne traduit QUE ce qui est
     * vide. Jamais d'ecrasement. Le contenu du site porte des traductions
     * anglaises humaines, dont la recuperation a coute une investigation
     * entiere ; une traduction machine declenchee a chaque enregistrement les
     * aurait effacees sans que personne s'en apercoive avant de relire le
     * site.
     *
     * Le sens suit ce qui est rempli : francais vers anglais, ou l'inverse.
     * Chaque champ est traite separement, un contenu pouvant etre complet
     * d'un cote et partiel de l'autre.
     */
    protected function remplirParTraductionCeQuiEstVide(): void
    {
        $traducteur = app(Traducteur::class);

        if (! $traducteur->disponible()) {
            return;
        }

        foreach ($this->champsTraduisibles() as $champ) {
            $fr = $champ.'Fr';
            $en = $champ.'En';

            if (blank($this->$en) && filled($this->$fr)) {
                $this->$en = $this->traduireTexte($traducteur, $this->$fr, 'en', 'fr') ?? $this->$en;
            } elseif (blank($this->$fr) && filled($this->$en)) {
                $this->$fr = $this->traduireTexte($traducteur, $this->$en, 'fr', 'en') ?? $this->$fr;
            }
        }
    }

    /**
     * Complete le membre vide d'un couple francais / anglais.
     *
     * Meme regle que remplirParTraductionCeQuiEstVide(), mais sur un couple
     * passe en argument plutot que sur deux proprietes nommees : les ecrans
     * qui editent plusieurs elements d'un bloc tiennent leurs textes dans un
     * tableau, ou aucune propriete {prefixe}Fr n'existe. Ecrire la regle deux
     * fois aurait donne deux endroits ou la corriger.
     *
     * @return array{0: string, 1: string} le couple complete
     */
    protected function completerCouple(?string $fr, ?string $en): array
    {
        $fr = (string) $fr;
        $en = (string) $en;

        $traducteur = app(Traducteur::class);

        if (! $traducteur->disponible()) {
            return [$fr, $en];
        }

        if (blank($en) && filled($fr)) {
            $en = $this->traduireTexte($traducteur, $fr, 'en', 'fr') ?? $en;
        } elseif (blank($fr) && filled($en)) {
            $fr = $this->traduireTexte($traducteur, $en, 'fr', 'en') ?? $fr;
        }

        return [$fr, $en];
    }

    /**
     * Traduit un texte en preservant ses paragraphes.
     *
     * Les paragraphes partent comme autant de textes distincts plutot qu'en un
     * seul bloc : DeepL recolle volontiers les lignes vides, et le contenu
     * arriverait d'un seul tenant sur la page publique, qui decoupe justement
     * sur ces lignes vides.
     */
    protected function traduireTexte(Traducteur $traducteur, string $texte, string $vers, string $depuis): ?string
    {
        $paragraphes = preg_split('/\R{2,}/u', trim($texte)) ?: [];

        $traduits = $traducteur->traduire($paragraphes, $vers, $depuis);

        return $traduits === null ? null : implode("\n\n", $traduits);
    }
}

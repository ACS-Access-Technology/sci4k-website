<?php

namespace App\Models\Concerns;

/**
 * Lit un champ traduit stocke dans deux colonnes suffixees `_fr` / `_en`.
 *
 * Regles :
 * - la langue doit appartenir a la liste blanche (fr, en) ; toute autre
 *   valeur (faute de frappe, langue non geree) retombe sur le francais ;
 * - si la valeur demandee est vide ('' ou null), on replie sur le francais
 *   plutot que d'afficher du vide ;
 * - le francais est la langue par defaut quand aucune langue n'est passee.
 */
trait TraduitParColonnes
{
    /** Langues acceptees, dans l'ordre de repli. */
    private const LANGUES_AUTORISEES = ['fr', 'en'];

    private const LANGUE_PAR_DEFAUT = 'fr';

    /**
     * Renvoie la valeur de `{$prefixe}_{$langue}`, avec repli sur le francais
     * si la langue est inconnue ou si la valeur trouvee est vide.
     */
    protected function texteDansLaLangue(string $prefixe, string $langue = self::LANGUE_PAR_DEFAUT): string
    {
        $langue = in_array($langue, self::LANGUES_AUTORISEES, true)
            ? $langue
            : self::LANGUE_PAR_DEFAUT;

        $valeur = $this->{$prefixe.'_'.$langue};

        if ($valeur === null || $valeur === '') {
            $valeur = $this->{$prefixe.'_'.self::LANGUE_PAR_DEFAUT};
        }

        return (string) $valeur;
    }
}

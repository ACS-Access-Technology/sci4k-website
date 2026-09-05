<?php

namespace App\Livewire\Concerns;

/**
 * L'en-tete d'une section, et le filtre qui protege son ecriture.
 *
 * `$entete` est une propriete publique Livewire : le navigateur en fixe le
 * contenu, CLES COMPRISES. La passer telle quelle a `fill()` laissait ecrire
 * toute colonne `fillable` de ReglageDeSection que l'ecran n'expose pas —
 * `contenu_fr`, qui PRIME sur le chapo sur le site public et qu'aucun `max`
 * ne borne ; `options`, ce qui contournait les `Rule::in` des reglages
 * d'apparence ; `slug`, ce qui faisait tomber l'enregistrement sur une
 * violation d'unicite.
 *
 * Le meme filtre existait deja dans PorteDesTextesDeBloc::poserLesTextes(),
 * dans EditionGroupee::enregistrer() et dans FormulaireDeBloc::clesDeclarees()
 * — chacun avec le commentaire qui explique pourquoi. `$entete` etait le frere
 * oublie, sur les SEPT ecrans de page. D'ou ce trait : un seul filtre, que les
 * sept partagent.
 */
trait PorteUnEnteteDeSection
{
    /**
     * Les champs d'en-tete qu'une section porte, hors suffixe de langue.
     *
     * @var list<string>
     */
    public const CHAMPS_ENTETE = ['etiquette', 'titre', 'chapo'];

    /**
     * Les champs que l'ecran declare, module par module.
     *
     * Un ecran peut en porter davantage : « Pages du site → Présentation »
     * ajoute `contenu` sur deux de ses modules, un corps de texte long que la
     * page publique affiche a la place du chapo. Un filtre a liste figee aurait
     * efface ce texte a chaque enregistrement — un trou de securite remplace
     * par une perte de donnees. La liste suit donc la DECLARATION du module.
     *
     * @return list<string>
     */
    protected function champsDeLEntete(): array
    {
        return self::CHAMPS_ENTETE;
    }

    /** Les colonnes reellement ecrites, suffixes de langue compris. */
    protected function colonnesDeLEntete(): array
    {
        $colonnes = [];

        foreach ($this->champsDeLEntete() as $champ) {
            $colonnes[] = $champ.'_fr';
            $colonnes[] = $champ.'_en';
        }

        return $colonnes;
    }

    /** L'en-tete reduite aux colonnes declarees. */
    protected function enteteFiltree(): array
    {
        return array_intersect_key($this->entete, array_flip($this->colonnesDeLEntete()));
    }

    /**
     * Regles de validation de l'en-tete.
     *
     * Elles vivent ici avec le filtre : deux listes de champs entretenues
     * separement finissent par diverger, et c'est la divergence qui ouvre le
     * trou.
     */
    protected function reglesDeLEntete(int $longueurMaximale = 500): array
    {
        $regles = [];

        foreach ($this->colonnesDeLEntete() as $colonne) {
            $regles['entete.'.$colonne] = ['nullable', 'string', 'max:'.$longueurMaximale];
        }

        return $regles;
    }

    /**
     * Des options reduites aux cles declarees.
     *
     * Meme raison que l'en-tete : `$bandeau`, `$boutons`, `$options` et
     * `$atouts` sont des proprietes publiques, et `poserOptions()` verse ce
     * qu'on lui donne dans le sac JSON de la section.
     *
     * @param  array<string, mixed>  $valeurs
     * @param  list<string>  $clesDeclarees
     */
    protected function optionsFiltrees(array $valeurs, array $clesDeclarees): array
    {
        return array_intersect_key($valeurs, array_flip($clesDeclarees));
    }
}

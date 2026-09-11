<?php

namespace App\Livewire\Concerns;

use App\Services\Traduction\Traducteur;
use Illuminate\Validation\ValidationException;

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
    /**
     * Valide, et OUVRE L'ONGLET de la langue fautive si l'onglet ouvert est sain.
     *
     * Les trois ecrans de saisie bilingue rendent LES DEUX langues et masquent
     * celle qui n'est pas active : basculer d'un onglet a l'autre ne doit
     * couter ni aller-retour au serveur ni saisie en cours. Un message
     * d'erreur range dans l'onglet ferme etait donc ecrit dans la page et
     * invisible a l'ecran — l'editeur voyait un bouton « Enregistrer » sans
     * effet, et rien pour le renseigner.
     *
     * On ne bascule QUE si l'onglet ouvert ne porte aucune erreur : sinon
     * l'editeur perdrait de vue celle qu'il est en train de lire.
     */
    protected function validerEnMontrantLaLangueFautive(): void
    {
        try {
            $this->validate();
        } catch (ValidationException $erreur) {
            $this->ouvrirLaLangueFautive(array_keys($erreur->validator->errors()->messages()));

            throw $erreur;
        }
    }

    /**
     * @param  list<string>  $cles  Champs en faute, tels que « valeurs.titre_fr »,
     *                              « lignes.3.titre_en » ou « titreEn ».
     */
    private function ouvrirLaLangueFautive(array $cles): void
    {
        $enFaute = [];

        // Deux conventions de nommage coexistent : le suffixe « _fr » / « _en »
        // des formulaires pilotes par description, et le « Fr » / « En » en
        // casse chameau d'ArticleFormulaire, dont les champs sont des
        // proprietes declarees une a une.
        foreach ($cles as $cle) {
            if (preg_match('/(?:_(fr|en)|(?<=[a-z])(Fr|En))$/', $cle, $trouve) === 1) {
                $enFaute[] = strtolower($trouve[1] !== '' ? $trouve[1] : $trouve[2]);
            }
        }

        if ($enFaute === [] || in_array($this->langueActive, $enFaute, true)) {
            return;
        }

        $this->langueActive = $enFaute[0];
    }

    /**
     * La meme regle de validation, mais sans obligation de saisie — celle qui
     * s'applique a la colonne ANGLAISE d'un champ bilingue.
     *
     * POURQUOI L'ANGLAIS N'EST PAS OBLIGATOIRE
     *
     * TraduitParColonnes replie sur le francais des que la colonne anglaise est
     * vide : un titre saisi en francais seul s'affiche dans les deux langues.
     * Exiger les deux colonnes contredisait donc la facon dont le site lit ses
     * propres donnees — et cette contradiction ne se voyait pas, parce que
     * completerCouple() remplissait l'anglais tant qu'une cle DeepL etait posee.
     *
     * Le jour ou elle ne l'etait plus, l'editeur qui remplissait l'onglet
     * francais voyait son enregistrement refuse sur un champ range dans
     * l'onglet anglais, lequel est rendu masque : le message existait dans la
     * page et restait invisible. C'est arrive sur l'hebergement d'essai, ou
     * l'encart d'annonce de l'accueil est reste impossible a modifier.
     *
     * Le repli ne va QUE du francais vers l'anglais. Le francais garde donc son
     * obligation, sans quoi la page francaise resterait vide.
     *
     * @param  list<string>  $regles
     * @return list<string>
     */
    protected static function sansObligation(array $regles): array
    {
        $regles = array_values(array_filter($regles, fn ($regle) => $regle !== 'required'));

        return in_array('nullable', $regles, true) ? $regles : ['nullable', ...$regles];
    }

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

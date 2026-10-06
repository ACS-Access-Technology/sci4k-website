<?php

namespace App\Support;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * Le HTML qu'un compte de l'administration peut faire afficher au visiteur.
 *
 * Les pages legales sont saisies en HTML et rendues sans echappement. Rien ne
 * filtrait ce HTML : un <script> ou un onerror= enregistre depuis le backoffice
 * s'executait chez chaque visiteur — administrateurs compris, dont la session
 * devenait alors utilisable par l'auteur du texte.
 *
 * Le filtre repose sur un vrai analyseur HTML5 (symfony/html-sanitizer), et non
 * sur des expressions regulieres : le HTML que lit le navigateur n'est pas
 * celui qu'une regex croit lire, et c'est dans cet ecart que passent les
 * contournements.
 *
 * LISTE BLANCHE. Seules les balises du contenu editorial passent — celles
 * qu'emploient les pages d'origine (div.legal-block, span.legal-placeholder),
 * plus les listes et les intertitres. Tout le reste disparait AVEC son contenu,
 * ce qui est le comportement voulu pour script, style, iframe, object, embed,
 * form, svg. Les attributs d'evenement (on*) ne sont jamais admis ; les liens
 * n'acceptent que http, https, mailto, tel et les adresses relatives —
 * « javascript: » est retire.
 *
 * Quelques conteneurs inoffensifs sont DEBALLES plutot que supprimes : un h1 ou
 * une section colles depuis un traitement de texte perdent leur balise, mais
 * pas leur texte. Supprimer un paragraphe de mentions legales parce qu'il etait
 * emballe dans une balise imprevue serait une autre facon de casser la page.
 */
class HtmlEditorial
{
    /**
     * Meme plafond que la validation de l'ecran d'edition. Le filtre en porte
     * un de 20 000 caracteres par defaut, au-dela duquel il TRONQUE : une page
     * longue aurait perdu sa fin sans que personne ne s'en apercoive.
     */
    public const LONGUEUR_MAXIMALE = 50000;

    /** @var array<string, list<string>> balise => attributs admis */
    private const BALISES = [
        'p' => ['class'],
        'br' => [],
        'strong' => [],
        'em' => [],
        'b' => [],
        'i' => [],
        'u' => [],
        'ul' => ['class'],
        'ol' => ['class'],
        'li' => [],
        'h2' => ['class'],
        'h3' => ['class'],
        'h4' => ['class'],
        'a' => ['href', 'title'],
        'div' => ['class'],
        'span' => ['class'],
        'blockquote' => [],
    ];

    /** Retirees, texte conserve. */
    private const DEBALLEES = [
        'h1', 'h5', 'h6', 'section', 'article', 'header', 'footer', 'main',
        'small', 'sup', 'sub', 'font', 'center',
    ];

    private static ?HtmlSanitizer $filtre = null;

    public static function nettoyer(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        return self::filtre()->sanitize($html);
    }

    private static function filtre(): HtmlSanitizer
    {
        if (self::$filtre) {
            return self::$filtre;
        }

        $config = (new HtmlSanitizerConfig)
            ->allowLinkSchemes(['http', 'https', 'mailto', 'tel'])
            ->allowRelativeLinks()
            ->withMaxInputLength(self::LONGUEUR_MAXIMALE);

        foreach (self::BALISES as $balise => $attributs) {
            $config = $config->allowElement($balise, $attributs);
        }

        foreach (self::DEBALLEES as $balise) {
            $config = $config->blockElement($balise);
        }

        return self::$filtre = new HtmlSanitizer($config);
    }
}

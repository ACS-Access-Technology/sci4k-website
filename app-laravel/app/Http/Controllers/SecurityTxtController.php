<?php

namespace App\Http\Controllers;

use App\Models\Parametre;
use Illuminate\Http\Response;

/**
 * Le fichier qui dit ou signaler une faille — RFC 9116.
 *
 * Sans lui, un chercheur qui trouve un defaut sur le site n'a aucun moyen
 * evident de le dire : il ecrit a une adresse trouvee au hasard, ou il publie.
 *
 * RENDU, ET NON DEPOSE DANS public/. Deux raisons, et la premiere suffit :
 *
 * 1. La date d'expiration est OBLIGATOIRE dans ce format. Un fichier statique
 *    se perime donc en silence, et un security.txt perime vaut moins que pas de
 *    security.txt : il affirme une disponibilite qui n'existe plus. Calculee a
 *    chaque requete, elle ne peut pas se perimer.
 *
 * 2. L'adresse de contact suit celle de l'ecran Configuration. La changer la
 *    doit suffire, sans qu'on ait a se souvenir qu'un fichier la repete
 *    ailleurs — c'est le raisonnement deja tenu pour robots.txt et le plan du
 *    site, qu'une copie figee dans public/ avait masques.
 */
class SecurityTxtController extends Controller
{
    /**
     * Douze mois : la duree maximale que le standard recommande.
     *
     * Plus court obligerait a revenir dessus sans raison ; plus long fait
     * douter le lecteur de la fraicheur de l'engagement.
     */
    protected const MOIS_DE_VALIDITE = 12;

    public function __invoke(): Response
    {
        $contact = (string) Parametre::lire('email_public', 'contact@sci4k.com');

        $lignes = [
            '# Signalement de vulnérabilité — '.Parametre::lire('nom_du_site', 'SCI4K'),
            '#',
            '# Merci de nous écrire AVANT toute publication, et de nous laisser',
            '# le temps de corriger. Nous répondons sous cinq jours ouvrés.',
            '',
            'Contact: mailto:'.$contact,
            'Expires: '.now()->addMonths(self::MOIS_DE_VALIDITE)->toIso8601ZuluString(),
            'Preferred-Languages: fr, en',
            'Canonical: '.url('/.well-known/security.txt'),
        ];

        return response(implode("\n", $lignes)."\n", 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            // Ce fichier n'a rien a faire dans un moteur de recherche : il
            // s'adresse a qui le cherche a cette adresse precise.
            'X-Robots-Tag' => 'noindex',
            // Il change a chaque requete par sa date : rien a mettre en cache.
            'Cache-Control' => 'no-cache, must-revalidate',
        ]);
    }
}

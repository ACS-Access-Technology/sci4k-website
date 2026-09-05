<?php

namespace App\Http\Controllers;

use App\Models\Parametre;
use Illuminate\Http\Response;

/**
 * Le fichier robots.txt, servi depuis la configuration.
 *
 * Il existait en deux exemplaires qui ne se parlaient pas : un fichier fige
 * dans public/, servi par le serveur web, et un champ « Fichier robots.txt »
 * dans l'ecran Configuration que RIEN ne lisait. L'editeur pouvait donc le
 * remplir et le relire sans que le site change d'un octet — le pire etat pour
 * un reglage, puisqu'il donne le sentiment d'avoir agi.
 *
 * Le fichier fige a ete retire, sans quoi il continuerait de gagner : Apache
 * sert un fichier existant avant de passer la main a Laravel.
 *
 * Le contenu par defaut declare le plan du site. C'est ce qui manquait le plus :
 * un robots.txt qui n'annonce pas son sitemap oblige chaque moteur a le
 * deviner, et Google recommande explicitement de l'y mettre.
 *
 * Le refus d'indexation est POSE ICI, et pas seulement dans la balise meta de
 * la page. Les deux ne disent pas la meme chose au meme moment : la balise est
 * lue apres avoir charge la page, robots.txt avant de la demander. Un site en
 * preparation doit etre refuse des la porte.
 */
class RobotsController extends Controller
{
    public function __invoke(): Response
    {
        $indexation = Parametre::actif('autoriser_indexation', true);

        $contenu = trim((string) Parametre::lire('robots_txt', ''));

        if ($contenu === '') {
            $contenu = $indexation
                ? "User-agent: *\nDisallow: /admin\nDisallow: /login"
                : "User-agent: *\nDisallow: /";
        }

        // Le plan du site est ajoute apres le texte saisi, et une seule fois :
        // un editeur qui l'a deja ecrit ne doit pas le voir apparaitre en
        // double, et un editeur qui l'a oublie ne doit pas priver les moteurs
        // de la seule adresse qui les mene a tout le reste.
        if (! str_contains(strtolower($contenu), 'sitemap:')) {
            $contenu .= "\n\nSitemap: ".route('plan-du-site');
        }

        return response($contenu."\n", 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }
}

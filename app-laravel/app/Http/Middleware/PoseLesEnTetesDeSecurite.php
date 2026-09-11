<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Les en-tetes de securite poses sur chaque reponse.
 *
 * Releves absents par un audit externe. Chacun ferme une porte precise, et
 * aucun ne depend d'un reglage : ils valent pour toutes les pages, publiques
 * comme administratives.
 *
 * CE QUI N'EST PAS ICI : la politique de securite du contenu. Elle demanderait
 * « unsafe-inline » tant que les gabarits portent des gestionnaires en ligne
 * (onsubmit="…") et des styles en ligne herites des maquettes, et elle
 * bloquerait le chat ou les statistiques le jour ou l'editeur les active depuis
 * l'ecran Configuration — sans un mot, ce qui est le pire des defauts. Elle
 * fera l'objet d'un lot a elle, en mode observation d'abord.
 */
class PoseLesEnTetesDeSecurite
{
    public function handle(Request $requete, Closure $suite): Response
    {
        $reponse = $suite($requete);

        // Un navigateur ne devine plus le type d'un fichier : une image
        // televersee qui contiendrait du script ne sera pas executee comme tel.
        $reponse->headers->set('X-Content-Type-Options', 'nosniff');

        // Personne n'affiche ce site dans son propre cadre pour y faire cliquer
        // un visiteur a son insu. DENY et non SAMEORIGIN : aucune page du site
        // ne s'inclut elle-meme. La carte de la page contact est l'inverse —
        // c'est nous qui incluons Google, ce que cet en-tete ne regarde pas.
        $reponse->headers->set('X-Frame-Options', 'DENY');

        // L'adresse complete d'une page ne suit pas le visiteur vers un autre
        // domaine : seule l'origine part. Un bien consulte, une recherche
        // faite, ne se lisent plus dans les journaux du site suivant.
        $reponse->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Le site ne demande ni camera, ni micro, ni position. L'ecrire empeche
        // qu'un contenu tiers inclus un jour le demande a notre place.
        $reponse->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=()');

        $this->annoncerHttpsSiLaRequeteLEstDeja($requete, $reponse);

        return $reponse;
    }

    /**
     * HSTS, et seulement sur une reponse deja servie en HTTPS.
     *
     * Envoye en clair, il est ignore par les navigateurs — et l'annoncer depuis
     * un poste de developpement en http:// forcerait ce poste a exiger un HTTPS
     * qu'il n'a pas, PENDANT UN AN, sans moyen simple de revenir en arriere.
     *
     * NI includeSubDomains NI preload, deliberement :
     *
     * - includeSubDomains engagerait des sous-domaines qui ne nous appartiennent
     *   pas encore. Le site vit aujourd'hui sous *.up.railway.app, partage avec
     *   d'autres applications ; et le jour ou il passera sur sci4k.com, les
     *   sous-domaines existants portent la messagerie et l'ancien site.
     * - preload est IRREVERSIBLE en pratique : le retrait des listes embarquees
     *   dans les navigateurs prend des mois. On ne s'y inscrit pas depuis un
     *   environnement d'essai.
     */
    protected function annoncerHttpsSiLaRequeteLEstDeja(Request $requete, Response $reponse): void
    {
        if (! $requete->secure()) {
            return;
        }

        $reponse->headers->set('Strict-Transport-Security', 'max-age=31536000');
    }
}

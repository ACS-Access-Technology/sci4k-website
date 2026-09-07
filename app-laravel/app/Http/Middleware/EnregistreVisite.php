<?php

namespace App\Http\Middleware;

use App\Models\Visite;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Compte les pages vues, sans faire attendre le visiteur.
 *
 * L'ecriture se faisait dans handle(), donc AVANT que la reponse ne parte :
 * chaque page vue portait l'aller-retour vers la base sur le temps d'attente du
 * visiteur. Elle vit desormais dans terminate(), que Laravel appelle une fois la
 * reponse envoyee — sous PHP-FPM, fastcgi_finish_request() a deja rendu la main
 * au navigateur, et la requete ne fait plus que finir de mourir.
 *
 * Le serveur fait toujours une ecriture par page vue : c'est le NOMBRE
 * d'ecritures qui reste a borner, si le trafic reel l'exige un jour. Ce qui
 * change ici, c'est qui les attend.
 */
class EnregistreVisite
{
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    /**
     * Enregistre la visite, la reponse etant deja partie.
     *
     * La condition se recalcule ici plutot que de se transmettre depuis
     * handle() : le noyau resout le middleware une SECONDE fois pour cet appel,
     * par app->make(), donc sur une autre instance. Une propriete posee dans
     * handle() n'y serait pas.
     */
    public function terminate(Request $request, Response $response): void
    {
        if (! $this->estUnePageVue($request, $response)) {
            return;
        }

        // A ce stade la reponse est partie : une base indisponible n'a plus
        // personne a qui se plaindre. L'echec se consigne donc et s'arrete la.
        // Laisser l'exception se propager ne reparerait rien et remplirait le
        // journal de traces fatales pour une statistique perdue.
        try {
            // Ni adresse IP ni type de navigateur : la mesure compte des pages
            // vues et des visiteurs distincts, rien de plus. `session_hash` est
            // l'empreinte d'un identifiant que le site a lui-meme tire au sort,
            // et non une donnee prise au visiteur — c'est ce qui permet de
            // distinguer deux visites sans reconnaitre personne.
            Visite::create([
                'chemin' => '/'.ltrim($request->path(), '/'),
                'session_hash' => hash('sha256', (string) $request->session()->getId()),
                'visitee_le' => now(),
            ]);
        } catch (Throwable $erreur) {
            report($erreur);
        }
    }

    protected function estUnePageVue(Request $request, Response $response): bool
    {
        return $request->isMethod('GET')
            && $response->isSuccessful()
            && ! $request->expectsJson();
    }
}

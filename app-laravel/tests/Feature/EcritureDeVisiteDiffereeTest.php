<?php

use App\Http\Middleware\EnregistreVisite;
use App\Models\Visite;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Schema;

/*
 * L'enregistrement d'une visite ne doit pas se payer sur le temps d'attente du
 * visiteur.
 *
 * Le middleware ecrivait sa ligne AVANT de rendre la reponse : chaque page vue
 * portait l'aller-retour vers la base. L'ecriture part desormais depuis
 * terminate(), que Laravel appelle une fois la reponse envoyee — sous PHP-FPM,
 * fastcgi_finish_request() a deja rendu la main au navigateur.
 *
 * LE TEST QUI COMPTE est le premier. Verifier qu'une visite existe apres un GET
 * passerait aussi bien avant qu'apres le changement, et ne prouverait rien :
 * seule l'absence d'ecriture pendant handle() etablit qu'elle en est sortie.
 */

/** Une requete GET munie d'une session, comme le middleware l'attend. */
function requeteAvecSession(string $chemin = '/biens'): Request
{
    $requete = Request::create($chemin);
    $requete->setLaravelSession(app('session.store'));

    return $requete;
}

it("n'ecrit rien pendant le traitement de la requete", function () {
    $requete = requeteAvecSession();

    (new EnregistreVisite)->handle($requete, fn () => new Response('', 200));

    expect(Visite::count())->toBe(0);
});

it('ecrit une fois la reponse rendue', function () {
    $requete = requeteAvecSession('/biens');
    $reponse = (new EnregistreVisite)->handle($requete, fn () => new Response('', 200));

    // Le noyau resout le middleware une SECONDE fois pour terminate() —
    // app->make(), donc une autre instance. Rien ne peut donc etre memorise
    // entre les deux appels, et la condition se recalcule ici.
    (new EnregistreVisite)->terminate($requete, $reponse);

    expect(Visite::count())->toBe(1)
        ->and(Visite::first()->chemin)->toBe('/biens');
});

it('ne laisse pas une ecriture manquee remonter en erreur', function () {
    Exceptions::fake();

    $requete = requeteAvecSession();
    $reponse = (new EnregistreVisite)->handle($requete, fn () => new Response('', 200));

    // A ce stade la reponse est partie : plus personne a qui signaler quoi que
    // ce soit. Une base indisponible doit se consigner, pas se propager — une
    // statistique perdue ne vaut pas une trace fatale.
    Schema::drop('visites');

    (new EnregistreVisite)->terminate($requete, $reponse);

    Exceptions::assertReported(QueryException::class);
});

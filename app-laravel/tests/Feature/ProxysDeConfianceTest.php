<?php

use App\Providers\AppServiceProvider;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;

/**
 * Derriere un proxy, c'est le proxy qui parle en HTTPS au visiteur et en HTTP
 * a PHP. Tant qu'on n'a pas dit a Laravel a QUI se fier, isSecure() repond
 * faux : les URL et les redirections repartent en http, le cookie de session
 * ne recoit pas son attribut Secure malgre SESSION_SECURE_COOKIE, et la
 * limitation de debit compte toutes les visites sur l'adresse du proxy.
 *
 * TrustProxies garde son reglage dans une propriete STATIQUE. Sans le
 * flushState ci-dessous, le premier test qui declare un proxy le laisserait
 * en place pour toute la suite.
 */
afterEach(function () {
    TrustProxies::flushState();
});

/**
 * Rejoue la declaration du provider, celle-la meme qui tourne au demarrage.
 * Passer par la methode reelle plutot que par TrustProxies::at() directement :
 * c'est la lecture de la configuration qu'on veut eprouver, pas Laravel.
 */
function declarerLesProxys(): void
{
    $provider = new AppServiceProvider(app());
    $methode = new ReflectionMethod($provider, 'declarerLesProxysDeConfiance');
    $methode->invoke($provider);
}

/**
 * Fait traverser le middleware a une requete venue de $adresse, en se
 * presentant comme relayee en HTTPS, et repond si Laravel l'a crue.
 */
function requeteVueCommeSecurisee(string $adresse): bool
{
    $requete = Request::create('http://exemple.ci/', server: [
        'REMOTE_ADDR' => $adresse,
        'HTTP_X_FORWARDED_PROTO' => 'https',
    ]);

    return (new TrustProxies)->handle($requete, fn (Request $r) => $r)->isSecure();
}

it('ne se fie a aucun proxy tant que rien n est declare', function () {
    config(['app.trusted_proxies' => null]);
    declarerLesProxys();

    // Le comportement d'un serveur sans proxy devant lui ne doit pas changer :
    // un en-tete X-Forwarded-Proto venu de n'importe ou ne prouve rien.
    expect(requeteVueCommeSecurisee('192.168.1.1'))->toBeFalse();
});

it('se fie a l adresse declaree, et a elle seule', function () {
    config(['app.trusted_proxies' => '192.168.1.1']);
    declarerLesProxys();

    expect(requeteVueCommeSecurisee('192.168.1.1'))->toBeTrue()
        ->and(requeteVueCommeSecurisee('203.0.113.7'))->toBeFalse();
});

it('accepte plusieurs adresses separees par des virgules', function () {
    // Les espaces autour des virgules sont frequents dans un .env ecrit a la
    // main. Les laisser passer donnerait une adresse « 10.0.0.2 » precedee
    // d'un blanc, qui ne correspondrait jamais.
    config(['app.trusted_proxies' => '10.0.0.1, 10.0.0.2']);
    declarerLesProxys();

    expect(requeteVueCommeSecurisee('10.0.0.1'))->toBeTrue()
        ->and(requeteVueCommeSecurisee('10.0.0.2'))->toBeTrue()
        ->and(requeteVueCommeSecurisee('10.0.0.3'))->toBeFalse();
});

it('accepte l etoile quand PHP n est joignable que par le proxy', function () {
    config(['app.trusted_proxies' => '*']);
    declarerLesProxys();

    expect(requeteVueCommeSecurisee('203.0.113.7'))->toBeTrue();
});

it('lit la configuration et non l environnement', function () {
    // .env n'est plus lu des que la configuration est mise en cache, ce que
    // fait tout deploiement de production. Un reglage pris dans env() aurait
    // donc fonctionne en developpement et serait reste inerte la ou il sert.
    // Ici la valeur ne vient que de config() : aucune variable d'environnement
    // n'est posee, et la declaration suit quand meme.
    expect(env('TRUSTED_PROXIES'))->toBeNull();

    config(['app.trusted_proxies' => '198.51.100.4']);
    declarerLesProxys();

    expect(requeteVueCommeSecurisee('198.51.100.4'))->toBeTrue();
});

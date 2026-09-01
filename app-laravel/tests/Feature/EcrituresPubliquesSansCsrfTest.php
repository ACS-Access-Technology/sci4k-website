<?php

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;

/**
 * Les trois points d'ecriture publics doivent rester hors du controle CSRF.
 *
 * Ce test existe parce qu'aucun test de fonctionnalite ne peut attraper la
 * panne qu'il previent : Laravel court-circuite ValidateCsrfToken en
 * environnement de test. Les envois du formulaire de contact, de la lettre
 * d'information et des demandes de visite repondaient 419 en production
 * pendant que la suite entiere restait verte — et main.js avale l'echec en
 * silence, donc rien ne le signalait a l'ecran non plus.
 *
 * On verifie donc la CONFIGURATION, faute de pouvoir verifier le
 * comportement. Retirer une de ces routes de l'exception casse ce test.
 */
/**
 * La liste est une propriete statique protegee : Laravel ne l'expose pas.
 *
 * @return list<string>
 */
function cheminsHorsCsrf(): array
{
    return (new ReflectionClass(ValidateCsrfToken::class))
        ->getStaticPropertyValue('neverVerify');
}

it('laisse les ecritures publiques hors du controle CSRF', function (string $chemin) {
    expect(cheminsHorsCsrf())->toContain($chemin);
})->with(['messages', 'newsletter', 'visites']);

/**
 * L'exception ne doit couvrir QUE ces trois routes. Une entree de trop —
 * « admin/* », par exemple — retirerait le controle la ou il protege vraiment
 * quelque chose : une action accomplie par un compte connecte a son insu.
 */
it('n ouvre l exception a rien d autre', function () {
    expect(cheminsHorsCsrf())
        ->toEqualCanonicalizing(['messages', 'newsletter', 'visites']);
});

/**
 * La limitation de debit est ce qui remplace le controle retire. Si elle
 * disparaissait, ces routes deviendraient des robinets ouverts.
 */
it('borne le debit des trois routes', function (string $nom) {
    $route = collect(app('router')->getRoutes())->first(
        fn ($r) => $r->getName() === $nom,
    );

    expect($route)->not->toBeNull("La route {$nom} doit exister.");
    expect($route->gatherMiddleware())->toContain('throttle:5,1');
})->with([
    'messages.reception',
    'newsletter.inscription',
    'visites.reception',
]);

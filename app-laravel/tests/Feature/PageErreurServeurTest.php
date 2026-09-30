<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/**
 * La page « erreur serveur » du site, et d'abord quand la base est tombee.
 *
 * Une erreur 500 arrive souvent PARCE QUE la base est injoignable. Une page
 * qui la lirait — pour son menu, son logo, ses textes — tomberait a son tour,
 * et Laravel reviendrait a son ecran d'usine, « Server Error », en anglais.
 * Ces tests coupent donc la base pour de bon, et regardent ce que recoit le
 * visiteur.
 */
beforeEach(function () {
    // L'ecran de debogage prendrait la place de la page : en production,
    // APP_DEBUG vaut false.
    config(['app.debug' => false]);
    $this->connexionDOrigine = config('database.default');
});

// La transaction du test vit sur la connexion d'origine : elle doit etre
// retablie avant que RefreshDatabase ne l'annule.
afterEach(function () {
    config(['database.default' => $this->connexionDOrigine]);
    DB::purge('injoignable');
});

/** Une base qui n'existe pas : toute requete SQL echoue aussitot. */
function couperLaBase(): void
{
    config([
        'database.connections.injoignable' => [
            'driver' => 'sqlite',
            'database' => storage_path('framework/testing/base-absente-'.uniqid().'.sqlite'),
        ],
        'database.default' => 'injoignable',
    ]);
    DB::purge('injoignable');
}

it('affiche la page du site quand la base est injoignable', function (string $adresse, string $langue, string $titre) {
    couperLaBase();

    $reponse = $this->get($adresse)->assertStatus(500);

    expect($reponse->getContent())
        ->toContain('<html lang="'.$langue.'">')
        ->toContain($titre)
        ->toContain('<meta name="robots" content="noindex, nofollow">')
        ->not->toContain('Server Error');
})->with([
    'francais' => ['/', 'fr', 'Le site rencontre un problème'],
    'anglais' => ['/en/services', 'en', 'The site has run into a problem'],
]);

it('ne lit rien en base pour s afficher', function () {
    $requetes = 0;
    DB::listen(function () use (&$requetes) {
        $requetes++;
    });

    $html = view('errors.500')->render();

    expect($html)->toContain('500')
        ->and($requetes)->toBe(0);
});

it('garde ses scripts sous le nonce de la politique de contenu', function () {
    Route::middleware('web')->get('/essai-erreur-serveur', fn () => throw new RuntimeException('Essai.'));

    $reponse = $this->get('/essai-erreur-serveur')->assertStatus(500);

    preg_match("/'nonce-([^']+)'/", (string) $reponse->headers->get('Content-Security-Policy'), $nonce);

    expect($nonce[1] ?? null)->not->toBeNull()
        ->and($reponse->getContent())->toContain('<script nonce="'.$nonce[1].'">')
        ->and($reponse->getContent())->toContain('Le site rencontre un problème');
});

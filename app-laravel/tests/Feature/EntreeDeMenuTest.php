<?php

use App\Models\EntreeDeMenu;

/*
 * La cible d'une entree de menu.
 *
 * Elle est saisie en administration et ressort dans un attribut href, sur
 * TOUTES les pages du site puisque le menu y est partout. C'est le point le
 * plus sensible de cette table, et les tests portent d'abord sur lui.
 */

it('accepte un chemin interne', function () {
    expect(EntreeDeMenu::cibleAcceptable('/biens.html'))->toBeTrue()
        ->and(EntreeDeMenu::cibleAcceptable('/'))->toBeTrue();
});

it('accepte un nom de route de l application', function () {
    expect(EntreeDeMenu::cibleAcceptable('services.index'))->toBeTrue();
});

it('accepte une adresse http complete', function () {
    expect(EntreeDeMenu::cibleAcceptable('https://sci4k.com'))->toBeTrue()
        ->and(EntreeDeMenu::cibleAcceptable('http://exemple.ci/page'))->toBeTrue();
});

it('refuse une cible qui executerait du code au clic', function () {
    // Le cas qui justifie tout ce controle : ces cibles s'executent dans le
    // navigateur du VISITEUR, sur chaque page du site.
    foreach ([
        'javascript:alert(1)',
        'JavaScript:alert(1)',
        'data:text/html,<script>alert(1)</script>',
        'vbscript:msgbox(1)',
    ] as $cible) {
        expect(EntreeDeMenu::cibleAcceptable($cible))->toBeFalse("« $cible » aurait dû être refusée");
    }
});

it('refuse une cible protocole-relative', function () {
    // « //autre-site.com » RESSEMBLE a un chemin interne et n'en est pas un :
    // le navigateur y voit un autre domaine, servi dans le protocole courant.
    expect(EntreeDeMenu::cibleAcceptable('//evil.example'))->toBeFalse();
});

it('refuse une cible vide', function () {
    expect(EntreeDeMenu::cibleAcceptable(''))->toBeFalse()
        ->and(EntreeDeMenu::cibleAcceptable('   '))->toBeFalse()
        ->and(EntreeDeMenu::cibleAcceptable(null))->toBeFalse();
});

it('degrade un lien devenu invalide plutot que de le rendre dangereux', function () {
    // Une donnee ancienne, ou une route renommee depuis. Le lien doit devenir
    // inerte, jamais executable.
    $entree = new EntreeDeMenu(['cible' => 'javascript:alert(1)']);

    expect($entree->lien())->toBe('#');
});

it('resout un nom de route en adresse', function () {
    $entree = new EntreeDeMenu(['cible' => 'services.index']);

    expect($entree->lien())->toBe(route('services.index'));
});

it('rend un chemin interne tel quel', function () {
    $entree = new EntreeDeMenu(['cible' => '/biens.html']);

    expect($entree->lien())->toBe('/biens.html');
});

<?php

use App\Support\Ressource;

/**
 * Les feuilles et scripts du site portent une empreinte de version.
 *
 * Sans elle, un navigateur qui les a deja vus les ressert depuis son cache :
 * une correction livree et deployee restait invisible, sans que rien ne le
 * signale. C'est ce qui a fait croire, plusieurs fois de suite, qu'un
 * correctif CSS n'avait aucun effet.
 */
beforeEach(fn () => Ressource::oublierLesEmpreintes());

it('ajoute une empreinte de version a une ressource existante', function () {
    $url = Ressource::url('assets/style.css');

    expect($url)->toContain('assets/style.css')
        ->and($url)->toMatch('/\?v=\d+$/');
});

it('reprend la date de modification du fichier', function () {
    $attendue = filemtime(public_path('assets/style.css'));

    expect(Ressource::url('assets/style.css'))->toEndWith('?v='.$attendue);
});

/**
 * Une ressource absente ne doit pas faire echouer le rendu : la page se sert
 * alors sans empreinte, exactement comme avant.
 */
it('reste silencieuse quand le fichier est absent', function () {
    $url = Ressource::url('assets/inexistant.css');

    expect($url)->toContain('assets/inexistant.css')
        ->and($url)->not->toContain('?v=');
});

it('sert les trois ressources du site avec leur empreinte', function () {
    $reponse = $this->get('/');

    $reponse->assertOk();

    foreach (['assets/style.css', 'assets/images.css', 'assets/main.js'] as $chemin) {
        $reponse->assertSee($chemin.'?v=', false);
    }
});

/** Le catalogue emploie l'autre gabarit ; il ne doit pas etre oublie. */
it('sert aussi les ressources du catalogue avec leur empreinte', function () {
    $this->get('/biens')
        ->assertOk()
        ->assertSee('assets/style.css?v=', false);
});

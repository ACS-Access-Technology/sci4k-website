<?php

use App\Models\RubriqueFaq;

/*
 * La bascule de langue ne doit jamais se renvoyer a elle-meme.
 *
 * Defaut trouve par la relecture adversariale, et introduit par la correction
 * precedente : LangueController rendait `back()`, or la session enregistre
 * /langue/* comme URL precedente — requete GET, route existante, pas d'en-tete
 * AJAX. Deux bascules consecutives se renvoyaient donc l'une a l'autre :
 *
 *   GET /langue/en -> 302 vers /services      (correct)
 *   GET /langue/fr -> 302 vers /langue/en     (boucle)
 *
 * Le `fetch` de main.js suit les redirections, et CHAQUE saut reecrit la
 * langue en session : un visiteur qui bascule deux fois depuis une page
 * statique terminait sur la langue opposee a celle qu'il avait demandee. Le
 * `.catch()` du script rendait la chose muette.
 */
beforeEach(function () {
    RubriqueFaq::factory()->create(['slug' => 'foncier', 'nom_fr' => 'Foncier', 'ordre' => 1]);
});

it('renvoie vers la meme page, dans la langue demandee', function () {
    // La bascule ne ramene plus a l'adresse d'ou l'on vient : elle mene a sa
    // TRADUCTION. La langue vit dans l'adresse, changer de langue c'est donc
    // changer d'adresse.
    $this->get('/services');

    $this->get(route('langue.basculer', 'en'))
        ->assertRedirect('/en/services');
});

it('ne se renvoie jamais a une adresse de bascule', function () {
    // Le coeur du defaut : la seconde bascule ne doit pas pointer la premiere.
    $this->get('/services');
    $this->get(route('langue.basculer', 'en'));

    $reponse = $this->get(route('langue.basculer', 'fr'));

    $destination = $reponse->headers->get('Location');

    expect($destination)->not->toContain('/langue/');
});

it('retombe sur l accueil quand la page precedente est une bascule', function () {
    $this->get('/services');
    $this->get(route('langue.basculer', 'en'));

    $this->get(route('langue.basculer', 'fr'))->assertRedirect('/');
});

it('applique bien la langue demandee malgre l enchainement', function () {
    // Ce qui comptait pour le visiteur : deux bascules d'affilee doivent finir
    // sur la langue du dernier clic, et non sur son contraire.
    $this->get('/services');
    $this->get(route('langue.basculer', 'en'));
    $this->get(route('langue.basculer', 'fr'));

    expect(session('langue'))->toBe('fr');
    $this->get('/services')->assertOk()->assertSee('<html lang="fr">', false);
});

it('retombe sur l accueil sans page precedente', function () {
    // L'accueil de la langue demandee, et non celui du francais.
    $this->get(route('langue.basculer', 'en'))->assertRedirect('/en');
});

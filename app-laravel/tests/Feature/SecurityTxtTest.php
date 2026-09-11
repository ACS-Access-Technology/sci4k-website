<?php

/*
 * Le fichier qui dit ou signaler une faille.
 *
 * Sans lui, un chercheur qui trouve un defaut sur le site n'a aucun moyen
 * evident de le dire : il ecrit a une adresse trouvee au hasard, ou il publie.
 * C'est le standard RFC 9116, et le manuel ACS l'exige sur toute plateforme
 * exposee.
 *
 * LA DATE D'EXPIRATION EST OBLIGATOIRE dans ce format, et c'est son piege : un
 * fichier statique se perime en silence, et un security.txt perime vaut moins
 * que pas de security.txt — il affirme une disponibilite qui n'existe plus. On
 * la calcule donc a chaque requete.
 */

use App\Models\Parametre;

it('sert le fichier a l adresse normalisee', function () {
    $this->get('/.well-known/security.txt')
        ->assertOk()
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
});

/** L'ancienne adresse reste servie : les outils d'audit la cherchent encore. */
it('sert aussi l ancienne adresse', function () {
    $this->get('/security.txt')->assertOk();
});

it('porte un contact et une expiration future', function () {
    $corps = $this->get('/.well-known/security.txt')->assertOk()->getContent();

    expect($corps)->toContain('Contact:')
        ->toContain('Expires:')
        ->toContain('Preferred-Languages:');

    preg_match('/Expires:\s*(\S+)/', $corps, $trouve);

    expect(strtotime($trouve[1]))->toBeGreaterThan(time(), 'La date d’expiration est déjà passée.');
});

/*
 * Le contact suit l'adresse publique du site : la changer dans Configuration
 * doit suffire, sans qu'on ait a se souvenir d'un fichier.
 */
it('reprend l adresse publique enregistree', function () {
    Parametre::poser('email_public', 'securite@sci4k.com', 'contact');

    $this->get('/.well-known/security.txt')
        ->assertOk()
        ->assertSee('securite@sci4k.com', false);
});

/** Le fichier n'est pas indexable : il n'a rien a faire dans un moteur. */
it('demande aux moteurs de l ignorer', function () {
    $this->get('/.well-known/security.txt')->assertHeader('X-Robots-Tag', 'noindex');
});

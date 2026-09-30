<?php

/**
 * Ce que le serveur sert sans passer par PHP, et ce que l'image emporte.
 *
 * Les fichiers statiques et televerses sont servis par Caddy, avant Laravel :
 * aucun middleware ne leur pose d'en-tete. Ces tests lisent docker/Caddyfile
 * et .dockerignore ; le conteneur reel les a verifies de bout en bout.
 */
function caddyfile(): string
{
    return (string) file_get_contents(base_path('../docker/Caddyfile'));
}

it('pose les en-tetes de securite sur les fichiers servis par Caddy', function () {
    $caddy = caddyfile();

    // « ? » : seulement s'ils manquent. Les pages de Laravel gardent les
    // leurs, et surtout leur politique de contenu.
    expect($caddy)->toMatch('/header \{[^}]*\?X-Content-Type-Options nosniff/s')
        ->toMatch('/header \{[^}]*\?Referrer-Policy strict-origin-when-cross-origin/s')
        ->toMatch('/header \{[^}]*\?X-Frame-Options DENY/s');
});

it('n execute jamais un fichier televerse comme une page', function () {
    // L'ecran Configuration accepte un favicon SVG ; un SVG ouvert
    // directement peut porter du script.
    expect(caddyfile())->toContain('@televerses path /storage/*')
        ->toMatch('/header @televerses \?Content-Security-Policy "[^"]*default-src \'none\'[^"]*sandbox"/');
});

it('n emporte pas les fichiers des tests dans l image', function () {
    $ignores = array_map('trim', file(base_path('../.dockerignore')));

    expect($ignores)->toContain('app-laravel/storage/framework/testing/');
});

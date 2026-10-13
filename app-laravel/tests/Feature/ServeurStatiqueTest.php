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

/*
 * php_server execute tout fichier .php trouve sous public/, et /storage mene
 * au volume des televersements. L'essai en conteneur (rapport de la phase 14)
 * a verifie les variantes : casse, suite de chemin, double barre, encodage.
 */
it('n execute aucun fichier PHP depose sous storage', function () {
    $caddy = caddyfile();

    expect($caddy)->toContain('@php_televerse path_regexp (?i)/storage/.*\.php')
        ->toContain('respond @php_televerse 404');

    // Avant php_server dans le fichier, par lisibilite ; Caddy les ordonne
    // de toute facon selon l'ordre des directives, « respond » en premier.
    expect(strpos($caddy, 'respond @php_televerse'))->toBeLessThan(strrpos($caddy, "\tphp_server"));
});

it('n emporte pas les fichiers des tests dans l image', function () {
    $ignores = array_map('trim', file(base_path('../.dockerignore')));

    expect($ignores)->toContain('app-laravel/storage/framework/testing/');
});

/*
 * php_server execute le point d'entree de Laravel sous /index.php : sans cette
 * regle, chaque page repondait aussi a /index.php/<page>, avec une balise
 * canonique pointant sur cette copie. Les regles ont ete essayees dans un
 * conteneur FrankenPHP : / et /contact restent servis, /index.php,
 * /index.php?x=1 et /index.php/contact repondent 301 vers /, / et /contact.
 */
it('redirige /index.php et /index.php/<page> vers l adresse sans le point d entree', function () {
    $caddy = caddyfile();

    expect($caddy)->toContain('@point_d_entree_seul path /index.php')
        ->toContain('redir @point_d_entree_seul / 301')
        ->toMatch('/@point_d_entree_et_chemin path_regexp point_d_entree \^\/index\\\.php\(\?P<reste>\/\.\+\)\$/')
        ->toContain('redir @point_d_entree_et_chemin {http.regexp.point_d_entree.reste} 301');

    // Avant php_server, que ces adresses atteindraient sinon.
    expect(strpos($caddy, 'redir @point_d_entree_seul'))->toBeLessThan(strrpos($caddy, "\tphp_server"));
});

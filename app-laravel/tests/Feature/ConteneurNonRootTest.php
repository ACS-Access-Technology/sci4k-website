<?php

/**
 * Le conteneur de production ne fait plus tourner le site sous root.
 *
 * Ces tests lisent le Dockerfile et le script de demarrage : la suite ne
 * construit pas l'image. Ils gardent les trois points qu'une retouche
 * pourrait defaire sans que rien d'autre ne le signale — l'abandon des
 * privileges, son moment, et des droits limites a ce que Laravel ecrit. La
 * verification en conteneur reel est decrite dans le rapport de la phase 8.
 */
function fichierDuDepot(string $chemin): string
{
    return (string) file_get_contents(base_path('../'.$chemin));
}

/** Les lignes executees d'un fichier, sans ses commentaires. */
function lignesExecutees(string $contenu): string
{
    return collect(preg_split('/\R/', $contenu))
        ->reject(fn ($ligne) => str_starts_with(ltrim($ligne), '#'))
        ->implode("\n");
}

it('abandonne les privileges de root avant toute commande du site', function () {
    $script = lignesExecutees(fichierDuDepot('tools/demarrer-conteneur.sh'));

    $abandon = strpos($script, 'exec setpriv');

    expect($abandon)->not->toBeFalse('le script ne quitte jamais root')
        ->and($script)->toMatch('/setpriv --reuid="\$utilisateur" --regid="\$utilisateur" --init-groups/')
        ->and($script)->toContain('--no-new-privs')
        ->and($script)->toContain('utilisateur=www-data');

    // Rien de l'application ne s'execute avant : ni artisan, ni le serveur.
    foreach (['php artisan', 'frankenphp run', 'schedule:work'] as $commande) {
        $position = strpos($script, $commande);

        expect($position)->not->toBeFalse("« $commande » a disparu du script")
            ->and($position)->toBeGreaterThan($abandon, "« $commande » tournerait encore sous root");
    }
});

it('ne rend sous root que le volume, et seulement ce qui ne lui appartient pas', function () {
    $script = lignesExecutees(fichierDuDepot('tools/demarrer-conteneur.sh'));
    $debut = strpos($script, 'if [[ "$(id -u)" = "0" ]]');
    $sousRoot = substr($script, $debut, strpos($script, 'exec setpriv') - $debut);

    expect($sousRoot)->toContain('find storage bootstrap/cache \! -user "$utilisateur"')
        ->and($sousRoot)->not->toMatch('/chown -R|chmod/');
});

it('ne donne l ecriture qu aux dossiers que Laravel ecrit', function () {
    // Les lignes continuees (« \ » en fin de ligne) sont recollees d'abord.
    $dockerfile = preg_replace('/\\\\\R\s*/', ' ', lignesExecutees(fichierDuDepot('Dockerfile')));

    preg_match_all('/chown\s+(-R\s+)?www-data:www-data\s+([^&\n]+)/', $dockerfile, $trouves);
    $cibles = preg_split('/\s+/', trim(implode(' ', $trouves[2])), -1, PREG_SPLIT_NO_EMPTY);

    expect($cibles)->toEqualCanonicalizing([
        'app-laravel/storage', 'app-laravel/bootstrap/cache', '/data/caddy', '/config/caddy',
    ]);

    // Ni ecriture pour tous, ni l'application entiere cedee a www-data.
    expect($dockerfile)->not->toMatch('/chmod\s+(-R\s+)?(0?777|a\+w|o\+w)/')
        ->not->toMatch('/chown[^\n]*\s(\/app|\.|app-laravel)\s*$/m');
});

it('pose le lien public storage pendant la construction', function () {
    // Au demarrage, www-data ne peut plus ecrire dans public/.
    expect(lignesExecutees(fichierDuDepot('Dockerfile')))
        ->toContain('ln -sfn ../storage/app/public app-laravel/public/storage');
});

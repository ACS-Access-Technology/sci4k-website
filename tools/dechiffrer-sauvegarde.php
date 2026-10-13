<?php

/**
 * Dechiffre une sauvegarde SCI4K avec PHP seul (extension sodium) :
 *
 *   SAUVEGARDE_CLE=... php tools/dechiffrer-sauvegarde.php base-....sql.gz.enc base.sql.gz
 *
 * N'a besoin ni de l'application, ni de Composer, ni de la base : c'est fait
 * pour le jour ou il ne reste que les fichiers sauvegardes et la cle.
 * Voir docs/SAUVEGARDES.md.
 */

require_once __DIR__.'/../app-laravel/app/Support/Sauvegarde/FluxChiffre.php';

use App\Support\Sauvegarde\FluxChiffre;

if ($argc !== 3) {
    fwrite(STDERR, "Usage : SAUVEGARDE_CLE=... php tools/dechiffrer-sauvegarde.php entree.enc sortie\n");
    exit(2);
}

try {
    FluxChiffre::dechiffrer($argv[1], $argv[2], FluxChiffre::cle((string) getenv('SAUVEGARDE_CLE')));
    fwrite(STDOUT, "Dechiffre : $argv[2]\n");
} catch (Throwable $e) {
    fwrite(STDERR, 'ERREUR : '.$e->getMessage()."\n");
    exit(1);
}

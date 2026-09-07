<?php

/*
 * Le point d'entree des plateformes au disque en lecture seule.
 *
 * Laravel ecrit des son demarrage — vues compilees, journaux — et Vercel monte
 * le depot en lecture seule hors d'un /tmp ephemere. Ces redirections sont donc
 * la condition pour que la premiere page s'affiche.
 *
 * Un contournement qu'on ne peut pas verifier est un pari : ce test l'exerce.
 */

it('deplace les vues compilees et les journaux hors du disque en lecture seule', function () {
    $variables = require base_path('api/preparer-environnement.php');

    expect($variables['VIEW_COMPILED_PATH'])->toStartWith(sys_get_temp_dir())
        ->and(is_dir($variables['VIEW_COMPILED_PATH']))->toBeTrue()
        ->and($variables['LOG_CHANNEL'])->toBe('stderr')
        // LOG_STACK aussi : le canal « stack » lit cette seconde variable, et
        // la laisser a « daily » ferait ecrire un fichier malgre tout.
        ->and($variables['LOG_STACK'])->toBe('stderr');
});

it('pose les variables la ou l environnement les lira', function () {
    require base_path('api/preparer-environnement.php');

    // putenv() seul ne suffit pas : le lecteur d'environnement de Laravel
    // consulte $_ENV et $_SERVER selon la configuration du serveur.
    expect(getenv('VIEW_COMPILED_PATH'))->not->toBeFalse()
        ->and($_ENV['VIEW_COMPILED_PATH'] ?? null)->not->toBeNull()
        ->and($_SERVER['VIEW_COMPILED_PATH'] ?? null)->not->toBeNull();
});

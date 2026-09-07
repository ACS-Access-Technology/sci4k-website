<?php

/*
 * Les contournements qu'exige un disque en lecture seule.
 *
 * Vercel monte le depot en lecture seule, hors d'un /tmp ephemere de 500 Mo.
 * Or Laravel ecrit des son demarrage : les vues Blade compilees, et les
 * journaux. Sans ces redirections, la premiere page rendue echoue sur une
 * ecriture refusee — et l'erreur accuse la vue, non le systeme de fichiers.
 *
 * Ce fichier est separe du point d'entree pour qu'un test puisse l'exercer :
 * un contournement qu'on ne peut pas verifier est un pari.
 *
 * @return array<string, string> Les variables posees, pour que le test les lise.
 */

return (static function (): array {
    $vues = sys_get_temp_dir().'/vues-compilees';

    if (! is_dir($vues)) {
        @mkdir($vues, 0755, true);
    }

    $variables = [
        // config/view.php du framework lit cette variable : la poser avant le
        // demarrage suffit, sans toucher a la configuration du projet.
        'VIEW_COMPILED_PATH' => $vues,
        // « daily » tenterait d'ecrire dans storage/logs. La plateforme
        // collecte la sortie d'erreur.
        'LOG_CHANNEL' => 'stderr',
        'LOG_STACK' => 'stderr',
    ];

    foreach ($variables as $cle => $valeur) {
        putenv("{$cle}={$valeur}");
        $_ENV[$cle] = $_SERVER[$cle] = $valeur;
    }

    return $variables;
})();

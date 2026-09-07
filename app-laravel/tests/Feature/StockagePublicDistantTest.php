<?php

/*
 * Ou vivent les fichiers televerses depuis le backoffice.
 *
 * Sur un hebergement ordinaire, ils vivent sur le disque du serveur, servis par
 * le lien public/storage. Sur une plateforme sans systeme de fichiers persistant
 * — Vercel, ou le disque est en lecture seule hors d'un /tmp ephemere — ils
 * doivent partir vers un stockage objet, sans quoi chaque image ajoutee
 * disparait a la mise en ligne suivante.
 *
 * LE CODE METIER N'EN SAIT RIEN, et c'est tout l'objet de ce reglage. Les
 * treize points qui ecrivent ou effacent une image passent par
 * Storage::disk('public') ; basculer ce disque suffit, sans qu'aucune de ces
 * lignes ne change. C'est le contrat Filesystem de Laravel qui rend le
 * deplacement possible.
 */

/** Relit la configuration des disques avec une variable d'environnement posee. */
function disquesAvec(array $environnement): array
{
    $anciennes = [];

    foreach ($environnement as $cle => $valeur) {
        $anciennes[$cle] = $_ENV[$cle] ?? null;
        $_ENV[$cle] = $_SERVER[$cle] = $valeur;
        putenv("{$cle}={$valeur}");
    }

    $configuration = require config_path('filesystems.php');

    foreach ($anciennes as $cle => $valeur) {
        if ($valeur === null) {
            unset($_ENV[$cle], $_SERVER[$cle]);
            putenv($cle);
        } else {
            $_ENV[$cle] = $_SERVER[$cle] = $valeur;
            putenv("{$cle}={$valeur}");
        }
    }

    return $configuration['disks'];
}

it('garde les fichiers sur le disque du serveur par defaut', function () {
    $disques = disquesAvec(['STOCKAGE_PUBLIC_DISTANT' => 'false']);

    expect($disques['public']['driver'])->toBe('local')
        ->and($disques['public']['root'])->toBe(storage_path('app/public'));
});

it('bascule vers le stockage objet quand il est demande', function () {
    $disques = disquesAvec([
        'STOCKAGE_PUBLIC_DISTANT' => 'true',
        'AWS_BUCKET' => 'sci4k-medias',
        'AWS_ENDPOINT' => 'https://exemple.r2.cloudflarestorage.com',
    ]);

    expect($disques['public']['driver'])->toBe('s3')
        ->and($disques['public']['bucket'])->toBe('sci4k-medias')
        // Sans visibilite publique, les images televersees seraient ecrites
        // puis refusees a la lecture : le site afficherait des cadres vides.
        ->and($disques['public']['visibility'])->toBe('public');
});

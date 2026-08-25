<?php

/*
 * Garde-fou sur la convention de traduction du projet : les cles de __() sont
 * les textes francais eux-memes.
 *
 * Laravel resout une cle sans point contre ses fichiers de langue avant de
 * regarder le dictionnaire JSON. Quatre noms sont donc reserves — auth,
 * pagination, passwords, validation — et __('Pagination') renvoie le TABLEAU
 * du fichier au lieu d'une chaine, ce qui fait tomber la page en 500 des que
 * Blade tente de l'echapper.
 *
 * Le piege est d'autant plus vicieux que la resolution suit la casse du
 * systeme de fichiers : sur macOS « Pagination » heurte pagination.php, sur un
 * serveur Linux non. Une page saine en developpement peut donc casser en
 * production, ou l'inverse. Ce test verrouille les deux cas en interdisant la
 * collision quelle que soit la casse.
 */

use Illuminate\Support\Facades\App;

/** Extrait les cles litterales passees a __() dans toutes les vues Blade. */
function clesDeTraductionDesVues(): array
{
    $racine = resource_path('views');
    $cles = [];

    $fichiers = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($racine));

    foreach ($fichiers as $fichier) {
        if (! $fichier->isFile() || ! str_ends_with($fichier->getFilename(), '.blade.php')) {
            continue;
        }

        $contenu = file_get_contents($fichier->getPathname());

        // __('…') et __("…"), en ignorant les appels dont l'argument est une
        // variable ou une concatenation : seules les cles litterales comptent.
        preg_match_all('/__\(\s*([\'"])((?:(?!\1).)*)\1/s', $contenu, $trouvees);

        foreach ($trouvees[2] as $cle) {
            $cles[$cle] = $fichier->getPathname();
        }
    }

    return $cles;
}

it('trouve des cles de traduction a controler', function () {
    expect(clesDeTraductionDesVues())->not->toBeEmpty();
});

it('ne rend jamais un tableau la ou une chaine est attendue', function () {
    $fautives = [];

    foreach (clesDeTraductionDesVues() as $cle => $fichier) {
        foreach (['fr', 'en'] as $langue) {
            App::setLocale($langue);

            if (! is_string(__($cle))) {
                $fautives[] = sprintf('%s [%s] dans %s', $cle, $langue, $fichier);
            }
        }
    }

    App::setLocale('fr');

    expect($fautives)->toBe([], "Ces cles se resolvent contre un fichier de langue du framework au lieu du dictionnaire :\n".implode("\n", $fautives));
});

it('refuse les quatre noms reserves par le framework, quelle que soit la casse', function () {
    $reserves = ['auth', 'pagination', 'passwords', 'validation'];

    $collisions = array_filter(
        array_keys(clesDeTraductionDesVues()),
        fn ($cle) => in_array(mb_strtolower($cle), $reserves, true)
    );

    expect(array_values($collisions))->toBe([]);
});

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

/**
 * Extrait les cles litterales passees a __() et a trans_choice() dans les
 * vues Blade et dans le code de l'application.
 *
 * `app/` compte autant que `resources/views/` : les messages de validation et
 * les notifications y sont ecrits, et echappaient au controle tant qu'il ne
 * regardait que les vues.
 *
 * trans_choice() est traite comme __() depuis la ronde de correction 1 : une
 * forme plurielle absente d'un dictionnaire y echappait entierement, le
 * controle « toute cle employee figure dans un dictionnaire » ne regardant
 * jusque-la que les appels a __().
 */
function clesDeTraductionDesVues(): array
{
    $cles = [];

    $fichiers = new AppendIterator;
    foreach ([resource_path('views'), app_path()] as $racine) {
        $fichiers->append(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($racine)));
    }

    foreach ($fichiers as $fichier) {
        $nom = $fichier->getFilename();

        if (! $fichier->isFile() || ! (str_ends_with($nom, '.blade.php') || str_ends_with($nom, '.php'))) {
            continue;
        }

        $contenu = file_get_contents($fichier->getPathname());

        // __('…'), __("…") et trans_choice('…', …), en ignorant les appels
        // dont l'argument est une variable ou une concatenation : seules les
        // cles litterales comptent. `\\.` avant l'alternative laisse passer
        // les quotes echappees, sans quoi __('Don\'t have an account?')
        // serait tronque a « Don\ ».
        preg_match_all('/(?:__|trans_choice)\(\s*([\'"])((?:\\\\.|(?!\1).)*)\1/s', $contenu, $trouvees);

        foreach ($trouvees[2] as $i => $cle) {
            $delimiteur = $trouvees[1][$i];
            $cle = str_replace(['\\'.$delimiteur, '\\\\'], [$delimiteur, '\\'], $cle);
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

it('traduit chaque cle employee par les vues', function () {
    /*
     * Le projet porte deux dictionnaires de sens inverse : en.json traduit les
     * cles francaises des vues du projet, fr.json les cles anglaises heritees
     * du starter kit. Une cle absente des deux n'est donc traduite dans aucune
     * direction — elle s'affichera dans sa langue d'origine au milieu de
     * l'autre, ce qui est exactement le defaut qui avait laisse « Dashboard »
     * et « Log out » dans une interface annoncee comme entierement francaise.
     *
     * Laravel ne fait pas jouer la langue de repli pour les cles JSON : une
     * cle absente ressort telle quelle, sans erreur ni trace. Rien ne signale
     * l'oubli, d'ou ce controle.
     */
    $dictionnaires = collect(['en', 'fr'])
        ->flatMap(fn ($langue) => array_keys(
            json_decode(file_get_contents(lang_path($langue.'.json')), true) ?? []
        ))
        ->flip();

    $orphelines = collect(clesDeTraductionDesVues())
        ->reject(fn ($fichier, $cle) => $dictionnaires->has($cle))
        ->map(fn ($fichier, $cle) => $cle.'  ('.str_replace(resource_path('views').'/', '', $fichier).')')
        ->values()
        ->all();

    expect($orphelines)->toBe([], "Ces cles ne figurent dans aucun des deux dictionnaires :\n".implode("\n", $orphelines));
});

it('ne sert jamais l anglais a un lecteur francais', function () {
    /*
     * Piege decouvert en refondant le tableau d'administration : trans_choice
     * consulte la langue de repli, la ou __() se contente de rendre la cle.
     * Une forme plurielle absente de fr.json retombait donc sur en.json, et la
     * page francaise affichait « 13 published articles » au milieu du reste.
     *
     * Le controle precedent ne pouvait pas le voir : la cle etait bien presente
     * dans un dictionnaire, simplement dans le mauvais.
     */
    $anglais = json_decode(file_get_contents(lang_path('en.json')), true) ?? [];

    App::setLocale('fr');

    $fuites = [];

    foreach ($anglais as $cle => $traduction) {
        // Une cle dont la traduction est identique au francais ne peut pas
        // fuir : « Documentation » se dit pareil des deux cotes.
        if ($traduction === $cle) {
            continue;
        }

        $rendu = str_contains($cle, '|')
            ? trans_choice($cle, 2, ['nombre' => 2])
            : __($cle);

        if ($rendu === $traduction) {
            $fuites[] = $cle.'  ->  '.$rendu;
        }
    }

    expect($fuites)->toBe([], "Ces cles rendent l'anglais alors que la langue est le francais :\n".implode("\n", $fuites));
});

it('rend les messages de validation en clair, jamais leur cle', function () {
    /*
     * Laravel ne livre ses messages qu'en anglais : en francais, une erreur de
     * saisie s'affichait « validation.required » — la cle brute. Aucune
     * exception, aucune trace dans les journaux ; le defaut ne se voyait qu'en
     * soumettant un formulaire incomplet.
     */
    $regles = ['required', 'string', 'date', 'image', 'unique', 'exists', 'in', 'regex', 'email', 'confirmed'];

    $brutes = [];

    foreach (['fr', 'en'] as $langue) {
        App::setLocale($langue);

        foreach ($regles as $regle) {
            $message = trans('validation.'.$regle, ['attribute' => 'champ']);

            if (! is_string($message) || str_starts_with($message, 'validation.')) {
                $brutes[] = $regle.' ['.$langue.']';
            }
        }

        foreach (['auth.failed', 'passwords.sent', 'passwords.token'] as $cle) {
            if (str_starts_with((string) trans($cle), explode('.', $cle)[0].'.')) {
                $brutes[] = $cle.' ['.$langue.']';
            }
        }
    }

    App::setLocale('fr');

    expect($brutes)->toBe([], "Ces messages sortent en cle brute :\n".implode("\n", $brutes));
});

it('nomme les champs du formulaire en clair dans les erreurs', function () {
    foreach (['fr', 'en'] as $langue) {
        App::setLocale($langue);

        foreach (['titreEn', 'contenuFr', 'couverture', 'slug'] as $champ) {
            expect(trans('validation.attributes.'.$champ))
                ->not->toBe('validation.attributes.'.$champ, "Le champ {$champ} n'a pas de nom lisible en {$langue}.");
        }
    }

    App::setLocale('fr');
});

it('refuse les quatre noms reserves par le framework, quelle que soit la casse', function () {
    $reserves = ['auth', 'pagination', 'passwords', 'validation'];

    $collisions = array_filter(
        array_keys(clesDeTraductionDesVues()),
        fn ($cle) => in_array(mb_strtolower($cle), $reserves, true)
    );

    expect(array_values($collisions))->toBe([]);
});

it('declare les formes plurielles dans les deux dictionnaires', function () {
    /*
     * Une forme plurielle absente de fr.json retombe sur la langue de repli et
     * rend l'anglais au milieu du francais ; absente de en.json, elle rend le
     * francais au milieu de l'anglais. Les deux sont donc obligatoires.
     *
     * Les controles voisins ne voyaient pas ce cas : l'un se contente d'un
     * dictionnaire sur deux, l'autre ne parcourt que les cles de en.json.
     */
    $anglais = json_decode(file_get_contents(lang_path('en.json')), true) ?? [];
    $francais = json_decode(file_get_contents(lang_path('fr.json')), true) ?? [];

    $manquantes = [];

    foreach (array_keys(clesDeTraductionDesVues()) as $cle) {
        if (! str_contains($cle, '|')) {
            continue;
        }

        if (! array_key_exists($cle, $anglais)) {
            $manquantes[] = $cle.' — absente de en.json';
        }

        if (! array_key_exists($cle, $francais)) {
            $manquantes[] = $cle.' — absente de fr.json';
        }
    }

    expect($manquantes)->toBe([], "Formes plurielles incompletes :\n".implode("\n", $manquantes));
});

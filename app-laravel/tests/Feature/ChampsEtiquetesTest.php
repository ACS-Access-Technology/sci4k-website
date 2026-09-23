<?php

use Illuminate\Support\Facades\File;

/*
 * Chaque champ de formulaire doit avoir un nom qu'un lecteur d'ecran annonce.
 *
 * Sans lui, on entend « zone de recherche », « liste deroulante », « bouton
 * parcourir » — sans savoir laquelle. Le texte indicatif ne compte pas : il
 * disparait des qu'on commence a taper, et les lecteurs d'ecran ne le lisent
 * pas tous.
 *
 * Une passe du 2026-09-23 en a trouve vingt et un sans nom dans
 * l'administration. Ce test les empeche de revenir, en relisant les vues comme
 * le ferait un analyseur — mais sans les trois erreurs qui faisaient mentir
 * SonarCloud sur ce projet :
 *
 *   - il voit les libelles poses par <x-admin.champ-filtre pour="...">, que
 *     l'analyseur ne peut pas suivre dans un autre fichier ;
 *   - il ignore les commentaires Blade, ou un mot comme « <input> » n'est pas
 *     une balise ;
 *   - il ne coupe pas une balise sur le « > » d'une directive comme
 *     @checked($tache->terminee).
 *
 * Un champ est nomme s'il porte aria-label ou aria-labelledby, s'il est
 * enveloppe dans un <label>, ou si son id est la cible d'un <label for> ou
 * d'un champ-filtre.
 */

/** Index du « > » qui ferme la balise, sans s'arreter dans une chaine ou une parenthese. */
function finDeBalise(string $source, int $depart): int
{
    $profondeur = 0;
    $guillemet = null;

    for ($i = $depart, $n = strlen($source); $i < $n; $i++) {
        $c = $source[$i];

        if ($guillemet !== null) {
            if ($c === $guillemet) {
                $guillemet = null;
            }
        } elseif ($c === '"' || $c === "'") {
            $guillemet = $c;
        } elseif ($c === '(') {
            $profondeur++;
        } elseif ($c === ')') {
            $profondeur = max(0, $profondeur - 1);
        } elseif ($c === '>' && $profondeur === 0) {
            return $i;
        }
    }

    return strlen($source);
}

/** Blanchit les commentaires Blade, en gardant les numeros de ligne. */
function sansCommentairesBlade(string $source): string
{
    return preg_replace_callback(
        '/\{\{--.*?--\}\}/s',
        fn (array $m) => preg_replace('/[^\n]/', ' ', $m[0]),
        $source
    );
}

/** @return list<string> « fichier:ligne <balise> » pour chaque champ sans nom */
function champsSansNom(): array
{
    $sansNom = [];
    $types = ['hidden', 'submit', 'button', 'image', 'reset'];

    foreach (File::allFiles(resource_path('views')) as $fichier) {
        if (! str_ends_with($fichier->getFilename(), '.blade.php')) {
            continue;
        }

        $source = sansCommentairesBlade($fichier->getContents());

        preg_match_all('/<label\b[^>]*\bfor="([^"]+)"/', $source, $pour);
        preg_match_all('/<x-admin\.champ-filtre\b[^>]*\bpour="([^"]+)"/', $source, $filtres);
        $cibles = array_flip([...$pour[1], ...$filtres[1]]);

        preg_match_all('/<(input|select|textarea)\b/', $source, $balises, PREG_OFFSET_CAPTURE);

        foreach ($balises[0] as $index => [$texte, $position]) {
            $debut = $position + strlen($texte);
            $attributs = substr($source, $debut, finDeBalise($source, $debut) - $debut);

            if (preg_match('/\btype="([^"]+)"/', $attributs, $type) && in_array($type[1], $types, true)) {
                continue;
            }

            if (preg_match('/\baria-label(ledby)?=/', $attributs)) {
                continue;
            }

            $avant = substr($source, 0, $position);
            if (preg_match_all('/<label\b/', $avant) > preg_match_all('/<\/label>/', $avant)) {
                continue;
            }

            if (preg_match('/\bid="([^"]+)"/', $attributs, $id) && isset($cibles[$id[1]])) {
                continue;
            }

            $sansNom[] = $fichier->getRelativePathname().':'.(substr_count($avant, "\n") + 1)
                .' <'.$balises[1][$index][0].'>';
        }
    }

    return $sansNom;
}

it('donne un nom accessible a chaque champ de formulaire', function () {
    expect(champsSansNom())->toBe([],
        'Ces champs n\'ont aucun nom pour un lecteur d\'ecran. Donnez-leur un <label>, '.
        'un aria-label, ou placez-les dans <x-admin.champ-filtre>.');
});

it('repere bien un champ sans nom', function () {
    // Le releve doit pouvoir echouer : un test qui ne trouve jamais rien ne
    // prouve rien. On depose une vue fautive le temps de l'assertion.
    $piege = resource_path('views/test-champ-sans-nom.blade.php');
    File::put($piege, "{{-- <input> dans un commentaire : ignore --}}\n<input type=\"search\" placeholder=\"Rechercher\">\n");

    try {
        expect(champsSansNom())->toContain('test-champ-sans-nom.blade.php:2 <input>')
            ->and(champsSansNom())->not->toContain('test-champ-sans-nom.blade.php:1 <input>');
    } finally {
        File::delete($piege);
    }
});

<?php

use App\Livewire\Admin\Configuration;
use App\Livewire\Admin\Mediatheque;
use App\Livewire\Admin\PageAccueil;
use App\Livewire\Admin\PageActualites;
use App\Livewire\Admin\PageBiens;
use App\Livewire\Admin\PageContact;
use App\Livewire\Admin\PageFaq;
use App\Livewire\Admin\PagePresentation;
use App\Livewire\Admin\PageServices;

/**
 * Tout ce que le site public LIT doit etre modifiable quelque part.
 *
 * Ces trois tests comparent deux listes que rien ne reliait : ce que les vues
 * publiques consomment, et ce que le backoffice propose. Le premier audit — 3
 * septembre 2026 — a trouve neuf images de fond et sept reglages que le site
 * affichait sans que personne puisse les changer.
 *
 * Ils sont ecrits pour ECHOUER quand on ajoute une image ou un reglage sans
 * lui donner d'ecran : c'est le seul moment ou l'oubli se voit.
 */

/**
 * Les slugs qu'un module d'ecran de page declare, plus les deux fonds sans
 * page que porte la mediatheque.
 *
 * @return list<string>
 */
function slugsDeFondCouverts(): array
{
    $ecrans = [
        PageAccueil::class,
        PagePresentation::class,
        PageBiens::class,
        PageServices::class,
        PageActualites::class,
        PageFaq::class,
        PageContact::class,
    ];

    $couverts = Mediatheque::FONDS_SANS_PAGE;

    foreach ($ecrans as $ecran) {
        foreach ((new $ecran)->modules() as $module) {
            $couverts = array_merge(
                $couverts,
                $module['fonds'] ?? array_filter([$module['fond'] ?? null]),
            );
        }
    }

    return array_values(array_unique($couverts));
}

/**
 * TOUTE ligne visible de la table devient une variable CSS `--img-{slug}` sur
 * le site — voir AppServiceProvider::variablesImagesDeFond. Une image que
 * personne ne declare est donc affichee sans etre modifiable.
 */
it('rend chaque image de fond modifiable depuis un ecran', function () {
    $declarees = json_decode(file_get_contents(database_path('data/images-de-fond.json')), true);
    $slugs = array_column($declarees, 'slug');

    $orphelines = array_values(array_diff($slugs, slugsDeFondCouverts()));

    expect($orphelines)->toBe([], 'Images affichées par le site mais modifiables nulle part : '
        .implode(', ', $orphelines));
});

/** Le contraire : un module ne doit pas annoncer une image qui n'existe pas. */
it('ne declare aucune image de fond inexistante', function () {
    $declarees = json_decode(file_get_contents(database_path('data/images-de-fond.json')), true);
    $slugs = array_column($declarees, 'slug');

    $inventees = array_values(array_diff(slugsDeFondCouverts(), $slugs));

    expect($inventees)->toBe([], 'Modules qui annoncent une image absente de la base : '
        .implode(', ', $inventees));
});

/**
 * Chaque reglage lu par le site public — vues, controleurs, composeurs de vue
 * — doit figurer dans l'ecran de configuration.
 */
it('rend chaque reglage lu par le site modifiable depuis la configuration', function () {
    $sources = collect(glob(resource_path('views/public/**/*.blade.php')))
        ->merge(glob(resource_path('views/public/*.blade.php')))
        ->merge(glob(app_path('Http/Controllers/*.php')))
        ->push(app_path('Providers/AppServiceProvider.php'))
        ->map(fn (string $f) => file_get_contents($f))
        ->implode("\n");

    preg_match_all(
        "/(?:Parametre::lire|\\\$this->parametre|\\\$this->parametreActif)\('([a-z0-9_]+)'/",
        $sources,
        $trouves,
    );

    // Les cles suffixees par la langue sont lues par concatenation : on les
    // ramene a leurs deux formes reelles.
    $lues = collect($trouves[1])
        ->flatMap(fn (string $cle) => str_ends_with($cle, '_')
            ? [$cle.'fr', $cle.'en']
            : [$cle])
        ->unique();

    $declares = array_keys(collect((new Configuration)->onglets())
        ->flatMap(fn (array $onglet) => $onglet['champs'])
        ->all());

    // Le logo et la favicone s'editent bien, mais par televersement : ils ont
    // leur propre mecanisme dans Configuration et ne figurent donc pas parmi
    // les champs declares.
    $declares = array_merge($declares, ['logo', 'favicon']);

    $manquants = $lues->reject(fn (string $cle) => in_array($cle, $declares, true))
        ->values()
        ->all();

    expect($manquants)->toBe([], 'Réglages lus par le site mais absents de la configuration : '
        .implode(', ', $manquants));
});

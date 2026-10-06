<?php

use App\Models\Referentiel;
use Database\Seeders\ReferentielsSeeder;

/**
 * Le retour arriere de la migration qui aligne les referentiels sur le site.
 *
 * Il echouait sur MySQL des que la table etait semee : array_flip() changeait
 * les valeurs « 1 », « 3 », « 5 » en entiers, et MySQL strict refusait de
 * comparer la colonne texte a un nombre (« Truncated incorrect DOUBLE value:
 * '1-2' »). SQLite laissait passer — ce test ne prouve donc quelque chose que
 * lors de la passe MySQL de la CI, qui rejoue toute la suite sur ce moteur.
 *
 * La migration ne fait que des UPDATE : elle tourne sans risque dans la
 * transaction du test.
 */
it('defait puis refait l alignement des referentiels', function () {
    $this->seed(ReferentielsSeeder::class);
    $migration = require database_path('migrations/2026_08_27_180000_aligne_les_referentiels_sur_les_filtres_du_site.php');

    $valeurs = fn (string $famille) => Referentiel::where('famille', $famille)->pluck('valeur')->sort()->values()->all();
    $pieces = $valeurs('tranches_pieces');

    $migration->down();

    expect($valeurs('tranches_pieces'))->toContain('1-2', '3-4', '5-plus')
        ->and(Referentiel::where('famille', 'types_de_bien')->where('valeur', 'villa-duplex')->exists())->toBeTrue();

    $migration->up();

    expect($valeurs('tranches_pieces'))->toBe($pieces);
});

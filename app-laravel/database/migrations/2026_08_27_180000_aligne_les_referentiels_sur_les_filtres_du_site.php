<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Aligne les valeurs techniques des referentiels sur celles du site.
 *
 * Elles avaient ete derivees des LIBELLES des filtres de /biens — « Villa &
 * Duplex » donnait « villa-duplex » — alors que le site emploie ses propres
 * cles : l'attribut `value` des listes deroulantes, et les memes chaines dans
 * les donnees des biens. Onze valeurs sur seize ne correspondaient pas.
 *
 * Le referentiel existait justement pour qu'il n'y ait QU'UN vocabulaire des
 * deux cotes. Le laisser diverger aurait oblige le lot 3 a traduire entre deux
 * listes, c'est-a-dire a refaire le probleme que cette table devait resoudre —
 * et un bien classe « villa-duplex » serait reste introuvable par un filtre
 * qui cherche « villa ».
 *
 * Une migration plutot qu'un simple correctif du fichier d'import : la valeur
 * technique est la CLE d'idempotence du seeder. Rejouer l'import avec les
 * nouvelles cles aurait ajoute seize lignes a cote des seize anciennes.
 */
return new class extends Migration
{
    /**
     * Ancienne valeur => valeur du site, par famille.
     *
     * @return array<string, array<string, string>>
     */
    protected function correspondances(): array
    {
        return [
            'types_de_bien' => [
                'villa-duplex' => 'villa',
                'appartement-studio' => 'appartement',
                'immeuble-de-rapport' => 'immeuble',
                'terrain-viabilise' => 'terrain',
            ],
            'zones' => [
                'cocody-riviera' => 'cocody',
            ],
            // Le filtre des pieces ne compare pas une chaine mais un NOMBRE :
            // « 1 » signifie « deux pieces au plus », « 3 » de trois a quatre,
            // « 5 » cinq et plus. La valeur est donc la borne basse.
            'tranches_pieces' => [
                '1-2' => '1',
                '3-4' => '3',
                '5-plus' => '5',
            ],
            'tranches_surface' => [
                'moins-100' => 's1',
                '100-250' => 's2',
                '250-500' => 's3',
                'plus-500' => 's4',
            ],
        ];
    }

    public function up(): void
    {
        $this->appliquer(fn (array $paires) => $paires);
    }

    public function down(): void
    {
        $this->appliquer(fn (array $paires) => array_flip($paires));
    }

    /** @param  callable(array<string, string>): array<string, string>  $sens */
    protected function appliquer(callable $sens): void
    {
        foreach ($this->correspondances() as $famille => $paires) {
            foreach ($sens($paires) as $avant => $apres) {
                DB::table('referentiels')
                    ->where('famille', $famille)
                    ->where('valeur', $avant)
                    ->update(['valeur' => $apres]);
            }
        }
    }
};

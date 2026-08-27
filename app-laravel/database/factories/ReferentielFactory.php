<?php

namespace Database\Factories;

use App\Models\Referentiel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Referentiel>
 */
class ReferentielFactory extends Factory
{
    public function definition(): array
    {
        $libelle = fake()->unique()->words(2, true);

        return [
            'famille' => 'zones',
            // La valeur technique derive du libelle pour rester lisible dans
            // les messages d'echec d'un test.
            'valeur' => str($libelle)->slug()->toString(),
            'libelle_fr' => ucfirst($libelle),
            'libelle_en' => ucfirst($libelle),
            'ordre' => 0,
            'visible' => true,
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\EntreeDeMenu;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EntreeDeMenu>
 */
class EntreeDeMenuFactory extends Factory
{
    public function definition(): array
    {
        $libelle = fake()->unique()->words(2, true);

        return [
            'menu' => 'principal',
            'libelle_fr' => ucfirst($libelle),
            'libelle_en' => ucfirst($libelle),
            'cible' => '/'.str($libelle)->slug()->toString(),
            'ordre' => 0,
            'visible' => true,
        ];
    }
}

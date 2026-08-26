<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ChiffreCleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'ordre' => 0,
            'valeur' => fake()->numberBetween(1, 500),
            'intitule_fr' => fake()->words(2, true),
            'intitule_en' => fake()->words(2, true).' (EN)',
        ];
    }
}

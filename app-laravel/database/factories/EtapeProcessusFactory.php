<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class EtapeProcessusFactory extends Factory
{
    public function definition(): array
    {
        return [
            'ordre' => 0,
            'titre_fr' => fake()->words(2, true),
            'titre_en' => fake()->words(2, true).' (EN)',
            'texte_fr' => fake()->paragraph(),
            'texte_en' => fake()->paragraph().' (EN)',
        ];
    }
}

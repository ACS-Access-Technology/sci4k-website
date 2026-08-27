<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class MembreEquipeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'ordre' => 0,
            'visible' => true,
            'nom' => fake()->name(),
            'photo' => null,
            'linkedin' => null,
            'email' => null,
            'etiquette_fr' => fake()->word(),
            'etiquette_en' => fake()->word().' (EN)',
            'fonction_fr' => fake()->words(3, true),
            'fonction_en' => fake()->words(3, true).' (EN)',
            'biographie_fr' => fake()->paragraph(),
            'biographie_en' => fake()->paragraph().' (EN)',
        ];
    }
}

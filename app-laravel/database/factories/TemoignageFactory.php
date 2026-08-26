<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class TemoignageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'ordre' => 0,
            'visible' => true,
            'auteur' => fake()->name(),
            'initiales' => strtoupper(fake()->lexify('??')),
            'note' => 5,
            'citation_fr' => fake()->paragraph(),
            'citation_en' => fake()->paragraph().' (EN)',
            'role_fr' => fake()->words(3, true),
            'role_en' => fake()->words(3, true).' (EN)',
        ];
    }
}

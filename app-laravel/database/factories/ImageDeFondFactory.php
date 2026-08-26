<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ImageDeFondFactory extends Factory
{
    public function definition(): array
    {
        return [
            'ordre' => 0,
            'visible' => true,
            'slug' => Str::slug(fake()->unique()->words(2, true)),
            'fichier' => 'images/'.fake()->unique()->slug(2).'.jpg',
            'texte_alternatif_fr' => fake()->sentence(3),
            'texte_alternatif_en' => fake()->sentence(3).' (EN)',
        ];
    }
}

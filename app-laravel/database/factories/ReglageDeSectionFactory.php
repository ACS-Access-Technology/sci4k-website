<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ReglageDeSectionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'slug' => Str::slug(fake()->unique()->words(2, true)),
            'etiquette_fr' => fake()->words(2, true),
            'etiquette_en' => fake()->words(2, true).' (EN)',
            'titre_fr' => fake()->sentence(4),
            'titre_en' => fake()->sentence(4).' (EN)',
            'chapo_fr' => fake()->paragraph(),
            'chapo_en' => fake()->paragraph().' (EN)',
        ];
    }
}

<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CategorieFactory extends Factory
{
    public function definition(): array
    {
        $nom = $this->faker->unique()->words(2, true);

        return [
            'slug' => Str::slug($nom).'-'.$this->faker->unique()->numberBetween(1, 99999),
            'nom_fr' => $nom,
            'nom_en' => $nom.' (EN)',
            'ordre' => 0,
        ];
    }
}

<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class RubriqueFaqFactory extends Factory
{
    public function definition(): array
    {
        $nom = fake()->unique()->words(2, true);

        return [
            'slug' => Str::slug($nom),
            'ordre' => 0,
            'visible' => true,
            'nom_fr' => ucfirst($nom),
            'nom_en' => ucfirst($nom).' (EN)',
        ];
    }
}

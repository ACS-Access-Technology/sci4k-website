<?php

namespace Database\Factories;

use App\Models\Categorie;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ServiceFactory extends Factory
{
    public function definition(): array
    {
        $nom = fake()->unique()->words(2, true);

        return [
            'slug' => Str::slug($nom).'-'.fake()->unique()->numberBetween(1, 99999),
            'categorie_id' => Categorie::factory(),
            'ordre' => 0,
            'visible' => true,
            'nom_fr' => $nom,
            'nom_en' => $nom.' (EN)',
            'accroche_fr' => fake()->sentence(),
            'accroche_en' => fake()->sentence().' (EN)',
            'description_fr' => fake()->paragraph(),
            'description_en' => fake()->paragraph().' (EN)',
        ];
    }
}

<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class EncartFactory extends Factory
{
    public function definition(): array
    {
        return [
            'slug' => Str::slug(fake()->unique()->words(2, true)),
            'ordre' => 0,
            'visible' => true,
            'etiquette_fr' => fake()->words(2, true),
            'etiquette_en' => fake()->words(2, true).' (EN)',
            'titre_fr' => fake()->sentence(4),
            'titre_en' => fake()->sentence(4).' (EN)',
            'texte_fr' => fake()->paragraph(),
            'texte_en' => fake()->paragraph().' (EN)',
            'libelle_bouton_fr' => 'Nous contacter',
            'libelle_bouton_en' => 'Contact us',
            'cible_bouton' => '/contact.html',
            'image_source' => null,
        ];
    }
}

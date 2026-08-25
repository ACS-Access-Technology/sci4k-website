<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ArticleFactory extends Factory
{
    public function definition(): array
    {
        $titre = $this->faker->sentence(6);

        return [
            'slug' => $this->faker->unique()->slug(),
            'date_publication' => $this->faker->date(),
            'statut' => 'publie',
            'titre_fr' => $titre,
            'titre_en' => $titre.' (EN)',
            'resume_fr' => $this->faker->paragraph(),
            'resume_en' => $this->faker->paragraph(),
            'contenu_fr' => $this->faker->paragraphs(4, true),
            'contenu_en' => $this->faker->paragraphs(4, true),
        ];
    }
}

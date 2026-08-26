<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PartenaireFactory extends Factory
{
    public function definition(): array
    {
        return [
            'ordre' => 0,
            'visible' => true,
            'nom' => fake()->company(),
            'logo' => 'images/partners/'.fake()->unique()->slug(2).'.png',
            'site' => fake()->url(),
        ];
    }
}

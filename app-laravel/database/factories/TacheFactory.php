<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TacheFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'texte' => fake()->sentence(6),
            'echeance' => null,
            'terminee' => false,
            'ordre' => 0,
        ];
    }
}

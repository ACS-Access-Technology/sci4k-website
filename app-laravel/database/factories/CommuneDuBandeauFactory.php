<?php

namespace Database\Factories;

use App\Models\CommuneDuBandeau;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CommuneDuBandeau>
 */
class CommuneDuBandeauFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nom' => fake()->unique()->city(),
            'ordre' => 0,
            'visible' => true,
        ];
    }
}

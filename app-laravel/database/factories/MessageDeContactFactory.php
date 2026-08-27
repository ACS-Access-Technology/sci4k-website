<?php

namespace Database\Factories;

use App\Models\MessageDeContact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MessageDeContact>
 */
class MessageDeContactFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nom' => fake()->name(),
            'email' => fake()->safeEmail(),
            'telephone' => '+225 07 '.fake()->numerify('## ## ## ##'),
            'sujet' => fake()->sentence(4),
            'message' => fake()->paragraph(),
            'statut' => MessageDeContact::NOUVEAU,
        ];
    }
}

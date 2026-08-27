<?php

namespace Database\Factories;

use App\Models\DemandeDeVisite;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DemandeDeVisite>
 */
class DemandeDeVisiteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nom' => fake()->name(),
            'telephone' => '+225 07 '.fake()->numerify('## ## ## ##'),
            'email' => fake()->safeEmail(),
            'creneau_souhaite' => now()->addDays(3)->setTime(10, 0),
            'statut' => DemandeDeVisite::A_CONFIRMER,
        ];
    }
}

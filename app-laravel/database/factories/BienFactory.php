<?php

namespace Database\Factories;

use App\Models\Bien;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Bien>
 */
class BienFactory extends Factory
{
    public function definition(): array
    {
        $titre = fake()->unique()->words(3, true);

        return [
            'slug' => Str::slug($titre),
            'titre_fr' => ucfirst($titre),
            'titre_en' => ucfirst($titre),
            'type' => 'villa',
            'offre' => Bien::VENTE,
            'zone' => 'cocody',
            'surface_habitable' => 150,
            'nombre_pieces' => 4,
            'statut' => Bien::PUBLIE,
            'ordre' => 0,
        ];
    }

    public function terrain(): static
    {
        // Un terrain n'a ni surface habitable ni pieces : c'est le cas limite
        // que la modelisation doit tenir.
        return $this->state(fn () => [
            'type' => 'terrain',
            'surface_habitable' => null,
            'surface_terrain' => 800,
            'nombre_pieces' => null,
        ]);
    }

    public function brouillon(): static
    {
        return $this->state(fn () => ['statut' => Bien::BROUILLON]);
    }
}

<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\Commentaire;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Commentaire> */
class CommentaireFactory extends Factory
{
    protected $model = Commentaire::class;

    public function definition(): array
    {
        return [
            'article_id' => Article::factory(),
            'parent_id' => null,
            'auteur' => $this->faker->name(),
            'email' => $this->faker->safeEmail(),
            // Ni lien ni majuscules : la fabrique doit produire un commentaire
            // que le filtre laisse passer, sans quoi chaque test partirait
            // d'un cas de courrier indesirable.
            'message' => $this->faker->sentence(12),
            'statut' => Commentaire::PUBLIE,
            'motif_de_mise_en_attente' => null,
            'adresse_ip' => $this->faker->ipv4(),
        ];
    }

    public function enAttente(): static
    {
        return $this->state(fn () => [
            'statut' => Commentaire::EN_ATTENTE,
            'motif_de_mise_en_attente' => 'Contient un lien',
        ]);
    }
}

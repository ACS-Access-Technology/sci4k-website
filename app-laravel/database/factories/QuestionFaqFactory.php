<?php

namespace Database\Factories;

use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuestionFaqFactory extends Factory
{
    public function definition(): array
    {
        return [
            'service_id' => Service::factory(),
            'ordre' => 0,
            'visible' => true,
            'question_fr' => fake()->sentence().' ?',
            'question_en' => fake()->sentence().' ? (EN)',
            'reponse_fr' => fake()->paragraph(),
            'reponse_en' => fake()->paragraph().' (EN)',
        ];
    }
}

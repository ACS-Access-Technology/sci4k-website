<?php

namespace Database\Factories;

use App\Models\RubriqueFaq;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuestionFaqFactory extends Factory
{
    public function definition(): array
    {
        return [
            'rubrique_id' => RubriqueFaq::factory(),
            'ordre' => 0,
            'visible' => true,
            'question_fr' => fake()->sentence().' ?',
            'question_en' => fake()->sentence().' ? (EN)',
            'reponse_fr' => fake()->paragraph(),
            'reponse_en' => fake()->paragraph().' (EN)',
        ];
    }
}

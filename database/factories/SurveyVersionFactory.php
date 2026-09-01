<?php

namespace Database\Factories;

use App\Models\Questionnaire;
use Illuminate\Database\Eloquent\Factories\Factory;

class SurveyVersionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'questionnaire_id' => Questionnaire::factory(),
            'version' => '1.0',
            'active' => true,
            'published_at' => now(),
        ];
    }
}
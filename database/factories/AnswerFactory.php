<?php

namespace Database\Factories;

use App\Models\Answer;
use App\Models\Question;
use App\Models\SurveyResponse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Answer>
 */
class AnswerFactory extends Factory
{
    protected $model = Answer::class;

    public function definition(): array
    {
        return [
            'survey_response_id' => SurveyResponse::factory(),
            'question_id' => Question::factory(),
            'text_value' => null,
            'number_value' => null,
            'date_value' => null,
            'option_id' => null,
            'household_member_id' => null,
        ];
    }
}

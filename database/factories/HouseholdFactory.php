<?php

namespace Database\Factories;

use App\Models\Household;
use App\Models\SurveyResponse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Household>
 */
class HouseholdFactory extends Factory
{
    protected $model = Household::class;

    public function definition(): array
    {
        return [
            'survey_response_id' => SurveyResponse::factory(),
        ];
    }
}

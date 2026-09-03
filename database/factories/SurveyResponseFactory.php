<?php

namespace Database\Factories;

use App\Enums\SurveyResponseStatus;
use App\Models\Questionnaire;
use App\Models\SurveyResponse;
use App\Models\SurveyVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SurveyResponse>
 */
class SurveyResponseFactory extends Factory
{
    protected $model = SurveyResponse::class;

    public function definition(): array
    {
        return [
            'questionnaire_id' => Questionnaire::factory(),
            'survey_version_id' => SurveyVersion::factory(),
            'community_id' => null,
            'created_by' => User::factory(),
            'status' => SurveyResponseStatus::DRAFT,
            'started_at' => null,
            'completed_at' => null,
        ];
    }
}

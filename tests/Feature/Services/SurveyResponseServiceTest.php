<?php

use App\Enums\SurveyResponseStatus;
use App\Models\Community;
use App\Models\Questionnaire;
use App\Models\SurveyResponse;
use App\Models\SurveyVersion;
use App\Models\User;
use App\Services\SurveyResponseService;

it('creates a survey response in draft status', function () {
    $questionnaire = Questionnaire::factory()->create();
    $surveyVersion = SurveyVersion::factory()->create([
        'questionnaire_id' => $questionnaire->id,
    ]);
    $user = User::factory()->create();
    $community = Community::factory()->create();

    $service = app(SurveyResponseService::class);

    $response = $service->createDraft(
        $questionnaire->id,
        $surveyVersion->id,
        $user->id,
        $community->id,
    );

    expect($response)
        ->toBeInstanceOf(SurveyResponse::class)
        ->and($response->questionnaire_id)->toBe($questionnaire->id)
        ->and($response->survey_version_id)->toBe($surveyVersion->id)
        ->and($response->created_by)->toBe($user->id)
        ->and($response->community_id)->toBe($community->id)
        ->and($response->status)->toBe(SurveyResponseStatus::DRAFT);

    $this->assertDatabaseHas('survey_responses', [
        'id' => $response->id,
        'questionnaire_id' => $questionnaire->id,
        'survey_version_id' => $surveyVersion->id,
        'community_id' => $community->id,
        'created_by' => $user->id,
        'status' => SurveyResponseStatus::DRAFT->value,
    ]);
});

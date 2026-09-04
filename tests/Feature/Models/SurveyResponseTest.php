<?php

use App\Models\Questionnaire;
use App\Models\SurveyResponse;
use App\Models\User;
use App\Models\SurveyVersion;
use App\Models\Community;
use App\Models\Answer;
use App\Models\Question;
use App\Enums\SurveyResponseStatus;

it('belongs to a questionnaire', function () {
    $questionnaire = Questionnaire::factory()->create();

    $response = SurveyResponse::factory()->create([
        'questionnaire_id' => $questionnaire->id,
    ]);

    expect($response->questionnaire)
        ->toBeInstanceOf(Questionnaire::class)
        ->and($response->questionnaire->id)
        ->toBe($questionnaire->id);
});

//----------------------------------------------------

it('belongs to a survey version', function () {
    $questionnaire = Questionnaire::factory()->create();

    $version = SurveyVersion::factory()->create([
        'questionnaire_id' => $questionnaire->id,
    ]);

    $response = SurveyResponse::factory()->create([
        'questionnaire_id' => $questionnaire->id,
        'survey_version_id' => $version->id,
    ]);

    expect($response->surveyVersion)
        ->toBeInstanceOf(SurveyVersion::class)
        ->and($response->surveyVersion->id)
        ->toBe($version->id);
});

//--------------------------------------------------

it('belongs to a community', function () {
    $community = Community::factory()->create();

    $response = SurveyResponse::factory()->create([
        'community_id' => $community->id,
    ]);

    expect($response->community)
        ->toBeInstanceOf(Community::class)
        ->and($response->community->id)
        ->toBe($community->id);
});

//--------------------------------------------------
it('belongs to a creator user', function () {
    $user = User::factory()->create();

    $response = SurveyResponse::factory()->create([
        'created_by' => $user->id,
    ]);

    expect($response->creator)
        ->toBeInstanceOf(User::class)
        ->and($response->creator->id)
        ->toBe($user->id);
});

//----------------------------------------------------

it('has many answers', function () {
    $response = SurveyResponse::factory()->create();

    $answer = Answer::create([
        'survey_response_id' => $response->id,
        'question_id' => Question::factory()->create()->id,
    ]);

    expect($response->answers)
        ->toHaveCount(1)
        ->and($response->answers->first())
        ->toBeInstanceOf(Answer::class)
        ->and($response->answers->first()->id)
        ->toBe($answer->id);
});

//----------------------------------------------------------

it('casts status to SurveyResponseStatus enum', function () {
    $response = SurveyResponse::factory()->create([
        'status' => SurveyResponseStatus::IN_PROGRESS,
    ]);

    $response->refresh();

    expect($response->status)
        ->toBe(SurveyResponseStatus::IN_PROGRESS);
});

//---------------------------------------------------------

it('casts started_at and completed_at as dates', function () {
    $startedAt = now()->subMinutes(10);
    $completedAt = now();

    $response = SurveyResponse::factory()->create([
        'started_at' => $startedAt,
        'completed_at' => $completedAt,
    ]);

    $response->refresh();

    expect($response->started_at)
        ->toBeInstanceOf(\Illuminate\Support\Carbon::class)
        ->and($response->completed_at)
        ->toBeInstanceOf(\Illuminate\Support\Carbon::class);
});

//-------------------------------------------------------

it('allows started_at and completed_at to be null', function () {
    $response = SurveyResponse::factory()->create([
        'started_at' => null,
        'completed_at' => null,
    ]);

    expect($response->started_at)
        ->toBeNull()
        ->and($response->completed_at)
        ->toBeNull();
});

//-----------------------------------------------------

it('persists status enum correctly', function () {
    $response = SurveyResponse::factory()->create([
        'status' => SurveyResponseStatus::COMPLETED,
    ]);

    expect($response->status)
        ->toBe(SurveyResponseStatus::COMPLETED);

    $this->assertDatabaseHas('survey_responses', [
        'id' => $response->id,
        'status' => SurveyResponseStatus::COMPLETED->value,
    ]);
});
//-----------------------------------------------------

it('deletes answers when survey response is deleted', function () {
    $response = SurveyResponse::factory()->create();

    $question = Question::factory()->create();

    $answer = Answer::create([
        'survey_response_id' => $response->id,
        'question_id' => $question->id,
    ]);

    $answerId = $answer->id;

    $response->delete();

    expect(Answer::find($answerId))->toBeNull();
});

//------------------------------------------------------

it('stores and retrieves the reference', function () {
    $reference = 'ENC-20260904-0001';

    $response = SurveyResponse::factory()->create([
        'reference' => $reference,
    ]);

    $response->refresh();

    expect($response->reference)
        ->toBe($reference);
});
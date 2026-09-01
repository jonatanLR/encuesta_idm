<?php

use App\Enums\SurveyResponseStatus;
use App\Models\Community;
use App\Models\Questionnaire;
use App\Models\SurveyResponse;
use App\Models\SurveyVersion;
use App\Models\User;
use App\Services\SurveyResponseService;
use App\Models\Question;
use App\Models\QuestionType;
use App\Models\Section;

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
        ->and($response->community_id)->toBe($community->id)
        ->and($response->created_by)->toBe($user->id)
        ->and($response->status)->toBe(SurveyResponseStatus::DRAFT)
        ->and($response->reference)->toBe(
            'ENC-' . now()->format('Ymd') . '-' . str_pad(
                (string) $response->id,
                4,
                '0',
                STR_PAD_LEFT
            )
        );

    $this->assertDatabaseHas('survey_responses', [
        'id' => $response->id,
        'reference' => $response->reference,
        'questionnaire_id' => $questionnaire->id,
        'survey_version_id' => $surveyVersion->id,
        'community_id' => $community->id,
        'created_by' => $user->id,
        'status' => SurveyResponseStatus::DRAFT->value,
    ]);
});

it('starts a draft survey response', function () {
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

expect($response->status)
    ->toBe(SurveyResponseStatus::DRAFT);

$startedResponse = $service->start($response);

expect($startedResponse->status)
    ->toBe(SurveyResponseStatus::IN_PROGRESS)
    ->and($startedResponse->started_at)
    ->not->toBeNull()
    ->and($startedResponse->completed_at)
    ->toBeNull();

$this->assertDatabaseHas('survey_responses', [
    'id' => $response->id,
    'status' => SurveyResponseStatus::IN_PROGRESS->value,
]);

});

//-------------------------------------------------------------------

it('does not restart an already started survey response', function () {
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

$startedResponse = $service->start($response);

$originalStartedAt = $startedResponse->started_at;

$startedAgain = $service->start($startedResponse);

expect($startedAgain->status)
    ->toBe(SurveyResponseStatus::IN_PROGRESS)
    ->and($startedAgain->started_at->equalTo($originalStartedAt))
    ->toBeTrue();

expect($startedAgain->id)
    ->toBe($response->id);

});

//------------------------------------------------------------------
it('does not allow a cancelled survey response to be started', function () {
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

$cancelledResponse = $service->cancel($response);

expect($cancelledResponse->status)
    ->toBe(SurveyResponseStatus::CANCELLED);

expect(fn () => $service->start($cancelledResponse))
    ->toThrow(InvalidArgumentException::class);

expect($cancelledResponse->refresh()->status)
    ->toBe(SurveyResponseStatus::CANCELLED);


});

//----------------------------------------------------------
it('cancels a draft survey response', function () {
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

$cancelledResponse = $service->cancel($response);

expect($cancelledResponse->status)
    ->toBe(SurveyResponseStatus::CANCELLED);

expect($cancelledResponse->completed_at)
    ->toBeNull();

$this->assertDatabaseHas('survey_responses', [
    'id' => $response->id,
    'status' => SurveyResponseStatus::CANCELLED->value,
]);


});

//------------------------------------------------------------------
it('cancels an in-progress survey response', function () {
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

$response = $service->start($response);

expect($response->status)
    ->toBe(SurveyResponseStatus::IN_PROGRESS)
    ->and($response->started_at)
    ->not->toBeNull();

$cancelledResponse = $service->cancel($response);

expect($cancelledResponse->status)
    ->toBe(SurveyResponseStatus::CANCELLED)
    ->and($cancelledResponse->started_at)
    ->not->toBeNull()
    ->and($cancelledResponse->completed_at)
    ->toBeNull();

$this->assertDatabaseHas('survey_responses', [
    'id' => $response->id,
    'status' => SurveyResponseStatus::CANCELLED->value,
]);


});

//--------------------------------------------
it('does not allow a completed survey response to be cancelled', function () {
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

$response->update([
    'status' => SurveyResponseStatus::COMPLETED,
    'completed_at' => now(),
]);

expect(fn () => $service->cancel($response))
    ->toThrow(InvalidArgumentException::class);

expect($response->refresh()->status)
    ->toBe(SurveyResponseStatus::COMPLETED);

expect($response->completed_at)
    ->not->toBeNull();


});

//-------------------------------------------------------------

it('does not complete an in-progress survey response with validation errors', function () {
$questionnaire = Questionnaire::factory()->create();


$surveyVersion = SurveyVersion::factory()->create([
    'questionnaire_id' => $questionnaire->id,
]);

$section = Section::factory()->create([
    'survey_version_id' => $surveyVersion->id,
]);

$questionType = QuestionType::factory()->create([
    'code' => 'text',
    'name' => 'Texto',
]);

Question::factory()->create([
    'section_id' => $section->id,
    'question_type_id' => $questionType->id,
    'required' => true,
    'active' => true,
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

$response = $service->start($response);

expect($response->status)
    ->toBe(SurveyResponseStatus::IN_PROGRESS);

expect(fn () => $service->complete($response))
    ->toThrow(\App\Exceptions\SurveyValidationException::class);

expect($response->refresh()->status)
    ->toBe(SurveyResponseStatus::IN_PROGRESS);

expect($response->completed_at)
    ->toBeNull();


});

//------------------------------------------------------------------------
it('completes an in-progress survey response when all required questions are answered', function () {
$questionnaire = Questionnaire::factory()->create();


$surveyVersion = SurveyVersion::factory()->create([
    'questionnaire_id' => $questionnaire->id,
]);

$section = Section::factory()->create([
    'survey_version_id' => $surveyVersion->id,
]);

$questionType = QuestionType::factory()->create([
    'code' => 'text',
    'name' => 'Texto',
]);

$question = Question::factory()->create([
    'section_id' => $section->id,
    'question_type_id' => $questionType->id,
    'required' => true,
    'active' => true,
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

$response = $service->start($response);

$service->saveAnswer(
    $response,
    $question,
    'Respuesta de prueba',
);

$completedResponse = $service->complete($response);

expect($completedResponse->status)
    ->toBe(SurveyResponseStatus::COMPLETED);

expect($completedResponse->completed_at)
    ->not->toBeNull();

$this->assertDatabaseHas('answers', [
    'survey_response_id' => $response->id,
    'question_id' => $question->id,
    'text_value' => 'Respuesta de prueba',
]);

$this->assertDatabaseHas('survey_responses', [
    'id' => $response->id,
    'status' => SurveyResponseStatus::COMPLETED->value,
]);


});

//-------------------------------------------------------------

it('does not complete an already completed survey response again', function () {
$questionnaire = Questionnaire::factory()->create();


$surveyVersion = SurveyVersion::factory()->create([
    'questionnaire_id' => $questionnaire->id,
]);

$section = Section::factory()->create([
    'survey_version_id' => $surveyVersion->id,
]);

$questionType = QuestionType::factory()->create([
    'code' => 'text',
    'name' => 'Texto',
]);

$question = Question::factory()->create([
    'section_id' => $section->id,
    'question_type_id' => $questionType->id,
    'required' => true,
    'active' => true,
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

$response = $service->start($response);

$service->saveAnswer(
    $response,
    $question,
    'Respuesta de prueba',
);

$completedResponse = $service->complete($response);

$completedAt = $completedResponse->completed_at;

$secondResult = $service->complete($completedResponse);

expect($secondResult->status)
    ->toBe(SurveyResponseStatus::COMPLETED);

expect($secondResult->completed_at)
    ->toEqual($completedAt);

expect($secondResult->id)
    ->toBe($completedResponse->id);


});

//=======================================================================
it('does not allow a cancelled survey response to be completed', function () {
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

$response = $service->start($response);
$response = $service->cancel($response);

expect($response->status)
    ->toBe(SurveyResponseStatus::CANCELLED);

expect(fn () => $service->complete($response))
    ->toThrow(\InvalidArgumentException::class);

expect($response->refresh()->status)
    ->toBe(SurveyResponseStatus::CANCELLED);

expect($response->completed_at)
    ->toBeNull();


});






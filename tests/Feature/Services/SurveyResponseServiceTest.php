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
use App\Models\QuestionOption;
use App\Models\Section;
use App\Models\Answer;

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

    expect(fn() => $service->start($cancelledResponse))
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

    expect(fn() => $service->cancel($response))
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

    expect(fn() => $service->complete($response))
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

    expect(fn() => $service->complete($response))
        ->toThrow(\InvalidArgumentException::class);

    expect($response->refresh()->status)
        ->toBe(SurveyResponseStatus::CANCELLED);

    expect($response->completed_at)
        ->toBeNull();
});

//----------------------------------------------------------------------------
it('completes a survey response when an optional question is unanswered', function () {
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
        'required' => false,
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

    $completedResponse = $service->complete($response);

    expect($completedResponse->status)
        ->toBe(SurveyResponseStatus::COMPLETED);

    expect($completedResponse->completed_at)
        ->not->toBeNull();
});

//----------------------------------------------------------

it('completes a survey response with a valid single choice answer', function () {
    $questionnaire = Questionnaire::factory()->create();

    $surveyVersion = SurveyVersion::factory()->create([
        'questionnaire_id' => $questionnaire->id,
    ]);

    $section = Section::factory()->create([
        'survey_version_id' => $surveyVersion->id,
    ]);

    $questionType = QuestionType::factory()->create([
        'code' => 'single_choice',
        'name' => 'Selección simple',
    ]);

    $question = Question::factory()->create([
        'section_id' => $section->id,
        'question_type_id' => $questionType->id,
        'required' => true,
        'active' => true,
    ]);

    $option = $question->options()->create([
        'value' => 'opcion_1',
        'label' => 'Opción 1',
        'sort_order' => 1,
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
        $option->id,
    );

    $completedResponse = $service->complete($response);

    expect($completedResponse->status)
        ->toBe(SurveyResponseStatus::COMPLETED);

    expect($completedResponse->completed_at)
        ->not->toBeNull();

    $this->assertDatabaseHas('answers', [
        'survey_response_id' => $response->id,
        'question_id' => $question->id,
        'option_id' => $option->id,
    ]);
});

//--------------------------------------------------------------------------------

it('does not allow a single choice answer from another question', function () {
    $questionnaire = Questionnaire::factory()->create();

    $surveyVersion = SurveyVersion::factory()->create([
        'questionnaire_id' => $questionnaire->id,
    ]);

    $section = Section::factory()->create([
        'survey_version_id' => $surveyVersion->id,
    ]);

    $questionType = QuestionType::factory()->create([
        'code' => 'single_choice',
        'name' => 'Selección simple',
    ]);

    $question = Question::factory()->create([
        'section_id' => $section->id,
        'question_type_id' => $questionType->id,
        'required' => true,
        'active' => true,
    ]);

    $otherQuestion = Question::factory()->create([
        'section_id' => $section->id,
        'question_type_id' => $questionType->id,
        'required' => false,
        'active' => true,
    ]);

    $otherOption = $otherQuestion->options()->create([
        'value' => 'otra_opcion',
        'label' => 'Opción de otra pregunta',
        'sort_order' => 1,
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

    expect(fn() => $service->saveAnswer(
        $response,
        $question,
        $otherOption->id,
    ))->toThrow(
        InvalidArgumentException::class,
        'La opción seleccionada no pertenece a la pregunta.'
    );

    $this->assertDatabaseMissing('answers', [
        'survey_response_id' => $response->id,
        'question_id' => $question->id,
        'option_id' => $otherOption->id,
    ]);
});

//---------------------------------------------------------------
it('completes a survey response with valid multiple choice answers', function () {
    $questionnaire = Questionnaire::factory()->create();

    $surveyVersion = SurveyVersion::factory()->create([
        'questionnaire_id' => $questionnaire->id,
    ]);

    $section = Section::factory()->create([
        'survey_version_id' => $surveyVersion->id,
    ]);

    $questionType = QuestionType::factory()->create([
        'code' => 'multiple_choice',
        'name' => 'Selección múltiple',
    ]);

    $question = Question::factory()->create([
        'section_id' => $section->id,
        'question_type_id' => $questionType->id,
        'required' => true,
        'active' => true,
    ]);

    $option1 = $question->options()->create([
        'value' => 'opcion_1',
        'label' => 'Opción 1',
        'sort_order' => 1,
        'active' => true,
    ]);

    $option2 = $question->options()->create([
        'value' => 'opcion_2',
        'label' => 'Opción 2',
        'sort_order' => 2,
        'active' => true,
    ]);

    $option3 = $question->options()->create([
        'value' => 'opcion_3',
        'label' => 'Opción 3',
        'sort_order' => 3,
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
        [$option1->id, $option3->id],
    );

    $completedResponse = $service->complete($response);

    expect($completedResponse->status)
        ->toBe(SurveyResponseStatus::COMPLETED);

    expect($completedResponse->completed_at)
        ->not->toBeNull();

    $this->assertDatabaseHas('answers', [
        'survey_response_id' => $response->id,
        'question_id' => $question->id,
    ]);

    $answer = $response->answers()
        ->where('question_id', $question->id)
        ->first();

    expect($answer)->not->toBeNull();

    $this->assertDatabaseHas('answer_options', [
        'answer_id' => $answer->id,
        'question_option_id' => $option1->id,
    ]);

    $this->assertDatabaseHas('answer_options', [
        'answer_id' => $answer->id,
        'question_option_id' => $option3->id,
    ]);

    $this->assertDatabaseMissing('answer_options', [
        'answer_id' => $answer->id,
        'question_option_id' => $option2->id,
    ]);
});

//---------------------------------------------------------------------

it('does not allow multiple choice answers containing an option from another question', function () {
    $questionnaire = Questionnaire::factory()->create();

    $surveyVersion = SurveyVersion::factory()->create([
        'questionnaire_id' => $questionnaire->id,
    ]);

    $section = Section::factory()->create([
        'survey_version_id' => $surveyVersion->id,
    ]);

    $questionType = QuestionType::factory()->create([
        'code' => 'multiple_choice',
        'name' => 'Selección múltiple',
    ]);

    $question = Question::factory()->create([
        'section_id' => $section->id,
        'question_type_id' => $questionType->id,
        'required' => true,
        'active' => true,
    ]);

    $otherQuestion = Question::factory()->create([
        'section_id' => $section->id,
        'question_type_id' => $questionType->id,
        'required' => false,
        'active' => true,
    ]);

    $option1 = $question->options()->create([
        'value' => 'opcion_1',
        'label' => 'Opción 1',
        'sort_order' => 1,
        'active' => true,
    ]);

    $option2 = $question->options()->create([
        'value' => 'opcion_2',
        'label' => 'Opción 2',
        'sort_order' => 2,
        'active' => true,
    ]);

    $invalidOption = $otherQuestion->options()->create([
        'value' => 'otra_opcion',
        'label' => 'Opción de otra pregunta',
        'sort_order' => 1,
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

    expect(fn() => $service->saveAnswer(
        $response,
        $question,
        [$option1->id, $invalidOption->id],
    ))->toThrow(
        InvalidArgumentException::class,
        'Una o más opciones no pertenecen a la pregunta.'
    );

    $this->assertDatabaseMissing('answers', [
        'survey_response_id' => $response->id,
        'question_id' => $question->id,
    ]);

    $this->assertDatabaseMissing('answer_options', [
        'question_option_id' => $option1->id,
    ]);

    $this->assertDatabaseMissing('answer_options', [
        'question_option_id' => $invalidOption->id,
    ]);
});

//-----------------------------------------------------------------------

it('does not complete a survey response when a required multiple choice question has no selected options', function () {
    $questionnaire = Questionnaire::factory()->create();

    $surveyVersion = SurveyVersion::factory()->create([
        'questionnaire_id' => $questionnaire->id,
    ]);

    $section = Section::factory()->create([
        'survey_version_id' => $surveyVersion->id,
    ]);

    $questionType = QuestionType::factory()->create([
        'code' => 'multiple_choice',
        'name' => 'Selección múltiple',
    ]);

    $question = Question::factory()->create([
        'section_id' => $section->id,
        'question_type_id' => $questionType->id,
        'required' => true,
        'active' => true,
    ]);

    $question->options()->create([
        'value' => 'opcion_1',
        'label' => 'Opción 1',
        'sort_order' => 1,
        'active' => true,
    ]);

    $question->options()->create([
        'value' => 'opcion_2',
        'label' => 'Opción 2',
        'sort_order' => 2,
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
        [],
    );

    expect(fn() => $service->complete($response))
        ->toThrow(\App\Exceptions\SurveyValidationException::class);

    expect($response->refresh()->status)
        ->toBe(SurveyResponseStatus::IN_PROGRESS);

    expect($response->completed_at)
        ->toBeNull();
});

//----------------------------------------------------------------

it('does not require a conditional question when its condition is not met', function () {
    $questionnaire = Questionnaire::factory()->create();

    $surveyVersion = SurveyVersion::factory()->create([
        'questionnaire_id' => $questionnaire->id,
    ]);

    $section = Section::factory()->create([
        'survey_version_id' => $surveyVersion->id,
    ]);

    $singleChoiceType = QuestionType::factory()->create([
        'code' => 'single_choice',
        'name' => 'Selección simple',
    ]);

    $textType = QuestionType::factory()->create([
        'code' => 'text',
        'name' => 'Texto',
    ]);

    $mainQuestion = Question::factory()->create([
        'section_id' => $section->id,
        'question_type_id' => $singleChoiceType->id,
        'required' => true,
        'active' => true,
    ]);

    $conditionalQuestion = Question::factory()->create([
        'section_id' => $section->id,
        'question_type_id' => $textType->id,
        'required' => true,
        'active' => true,
    ]);

    $optionA = $mainQuestion->options()->create([
        'value' => 'opcion_a',
        'label' => 'Opción A',
        'sort_order' => 1,
        'active' => true,
    ]);

    $optionOtros = $mainQuestion->options()->create([
        'value' => 'otros',
        'label' => 'Otros',
        'sort_order' => 2,
        'active' => true,
    ]);

    $conditionalQuestion->conditions()->create([
        'depends_on_question_id' => $mainQuestion->id,
        'depends_on_option_id' => $optionOtros->id,
        'operator' => 'equals',
        'expected_value' => 'otros',
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
        $mainQuestion,
        $optionA->id,
    );

    $completedResponse = $service->complete($response);

    expect($completedResponse->status)
        ->toBe(SurveyResponseStatus::COMPLETED);

    expect($completedResponse->completed_at)
        ->not->toBeNull();
});

//-----------------------------------------------------------------

it('requires a conditional question when its condition is met', function () {
    $questionnaire = Questionnaire::factory()->create();

    $surveyVersion = SurveyVersion::factory()->create([
        'questionnaire_id' => $questionnaire->id,
    ]);

    $section = Section::factory()->create([
        'survey_version_id' => $surveyVersion->id,
    ]);

    $singleChoiceType = QuestionType::factory()->create([
        'code' => 'single_choice',
        'name' => 'Selección simple',
    ]);

    $textType = QuestionType::factory()->create([
        'code' => 'text',
        'name' => 'Texto',
    ]);

    $mainQuestion = Question::factory()->create([
        'section_id' => $section->id,
        'question_type_id' => $singleChoiceType->id,
        'required' => true,
        'active' => true,
    ]);

    $conditionalQuestion = Question::factory()->create([
        'section_id' => $section->id,
        'question_type_id' => $textType->id,
        'required' => true,
        'active' => true,
    ]);

    $optionA = $mainQuestion->options()->create([
        'value' => 'opcion_a',
        'label' => 'Opción A',
        'sort_order' => 1,
        'active' => true,
    ]);

    $optionOtros = $mainQuestion->options()->create([
        'value' => 'otros',
        'label' => 'Otros',
        'sort_order' => 2,
        'active' => true,
    ]);

    $conditionalQuestion->conditions()->create([
        'depends_on_question_id' => $mainQuestion->id,
        'depends_on_option_id' => $optionOtros->id,
        'operator' => 'equals',
        'expected_value' => 'otros',
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
        $mainQuestion,
        $optionOtros->id,
    );

    expect(fn() => $service->complete($response))
        ->toThrow(\App\Exceptions\SurveyValidationException::class);

    expect($response->refresh()->status)
        ->toBe(SurveyResponseStatus::IN_PROGRESS);
});

//-----------------------------------------------------------------
it('completes a survey response when a conditional required question is answered', function () {
    $questionnaire = Questionnaire::factory()->create();

    $surveyVersion = SurveyVersion::factory()->create([
        'questionnaire_id' => $questionnaire->id,
    ]);

    $section = Section::factory()->create([
        'survey_version_id' => $surveyVersion->id,
    ]);

    $singleChoiceType = QuestionType::factory()->create([
        'code' => 'single_choice',
        'name' => 'Selección simple',
    ]);

    $textType = QuestionType::factory()->create([
        'code' => 'text',
        'name' => 'Texto',
    ]);

    $mainQuestion = Question::factory()->create([
        'section_id' => $section->id,
        'question_type_id' => $singleChoiceType->id,
        'required' => true,
        'active' => true,
    ]);

    $conditionalQuestion = Question::factory()->create([
        'section_id' => $section->id,
        'question_type_id' => $textType->id,
        'required' => true,
        'active' => true,
    ]);

    $optionA = $mainQuestion->options()->create([
        'value' => 'opcion_a',
        'label' => 'Opción A',
        'sort_order' => 1,
        'active' => true,
    ]);

    $optionOtros = $mainQuestion->options()->create([
        'value' => 'otros',
        'label' => 'Otros',
        'sort_order' => 2,
        'active' => true,
    ]);

    $conditionalQuestion->conditions()->create([
        'depends_on_question_id' => $mainQuestion->id,
        'depends_on_option_id' => $optionOtros->id,
        'operator' => 'equals',
        'expected_value' => 'otros',
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
        $mainQuestion,
        $optionOtros->id,
    );

    $service->saveAnswer(
        $response,
        $conditionalQuestion,
        'Mi respuesta',
    );

    $completedResponse = $service->complete($response);

    expect($completedResponse->status)
        ->toBe(SurveyResponseStatus::COMPLETED);

    expect($completedResponse->completed_at)
        ->not->toBeNull();

    $this->assertDatabaseHas('answers', [
        'survey_response_id' => $response->id,
        'question_id' => $mainQuestion->id,
        'option_id' => $optionOtros->id,
    ]);

    $this->assertDatabaseHas('answers', [
        'survey_response_id' => $response->id,
        'question_id' => $conditionalQuestion->id,
        'text_value' => 'Mi respuesta',
    ]);
});
//---------------------------------------------------
it('does not complete a survey when a required conditional question is unanswered', function () {
    $questionnaire = Questionnaire::factory()->create();

    $surveyVersion = SurveyVersion::factory()->create([
        'questionnaire_id' => $questionnaire->id,
    ]);

    $section = Section::factory()->create([
        'survey_version_id' => $surveyVersion->id,
    ]);

    $singleChoiceType = QuestionType::factory()->create([
        'code' => 'single_choice',
        'name' => 'Selección simple',
    ]);

    $textType = QuestionType::factory()->create([
        'code' => 'text',
        'name' => 'Texto',
    ]);

    $mainQuestion = Question::factory()->create([
        'section_id' => $section->id,
        'question_type_id' => $singleChoiceType->id,
        'required' => true,
        'active' => true,
    ]);

    $conditionalQuestion = Question::factory()->create([
        'section_id' => $section->id,
        'question_type_id' => $textType->id,
        'required' => true,
        'active' => true,
    ]);

    $optionA = $mainQuestion->options()->create([
        'value' => 'opcion_a',
        'label' => 'Opción A',
        'sort_order' => 1,
        'active' => true,
    ]);

    $optionOtros = $mainQuestion->options()->create([
        'value' => 'otros',
        'label' => 'Otros',
        'sort_order' => 2,
        'active' => true,
    ]);

    $conditionalQuestion->conditions()->create([
        'depends_on_question_id' => $mainQuestion->id,
        'depends_on_option_id' => $optionOtros->id,
        'operator' => 'equals',
        'expected_value' => 'otros',
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

    // Se responde la pregunta principal con "Otros".
    $service->saveAnswer(
        $response,
        $mainQuestion,
        $optionOtros->id,
    );

    // La pregunta condicional queda sin responder.
    expect(fn() => $service->complete($response))
        ->toThrow(\App\Exceptions\SurveyValidationException::class);

    expect($response->refresh()->status)
        ->toBe(SurveyResponseStatus::IN_PROGRESS);

    expect($response->completed_at)
        ->toBeNull();
});

//-------------------------------------------------------------------
it('does not require a conditional question after changing the triggering answer', function () {
    $questionnaire = Questionnaire::factory()->create();

    $surveyVersion = SurveyVersion::factory()->create([
        'questionnaire_id' => $questionnaire->id,
    ]);

    $section = Section::factory()->create([
        'survey_version_id' => $surveyVersion->id,
    ]);

    $singleChoiceType = QuestionType::factory()->create([
        'code' => 'single_choice',
        'name' => 'Selección simple',
    ]);

    $textType = QuestionType::factory()->create([
        'code' => 'text',
        'name' => 'Texto',
    ]);

    $mainQuestion = Question::factory()->create([
        'section_id' => $section->id,
        'question_type_id' => $singleChoiceType->id,
        'required' => true,
        'active' => true,
    ]);

    $conditionalQuestion = Question::factory()->create([
        'section_id' => $section->id,
        'question_type_id' => $textType->id,
        'required' => true,
        'active' => true,
    ]);

    $optionA = $mainQuestion->options()->create([
        'value' => 'opcion_a',
        'label' => 'Opción A',
        'sort_order' => 1,
        'active' => true,
    ]);

    $optionOtros = $mainQuestion->options()->create([
        'value' => 'otros',
        'label' => 'Otros',
        'sort_order' => 2,
        'active' => true,
    ]);

    $conditionalQuestion->conditions()->create([
        'depends_on_question_id' => $mainQuestion->id,
        'depends_on_option_id' => $optionOtros->id,
        'operator' => 'equals',
        'expected_value' => 'otros',
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

    // Primero se selecciona "Otros".
    $service->saveAnswer(
        $response,
        $mainQuestion,
        $optionOtros->id,
    );

    // La condición se cumple.
    expect(
        app(\App\Services\ConditionEvaluator::class)
            ->shouldShow($response, $conditionalQuestion)
    )->toBeTrue();

    // Se responde la pregunta condicional.
    $service->saveAnswer(
        $response,
        $conditionalQuestion,
        'Respuesta temporal',
    );

    // Ahora se cambia la respuesta principal a "Opción A".
    $service->saveAnswer(
        $response,
        $mainQuestion,
        $optionA->id,
    );

    // La condición ya no se cumple.
    expect(
        app(\App\Services\ConditionEvaluator::class)
            ->shouldShow($response, $conditionalQuestion)
    )->toBeFalse();

    // La encuesta debe poder finalizar sin exigir
    // la pregunta condicional.
    $completedResponse = $service->complete($response);

    expect($completedResponse->status)
        ->toBe(SurveyResponseStatus::COMPLETED);

    expect($completedResponse->completed_at)
        ->not->toBeNull();
});
//----------------------------------------------------------------------------

it('preserves a conditional answer when the triggering answer changes and changes back', function () {
    $questionnaire = Questionnaire::factory()->create();

    $surveyVersion = SurveyVersion::factory()->create([
        'questionnaire_id' => $questionnaire->id,
    ]);

    $section = Section::factory()->create([
        'survey_version_id' => $surveyVersion->id,
    ]);

    $singleChoiceType = QuestionType::factory()->create([
        'code' => 'single_choice',
        'name' => 'Selección simple',
    ]);

    $textType = QuestionType::factory()->create([
        'code' => 'text',
        'name' => 'Texto',
    ]);

    $mainQuestion = Question::factory()->create([
        'section_id' => $section->id,
        'question_type_id' => $singleChoiceType->id,
        'required' => true,
        'active' => true,
    ]);

    $conditionalQuestion = Question::factory()->create([
        'section_id' => $section->id,
        'question_type_id' => $textType->id,
        'required' => true,
        'active' => true,
    ]);

    $optionA = $mainQuestion->options()->create([
        'value' => 'opcion_a',
        'label' => 'Opción A',
        'sort_order' => 1,
        'active' => true,
    ]);

    $optionOtros = $mainQuestion->options()->create([
        'value' => 'otros',
        'label' => 'Otros',
        'sort_order' => 2,
        'active' => true,
    ]);

    $conditionalQuestion->conditions()->create([
        'depends_on_question_id' => $mainQuestion->id,
        'depends_on_option_id' => $optionOtros->id,
        'operator' => 'equals',
        'expected_value' => 'otros',
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

    // 1. Seleccionamos "Otros".
    $service->saveAnswer(
        $response,
        $mainQuestion,
        $optionOtros->id,
    );

    expect(
        app(\App\Services\ConditionEvaluator::class)
            ->shouldShow($response, $conditionalQuestion)
    )->toBeTrue();

    // 2. Respondemos la pregunta condicional.
    $service->saveAnswer(
        $response,
        $conditionalQuestion,
        'Respuesta temporal',
    );

    // 3. Cambiamos a "Opción A".
    $service->saveAnswer(
        $response,
        $mainQuestion,
        $optionA->id,
    );

    expect(
        app(\App\Services\ConditionEvaluator::class)
            ->shouldShow($response, $conditionalQuestion)
    )->toBeFalse();

    // 4. Volvemos a seleccionar "Otros".
    $service->saveAnswer(
        $response,
        $mainQuestion,
        $optionOtros->id,
    );

    expect(
        app(\App\Services\ConditionEvaluator::class)
            ->shouldShow($response, $conditionalQuestion)
    )->toBeTrue();

    // 5. La respuesta anterior debe seguir almacenada.
    $answer = $response->answers()
        ->where('question_id', $conditionalQuestion->id)
        ->first();

    expect($answer)->not->toBeNull()
        ->and($answer->text_value)->toBe('Respuesta temporal');
});

//-----------------------------------------------------------------

it('shows a conditional question only when all conditions are met', function () {
    $questionnaire = Questionnaire::factory()->create();

    $surveyVersion = SurveyVersion::factory()->create([
        'questionnaire_id' => $questionnaire->id,
    ]);

    $section = Section::factory()->create([
        'survey_version_id' => $surveyVersion->id,
    ]);

    $singleChoiceType = QuestionType::factory()->create([
        'code' => 'single_choice',
        'name' => 'Selección simple',
    ]);

    $textType = QuestionType::factory()->create([
        'code' => 'text',
        'name' => 'Texto',
    ]);

    $question1 = Question::factory()->create([
        'section_id' => $section->id,
        'question_type_id' => $singleChoiceType->id,
        'required' => true,
        'active' => true,
    ]);

    $question2 = Question::factory()->create([
        'section_id' => $section->id,
        'question_type_id' => $singleChoiceType->id,
        'required' => true,
        'active' => true,
    ]);

    $conditionalQuestion = Question::factory()->create([
        'section_id' => $section->id,
        'question_type_id' => $textType->id,
        'required' => true,
        'active' => true,
    ]);

    $option1Yes = $question1->options()->create([
        'value' => 'si',
        'label' => 'Sí',
        'sort_order' => 1,
        'active' => true,
    ]);

    $option1No = $question1->options()->create([
        'value' => 'no',
        'label' => 'No',
        'sort_order' => 2,
        'active' => true,
    ]);

    $option2Yes = $question2->options()->create([
        'value' => 'si',
        'label' => 'Sí',
        'sort_order' => 1,
        'active' => true,
    ]);

    $option2No = $question2->options()->create([
        'value' => 'no',
        'label' => 'No',
        'sort_order' => 2,
        'active' => true,
    ]);

    $conditionalQuestion->conditions()->create([
        'depends_on_question_id' => $question1->id,
        'depends_on_option_id' => $option1Yes->id,
        'operator' => 'equals',
        'expected_value' => 'si',
        'active' => true,
    ]);

    $conditionalQuestion->conditions()->create([
        'depends_on_question_id' => $question2->id,
        'depends_on_option_id' => $option2Yes->id,
        'operator' => 'equals',
        'expected_value' => 'si',
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

    // Primera condición cumplida.
    $service->saveAnswer(
        $response,
        $question1,
        $option1Yes->id,
    );

    // Segunda condición todavía NO cumplida.
    expect(
        app(\App\Services\ConditionEvaluator::class)
            ->shouldShow($response, $conditionalQuestion)
    )->toBeFalse();

    // Ahora cumplimos la segunda condición.
    $service->saveAnswer(
        $response,
        $question2,
        $option2Yes->id,
    );

    expect(
        app(\App\Services\ConditionEvaluator::class)
            ->shouldShow($response, $conditionalQuestion)
    )->toBeTrue();

    // Cambiamos la primera condición para que deje de cumplirse.
    $service->saveAnswer(
        $response,
        $question1,
        $option1No->id,
    );

    expect(
        app(\App\Services\ConditionEvaluator::class)
            ->shouldShow($response, $conditionalQuestion)
    )->toBeFalse();
});

//--------------------------------------------------------------------

it('shows a conditional question when a not_equals condition is satisfied', function () {
    $questionnaire = Questionnaire::factory()->create();

    $surveyVersion = SurveyVersion::factory()->create([
        'questionnaire_id' => $questionnaire->id,
    ]);

    $section = Section::factory()->create([
        'survey_version_id' => $surveyVersion->id,
    ]);

    $singleChoiceType = QuestionType::factory()->create([
        'code' => 'single_choice',
        'name' => 'Selección simple',
    ]);

    $textType = QuestionType::factory()->create([
        'code' => 'text',
        'name' => 'Texto',
    ]);

    $mainQuestion = Question::factory()->create([
        'section_id' => $section->id,
        'question_type_id' => $singleChoiceType->id,
        'required' => true,
        'active' => true,
    ]);

    $conditionalQuestion = Question::factory()->create([
        'section_id' => $section->id,
        'question_type_id' => $textType->id,
        'required' => true,
        'active' => true,
    ]);

    $optionA = $mainQuestion->options()->create([
        'value' => 'opcion_a',
        'label' => 'Opción A',
        'sort_order' => 1,
        'active' => true,
    ]);

    $optionB = $mainQuestion->options()->create([
        'value' => 'opcion_b',
        'label' => 'Opción B',
        'sort_order' => 2,
        'active' => true,
    ]);

    $conditionalQuestion->conditions()->create([
        'depends_on_question_id' => $mainQuestion->id,
        'depends_on_option_id' => $optionA->id,
        'operator' => 'not_equals',
        'expected_value' => 'opcion_a',
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

    // Seleccionamos una opción diferente de la condición.
    $service->saveAnswer(
        $response,
        $mainQuestion,
        $optionB->id,
    );

    expect(
        app(\App\Services\ConditionEvaluator::class)
            ->shouldShow($response, $conditionalQuestion)
    )->toBeTrue();

    // Ahora seleccionamos exactamente la opción indicada
    // por la condición.
    $service->saveAnswer(
        $response,
        $mainQuestion,
        $optionA->id,
    );

    expect(
        app(\App\Services\ConditionEvaluator::class)
            ->shouldShow($response, $conditionalQuestion)
    )->toBeFalse();
});

//---------------------------------------------------------------------------

it('does not show a conditional question when its dependency has no answer', function () {
    $questionnaire = Questionnaire::factory()->create();

    $surveyVersion = SurveyVersion::factory()->create([
        'questionnaire_id' => $questionnaire->id,
    ]);

    $section = Section::factory()->create([
        'survey_version_id' => $surveyVersion->id,
    ]);

    $singleChoiceType = QuestionType::factory()->create([
        'code' => 'single_choice',
        'name' => 'Selección simple',
    ]);

    $textType = QuestionType::factory()->create([
        'code' => 'text',
        'name' => 'Texto',
    ]);

    $mainQuestion = Question::factory()->create([
        'section_id' => $section->id,
        'question_type_id' => $singleChoiceType->id,
        'required' => true,
        'active' => true,
    ]);

    $conditionalQuestion = Question::factory()->create([
        'section_id' => $section->id,
        'question_type_id' => $textType->id,
        'required' => true,
        'active' => true,
    ]);

    $optionA = $mainQuestion->options()->create([
        'value' => 'opcion_a',
        'label' => 'Opción A',
        'sort_order' => 1,
        'active' => true,
    ]);

    $conditionalQuestion->conditions()->create([
        'depends_on_question_id' => $mainQuestion->id,
        'depends_on_option_id' => $optionA->id,
        'operator' => 'equals',
        'expected_value' => 'opcion_a',
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

    // La pregunta de la que depende la condición
    // todavía no tiene ninguna respuesta.
    expect(
        app(\App\Services\ConditionEvaluator::class)
            ->shouldShow($response, $conditionalQuestion)
    )->toBeFalse();
});

//-------------------------------------------------------------------
it('shows a conditional question when a text answer matches the expected value', function () {
    $questionnaire = Questionnaire::factory()->create();

    $surveyVersion = SurveyVersion::factory()->create([
        'questionnaire_id' => $questionnaire->id,
    ]);

    $section = Section::factory()->create([
        'survey_version_id' => $surveyVersion->id,
    ]);

    $textType = QuestionType::factory()->create([
        'code' => 'text',
        'name' => 'Texto',
    ]);

    $mainQuestion = Question::factory()->create([
        'section_id' => $section->id,
        'question_type_id' => $textType->id,
        'required' => true,
        'active' => true,
    ]);

    $conditionalQuestion = Question::factory()->create([
        'section_id' => $section->id,
        'question_type_id' => $textType->id,
        'required' => true,
        'active' => true,
    ]);

    $conditionalQuestion->conditions()->create([
        'depends_on_question_id' => $mainQuestion->id,
        'operator' => 'equals',
        'expected_value' => 'Municipal',
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

    // La respuesta todavía no coincide.
    $service->saveAnswer(
        $response,
        $mainQuestion,
        'Privada',
    );

    expect(
        app(\App\Services\ConditionEvaluator::class)
            ->shouldShow($response, $conditionalQuestion)
    )->toBeFalse();

    // Cambiamos la respuesta al valor esperado.
    $service->saveAnswer(
        $response,
        $mainQuestion,
        'Municipal',
    );

    expect(
        app(\App\Services\ConditionEvaluator::class)
            ->shouldShow($response, $conditionalQuestion)
    )->toBeTrue();
});

//---------------------------------------------------

it('shows a conditional question when a text answer satisfies not_equals', function () {
    $questionnaire = Questionnaire::factory()->create();

    $surveyVersion = SurveyVersion::factory()->create([
        'questionnaire_id' => $questionnaire->id,
    ]);

    $section = Section::factory()->create([
        'survey_version_id' => $surveyVersion->id,
    ]);

    $textType = QuestionType::factory()->create([
        'code' => 'text',
        'name' => 'Texto',
    ]);

    $mainQuestion = Question::factory()->create([
        'section_id' => $section->id,
        'question_type_id' => $textType->id,
        'required' => true,
        'active' => true,
    ]);

    $conditionalQuestion = Question::factory()->create([
        'section_id' => $section->id,
        'question_type_id' => $textType->id,
        'required' => true,
        'active' => true,
    ]);

    $conditionalQuestion->conditions()->create([
        'depends_on_question_id' => $mainQuestion->id,
        'operator' => 'not_equals',
        'expected_value' => 'Municipal',
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

    // Un valor diferente debe cumplir not_equals.
    $service->saveAnswer(
        $response,
        $mainQuestion,
        'Privada',
    );

    expect(
        app(\App\Services\ConditionEvaluator::class)
            ->shouldShow($response, $conditionalQuestion)
    )->toBeTrue();

    // El mismo valor debe dejar de cumplir not_equals.
    $service->saveAnswer(
        $response,
        $mainQuestion,
        'Municipal',
    );

    expect(
        app(\App\Services\ConditionEvaluator::class)
            ->shouldShow($response, $conditionalQuestion)
    )->toBeFalse();
});

//-------------------------------------------------

it('shows a conditional question when a number answer matches the expected value', function () {
    $questionnaire = Questionnaire::factory()->create();

    $surveyVersion = SurveyVersion::factory()->create([
        'questionnaire_id' => $questionnaire->id,
    ]);

    $section = Section::factory()->create([
        'survey_version_id' => $surveyVersion->id,
    ]);

    $numberType = QuestionType::factory()->create([
        'code' => 'number',
        'name' => 'Número',
    ]);

    $textType = QuestionType::factory()->create([
        'code' => 'text',
        'name' => 'Texto',
    ]);

    $mainQuestion = Question::factory()->create([
        'section_id' => $section->id,
        'question_type_id' => $numberType->id,
        'required' => true,
        'active' => true,
    ]);

    $conditionalQuestion = Question::factory()->create([
        'section_id' => $section->id,
        'question_type_id' => $textType->id,
        'required' => true,
        'active' => true,
    ]);

    $conditionalQuestion->conditions()->create([
        'depends_on_question_id' => $mainQuestion->id,
        'operator' => 'equals',
        'expected_value' => '25',
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

    // Un valor diferente no cumple la condición.
    $service->saveAnswer(
        $response,
        $mainQuestion,
        20,
    );

    expect(
        app(\App\Services\ConditionEvaluator::class)
            ->shouldShow($response, $conditionalQuestion)
    )->toBeFalse();

    // El valor esperado sí cumple la condición.
    $service->saveAnswer(
        $response,
        $mainQuestion,
        25,
    );


    expect(
        app(\App\Services\ConditionEvaluator::class)
            ->shouldShow($response, $conditionalQuestion)
    )->toBeTrue();
});

//-----------------------------------------------------------
it('shows a conditional question when a date answer matches the expected value', function () {
    $questionnaire = Questionnaire::factory()->create();

    $surveyVersion = SurveyVersion::factory()->create([
        'questionnaire_id' => $questionnaire->id,
    ]);

    $section = Section::factory()->create([
        'survey_version_id' => $surveyVersion->id,
    ]);

    $dateType = QuestionType::factory()->create([
        'code' => 'date',
        'name' => 'Fecha',
    ]);

    $textType = QuestionType::factory()->create([
        'code' => 'text',
        'name' => 'Texto',
    ]);

    $mainQuestion = Question::factory()->create([
        'section_id' => $section->id,
        'question_type_id' => $dateType->id,
        'required' => true,
        'active' => true,
    ]);

    $conditionalQuestion = Question::factory()->create([
        'section_id' => $section->id,
        'question_type_id' => $textType->id,
        'required' => true,
        'active' => true,
    ]);

    $conditionalQuestion->conditions()->create([
        'depends_on_question_id' => $mainQuestion->id,
        'operator' => 'equals',
        'expected_value' => '2026-09-02',
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

    // Una fecha diferente no cumple la condición.
    $service->saveAnswer(
        $response,
        $mainQuestion,
        '2026-09-01',
    );

    expect(
        app(\App\Services\ConditionEvaluator::class)
            ->shouldShow($response, $conditionalQuestion)
    )->toBeFalse();

    // La fecha esperada sí cumple la condición.
    $service->saveAnswer(
        $response,
        $mainQuestion,
        '2026-09-02',
    );

    expect(
        app(\App\Services\ConditionEvaluator::class)
            ->shouldShow($response, $conditionalQuestion)
    )->toBeTrue();
});

//---------------------------------------------------------------------

it('does not show a conditional question when the operator is not supported', function () {
    $questionnaire = Questionnaire::factory()->create();

    $surveyVersion = SurveyVersion::factory()->create([
        'questionnaire_id' => $questionnaire->id,
    ]);

    $section = Section::factory()->create([
        'survey_version_id' => $surveyVersion->id,
    ]);

    $textType = QuestionType::factory()->create([
        'code' => 'text',
        'name' => 'Texto',
    ]);

    $mainQuestion = Question::factory()->create([
        'section_id' => $section->id,
        'question_type_id' => $textType->id,
        'required' => true,
        'active' => true,
    ]);

    $conditionalQuestion = Question::factory()->create([
        'section_id' => $section->id,
        'question_type_id' => $textType->id,
        'required' => true,
        'active' => true,
    ]);

    $conditionalQuestion->conditions()->create([
        'depends_on_question_id' => $mainQuestion->id,
        'operator' => 'contains',
        'expected_value' => 'Municipal',
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
        $mainQuestion,
        'Municipal',
    );

    expect(
        app(\App\Services\ConditionEvaluator::class)
            ->shouldShow($response, $conditionalQuestion)
    )->toBeFalse();
});

//------------------------------------------------------------------

it('does not apply an inactive condition', function () {
    $questionnaire = Questionnaire::factory()->create();

    $surveyVersion = SurveyVersion::factory()->create([
        'questionnaire_id' => $questionnaire->id,
    ]);

    $section = Section::factory()->create([
        'survey_version_id' => $surveyVersion->id,
    ]);

    $textType = QuestionType::factory()->create([
        'code' => 'text',
        'name' => 'Texto',
    ]);

    $mainQuestion = Question::factory()->create([
        'section_id' => $section->id,
        'question_type_id' => $textType->id,
        'required' => true,
        'active' => true,
    ]);

    $conditionalQuestion = Question::factory()->create([
        'section_id' => $section->id,
        'question_type_id' => $textType->id,
        'required' => true,
        'active' => true,
    ]);

    $conditionalQuestion->conditions()->create([
        'depends_on_question_id' => $mainQuestion->id,
        'operator' => 'equals',
        'expected_value' => 'Municipal',
        'active' => false,
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

    // Aunque la respuesta coincide con expected_value,
    // la condición está inactiva.
    $service->saveAnswer(
        $response,
        $mainQuestion,
        'Municipal',
    );

    expect(
        app(\App\Services\ConditionEvaluator::class)
            ->shouldShow($response, $conditionalQuestion)
    )->toBeTrue();
});

//------------------------------------------------------------------

it('replaces an existing text answer instead of creating a duplicate', function () {
    $questionnaire = Questionnaire::factory()->create();

    $surveyVersion = SurveyVersion::factory()->create([
        'questionnaire_id' => $questionnaire->id,
    ]);

    $section = Section::factory()->create([
        'survey_version_id' => $surveyVersion->id,
    ]);

    $textType = QuestionType::factory()->create([
        'code' => 'text',
        'name' => 'Texto',
    ]);

    $question = Question::factory()->create([
        'section_id' => $section->id,
        'question_type_id' => $textType->id,
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

    // Primera respuesta.
    $firstAnswer = $service->saveAnswer(
        $response,
        $question,
        'Primera respuesta',
    );

    // Segunda respuesta para la misma pregunta.
    $secondAnswer = $service->saveAnswer(
        $response,
        $question,
        'Segunda respuesta',
    );

    expect($secondAnswer->id)
        ->toBe($firstAnswer->id);

    expect($secondAnswer->text_value)
        ->toBe('Segunda respuesta');

    expect(
        $response->answers()
            ->where('question_id', $question->id)
            ->count()
    )->toBe(1);

    $this->assertDatabaseHas('answers', [
        'id' => $firstAnswer->id,
        'survey_response_id' => $response->id,
        'question_id' => $question->id,
        'text_value' => 'Segunda respuesta',
    ]);

    $this->assertDatabaseMissing('answers', [
        'survey_response_id' => $response->id,
        'question_id' => $question->id,
        'text_value' => 'Primera respuesta',
    ]);
});

//----------------------------------------------------

it('replaces an existing number answer instead of creating a duplicate', function () {
    $questionnaire = Questionnaire::factory()->create();

    $surveyVersion = SurveyVersion::factory()->create([
        'questionnaire_id' => $questionnaire->id,
    ]);

    $section = Section::factory()->create([
        'survey_version_id' => $surveyVersion->id,
    ]);

    $numberType = QuestionType::factory()->create([
        'code' => 'number',
        'name' => 'Número',
    ]);

    $question = Question::factory()->create([
        'section_id' => $section->id,
        'question_type_id' => $numberType->id,
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

    // Primera respuesta.
    $firstAnswer = $service->saveAnswer(
        $response,
        $question,
        25,
    );

    // Segunda respuesta para la misma pregunta.
    $secondAnswer = $service->saveAnswer(
        $response,
        $question,
        30,
    );

    expect($secondAnswer->id)
        ->toBe($firstAnswer->id);

    expect($secondAnswer->number_value)
        ->toBe('30.0000');

    expect(
        $response->answers()
            ->where('question_id', $question->id)
            ->count()
    )->toBe(1);

    $this->assertDatabaseHas('answers', [
        'id' => $firstAnswer->id,
        'survey_response_id' => $response->id,
        'question_id' => $question->id,
        'number_value' => '30.0000',
    ]);

    $this->assertDatabaseMissing('answers', [
        'survey_response_id' => $response->id,
        'question_id' => $question->id,
        'number_value' => '25.0000',
    ]);
});

//---------------------------------------------------

it('replaces an existing date answer instead of creating a duplicate', function () {
    $questionnaire = Questionnaire::factory()->create();

    $surveyVersion = SurveyVersion::factory()->create([
        'questionnaire_id' => $questionnaire->id,
    ]);

    $section = Section::factory()->create([
        'survey_version_id' => $surveyVersion->id,
    ]);

    $dateType = QuestionType::factory()->create([
        'code' => 'date',
        'name' => 'Fecha',
    ]);

    $question = Question::factory()->create([
        'section_id' => $section->id,
        'question_type_id' => $dateType->id,
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

    // Primera respuesta.
    $firstAnswer = $service->saveAnswer(
        $response,
        $question,
        '2026-09-01',
    );

    // Segunda respuesta para la misma pregunta.
    $secondAnswer = $service->saveAnswer(
        $response,
        $question,
        '2026-09-02',
    );

    expect($secondAnswer->id)
        ->toBe($firstAnswer->id);

    expect($secondAnswer->date_value->format('Y-m-d'))
        ->toBe('2026-09-02');

    expect(
        $response->answers()
            ->where('question_id', $question->id)
            ->count()
    )->toBe(1);

    $this->assertDatabaseHas('answers', [
        'id' => $firstAnswer->id,
        'survey_response_id' => $response->id,
        'question_id' => $question->id,
        'date_value' => '2026-09-02 00:00:00',
    ]);

    $this->assertDatabaseMissing('answers', [
        'survey_response_id' => $response->id,
        'question_id' => $question->id,
        'date_value' => '2026-09-01',
    ]);
});

//----------------------------------------------------------------------
it('replaces an existing single choice answer instead of creating a duplicate', function () {
    $questionType = QuestionType::factory()->create([
        'code' => 'single_choice',
    ]);

    $question = Question::factory()->create([
        'question_type_id' => $questionType->id,
        'required' => false,
        'active' => true,
    ]);

    $firstOption = QuestionOption::create([
        'question_id' => $question->id,
        'label' => 'Opción 1',
        'value' => 'opcion_1',
        'active' => true,
    ]);

    $secondOption = QuestionOption::create([
        'question_id' => $question->id,
        'label' => 'Opción 2',
        'value' => 'opcion_2',
        'active' => true,
    ]);

    $response = SurveyResponse::factory()->create([
        'status' => SurveyResponseStatus::DRAFT,
    ]);
    /* $response = SurveyResponse::create([
        'questionnaire_id' => $question->section->survey_version->questionnaire_id,
        'survey_version_id' => $question->section->survey_version_id,
        'created_by' => User::factory()->create()->id,
        'status' => SurveyResponseStatus::DRAFT,
    ]); */

    $service = app(SurveyResponseService::class);

    $firstAnswer = $service->saveAnswer(
        $response,
        $question,
        $firstOption->id
    );

    $secondAnswer = $service->saveAnswer(
        $response,
        $question,
        $secondOption->id
    );

    expect($secondAnswer->id)->toBe($firstAnswer->id);

    expect(
        Answer::query()
            ->where('survey_response_id', $response->id)
            ->where('question_id', $question->id)
            ->count()
    )->toBe(1);

    $this->assertDatabaseHas('answers', [
        'id' => $firstAnswer->id,
        'survey_response_id' => $response->id,
        'question_id' => $question->id,
        'option_id' => $secondOption->id,
    ]);
});

//----------------------------------------------------------------------------------
it('replaces an existing multiple choice answer instead of creating a duplicate', function () {
    $questionnaire = Questionnaire::factory()->create();

    $surveyVersion = SurveyVersion::factory()->create([
        'questionnaire_id' => $questionnaire->id,
    ]);

    $section = Section::factory()->create([
        'survey_version_id' => $surveyVersion->id,
    ]);

    $questionType = QuestionType::factory()->create([
        'code' => 'multiple_choice',
        'name' => 'Selección múltiple',
    ]);

    $question = Question::factory()->create([
        'section_id' => $section->id,
        'question_type_id' => $questionType->id,
        'required' => true,
        'active' => true,
    ]);

    $option1 = $question->options()->create([
        'value' => 'opcion_1',
        'label' => 'Opción 1',
        'sort_order' => 1,
        'active' => true,
    ]);

    $option2 = $question->options()->create([
        'value' => 'opcion_2',
        'label' => 'Opción 2',
        'sort_order' => 2,
        'active' => true,
    ]);

    $option3 = $question->options()->create([
        'value' => 'opcion_3',
        'label' => 'Opción 3',
        'sort_order' => 3,
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

    $firstAnswer = $service->saveAnswer(
        $response,
        $question,
        [$option1->id, $option2->id],
    );

    $secondAnswer = $service->saveAnswer(
        $response,
        $question,
        [$option2->id, $option3->id],
    );

    expect($secondAnswer->id)->toBe($firstAnswer->id);

    expect(
        Answer::query()
            ->where('survey_response_id', $response->id)
            ->where('question_id', $question->id)
            ->count()
    )->toBe(1);

    expect(
        $secondAnswer->selectedOptions()
            ->pluck('question_option_id')
            ->sort()
            ->values()
            ->all()
    )->toBe([
        $option2->id,
        $option3->id,
    ]);

    $this->assertDatabaseCount('answer_options', 2);

    $this->assertDatabaseHas('answer_options', [
        'answer_id' => $firstAnswer->id,
        'question_option_id' => $option2->id,
    ]);

    $this->assertDatabaseHas('answer_options', [
        'answer_id' => $firstAnswer->id,
        'question_option_id' => $option3->id,
    ]);

    $this->assertDatabaseMissing('answer_options', [
        'answer_id' => $firstAnswer->id,
        'question_option_id' => $option1->id,
    ]);
});

//--------------------------------------------------------------------

it('rejects an inactive option for a single choice answer', function () {
    $questionnaire = Questionnaire::factory()->create();

    $surveyVersion = SurveyVersion::factory()->create([
        'questionnaire_id' => $questionnaire->id,
    ]);

    $section = Section::factory()->create([
        'survey_version_id' => $surveyVersion->id,
    ]);

    $questionType = QuestionType::factory()->create([
        'code' => 'single_choice',
        'name' => 'Selección única',
    ]);

    $question = Question::factory()->create([
        'section_id' => $section->id,
        'question_type_id' => $questionType->id,
        'required' => false,
        'active' => true,
    ]);

    $inactiveOption = $question->options()->create([
        'value' => 'opcion_inactiva',
        'label' => 'Opción inactiva',
        'sort_order' => 1,
        'active' => false,
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

    expect(fn() => $service->saveAnswer(
        $response,
        $question,
        $inactiveOption->id
    ))->toThrow(
        InvalidArgumentException::class,
        'La opción seleccionada no pertenece a la pregunta.'
    );

    $this->assertDatabaseMissing('answers', [
        'survey_response_id' => $response->id,
        'question_id' => $question->id,
    ]);
});

//-------------------------------------------------
it('rejects an inactive option for a multiple choice answer', function () {
    $questionnaire = Questionnaire::factory()->create();

    $surveyVersion = SurveyVersion::factory()->create([
        'questionnaire_id' => $questionnaire->id,
    ]);

    $section = Section::factory()->create([
        'survey_version_id' => $surveyVersion->id,
    ]);

    $questionType = QuestionType::factory()->create([
        'code' => 'multiple_choice',
        'name' => 'Selección múltiple',
    ]);

    $question = Question::factory()->create([
        'section_id' => $section->id,
        'question_type_id' => $questionType->id,
        'required' => false,
        'active' => true,
    ]);

    $activeOption = $question->options()->create([
        'value' => 'opcion_activa',
        'label' => 'Opción activa',
        'sort_order' => 1,
        'active' => true,
    ]);

    $inactiveOption = $question->options()->create([
        'value' => 'opcion_inactiva',
        'label' => 'Opción inactiva',
        'sort_order' => 2,
        'active' => false,
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

    expect(fn() => $service->saveAnswer(
        $response,
        $question,
        [$activeOption->id, $inactiveOption->id]
    ))->toThrow(
        InvalidArgumentException::class,
        'Una o más opciones no pertenecen a la pregunta.'
    );

    $this->assertDatabaseMissing('answers', [
        'survey_response_id' => $response->id,
        'question_id' => $question->id,
    ]);

    $this->assertDatabaseCount('answer_options', 0);
});

//------------------------------------------------------

it('removes duplicate option ids when saving a multiple choice answer', function () {
    $questionnaire = Questionnaire::factory()->create();

    $surveyVersion = SurveyVersion::factory()->create([
        'questionnaire_id' => $questionnaire->id,
    ]);

    $section = Section::factory()->create([
        'survey_version_id' => $surveyVersion->id,
    ]);

    $questionType = QuestionType::factory()->create([
        'code' => 'multiple_choice',
        'name' => 'Selección múltiple',
    ]);

    $question = Question::factory()->create([
        'section_id' => $section->id,
        'question_type_id' => $questionType->id,
        'required' => false,
        'active' => true,
    ]);

    $option1 = $question->options()->create([
        'value' => 'opcion_1',
        'label' => 'Opción 1',
        'sort_order' => 1,
        'active' => true,
    ]);

    $option2 = $question->options()->create([
        'value' => 'opcion_2',
        'label' => 'Opción 2',
        'sort_order' => 2,
        'active' => true,
    ]);

    $option3 = $question->options()->create([
        'value' => 'opcion_3',
        'label' => 'Opción 3',
        'sort_order' => 3,
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

    $answer = $service->saveAnswer(
        $response,
        $question,
        [
            $option1->id,
            $option2->id,
            $option2->id,
            $option3->id,
            $option3->id,
        ],
    );

    expect(
        $answer->selectedOptions()
            ->pluck('question_option_id')
            ->sort()
            ->values()
            ->all()
    )->toBe([
        $option1->id,
        $option2->id,
        $option3->id,
    ]);

    $this->assertDatabaseCount('answer_options', 3);

    expect(
        $answer->selectedOptions()
            ->count()
    )->toBe(3);
});

//----------------------------------------------------------

it('saves a textarea answer correctly', function () {
    $questionType = QuestionType::factory()->create([
        'code' => 'textarea',
        'name' => 'Texto largo',
    ]);
    
    $questionnaire = Questionnaire::factory()->create();

    $surveyVersion = SurveyVersion::factory()->create([
        'questionnaire_id' => $questionnaire->id,
    ]);

    $section = Section::factory()->create([
        'survey_version_id' => $surveyVersion->id,
    ]);

    $question = Question::factory()->create([
        'section_id' => $section->id,
        'question_type_id' => $questionType->id,
        'required' => false,
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

    $value = 'Esta es una respuesta de texto largo para la pregunta.';

    $answer = $service->saveAnswer(
        $response,
        $question,
        $value,
    );

    expect($answer->text_value)->toBe($value);

    $this->assertDatabaseHas('answers', [
        'id' => $answer->id,
        'survey_response_id' => $response->id,
        'question_id' => $question->id,
        'text_value' => $value,
    ]);
});

//-----------------------------------------------------
it('rejects a non numeric value for a number answer', function () {
    $questionnaire = Questionnaire::factory()->create();

    $surveyVersion = SurveyVersion::factory()->create([
        'questionnaire_id' => $questionnaire->id,
    ]);

    $section = Section::factory()->create([
        'survey_version_id' => $surveyVersion->id,
    ]);

    $questionType = QuestionType::factory()->create([
        'code' => 'number',
        'name' => 'Número',
    ]);

    $question = Question::factory()->create([
        'section_id' => $section->id,
        'question_type_id' => $questionType->id,
        'required' => false,
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

    expect(fn () => $service->saveAnswer(
        $response,
        $question,
        'no-es-un-numero'
    ))->toThrow(
        InvalidArgumentException::class,
        'El valor debe ser numérico.'
    );

    $this->assertDatabaseMissing('answers', [
        'survey_response_id' => $response->id,
        'question_id' => $question->id,
    ]);
});

//------------------------------------------

it('rejects a non array value for a multiple choice answer', function () {
    $questionnaire = Questionnaire::factory()->create();

    $surveyVersion = SurveyVersion::factory()->create([
        'questionnaire_id' => $questionnaire->id,
    ]);

    $section = Section::factory()->create([
        'survey_version_id' => $surveyVersion->id,
    ]);

    $questionType = QuestionType::factory()->create([
        'code' => 'multiple_choice',
        'name' => 'Selección múltiple',
    ]);

    $question = Question::factory()->create([
        'section_id' => $section->id,
        'question_type_id' => $questionType->id,
        'required' => false,
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

    expect(fn () => $service->saveAnswer(
        $response,
        $question,
        'opcion_1'
    ))->toThrow(
        InvalidArgumentException::class,
        'Las opciones seleccionadas deben enviarse como un arreglo.'
    );

    $this->assertDatabaseMissing('answers', [
        'survey_response_id' => $response->id,
        'question_id' => $question->id,
    ]);
});

//--------------------------------------------------------------
it('rejects an unsupported question type when saving an answer', function () {
    $questionnaire = Questionnaire::factory()->create();

    $surveyVersion = SurveyVersion::factory()->create([
        'questionnaire_id' => $questionnaire->id,
    ]);

    $section = Section::factory()->create([
        'survey_version_id' => $surveyVersion->id,
    ]);

    $questionType = QuestionType::factory()->create([
        'code' => 'image',
        'name' => 'Imagen',
    ]);

    $question = Question::factory()->create([
        'section_id' => $section->id,
        'question_type_id' => $questionType->id,
        'required' => false,
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

    expect(fn () => $service->saveAnswer(
        $response,
        $question,
        'imagen.jpg'
    ))->toThrow(
        InvalidArgumentException::class,
        'Tipo de pregunta no soportado: image'
    );

    $this->assertDatabaseMissing('answers', [
        'survey_response_id' => $response->id,
        'question_id' => $question->id,
    ]);
});

//----------------------------------------------------------
it('does not start a completed survey response', function () {
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

    expect(fn () => $service->start($response))
        ->toThrow(
            InvalidArgumentException::class,
            'Solo una encuesta en estado draft puede iniciarse.'
        );

    $response->refresh();

    expect($response->status)
        ->toBe(SurveyResponseStatus::COMPLETED);

    expect($response->completed_at)
        ->not->toBeNull();
});

//--------------------------------------------------------------
it('does not complete a draft survey response', function () {
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

    expect(fn () => $service->complete($response))
        ->toThrow(
            InvalidArgumentException::class,
            'Solo una encuesta en progreso puede finalizarse.'
        );

    $response->refresh();

    expect($response->status)
        ->toBe(SurveyResponseStatus::DRAFT);

    expect($response->completed_at)
        ->toBeNull();
});

//------------------------------------------------------------
it('returns a cancelled survey response unchanged when cancelling it again', function () {
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

    $result = $service->cancel($cancelledResponse);

    expect($result->id)
        ->toBe($cancelledResponse->id);

    expect($result->status)
        ->toBe(SurveyResponseStatus::CANCELLED);

    expect($result->completed_at)
        ->toBeNull();

    expect(
        SurveyResponse::query()
            ->where('id', $response->id)
            ->count()
    )->toBe(1);
});

//------------------------------------------------------------
it('creates a draft survey response without a community', function () {
    $questionnaire = Questionnaire::factory()->create();

    $surveyVersion = SurveyVersion::factory()->create([
        'questionnaire_id' => $questionnaire->id,
    ]);

    $user = User::factory()->create();

    $service = app(SurveyResponseService::class);

    $response = $service->createDraft(
        $questionnaire->id,
        $surveyVersion->id,
        $user->id,
    );

    expect($response->status)
        ->toBe(SurveyResponseStatus::DRAFT);

    expect($response->community_id)
        ->toBeNull();

    expect($response->reference)
        ->not->toBeNull();

    $this->assertDatabaseHas('survey_responses', [
        'id' => $response->id,
        'questionnaire_id' => $questionnaire->id,
        'survey_version_id' => $surveyVersion->id,
        'created_by' => $user->id,
        'community_id' => null,
        'status' => SurveyResponseStatus::DRAFT->value,
    ]);
});

//-------------------------------------------------------
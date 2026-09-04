<?php

use App\Enums\SurveyResponseStatus;
use App\Models\Answer;
use App\Models\Questionnaire;
use App\Models\SurveyResponse;
use App\Models\SurveyVersion;
use App\Models\User;
use App\Services\SurveyResponseService;
use App\Models\Community;
use App\Models\Question;
use App\Models\QuestionType;
use App\Models\Section;
use App\Exceptions\SurveyValidationException;

it('creates a draft survey response with the correct context', function () {
    $questionnaire = Questionnaire::factory()->create();

    $version = SurveyVersion::factory()->create([
        'questionnaire_id' => $questionnaire->id,
    ]);

    $user = User::factory()->create();

    $service = app(SurveyResponseService::class);

    $response = $service->createDraft(
        questionnaireId: $questionnaire->id,
        surveyVersionId: $version->id,
        userId: $user->id,
    );

    expect($response)
        ->toBeInstanceOf(SurveyResponse::class)
        ->and($response->questionnaire_id)
        ->toBe($questionnaire->id)
        ->and($response->survey_version_id)
        ->toBe($version->id)
        ->and($response->created_by)
        ->toBe($user->id)
        ->and($response->status)
        ->toBe(SurveyResponseStatus::DRAFT)
        ->and($response->reference)
        ->not->toBeNull();

    $this->assertDatabaseHas('survey_responses', [
        'id' => $response->id,
        'questionnaire_id' => $questionnaire->id,
        'survey_version_id' => $version->id,
        'created_by' => $user->id,
        'status' => SurveyResponseStatus::DRAFT->value,
    ]);
});

//-------------------------------------------------
it('creates a draft survey response associated with a community', function () {
    $questionnaire = Questionnaire::factory()->create();

    $version = SurveyVersion::factory()->create([
        'questionnaire_id' => $questionnaire->id,
    ]);

    $user = User::factory()->create();

    $community = Community::factory()->create();

    $service = app(SurveyResponseService::class);

    $response = $service->createDraft(
        questionnaireId: $questionnaire->id,
        surveyVersionId: $version->id,
        userId: $user->id,
        communityId: $community->id,
    );

    expect($response->community_id)
        ->toBe($community->id)
        ->and($response->status)
        ->toBe(SurveyResponseStatus::DRAFT);

    $this->assertDatabaseHas('survey_responses', [
        'id' => $response->id,
        'community_id' => $community->id,
        'status' => SurveyResponseStatus::DRAFT->value,
    ]);
});

//---------------------------------------------------

it('starts a draft survey response', function () {
    $questionnaire = Questionnaire::factory()->create();

    $version = SurveyVersion::factory()->create([
        'questionnaire_id' => $questionnaire->id,
    ]);

    $user = User::factory()->create();

    $service = app(SurveyResponseService::class);

    $response = $service->createDraft(
        questionnaireId: $questionnaire->id,
        surveyVersionId: $version->id,
        userId: $user->id,
    );

    expect($response->status)
        ->toBe(SurveyResponseStatus::DRAFT)
        ->and($response->started_at)
        ->toBeNull();

    $startedResponse = $service->start($response);

    expect($startedResponse->status)
        ->toBe(SurveyResponseStatus::IN_PROGRESS)
        ->and($startedResponse->started_at)
        ->not->toBeNull();

    $this->assertDatabaseHas('survey_responses', [
        'id' => $response->id,
        'status' => SurveyResponseStatus::IN_PROGRESS->value,
    ]);
});

//------------------------------------------------

it('does not allow starting a completed or cancelled survey response', function () {
    $questionnaire = Questionnaire::factory()->create();

    $version = SurveyVersion::factory()->create([
        'questionnaire_id' => $questionnaire->id,
    ]);

    $user = User::factory()->create();

    $service = app(SurveyResponseService::class);

    $completedResponse = $service->createDraft(
        questionnaireId: $questionnaire->id,
        surveyVersionId: $version->id,
        userId: $user->id,
    );

    $completedResponse->update([
        'status' => SurveyResponseStatus::COMPLETED,
    ]);

    expect(fn() => $service->start($completedResponse))
        ->toThrow(InvalidArgumentException::class);

    $cancelledResponse = $service->createDraft(
        questionnaireId: $questionnaire->id,
        surveyVersionId: $version->id,
        userId: $user->id,
    );

    $cancelledResponse->update([
        'status' => SurveyResponseStatus::CANCELLED,
    ]);

    expect(fn() => $service->start($cancelledResponse))
        ->toThrow(InvalidArgumentException::class);
});

//------------------------------------------------

it('saves a text answer in an in-progress survey response', function () {
    $questionnaire = Questionnaire::factory()->create();

    $version = SurveyVersion::factory()->create([
        'questionnaire_id' => $questionnaire->id,
    ]);

    $section = Section::factory()->create([
        'survey_version_id' => $version->id,
    ]);

    $questionType = QuestionType::factory()->create([
        'code' => 'text',
    ]);

    $question = Question::factory()->create([
        'section_id' => $section->id,
        'question_type_id' => $questionType->id,
        'code' => 'TEST_TEXT_001',
    ]);

    $user = User::factory()->create();

    $service = app(SurveyResponseService::class);

    $response = $service->createDraft(
        questionnaireId: $questionnaire->id,
        surveyVersionId: $version->id,
        userId: $user->id,
    );

    $service->start($response);

    $service->saveAnswer(
        response: $response,
        question: $question,
        value: 'Respuesta de prueba',
    );

    $this->assertDatabaseHas('answers', [
        'survey_response_id' => $response->id,
        'question_id' => $question->id,
        'text_value' => 'Respuesta de prueba',
    ]);
});

//--------------------------------------------

it('saves a numeric answer in an in-progress survey response', function () {
    $questionnaire = Questionnaire::factory()->create();

    $version = SurveyVersion::factory()->create([
        'questionnaire_id' => $questionnaire->id,
    ]);

    $section = Section::factory()->create([
        'survey_version_id' => $version->id,
    ]);

    $questionType = QuestionType::factory()->create([
        'code' => 'number',
    ]);

    $question = Question::factory()->create([
        'section_id' => $section->id,
        'question_type_id' => $questionType->id,
        'code' => 'TEST_NUMBER_001',
    ]);

    $user = User::factory()->create();

    $service = app(SurveyResponseService::class);

    $response = $service->createDraft(
        questionnaireId: $questionnaire->id,
        surveyVersionId: $version->id,
        userId: $user->id,
    );

    $service->start($response);

    $service->saveAnswer(
        response: $response,
        question: $question,
        value: 25,
    );

    $this->assertDatabaseHas('answers', [
        'survey_response_id' => $response->id,
        'question_id' => $question->id,
        'number_value' => '25.0000',
    ]);
});

//----------------------------------------------

it('saves a single choice answer in an in-progress survey response', function () {
    $questionnaire = Questionnaire::factory()->create();

    $version = SurveyVersion::factory()->create([
        'questionnaire_id' => $questionnaire->id,
    ]);

    $section = Section::factory()->create([
        'survey_version_id' => $version->id,
    ]);

    $questionType = QuestionType::factory()->create([
        'code' => 'single_choice',
    ]);

    $question = Question::factory()->create([
        'section_id' => $section->id,
        'question_type_id' => $questionType->id,
        'code' => 'TEST_SINGLE_001',
    ]);

    $option = $question->options()->create([
        'value' => 'opcion_1',
        'label' => 'Opción 1',
        'sort_order' => 1,
        'active' => true,
    ]);

    $user = User::factory()->create();

    $service = app(SurveyResponseService::class);

    $response = $service->createDraft(
        questionnaireId: $questionnaire->id,
        surveyVersionId: $version->id,
        userId: $user->id,
    );

    $service->start($response);

    $service->saveAnswer(
        response: $response,
        question: $question,
        value: $option->id,
    );

    $this->assertDatabaseHas('answers', [
        'survey_response_id' => $response->id,
        'question_id' => $question->id,
        'option_id' => $option->id,
    ]);
});

//----------------------------------------------

it('saves multiple choice answers in an in-progress survey response', function () {
    $questionnaire = Questionnaire::factory()->create();

    $version = SurveyVersion::factory()->create([
        'questionnaire_id' => $questionnaire->id,
    ]);

    $section = Section::factory()->create([
        'survey_version_id' => $version->id,
    ]);

    $questionType = QuestionType::factory()->create([
        'code' => 'multiple_choice',
    ]);

    $question = Question::factory()->create([
        'section_id' => $section->id,
        'question_type_id' => $questionType->id,
        'code' => 'TEST_MULTIPLE_001',
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

    $service = app(SurveyResponseService::class);

    $response = $service->createDraft(
        questionnaireId: $questionnaire->id,
        surveyVersionId: $version->id,
        userId: $user->id,
    );

    $service->start($response);

    $service->saveAnswer(
        response: $response,
        question: $question,
        value: [$option1->id, $option3->id],
    );

    $answer = Answer::where('survey_response_id', $response->id)
        ->where('question_id', $question->id)
        ->first();

    expect($answer)
        ->not->toBeNull();

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

//--------------------------------------------

it('updates an existing answer instead of creating a duplicate', function () {
    $questionnaire = Questionnaire::factory()->create();

    $version = SurveyVersion::factory()->create([
        'questionnaire_id' => $questionnaire->id,
    ]);

    $section = Section::factory()->create([
        'survey_version_id' => $version->id,
    ]);

    $questionType = QuestionType::factory()->create([
        'code' => 'text',
    ]);

    $question = Question::factory()->create([
        'section_id' => $section->id,
        'question_type_id' => $questionType->id,
        'code' => 'TEST_UPDATE_001',
    ]);

    $user = User::factory()->create();

    $service = app(SurveyResponseService::class);

    $response = $service->createDraft(
        questionnaireId: $questionnaire->id,
        surveyVersionId: $version->id,
        userId: $user->id,
    );

    $service->start($response);

    $service->saveAnswer(
        response: $response,
        question: $question,
        value: 'Juan',
    );

    $firstAnswer = Answer::where('survey_response_id', $response->id)
        ->where('question_id', $question->id)
        ->first();

    expect($firstAnswer)
        ->not->toBeNull()
        ->and($firstAnswer->text_value)
        ->toBe('Juan');

    $service->saveAnswer(
        response: $response,
        question: $question,
        value: 'Pedro',
    );

    $secondAnswer = Answer::where('survey_response_id', $response->id)
        ->where('question_id', $question->id)
        ->first();

    expect($secondAnswer->id)
        ->toBe($firstAnswer->id)
        ->and($secondAnswer->text_value)
        ->toBe('Pedro');

    expect(
        Answer::where('survey_response_id', $response->id)
            ->where('question_id', $question->id)
            ->count()
    )->toBe(1);
});

//----------------------------------------

it('replaces multiple choice selections when updating an existing answer', function () {
    $questionnaire = Questionnaire::factory()->create();

    $version = SurveyVersion::factory()->create([
        'questionnaire_id' => $questionnaire->id,
    ]);

    $section = Section::factory()->create([
        'survey_version_id' => $version->id,
    ]);

    $questionType = QuestionType::factory()->create([
        'code' => 'multiple_choice',
    ]);

    $question = Question::factory()->create([
        'section_id' => $section->id,
        'question_type_id' => $questionType->id,
        'code' => 'TEST_MULTIPLE_UPDATE_001',
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

    $service = app(SurveyResponseService::class);

    $response = $service->createDraft(
        questionnaireId: $questionnaire->id,
        surveyVersionId: $version->id,
        userId: $user->id,
    );

    $service->start($response);

    $service->saveAnswer(
        response: $response,
        question: $question,
        value: [$option1->id, $option2->id],
    );

    $answer = Answer::where('survey_response_id', $response->id)
        ->where('question_id', $question->id)
        ->first();

    expect($answer)
        ->not->toBeNull();

    $service->saveAnswer(
        response: $response,
        question: $question,
        value: [$option2->id, $option3->id],
    );

    $answer->refresh();

    expect(
        Answer::where('survey_response_id', $response->id)
            ->where('question_id', $question->id)
            ->count()
    )->toBe(1);

    expect($answer->selectedOptions)
        ->toHaveCount(2);

    $this->assertDatabaseMissing('answer_options', [
        'answer_id' => $answer->id,
        'question_option_id' => $option1->id,
    ]);

    $this->assertDatabaseHas('answer_options', [
        'answer_id' => $answer->id,
        'question_option_id' => $option2->id,
    ]);

    $this->assertDatabaseHas('answer_options', [
        'answer_id' => $answer->id,
        'question_option_id' => $option3->id,
    ]);
});

//---------------------------------
it('ignores a required conditional question when its condition is not met', function () {
    $questionnaire = Questionnaire::factory()->create();

    $version = SurveyVersion::factory()->create([
        'questionnaire_id' => $questionnaire->id,
    ]);

    $section = Section::factory()->create([
        'survey_version_id' => $version->id,
    ]);

    $singleChoiceType = QuestionType::factory()->create([
        'code' => 'single_choice',
    ]);

    $textType = QuestionType::factory()->create([
        'code' => 'text',
    ]);

    $dependency = Question::factory()->create([
        'section_id' => $section->id,
        'question_type_id' => $singleChoiceType->id,
        'code' => 'TEST_CONDITION_001',
        'required' => true,
        'active' => true,
    ]);

    $conditionalQuestion = Question::factory()->create([
        'section_id' => $section->id,
        'question_type_id' => $textType->id,
        'code' => 'TEST_CONDITION_002',
        'required' => true,
        'active' => true,
    ]);

    $normalOption = $dependency->options()->create([
        'value' => 'normal',
        'label' => 'Normal',
        'sort_order' => 1,
        'active' => true,
    ]);

    $otrosOption = $dependency->options()->create([
        'value' => 'otros',
        'label' => 'Otros',
        'sort_order' => 2,
        'active' => true,
    ]);

    $conditionalQuestion->dependencies()->create([
        'depends_on_question_id' => $dependency->id,
        'operator' => 'equals',
        'expected_value' => 'otros',
        'active' => true,
    ]);

    $user = User::factory()->create();

    $service = app(SurveyResponseService::class);

    $response = $service->createDraft(
        questionnaireId: $questionnaire->id,
        surveyVersionId: $version->id,
        userId: $user->id,
    );

    $service->start($response);

    $service->saveAnswer(
        response: $response,
        question: $dependency,
        value: $normalOption->id,
    );

    $validator = app(\App\Services\SurveyResponseValidator::class);

    expect($validator->validate($response))
        ->not->toHaveKey($conditionalQuestion->code);
});

//------------------------------------------------------------------

it('requires a visible conditional question when its condition is met', function () {
    $questionnaire = Questionnaire::factory()->create();

    $version = SurveyVersion::factory()->create([
        'questionnaire_id' => $questionnaire->id,
    ]);

    $section = Section::factory()->create([
        'survey_version_id' => $version->id,
    ]);

    $singleChoiceType = QuestionType::factory()->create([
        'code' => 'single_choice',
    ]);

    $textType = QuestionType::factory()->create([
        'code' => 'text',
    ]);

    $dependency = Question::factory()->create([
        'section_id' => $section->id,
        'question_type_id' => $singleChoiceType->id,
        'code' => 'TEST_CONDITION_003',
        'required' => true,
        'active' => true,
    ]);

    $conditionalQuestion = Question::factory()->create([
        'section_id' => $section->id,
        'question_type_id' => $textType->id,
        'code' => 'TEST_CONDITION_004',
        'required' => true,
        'active' => true,
    ]);

    $otrosOption = $dependency->options()->create([
        'value' => 'otros',
        'label' => 'Otros',
        'sort_order' => 1,
        'active' => true,
    ]);

    $conditionalQuestion->dependencies()->create([
        'depends_on_question_id' => $dependency->id,
        'depends_on_option_id' => $otrosOption->id,
        'operator' => 'equals',
        'expected_value' => 'otros',
        'active' => true,
    ]);

    $user = User::factory()->create();

    $service = app(SurveyResponseService::class);

    $response = $service->createDraft(
        questionnaireId: $questionnaire->id,
        surveyVersionId: $version->id,
        userId: $user->id,
    );

    $service->start($response);

    $service->saveAnswer(
        response: $response,
        question: $dependency,
        value: $otrosOption->id,
    );

    $validator = app(\App\Services\SurveyResponseValidator::class);

    expect($validator->validate($response))
        ->toHaveKey($conditionalQuestion->code)
        ->and($validator->validate($response)[$conditionalQuestion->code])
        ->toBe('Esta pregunta es obligatoria.'); 
});

//----------------------------------------------------------------
it('completes a valid in-progress survey response', function () {
    $questionnaire = Questionnaire::factory()->create();

    $version = SurveyVersion::factory()->create([
        'questionnaire_id' => $questionnaire->id,
    ]);

    $section = Section::factory()->create([
        'survey_version_id' => $version->id,
    ]);

    $questionType = QuestionType::factory()->create([
        'code' => 'text',
    ]);

    $question = Question::factory()->create([
        'section_id' => $section->id,
        'question_type_id' => $questionType->id,
        'code' => 'TEST_COMPLETE_001',
        'required' => true,
        'active' => true,
    ]);

    $user = User::factory()->create();

    $service = app(SurveyResponseService::class);

    $response = $service->createDraft(
        questionnaireId: $questionnaire->id,
        surveyVersionId: $version->id,
        userId: $user->id,
    );

    $service->start($response);

    $service->saveAnswer(
        response: $response,
        question: $question,
        value: 'Respuesta válida',
    );

    $completedResponse = $service->complete($response);

    expect($completedResponse->status)
        ->toBe(SurveyResponseStatus::COMPLETED)
        ->and($completedResponse->completed_at)
        ->not->toBeNull();

    $this->assertDatabaseHas('survey_responses', [
        'id' => $response->id,
        'status' => SurveyResponseStatus::COMPLETED->value,
    ]);
});

//---------------------------------------------------------------------------

it('does not complete a survey with unanswered required questions', function () {
    $questionnaire = Questionnaire::factory()->create();

    $version = SurveyVersion::factory()->create([
        'questionnaire_id' => $questionnaire->id,
    ]);

    $section = Section::factory()->create([
        'survey_version_id' => $version->id,
    ]);

    $questionType = QuestionType::factory()->create([
        'code' => 'text',
    ]);

    Question::factory()->create([
        'section_id' => $section->id,
        'question_type_id' => $questionType->id,
        'code' => 'TEST_INCOMPLETE_001',
        'required' => true,
        'active' => true,
    ]);

    $user = User::factory()->create();

    $service = app(SurveyResponseService::class);

    $response = $service->createDraft(
        questionnaireId: $questionnaire->id,
        surveyVersionId: $version->id,
        userId: $user->id,
    );

    $service->start($response);

    expect(fn () => $service->complete($response))
        ->toThrow(SurveyValidationException::class);

    $response->refresh();

    expect($response->status)
        ->toBe(SurveyResponseStatus::IN_PROGRESS);

    $this->assertDatabaseHas('survey_responses', [
        'id' => $response->id,
        'status' => SurveyResponseStatus::IN_PROGRESS->value,
    ]);
});

//-----------------------------------------------

it('completes a survey when a required conditional question is answered', function () {
    $questionnaire = Questionnaire::factory()->create();

    $version = SurveyVersion::factory()->create([
        'questionnaire_id' => $questionnaire->id,
    ]);

    $section = Section::factory()->create([
        'survey_version_id' => $version->id,
    ]);

    $singleChoiceType = QuestionType::factory()->create([
        'code' => 'single_choice',
    ]);

    $textType = QuestionType::factory()->create([
        'code' => 'text',
    ]);

    $dependency = Question::factory()->create([
        'section_id' => $section->id,
        'question_type_id' => $singleChoiceType->id,
        'code' => 'TEST_COMPLETE_CONDITION_001',
        'required' => true,
        'active' => true,
    ]);

    $conditionalQuestion = Question::factory()->create([
        'section_id' => $section->id,
        'question_type_id' => $textType->id,
        'code' => 'TEST_COMPLETE_CONDITION_002',
        'required' => true,
        'active' => true,
    ]);

    $otrosOption = $dependency->options()->create([
        'value' => 'otros',
        'label' => 'Otros',
        'sort_order' => 1,
        'active' => true,
    ]);

    $conditionalQuestion->dependencies()->create([
        'depends_on_question_id' => $dependency->id,
        'depends_on_option_id' => $otrosOption->id,
        'operator' => 'equals',
        'expected_value' => 'otros',
        'active' => true,
    ]);

    $user = User::factory()->create();

    $service = app(SurveyResponseService::class);

    $response = $service->createDraft(
        questionnaireId: $questionnaire->id,
        surveyVersionId: $version->id,
        userId: $user->id,
    );

    $service->start($response);

    $service->saveAnswer(
        response: $response,
        question: $dependency,
        value: $otrosOption->id,
    );

    $service->saveAnswer(
        response: $response,
        question: $conditionalQuestion,
        value: 'Especificación de prueba',
    );

    $completedResponse = $service->complete($response);

    expect($completedResponse->status)
        ->toBe(SurveyResponseStatus::COMPLETED)
        ->and($completedResponse->completed_at)
        ->not->toBeNull();

    $this->assertDatabaseHas('survey_responses', [
        'id' => $response->id,
        'status' => SurveyResponseStatus::COMPLETED->value,
    ]);
});
//----------------------------------------------

it('allows completing an already completed survey response', function () {
    $questionnaire = Questionnaire::factory()->create();

    $version = SurveyVersion::factory()->create([
        'questionnaire_id' => $questionnaire->id,
    ]);

    $section = Section::factory()->create([
        'survey_version_id' => $version->id,
    ]);

    $questionType = QuestionType::factory()->create([
        'code' => 'text',
    ]);

    $question = Question::factory()->create([
        'section_id' => $section->id,
        'question_type_id' => $questionType->id,
        'code' => 'TEST_IDEMPOTENT_001',
        'required' => true,
        'active' => true,
    ]);

    $user = User::factory()->create();

    $service = app(SurveyResponseService::class);

    $response = $service->createDraft(
        questionnaireId: $questionnaire->id,
        surveyVersionId: $version->id,
        userId: $user->id,
    );

    $service->start($response);

    $service->saveAnswer(
        response: $response,
        question: $question,
        value: 'Respuesta válida',
    );

    $firstCompletion = $service->complete($response);

    $completedAt = $firstCompletion->completed_at;

    $secondCompletion = $service->complete($response);

    expect($secondCompletion->status)
        ->toBe(SurveyResponseStatus::COMPLETED)
        ->and($secondCompletion->completed_at)
        ->not->toBeNull()
        ->and($secondCompletion->completed_at->equalTo($completedAt))
        ->toBeTrue();
});

//------------------------------------------------------------

it('allows editing answers of a completed survey response', function () {
    $questionnaire = Questionnaire::factory()->create();

    $version = SurveyVersion::factory()->create([
        'questionnaire_id' => $questionnaire->id,
    ]);

    $section = Section::factory()->create([
        'survey_version_id' => $version->id,
    ]);

    $questionType = QuestionType::factory()->create([
        'code' => 'text',
    ]);

    $question = Question::factory()->create([
        'section_id' => $section->id,
        'question_type_id' => $questionType->id,
        'code' => 'TEST_COMPLETED_EDIT_001',
        'required' => true,
        'active' => true,
    ]);

    $user = User::factory()->create();

    $service = app(SurveyResponseService::class);

    $response = $service->createDraft(
        questionnaireId: $questionnaire->id,
        surveyVersionId: $version->id,
        userId: $user->id,
    );

    $service->start($response);

    $service->saveAnswer(
        response: $response,
        question: $question,
        value: 'Juan',
    );

    $service->complete($response);

    $response->refresh();

    expect($response->status)
        ->toBe(SurveyResponseStatus::COMPLETED);

    $service->saveAnswer(
        response: $response,
        question: $question,
        value: 'Pedro',
    );

    $answer = Answer::where('survey_response_id', $response->id)
        ->where('question_id', $question->id)
        ->first();

    expect($answer)
        ->not->toBeNull()
        ->and($answer->text_value)
        ->toBe('Pedro');

    expect(
        Answer::where('survey_response_id', $response->id)
            ->where('question_id', $question->id)
            ->count()
    )->toBe(1);

    $response->refresh();

    expect($response->status)
        ->toBe(SurveyResponseStatus::COMPLETED);
});

//------------------------------------------------------

it('cancels an in-progress survey response', function () {
    $questionnaire = Questionnaire::factory()->create();

    $version = SurveyVersion::factory()->create([
        'questionnaire_id' => $questionnaire->id,
    ]);

    $user = User::factory()->create();

    $service = app(SurveyResponseService::class);

    $response = $service->createDraft(
        questionnaireId: $questionnaire->id,
        surveyVersionId: $version->id,
        userId: $user->id,
    );

    $service->start($response);

    expect($response->status)
        ->toBe(SurveyResponseStatus::IN_PROGRESS);

    $cancelledResponse = $service->cancel($response);

    expect($cancelledResponse->status)
        ->toBe(SurveyResponseStatus::CANCELLED);

    $this->assertDatabaseHas('survey_responses', [
        'id' => $response->id,
        'status' => SurveyResponseStatus::CANCELLED->value,
    ]);
});

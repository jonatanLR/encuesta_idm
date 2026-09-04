<?php

use App\Models\Answer;
use App\Models\Community;
use App\Models\Question;
use App\Models\QuestionType;
use App\Models\Questionnaire;
use App\Models\QuestionOption;
use App\Models\Section;
use App\Models\SurveyResponse;
use App\Models\SurveyVersion;
use App\Models\User;
use App\Services\SurveyResponseService;
use App\Models\AnswerOption;

it('belongs to a survey response', function () {
    $questionnaire = Questionnaire::factory()->create();

    $version = SurveyVersion::factory()->create([
        'questionnaire_id' => $questionnaire->id,
    ]);

    $user = User::factory()->create();

    $service = app(SurveyResponseService::class);

    $response = $service->createDraft(
        $questionnaire->id,
        $version->id,
        $user->id
    );

    $section = Section::factory()->create([
        'survey_version_id' => $version->id,
    ]);

    $questionType = QuestionType::factory()->create([
        'code' => 'text',
    ]);

    $question = Question::factory()->create([
        'section_id' => $section->id,
        'question_type_id' => $questionType->id,
        'code' => 'TEST_ANSWER',
    ]);

    $answer = Answer::create([
        'survey_response_id' => $response->id,
        'question_id' => $question->id,
        'text_value' => 'Respuesta de prueba',
    ]);

    expect($answer->surveyResponse)
        ->toBeInstanceOf(SurveyResponse::class)
        ->and($answer->surveyResponse->id)
        ->toBe($response->id);
});

//--------------------------------------------------------

it('belongs to a question', function () {
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
        'code' => 'TEST_QUESTION',
    ]);

    $user = User::factory()->create();

    $service = app(SurveyResponseService::class);

    $response = $service->createDraft(
        $questionnaire->id,
        $version->id,
        $user->id
    );

    $answer = Answer::create([
        'survey_response_id' => $response->id,
        'question_id' => $question->id,
        'text_value' => 'Respuesta de prueba',
    ]);

    expect($answer->question)
        ->toBeInstanceOf(Question::class)
        ->and($answer->question->id)
        ->toBe($question->id);
});

//-------------------------------------------------------------------

it('belongs to a question option through option_id', function () {
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
        'code' => 'TEST_SINGLE',
    ]);

    $option = $question->options()->create([
        'label' => 'Opción de prueba',
        'value' => 'opcion_prueba',
        'sort_order' => 1,
        'active' => true,
    ]);

    $user = User::factory()->create();

    $service = app(SurveyResponseService::class);

    $response = $service->createDraft(
        $questionnaire->id,
        $version->id,
        $user->id
    );

    $answer = Answer::create([
        'survey_response_id' => $response->id,
        'question_id' => $question->id,
        'option_id' => $option->id,
    ]);

    expect($answer->option)
        ->toBeInstanceOf(QuestionOption::class)
        ->and($answer->option->id)
        ->toBe($option->id);
});

//-----------------------------------------------------------

it('has many selected options', function () {
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
        'code' => 'TEST_MULTIPLE',
    ]);

    $optionOne = $question->options()->create([
        'label' => 'Opción uno',
        'value' => 'opcion_uno',
        'sort_order' => 1,
        'active' => true,
    ]);

    $optionTwo = $question->options()->create([
        'label' => 'Opción dos',
        'value' => 'opcion_dos',
        'sort_order' => 2,
        'active' => true,
    ]);

    $user = User::factory()->create();

    $service = app(SurveyResponseService::class);

    $response = $service->createDraft(
        $questionnaire->id,
        $version->id,
        $user->id
    );

    $answer = Answer::create([
        'survey_response_id' => $response->id,
        'question_id' => $question->id,
    ]);

    $answer->selectedOptions()->create([
        'question_option_id' => $optionOne->id,
    ]);

    $answer->selectedOptions()->create([
        'question_option_id' => $optionTwo->id,
    ]);

    $selectedOptions = $answer->selectedOptions;

    expect($selectedOptions)->toHaveCount(2)
        ->and($selectedOptions->pluck('question_option_id')->all())
        ->toContain($optionOne->id, $optionTwo->id);
});
//------------------------------------------------------

it('casts number value to four decimals', function () {
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
        'code' => 'TEST_NUMBER',
    ]);

    $user = User::factory()->create();

    $service = app(SurveyResponseService::class);

    $response = $service->createDraft(
        $questionnaire->id,
        $version->id,
        $user->id
    );

    $answer = Answer::create([
        'survey_response_id' => $response->id,
        'question_id' => $question->id,
        'number_value' => '12.3000',
    ]);

    $answer->refresh();

    expect($answer->number_value)->toBe('12.3000');
});

//-------------------------------------------------------------

it('casts date value as a date', function () {
    $questionnaire = Questionnaire::factory()->create();

    $version = SurveyVersion::factory()->create([
        'questionnaire_id' => $questionnaire->id,
    ]);

    $section = Section::factory()->create([
        'survey_version_id' => $version->id,
    ]);

    $questionType = QuestionType::factory()->create([
        'code' => 'date',
    ]);

    $question = Question::factory()->create([
        'section_id' => $section->id,
        'question_type_id' => $questionType->id,
        'code' => 'TEST_DATE',
    ]);

    $user = User::factory()->create();

    $service = app(SurveyResponseService::class);

    $response = $service->createDraft(
        $questionnaire->id,
        $version->id,
        $user->id
    );

    $answer = Answer::create([
        'survey_response_id' => $response->id,
        'question_id' => $question->id,
        'date_value' => '2026-09-03',
    ]);

    $answer->refresh();

    expect($answer->date_value)
        ->toBeInstanceOf(\Illuminate\Support\Carbon::class)
        ->and($answer->date_value->format('Y-m-d'))
        ->toBe('2026-09-03');
});

//----------------------------------------------------------

it('preserves nullable answer values as null', function () {
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
        'code' => 'TEST_NULLABLE',
    ]);

    $user = User::factory()->create();

    $service = app(SurveyResponseService::class);

    $response = $service->createDraft(
        $questionnaire->id,
        $version->id,
        $user->id
    );

    $answer = Answer::create([
        'survey_response_id' => $response->id,
        'question_id' => $question->id,
        'number_value' => null,
        'date_value' => null,
        'option_id' => null,
    ]);

    $answer->refresh();

    expect($answer->number_value)->toBeNull()
        ->and($answer->date_value)->toBeNull()
        ->and($answer->option_id)->toBeNull();
});

//---------------------------------------------------------

it('deletes selected options when the answer is deleted', function () {
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
        'code' => 'TEST_CASCADE',
    ]);

    $option = $question->options()->create([
        'label' => 'Opción de prueba',
        'value' => 'opcion_prueba',
        'sort_order' => 1,
        'active' => true,
    ]);

    $user = User::factory()->create();

    $service = app(SurveyResponseService::class);

    $response = $service->createDraft(
        $questionnaire->id,
        $version->id,
        $user->id
    );

    $answer = Answer::create([
        'survey_response_id' => $response->id,
        'question_id' => $question->id,
    ]);

    $answerOption = $answer->selectedOptions()->create([
        'question_option_id' => $option->id,
    ]);

    expect($answerOption->exists)->toBeTrue();

    $answer->delete();

    expect(
        \App\Models\AnswerOption::find($answerOption->id)
    )->toBeNull();
});
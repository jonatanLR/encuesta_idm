<?php

use App\Models\Community;
use App\Models\Question;
use App\Models\Questionnaire;
use App\Models\QuestionType;
use App\Models\Section;
use App\Models\SurveyVersion;
use App\Models\User;
use App\Services\SurveyResponseService;
use App\Services\SurveyResponseValidator;
// use Illuminate\Database\QueryException;

it('ignores inactive required questions during validation', function () {
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
        // 'survey_version_id' => $version->id,
        'section_id' => $section->id,
        'question_type_id' => $questionType->id,
        'code' => 'INACTIVE_REQUIRED',
        'required' => true,
        'active' => false,
    ]);

    $user = User::factory()->create();

    $service = app(SurveyResponseService::class);

    $response = $service->createDraft(
        $questionnaire->id,
        $version->id,
        $user->id
    );

    $validator = app(SurveyResponseValidator::class);

    $errors = $validator->validate($response);

    expect($errors)->toBe([]);
});

//--------------------------------------------------------------------------
it('ignores required questions from another survey version', function () {
    $questionnaire = Questionnaire::factory()->create();

    $version = SurveyVersion::factory()->create([
        'questionnaire_id' => $questionnaire->id,
    ]);

    $otherVersion = SurveyVersion::factory()->create([
        'questionnaire_id' => $questionnaire->id,
        'version' => 2.0,
    ]);;

    $section = Section::factory()->create([
        'survey_version_id' => $version->id,
    ]);

    $otherSection = Section::factory()->create([
        'survey_version_id' => $otherVersion->id,
    ]);

    $questionType = QuestionType::factory()->create([
        'code' => 'text',
    ]);

    Question::factory()->create([
        'section_id' => $otherSection->id,
        'question_type_id' => $questionType->id,
        'code' => 'OTHER_VERSION_REQUIRED',
        'required' => true,
        'active' => true,
    ]);

    $user = User::factory()->create();

    $service = app(SurveyResponseService::class);

    $response = $service->createDraft(
        $questionnaire->id,
        $version->id,
        $user->id
    );

    $validator = app(SurveyResponseValidator::class);

    $errors = $validator->validate($response);

    expect($errors)->toBe([]);
});

//----------------------------------------------------------------------
it('returns the exact validation error for an unanswered required question', function () {
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
        'code' => 'REQUIRED_NAME',
        'required' => true,
        'active' => true,
    ]);

    $user = User::factory()->create();

    $service = app(SurveyResponseService::class);

    $response = $service->createDraft(
        $questionnaire->id,
        $version->id,
        $user->id
    );

    $validator = app(SurveyResponseValidator::class);

    $errors = $validator->validate($response);

    expect($errors)->toBe([
        'REQUIRED_NAME' => 'Esta pregunta es obligatoria.',
    ]);
});

//---------------------------------------------------------------------------------

it('returns false when the response has validation errors', function () {
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
        'code' => 'REQUIRED_FIELD',
        'required' => true,
        'active' => true,
    ]);

    $user = User::factory()->create();

    $service = app(SurveyResponseService::class);

    $response = $service->createDraft(
        $questionnaire->id,
        $version->id,
        $user->id
    );

    $validator = app(SurveyResponseValidator::class);

    expect($validator->isValid($response))->toBeFalse();
});

//-----------------------------------------------------------------------

it('returns true when the response is valid', function () {
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
        'code' => 'REQUIRED_FIELD',
        'required' => true,
        'active' => true,
    ]);

    $user = User::factory()->create();

    $service = app(SurveyResponseService::class);

    $response = $service->createDraft(
        $questionnaire->id,
        $version->id,
        $user->id
    );

    $service->start($response);

    $service->saveAnswer($response, $question, 'Respuesta válida');

    $validator = app(SurveyResponseValidator::class);

    expect($validator->isValid($response))->toBeTrue();
});

//---------------------------------------------------------


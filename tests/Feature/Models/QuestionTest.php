<?php

use App\Models\Answer;
use App\Models\Question;
use App\Models\QuestionCondition;
use App\Models\QuestionType;
use App\Models\Questionnaire;
use App\Models\Section;
use App\Models\SurveyVersion;
use App\Models\User;
use App\Services\SurveyResponseService;
use Illuminate\Database\QueryException;

it('belongs to a section', function () {
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
        'code' => 'TEST_SECTION',
    ]);

    expect($question->section)
        ->toBeInstanceOf(Section::class)
        ->and($question->section->id)
        ->toBe($section->id);
});

//---------------------------------------------

it('belongs to a question type', function () {
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
        'code' => 'TEST_TYPE',
    ]);

    expect($question->questionType)
        ->toBeInstanceOf(QuestionType::class)
        ->and($question->questionType->id)
        ->toBe($questionType->id);
});

//-------------------------------------------------

it('returns options ordered by sort order', function () {
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
        'code' => 'TEST_OPTIONS',
    ]);

    $optionLast = $question->options()->create([
        'label' => 'Opción C',
        'value' => 'c',
        'sort_order' => 3,
        'active' => true,
    ]);

    $optionFirst = $question->options()->create([
        'label' => 'Opción A',
        'value' => 'a',
        'sort_order' => 1,
        'active' => true,
    ]);

    $optionMiddle = $question->options()->create([
        'label' => 'Opción B',
        'value' => 'b',
        'sort_order' => 2,
        'active' => true,
    ]);

    $results = $question->options;

    expect($results->pluck('id')->all())
        ->toBe([
            $optionFirst->id,
            $optionMiddle->id,
            $optionLast->id,
        ]);
});

//----------------------------------------------------

it('returns the conditions belonging to the question', function () {
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
        'code' => 'TEST_CONDITIONS',
    ]);

    $dependencyQuestion = Question::factory()->create([
        'section_id' => $section->id,
        'question_type_id' => $questionType->id,
        'code' => 'TEST_DEPENDENCY',
    ]);

    $condition = QuestionCondition::create([
        'question_id' => $question->id,
        'depends_on_question_id' => $dependencyQuestion->id,
        'operator' => 'equals',
        'expected_value' => 'test',
        'active' => true,
    ]);

    $results = $question->conditions;

    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($condition->id);
});

//-----------------------------------------------------------

it('returns dependent question conditions', function () {
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
        'code' => 'TEST_PARENT',
    ]);

    $dependentQuestion = Question::factory()->create([
        'section_id' => $section->id,
        'question_type_id' => $questionType->id,
        'code' => 'TEST_DEPENDENT',
    ]);

    $condition = QuestionCondition::create([
        'question_id' => $dependentQuestion->id,
        'depends_on_question_id' => $question->id,
        'operator' => 'equals',
        'expected_value' => 'test',
        'active' => true,
    ]);

    $results = $question->dependentQuestions;

    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($condition->id);
});

//---------------------------------------------------------------
it('returns dependencies belonging to the question', function () {
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
        'code' => 'TEST_DEPENDENCY',
    ]);

    $otherQuestion = Question::factory()->create([
        'section_id' => $section->id,
        'question_type_id' => $questionType->id,
        'code' => 'TEST_OTHER',
    ]);

    $dependencyQuestion = Question::factory()->create([
        'section_id' => $section->id,
        'question_type_id' => $questionType->id,
        'code' => 'TEST_TRIGGER',
    ]);

    $condition = QuestionCondition::create([
        'question_id' => $question->id,
        'depends_on_question_id' => $dependencyQuestion->id,
        'operator' => 'equals',
        'expected_value' => 'test',
        'active' => true,
    ]);

    QuestionCondition::create([
        'question_id' => $otherQuestion->id,
        'depends_on_question_id' => $dependencyQuestion->id,
        'operator' => 'equals',
        'expected_value' => 'test',
        'active' => true,
    ]);

    $results = $question->dependencies;

    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($condition->id);
});

//----------------------------------------------------------

it('has many answers belonging to the question', function () {
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
        'code' => 'TEST_ANSWERS',
    ]);

    $otherQuestion = Question::factory()->create([
        'section_id' => $section->id,
        'question_type_id' => $questionType->id,
        'code' => 'TEST_OTHER_ANSWERS',
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
        'text_value' => 'Respuesta correcta',
    ]);

    Answer::create([
        'survey_response_id' => $response->id,
        'question_id' => $otherQuestion->id,
        'text_value' => 'Otra respuesta',
    ]);

    $results = $question->answers;

    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($answer->id);
});

//----------------------------------------------------------

it('casts required and active as booleans', function () {
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
        'code' => 'TEST_BOOLEAN_CASTS',
        'required' => 1,
        'active' => 0,
    ]);

    $question->refresh();

    expect($question->required)
        ->toBeTrue()
        ->and($question->active)
        ->toBeFalse();
});

//----------------------------------------------------------

it('enforces unique question code within the same section', function () {
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
        'code' => 'DUPLICATE_CODE',
    ]);

    expect(fn() => Question::factory()->create([
        'section_id' => $section->id,
        'question_type_id' => $questionType->id,
        'code' => 'DUPLICATE_CODE',
    ]))->toThrow(function (QueryException $exception) {
        expect($exception->getMessage())
            ->toContain('UNIQUE constraint failed: questions.section_id, questions.code');
    });
});

//--------------------------------------------------------

it('allows the same question code in different sections', function () {
    $questionnaire = Questionnaire::factory()->create();

    $version = SurveyVersion::factory()->create([
        'questionnaire_id' => $questionnaire->id,
    ]);

    $sectionOne = Section::factory()->create([
        'survey_version_id' => $version->id,
    ]);

    $sectionTwo = Section::factory()->create([
        'survey_version_id' => $version->id,
    ]);

    $questionType = QuestionType::factory()->create([
        'code' => 'text',
    ]);

    $questionOne = Question::factory()->create([
        'section_id' => $sectionOne->id,
        'question_type_id' => $questionType->id,
        'code' => 'SAME_CODE',
    ]);

    $questionTwo = Question::factory()->create([
        'section_id' => $sectionTwo->id,
        'question_type_id' => $questionType->id,
        'code' => 'SAME_CODE',
    ]);

    expect($questionOne->code)
        ->toBe('SAME_CODE')
        ->and($questionTwo->code)
        ->toBe('SAME_CODE')
        ->and($questionOne->section_id)
        ->not->toBe($questionTwo->section_id);
});

//------------------------------------------------------

it('deletes questions when their section is deleted', function () {
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
        'code' => 'TEST_CASCADE',
    ]);

    $questionId = $question->id;

    $section->delete();

    expect(Question::find($questionId))->toBeNull();
});

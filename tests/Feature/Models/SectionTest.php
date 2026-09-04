<?php

use App\Models\Questionnaire;
use App\Models\Section;
use App\Models\SurveyVersion;
use App\Models\QuestionType;

it('belongs to a survey version', function () {
    $questionnaire = Questionnaire::factory()->create();

    $version = SurveyVersion::factory()->create([
        'questionnaire_id' => $questionnaire->id,
    ]);

    $section = Section::factory()->create([
        'survey_version_id' => $version->id,
    ]);

    expect($section->surveyVersion)
        ->toBeInstanceOf(SurveyVersion::class)
        ->and($section->surveyVersion->id)
        ->toBe($version->id);
});

//-----------------------------------------------

it('resolves the parent section', function () {
    $questionnaire = Questionnaire::factory()->create();

    $version = SurveyVersion::factory()->create([
        'questionnaire_id' => $questionnaire->id,
    ]);

    $parent = Section::factory()->create([
        'survey_version_id' => $version->id,
        'parent_id' => null,
    ]);

    $child = Section::factory()->create([
        'survey_version_id' => $version->id,
        'parent_id' => $parent->id,
    ]);

    expect($child->parent)
        ->toBeInstanceOf(Section::class)
        ->and($child->parent->id)
        ->toBe($parent->id);
});

//----------------------------------------------

it('resolves child sections ordered by sort order', function () {
    $questionnaire = Questionnaire::factory()->create();

    $version = SurveyVersion::factory()->create([
        'questionnaire_id' => $questionnaire->id,
    ]);

    $parent = Section::factory()->create([
        'survey_version_id' => $version->id,
        'parent_id' => null,
    ]);

    $secondChild = Section::factory()->create([
        'survey_version_id' => $version->id,
        'parent_id' => $parent->id,
        'sort_order' => 2,
    ]);

    $firstChild = Section::factory()->create([
        'survey_version_id' => $version->id,
        'parent_id' => $parent->id,
        'sort_order' => 1,
    ]);

    expect($parent->children)
        ->toHaveCount(2)
        ->and($parent->children->first()->id)
        ->toBe($firstChild->id)
        ->and($parent->children->last()->id)
        ->toBe($secondChild->id);
});

//--------------------------------------------

it('resolves questions ordered by sort order', function () {
    $questionnaire = Questionnaire::factory()->create();

    $version = SurveyVersion::factory()->create([
        'questionnaire_id' => $questionnaire->id,
    ]);

    $section = Section::factory()->create([
        'survey_version_id' => $version->id,
    ]);

    $questionType = QuestionType::factory()->create();

    $secondQuestion = $section->questions()->create([
        'question_type_id' => $questionType->id,
        'code' => 'Q002',
        'label' => 'Segunda pregunta',
        'sort_order' => 2,
    ]);

    $firstQuestion = $section->questions()->create([
        'question_type_id' => $questionType->id,
        'code' => 'Q001',
        'label' => 'Primera pregunta',
        'sort_order' => 1,
    ]);

    expect($section->questions)
        ->toHaveCount(2)
        ->and($section->questions->first()->id)
        ->toBe($firstQuestion->id)
        ->and($section->questions->last()->id)
        ->toBe($secondQuestion->id);
});

//---------------------------------------------------------

it('casts active to boolean', function () {
    $section = Section::factory()->create([
        'active' => 1,
    ]);

    expect($section->active)
        ->toBeTrue();

    $section->update([
        'active' => 0,
    ]);

    $section->refresh();

    expect($section->active)
        ->toBeFalse();
});

//---------------------------------------------------------------

it('sets child parent_id to null when parent section is deleted', function () {
    $questionnaire = Questionnaire::factory()->create();

    $version = SurveyVersion::factory()->create([
        'questionnaire_id' => $questionnaire->id,
    ]);

    $parent = Section::factory()->create([
        'survey_version_id' => $version->id,
        'parent_id' => null,
    ]);

    $child = Section::factory()->create([
        'survey_version_id' => $version->id,
        'parent_id' => $parent->id,
    ]);

    $parent->delete();

    $child->refresh();

    expect($child->exists)->toBeTrue()
        ->and($child->parent_id)->toBeNull();
});

//-------------------------------------------------------------

it('deletes sections when survey version is deleted', function () {
    $questionnaire = Questionnaire::factory()->create();

    $version = SurveyVersion::factory()->create([
        'questionnaire_id' => $questionnaire->id,
    ]);

    $section = Section::factory()->create([
        'survey_version_id' => $version->id,
    ]);

    $sectionId = $section->id;

    $version->delete();

    expect(Section::find($sectionId))->toBeNull();
});
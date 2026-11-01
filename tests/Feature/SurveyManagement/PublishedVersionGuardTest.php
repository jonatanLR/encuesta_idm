<?php

use App\Livewire\SurveyManagement\Questions\Conditions\Index as ConditionsIndex;
use App\Livewire\SurveyManagement\Questions\Index as QuestionsIndex;
use App\Livewire\SurveyManagement\Questions\Options\Index as OptionsIndex;
use App\Livewire\SurveyManagement\Sections\Index as SectionsIndex;
use App\Models\Question;
use App\Models\QuestionCondition;
use App\Models\Questionnaire;
use App\Models\QuestionOption;
use App\Models\QuestionType;
use App\Models\Role;
use App\Models\Section;
use App\Models\SurveyVersion;
use App\Models\User;
use App\Services\PublishedVersionGuard;
use Livewire\Livewire;

/**
 * Crea un cuestionario con una versión (publicada o no), una sección, dos
 * preguntas de selección única con opciones y una condición entre ellas.
 *
 * @return array<string, mixed>
 */
function createVersionStructure(bool $published): array
{
    $questionnaire = Questionnaire::factory()->create();

    $version = SurveyVersion::factory()->create([
        'questionnaire_id' => $questionnaire->id,
        'published_at' => $published ? now() : null,
    ]);

    $section = Section::factory()->create([
        'survey_version_id' => $version->id,
        'code' => 'GENERAL',
        'name' => 'Información general',
    ]);

    $singleChoice = QuestionType::factory()->create(['code' => 'single_choice']);
    $text = QuestionType::factory()->create(['code' => 'text']);

    $parentQuestion = Question::factory()->create([
        'section_id' => $section->id,
        'question_type_id' => $singleChoice->id,
        'code' => 'GENERAL_005',
        'label' => 'Tipo de evento',
        'required' => true,
    ]);

    $childQuestion = Question::factory()->create([
        'section_id' => $section->id,
        'question_type_id' => $text->id,
        'code' => 'GENERAL_006',
        'label' => 'Especifique',
    ]);

    $option = QuestionOption::create([
        'question_id' => $parentQuestion->id,
        'label' => 'Otros',
        'value' => 'otros',
        'sort_order' => 1,
        'active' => true,
    ]);

    $condition = QuestionCondition::create([
        'question_id' => $childQuestion->id,
        'depends_on_question_id' => $parentQuestion->id,
        'depends_on_option_id' => $option->id,
        'operator' => 'equals',
        'active' => true,
    ]);

    return compact(
        'questionnaire',
        'version',
        'section',
        'parentQuestion',
        'childQuestion',
        'option',
        'condition',
        'text'
    );
}

beforeEach(function () {
    $admin = User::factory()->create();
    $admin->roles()->attach(Role::create(['name' => 'Administrador', 'slug' => 'admin']));

    $this->actingAs($admin);
});

// --- Servicio --------------------------------------------------------------

it('treats only versions with published_at as locked', function () {
    $guard = app(PublishedVersionGuard::class);

    expect($guard->isLocked(SurveyVersion::factory()->create(['published_at' => now()])))->toBeTrue()
        ->and($guard->isLocked(SurveyVersion::factory()->create(['published_at' => null, 'version' => '9.9'])))->toBeFalse()
        ->and($guard->changedFields(['code' => 'A', 'active' => true], ['code' => 'A', 'active' => false]))->toBe(['active']);
});

// --- Secciones -------------------------------------------------------------

it('blocks structural section changes in a published version but allows text and order', function () {
    ['questionnaire' => $questionnaire, 'version' => $version, 'section' => $section] = createVersionStructure(true);

    $component = Livewire::test(SectionsIndex::class, ['questionnaire' => $questionnaire, 'version' => $version])
        ->assertSet('versionLocked', true)
        ->assertSee('Versión publicada')
        ->assertDontSeeHtml('wire:click="openCreateModal"');

    $component->call('openCreateModal')
        ->assertSet('showFormModal', false)
        ->assertSee(PublishedVersionGuard::STRUCTURE_LOCKED_MESSAGE);

    $component->call('toggleActive', $section->id);
    expect($section->fresh()->active)->toBeTrue();

    $component->call('openEditModal', $section->id)
        ->set('active', false)
        ->call('saveSection')
        ->assertHasErrors('structure');
    expect($section->fresh()->active)->toBeTrue();

    $component->call('openEditModal', $section->id)
        ->set('name', 'Información general corregida')
        ->set('sortOrder', 3)
        ->call('saveSection')
        ->assertHasNoErrors();

    expect($section->fresh())
        ->name->toBe('Información general corregida')
        ->sort_order->toBe(3);
});

it('keeps full section editing in an unpublished version', function () {
    ['questionnaire' => $questionnaire, 'version' => $version, 'section' => $section] = createVersionStructure(false);

    Livewire::test(SectionsIndex::class, ['questionnaire' => $questionnaire, 'version' => $version])
        ->assertSet('versionLocked', false)
        ->assertSeeHtml('wire:click="openCreateModal"')
        ->call('toggleActive', $section->id);

    expect($section->fresh()->active)->toBeFalse();
});

// --- Preguntas -------------------------------------------------------------

it('blocks structural question changes in a published version but allows the label', function () {
    $structure = createVersionStructure(true);
    $question = $structure['parentQuestion'];
    $params = [
        'questionnaire' => $structure['questionnaire'],
        'version' => $structure['version'],
        'section' => $structure['section'],
    ];

    $component = Livewire::test(QuestionsIndex::class, $params)
        ->assertDontSee('+ Nueva pregunta');

    $component->call('toggleActive', $question->id);
    expect($question->fresh()->active)->toBeTrue();

    $component->call('openEditModal', $question->id)
        ->set('required', false)
        ->call('saveQuestion')
        ->assertHasErrors('structure');

    $component->call('openEditModal', $question->id)
        ->set('questionTypeId', $structure['text']->id)
        ->call('saveQuestion')
        ->assertHasErrors('structure');

    expect($question->fresh())
        ->required->toBeTrue()
        ->question_type_id->not->toBe($structure['text']->id);

    $component->call('openEditModal', $question->id)
        ->set('label', '¿Qué tipo de evento natural causó el daño?')
        ->call('saveQuestion')
        ->assertHasNoErrors();

    expect($question->fresh()->label)->toBe('¿Qué tipo de evento natural causó el daño?');
});

it('blocks creating questions in a published version', function () {
    $structure = createVersionStructure(true);

    Livewire::test(QuestionsIndex::class, [
        'questionnaire' => $structure['questionnaire'],
        'version' => $structure['version'],
        'section' => $structure['section'],
    ])
        ->set('code', 'GENERAL_099')
        ->set('label', 'Pregunta nueva')
        ->set('questionTypeId', $structure['text']->id)
        ->call('saveQuestion')
        ->assertHasErrors('structure');

    expect(Question::query()->where('code', 'GENERAL_099')->exists())->toBeFalse();
});

// --- Opciones --------------------------------------------------------------

it('blocks option value and status changes in a published version but allows the label', function () {
    $structure = createVersionStructure(true);
    $option = $structure['option'];

    $component = Livewire::test(OptionsIndex::class, [
        'questionnaire' => $structure['questionnaire'],
        'version' => $structure['version'],
        'section' => $structure['section'],
        'question' => $structure['parentQuestion'],
    ])->assertDontSee('+ Nueva opción');

    $component->call('toggleActive', $option->id);
    expect($option->fresh()->active)->toBeTrue();

    $component->call('openEditModal', $option->id)
        ->set('value', 'otro_valor')
        ->call('saveOption')
        ->assertHasErrors('structure');
    expect($option->fresh()->value)->toBe('otros');

    $component->call('openEditModal', $option->id)
        ->set('label', 'Otros eventos')
        ->call('saveOption')
        ->assertHasNoErrors();
    expect($option->fresh()->label)->toBe('Otros eventos');
});

// --- Condiciones -----------------------------------------------------------

it('blocks every condition change in a published version', function () {
    $structure = createVersionStructure(true);
    $condition = $structure['condition'];

    $component = Livewire::test(ConditionsIndex::class, [
        'questionnaire' => $structure['questionnaire'],
        'version' => $structure['version'],
        'section' => $structure['section'],
        'question' => $structure['childQuestion'],
    ])
        ->assertDontSee('+ Nueva condición')
        ->assertSee('Solo lectura');

    $component->call('toggleActive', $condition->id);
    expect($condition->fresh()->active)->toBeTrue();

    $component->call('openEditModal', $condition->id)
        ->assertSet('showFormModal', false);

    $component->set('editingConditionId', $condition->id)
        ->set('dependsOnQuestionId', $structure['parentQuestion']->id)
        ->set('dependsOnOptionId', $structure['option']->id)
        ->set('operator', 'not_equals')
        ->call('saveCondition')
        ->assertHasErrors('structure');

    expect($condition->fresh()->operator)->toBe('equals');
});

it('keeps condition editing in an unpublished version', function () {
    $structure = createVersionStructure(false);
    $condition = $structure['condition'];

    Livewire::test(ConditionsIndex::class, [
        'questionnaire' => $structure['questionnaire'],
        'version' => $structure['version'],
        'section' => $structure['section'],
        'question' => $structure['childQuestion'],
    ])->call('toggleActive', $condition->id);

    expect($condition->fresh()->active)->toBeFalse();
});

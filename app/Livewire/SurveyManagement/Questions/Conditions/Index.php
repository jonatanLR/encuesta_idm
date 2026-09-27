<?php

namespace App\Livewire\SurveyManagement\Questions\Conditions;

use App\Models\Question;
use App\Models\QuestionCondition;
use App\Models\Questionnaire;
use App\Models\Section;
use App\Models\SurveyVersion;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Component;

class Index extends Component
{
    public Questionnaire $questionnaire;
    public SurveyVersion $version;
    public Section $section;
    public Question $question;

    public bool $showFormModal = false;
    public ?int $editingConditionId = null;

    public ?int $dependsOnQuestionId = null;
    public ?int $dependsOnOptionId = null;
    public string $operator = 'equals';
    public ?string $expectedValue = null;
    public bool $active = true;

    public function mount(
        Questionnaire $questionnaire,
        SurveyVersion $version,
        Section $section,
        Question $question
    ): void {
        if ($version->questionnaire_id !== $questionnaire->id) {
            abort(404);
        }

        if ($section->survey_version_id !== $version->id) {
            abort(404);
        }

        if ($question->section_id !== $section->id) {
            abort(404);
        }

        $this->questionnaire = $questionnaire;
        $this->version = $version;
        $this->section = $section;
        $this->question = $question;
    }

    public function openCreateModal(): void
    {
        $this->authorize('create', QuestionCondition::class);

        $this->resetForm();
        $this->showFormModal = true;
    }

    public function openEditModal(int $conditionId): void
    {
        $condition = $this->findCondition($conditionId);

        $this->authorize('update', $condition);

        $this->editingConditionId = $condition->id;
        $this->dependsOnQuestionId = $condition->depends_on_question_id;
        $this->dependsOnOptionId = $condition->depends_on_option_id;
        $this->operator = $condition->operator;
        $this->expectedValue = $condition->expected_value;
        $this->active = $condition->active;

        $this->resetValidation();
        $this->showFormModal = true;
    }

    public function updatedDependsOnQuestionId(): void
    {
        $this->dependsOnOptionId = null;
        $this->expectedValue = null;
        $this->resetValidation();
    }

    public function saveCondition(): void
    {
        $isEditing = $this->editingConditionId !== null;

        $condition = $isEditing
            ? $this->findCondition($this->editingConditionId)
            : null;

        $this->authorize(
            $isEditing ? 'update' : 'create',
            $isEditing ? $condition : QuestionCondition::class
        );

        $this->validate([
            'dependsOnQuestionId' => [
                'required',
                'integer',
                'exists:questions,id',
            ],
            'operator' => [
                'required',
                'in:equals,not_equals',
            ],
            'dependsOnOptionId' => [
                'nullable',
                'integer',
                'exists:question_options,id',
            ],
            'expectedValue' => [
                'nullable',
                'string',
                'max:255',
            ],
            'active' => ['boolean'],
        ]);

        $dependsOnQuestion = $this->findReferenceQuestion(
            $this->dependsOnQuestionId
        );

        if ($dependsOnQuestion->id === $this->question->id) {
            $this->addError(
                'dependsOnQuestionId',
                'Una pregunta no puede depender de sí misma.'
            );

            return;
        }

        $hasOptions = $dependsOnQuestion->options()->exists();

        if ($hasOptions) {
            $this->validate([
                'dependsOnOptionId' => [
                    'required',
                    'integer',
                    'exists:question_options,id',
                ],
            ]);

            $optionBelongsToQuestion = $dependsOnQuestion
                ->options()
                ->whereKey($this->dependsOnOptionId)
                ->exists();

            if (! $optionBelongsToQuestion) {
                $this->addError(
                    'dependsOnOptionId',
                    'La opción seleccionada no pertenece a la pregunta de referencia.'
                );

                return;
            }

            $this->expectedValue = null;
        } else {
            $this->dependsOnOptionId = null;

            if ($this->expectedValue === null || $this->expectedValue === '') {
                $this->addError(
                    'expectedValue',
                    'Debe indicar el valor esperado.'
                );

                return;
            }
        }

        if ($this->wouldCreateCycle($this->dependsOnQuestionId)) {
            $this->addError(
                'dependsOnQuestionId',
                'La condición crearía una dependencia circular entre preguntas.'
            );

            return;
        }

        $data = [
            'question_id' => $this->question->id,
            'depends_on_question_id' => $this->dependsOnQuestionId,
            'depends_on_option_id' => $this->dependsOnOptionId,
            'operator' => $this->operator,
            'expected_value' => $this->expectedValue,
            'active' => $this->active,
        ];

        if ($isEditing) {
            $condition->update($data);

            session()->flash(
                'success',
                'Condición actualizada correctamente.'
            );
        } else {
            QuestionCondition::create($data);

            session()->flash(
                'success',
                'Condición creada correctamente.'
            );
        }

        $this->closeFormModal();
    }

    public function toggleActive(int $conditionId): void
    {
        $condition = $this->findCondition($conditionId);

        $this->authorize('activate', $condition);

        $condition->update([
            'active' => ! $condition->active,
        ]);

        session()->flash(
            'success',
            $condition->active
                ? 'Condición activada correctamente.'
                : 'Condición desactivada correctamente.'
        );
    }

    public function closeFormModal(): void
    {
        $this->showFormModal = false;
        $this->resetForm();
    }

    private function findCondition(int $conditionId): QuestionCondition
    {
        return $this->question
            ->conditions()
            ->with([
                'dependsOnQuestion.questionType',
                'dependsOnOption',
            ])
            ->whereKey($conditionId)
            ->firstOrFail();
    }

    private function findReferenceQuestion(
        ?int $questionId
    ): Question {
        abort_unless($questionId !== null, 404);

        return $this->version
            ->sections()
            ->with('questions.questionType')
            ->get()
            ->flatMap(fn(Section $section) => $section->questions)
            ->firstOrFail(
                fn(Question $question) =>
                $question->id === $questionId
            );
    }

    private function wouldCreateCycle(int $referenceQuestionId): bool
    {
        $visited = [];

        return $this->hasDependencyPath(
            $referenceQuestionId,
            $this->question->id,
            $visited
        );
    }

    private function hasDependencyPath(
        int $currentQuestionId,
        int $targetQuestionId,
        array &$visited
    ): bool {
        if ($currentQuestionId === $targetQuestionId) {
            return true;
        }

        if (in_array($currentQuestionId, $visited, true)) {
            return false;
        }

        $visited[] = $currentQuestionId;

        $question = Question::query()
            ->with('conditions')
            ->find($currentQuestionId);

        if (! $question) {
            return false;
        }

        foreach ($question->conditions as $condition) {
            if (
                $condition->depends_on_question_id !== null
                && $this->hasDependencyPath(
                    $condition->depends_on_question_id,
                    $targetQuestionId,
                    $visited
                )
            ) {
                return true;
            }
        }

        return false;
    }

    private function resetForm(): void
    {
        $this->reset([
            'editingConditionId',
            'dependsOnQuestionId',
            'dependsOnOptionId',
            'operator',
            'expectedValue',
            'active',
        ]);

        $this->dependsOnQuestionId = null;
        $this->dependsOnOptionId = null;
        $this->operator = 'equals';
        $this->expectedValue = null;
        $this->active = true;

        $this->resetValidation();
    }

    public function render(): View
    {
        $this->authorize('viewAny', QuestionCondition::class);

        $conditions = $this->question
            ->conditions()
            ->with([
                'dependsOnQuestion',
                'dependsOnOption',
            ])
            ->get();

        $referenceQuestions = $this->version
            ->sections()
            ->with([
                'questions' => fn($query) =>
                $query
                    ->where('id', '!=', $this->question->id)
                    ->orderBy('sort_order')
                    ->orderBy('id'),
            ])
            ->orderBy('sort_order')
            ->get()
            ->flatMap(
                fn(Section $section) =>
                $section->questions->map(
                    fn(Question $question) => [
                        'question' => $question,
                        'section' => $section,
                    ]
                )
            );

        $selectedReferenceQuestion = $this->dependsOnQuestionId
            ? $this->findReferenceQuestion(
                $this->dependsOnQuestionId
            )
            : null;

        $referenceOptions = $selectedReferenceQuestion
            ? $selectedReferenceQuestion
            ->options()
            ->where('active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            : collect();

        return view(
            'livewire.survey-management.questions.conditions.index',
            compact(
                'conditions',
                'referenceQuestions',
                'referenceOptions',
                'selectedReferenceQuestion'
            )
        );
    }
}

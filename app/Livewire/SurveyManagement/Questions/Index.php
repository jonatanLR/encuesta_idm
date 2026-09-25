<?php

namespace App\Livewire\SurveyManagement\Questions;

use App\Models\Question;
use App\Models\QuestionType;
use App\Models\Questionnaire;
use App\Models\Section;
use App\Models\SurveyVersion;
use Illuminate\View\View;
use Livewire\Component;

class Index extends Component
{
    public SurveyVersion $version;
    public Questionnaire $questionnaire;
    public Section $section;

    public bool $showFormModal = false;
    public ?int $editingQuestionId = null;

    public string $code = '';
    public string $label = '';
    public ?string $description = null;
    public ?int $questionTypeId = null;
    public bool $required = false;
    public int $sortOrder = 1;
    public bool $active = true;

    public function mount(
        Questionnaire $questionnaire,
        SurveyVersion $version,
        Section $section
    ): void {
        if ($version->questionnaire_id !== $questionnaire->id) {
            abort(404);
        }

        if ($section->survey_version_id !== $version->id) {
            abort(404);
        }

        $this->questionnaire = $questionnaire;
        $this->version = $version;
        $this->section = $section;
    }

    public function openCreateModal(): void
    {
        $this->authorize('create', Question::class);

        $this->resetForm();

        $this->sortOrder = $this->nextSortOrder();

        $this->showFormModal = true;
    }

    public function openEditModal(int $questionId): void
    {
        $question = $this->findQuestion($questionId);

        $this->authorize('update', $question);

        $this->editingQuestionId = $question->id;
        $this->code = $question->code;
        $this->label = $question->label;
        $this->description = $question->description;
        $this->questionTypeId = $question->question_type_id;
        $this->required = $question->required;
        $this->sortOrder = $question->sort_order;
        $this->active = $question->active;

        $this->resetValidation();

        $this->showFormModal = true;
    }

    public function saveQuestion(): void
    {
        $isEditing = $this->editingQuestionId !== null;

        $question = $isEditing
            ? $this->findQuestion($this->editingQuestionId)
            : null;

        $this->authorize(
            $isEditing ? 'update' : 'create',
            $isEditing ? $question : Question::class
        );

        $this->validate([
            'code' => [
                'required',
                'string',
                'max:50',
                'unique:questions,code,' .
                    ($this->editingQuestionId ?? 'NULL') .
                    ',id,section_id,' .
                    $this->section->id,
            ],
            'label' => [
                'required',
                'string',
            ],
            'description' => [
                'nullable',
                'string',
            ],
            'questionTypeId' => [
                'required',
                'integer',
                'exists:question_types,id',
            ],
            'required' => [
                'boolean',
            ],
            'sortOrder' => [
                'required',
                'integer',
                'min:1',
            ],
            'active' => [
                'boolean',
            ],
        ]);

        if ($isEditing) {
            $question->update([
                'code' => $this->code,
                'label' => $this->label,
                'description' => $this->description,
                'question_type_id' => $this->questionTypeId,
                'required' => $this->required,
                'sort_order' => $this->sortOrder,
                'active' => $this->active,
            ]);

            session()->flash(
                'success',
                'Pregunta actualizada correctamente.'
            );
        } else {
            $this->section->questions()->create([
                'question_type_id' => $this->questionTypeId,
                'code' => $this->code,
                'label' => $this->label,
                'description' => $this->description,
                'required' => $this->required,
                'sort_order' => $this->sortOrder,
                'active' => $this->active,
            ]);

            session()->flash(
                'success',
                'Pregunta creada correctamente.'
            );
        }

        $this->closeFormModal();
    }

    public function toggleActive(int $questionId): void
    {
        $question = $this->findQuestion($questionId);

        $this->authorize('activate', $question);

        $question->update([
            'active' => ! $question->active,
        ]);

        session()->flash(
            'success',
            $question->active
                ? 'Pregunta activada correctamente.'
                : 'Pregunta desactivada correctamente.'
        );
    }

    public function closeFormModal(): void
    {
        $this->showFormModal = false;
        $this->resetForm();
    }

    private function findQuestion(int $questionId): Question
    {
        return $this->section
            ->questions()
            ->whereKey($questionId)
            ->firstOrFail();
    }

    private function nextSortOrder(): int
    {
        return ((int) $this->section
            ->questions()
            ->max('sort_order')) + 1;
    }

    private function resetForm(): void
    {
        $this->reset([
            'editingQuestionId',
            'code',
            'label',
            'description',
            'questionTypeId',
            'required',
            'sortOrder',
            'active',
        ]);

        $this->description = null;
        $this->questionTypeId = null;
        $this->required = false;
        $this->sortOrder = 1;
        $this->active = true;

        $this->resetValidation();
    }

    public function render(): View
    {
        $this->authorize('viewAny', Question::class);

        $questions = $this->section
            ->questions()
            ->with('questionType')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $questionTypes = QuestionType::orderBy('name')->get();

        return view(
            'livewire.survey-management.questions.index',
            compact('questions', 'questionTypes')
        );
    }
}

<?php

namespace App\Livewire\SurveyManagement\Questions\Options;

use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Questionnaire;
use App\Models\Section;
use App\Models\SurveyVersion;
use Illuminate\View\View;
use Livewire\Component;

class Index extends Component
{
    public Questionnaire $questionnaire;
    public SurveyVersion $version;
    public Section $section;
    public Question $question;

    public bool $showFormModal = false;
    public ?int $editingOptionId = null;

    public string $label = '';
    public string $value = '';
    public int $sortOrder = 1;
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
        $this->authorize('create', QuestionOption::class);

        $this->resetForm();
        $this->sortOrder = $this->nextSortOrder();
        $this->showFormModal = true;
    }

    public function openEditModal(int $optionId): void
    {
        $option = $this->findOption($optionId);

        $this->authorize('update', $option);

        $this->editingOptionId = $option->id;
        $this->label = $option->label;
        $this->value = $option->value;
        $this->sortOrder = $option->sort_order;
        $this->active = $option->active;

        $this->resetValidation();
        $this->showFormModal = true;
    }

    public function saveOption(): void
    {
        $isEditing = $this->editingOptionId !== null;

        $option = $isEditing
            ? $this->findOption($this->editingOptionId)
            : null;

        $this->authorize(
            $isEditing ? 'update' : 'create',
            $isEditing ? $option : QuestionOption::class
        );

        $this->validate([
            'label' => ['required', 'string', 'max:255'],
            'value' => ['required', 'string', 'max:255'],
            'sortOrder' => ['required', 'integer', 'min:1'],
            'active' => ['boolean'],
        ]);

        if ($isEditing) {
            $option->update([
                'label' => $this->label,
                'value' => $this->value,
                'sort_order' => $this->sortOrder,
                'active' => $this->active,
            ]);

            session()->flash(
                'success',
                'Opción actualizada correctamente.'
            );
        } else {
            $this->question->options()->create([
                'label' => $this->label,
                'value' => $this->value,
                'sort_order' => $this->sortOrder,
                'active' => $this->active,
            ]);

            session()->flash(
                'success',
                'Opción creada correctamente.'
            );
        }

        $this->closeFormModal();
    }

    public function toggleActive(int $optionId): void
    {
        $option = $this->findOption($optionId);

        $this->authorize('activate', $option);

        $option->update([
            'active' => ! $option->active,
        ]);

        session()->flash(
            'success',
            $option->active
                ? 'Opción activada correctamente.'
                : 'Opción desactivada correctamente.'
        );
    }

    public function closeFormModal(): void
    {
        $this->showFormModal = false;
        $this->resetForm();
    }

    private function findOption(int $optionId): QuestionOption
    {
        return $this->question
            ->options()
            ->whereKey($optionId)
            ->firstOrFail();
    }

    private function nextSortOrder(): int
    {
        return ((int) $this->question->options()->max('sort_order')) + 1;
    }

    private function resetForm(): void
    {
        $this->reset([
            'editingOptionId',
            'label',
            'value',
            'sortOrder',
            'active',
        ]);

        $this->label = '';
        $this->value = '';
        $this->sortOrder = 1;
        $this->active = true;

        $this->resetValidation();
    }

    public function render(): View
    {
        $this->authorize('viewAny', QuestionOption::class);

        $options = $this->question
            ->options()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view(
            'livewire.survey-management.questions.options.index',
            compact('options')
        );
    }
}

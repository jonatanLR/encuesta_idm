<?php

namespace App\Livewire\SurveyManagement\Questions\Options;

use App\Models\Question;
use App\Models\Questionnaire;
use App\Models\QuestionOption;
use App\Models\Section;
use App\Models\SurveyVersion;
use App\Services\PublishedVersionGuard;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Index extends Component
{
    public Questionnaire $questionnaire;

    public SurveyVersion $version;

    public Section $section;

    public Question $question;

    /**
     * Versión publicada: solo se permiten correcciones de texto y orden (D-08).
     */
    #[Locked]
    public bool $versionLocked = false;

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
        $this->versionLocked = app(PublishedVersionGuard::class)->isLocked($version);
    }

    public function openCreateModal(): void
    {
        $this->authorize('create', QuestionOption::class);

        if ($this->blockWhenVersionLocked()) {
            return;
        }

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

        if ($this->rejectsStructuralChange(
            current: $isEditing ? [
                'value' => $option->value,
                'active' => (bool) $option->active,
            ] : [],
            submitted: [
                'value' => $this->value,
                'active' => $this->active,
            ],
            isEditing: $isEditing,
        )) {
            return;
        }

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

        if ($this->blockWhenVersionLocked()) {
            return;
        }

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

    /**
     * Si la versión está publicada, informa al usuario y devuelve true.
     */
    private function blockWhenVersionLocked(): bool
    {
        if (! app(PublishedVersionGuard::class)->isLocked($this->version)) {
            return false;
        }

        session()->flash('error', PublishedVersionGuard::STRUCTURE_LOCKED_MESSAGE);

        return true;
    }

    /**
     * En una versión publicada agrega el error del formulario si se intentó
     * crear un elemento o cambiar alguno de los campos estructurales.
     *
     * @param  array<string, mixed>  $current
     * @param  array<string, mixed>  $submitted
     */
    private function rejectsStructuralChange(array $current, array $submitted, bool $isEditing): bool
    {
        $guard = app(PublishedVersionGuard::class);

        if (! $guard->isLocked($this->version)) {
            return false;
        }

        if ($isEditing && $guard->changedFields($current, $submitted) === []) {
            return false;
        }

        $this->addError('structure', PublishedVersionGuard::STRUCTURE_LOCKED_MESSAGE);

        return true;
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

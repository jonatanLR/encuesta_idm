<?php

namespace App\Livewire\SurveyManagement;

use App\Models\Questionnaire;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Index extends Component
{
    public bool $showEditModal = false;

    public ?int $editingQuestionnaireId = null;

    public string $name = '';

    public ?string $description = null;

    public bool $active = true;

    public function openEditModal(int $questionnaireId): void
    {
        $questionnaire = Questionnaire::findOrFail($questionnaireId);

        $this->authorize('update', $questionnaire);

        $this->editingQuestionnaireId = $questionnaire->id;
        $this->name = $questionnaire->name;
        $this->description = $questionnaire->description;
        $this->active = $questionnaire->active;

        $this->resetValidation();
        $this->showEditModal = true;
    }

    public function updateQuestionnaire(): void
    {
        $questionnaire = Questionnaire::findOrFail($this->editingQuestionnaireId);

        $this->authorize('update', $questionnaire);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'active' => ['boolean'],
        ]);

        $questionnaire->update($validated);

        $this->resetEditForm();
        $this->showEditModal = false;

        session()->flash(
            'success',
            'El cuestionario se actualizó correctamente.'
        );
    }

    private function resetEditForm(): void
    {
        $this->reset([
            'editingQuestionnaireId',
            'name',
            'description',
            'active',
        ]);

        $this->resetValidation();

        $this->active = true;
    }

    public function render(): View
    {
        $questionnaires = Questionnaire::query()
            ->latest()
            ->get();

        return view(
            'livewire.survey-management.index',
            compact('questionnaires')
        );
    }
}

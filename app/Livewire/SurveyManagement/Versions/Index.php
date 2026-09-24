<?php

namespace App\Livewire\SurveyManagement\Versions;

use App\Models\Questionnaire;
use App\Services\SurveyVersionCloner;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Index extends Component
{
    public Questionnaire $questionnaire;

    public bool $showCreateModal = false;

    public string $version = '';

    public function mount(Questionnaire $questionnaire): void
    {
        $this->questionnaire = $questionnaire;
    }

    public function openCreateModal(): void
    {
        $this->authorize('update', $this->questionnaire);

        $this->resetValidation();

        $this->version = $this->suggestNextVersion();

        $this->showCreateModal = true;
    }

    public function createTestVersion(
        SurveyVersionCloner $cloner
    ): void {
        $this->authorize('update', $this->questionnaire);

        $validated = $this->validate([
            'version' => [
                'required',
                'string',
                'max:20',
            ],
        ]);

        $cloner->cloneTestVersion(
            $this->questionnaire,
            $validated['version']
        );

        $this->showCreateModal = false;

        $this->resetCreateForm();

        session()->flash(
            'success',
            "La versión {$validated['version']} se creó correctamente."
        );
    }

    private function suggestNextVersion(): string
    {
        $versions = $this->questionnaire
            ->versions()
            ->pluck('version')
            ->map(fn(string $version) => (float) $version)
            ->filter(fn(float $version) => $version > 0);

        if ($versions->isEmpty()) {
            return '1.0';
        }

        return number_format(
            $versions->max() + 0.1,
            1,
            '.',
            ''
        );
    }

    private function resetCreateForm(): void
    {
        $this->reset([
            'version',
        ]);

        $this->resetValidation();
    }

    public function render(): View
    {
        $versions = $this->questionnaire
            ->versions()
            ->orderByDesc('version')
            ->get();

        return view(
            'livewire.survey-management.versions.index',
            compact('versions')
        );
    }
}

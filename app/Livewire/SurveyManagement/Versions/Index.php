<?php

namespace App\Livewire\SurveyManagement\Versions;

use App\Models\Questionnaire;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Index extends Component
{
    public Questionnaire $questionnaire;

    public function mount(Questionnaire $questionnaire): void
    {
        $this->questionnaire = $questionnaire;
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

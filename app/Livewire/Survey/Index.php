<?php

namespace App\Livewire\Survey;

use App\Models\SurveyVersion;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Index extends Component
{
    public function render(): View
    {
        $surveyVersions = SurveyVersion::query()
            ->with('questionnaire')
            ->where('active', true)
            ->whereNotNull('published_at')
            ->whereHas('questionnaire', function ($query): void {
                $query->where('active', true);
            })
            ->latest('published_at')
            ->get();

        return view('livewire.survey.index', compact('surveyVersions'));
    }
}
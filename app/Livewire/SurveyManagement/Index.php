<?php

namespace App\Livewire\SurveyManagement;

use App\Models\Questionnaire;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Index extends Component
{
    public function render(): View
    {
        $questionnaires = Questionnaire::query()
            ->latest()
            ->get();

        return view('livewire.survey-management.index', compact('questionnaires'));
    }
}
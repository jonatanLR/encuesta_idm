<?php

namespace App\Livewire\Survey;

use App\Models\Questionnaire;
use App\Models\SurveyResponse;
use App\Models\SurveyVersion;
use App\Services\SurveyResponseService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Start extends Component
{
    public function startSurvey(
        SurveyResponseService $surveyResponseService
    ) {
        $questionnaire = Questionnaire::query()
            ->where('name', 'Encuesta de Situación Social')
            ->where('active', true)
            ->firstOrFail();

        $version = SurveyVersion::query()
            ->where('questionnaire_id', $questionnaire->id)
            ->where('version', '1.0')
            ->where('active', true)
            ->whereNotNull('published_at')
            ->firstOrFail();

        $response = $surveyResponseService->createDraft(
            questionnaireId: $questionnaire->id,
            surveyVersionId: $version->id,
            userId: Auth::id(),
            communityId: null,
        );

        $surveyResponseService->start($response);

        return $this->redirectRoute(
            'survey.form',
            ['response' => $response->id]
        );
    }

    public function render()
    {
        return view('livewire.survey.start');
    }
}

<?php

namespace App\Livewire\Survey;

use App\Models\Questionnaire;
use App\Models\SurveyResponse;
use App\Models\SurveyVersion;
use App\Services\SurveyResponseService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Index extends Component
{
    public function startSurvey(
        SurveyResponseService $surveyResponseService
    ) {
        $this->authorize('create', SurveyResponse::class);

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
        $this->authorize('viewAny', SurveyResponse::class);

        $responses = SurveyResponse::query()
            ->with([
                'community',
                'answers.question',
                'answers.option',
            ])
            ->latest('created_at')
            ->get();

        return view('livewire.survey.index', [
            'responses' => $responses,
        ]);
    }
}

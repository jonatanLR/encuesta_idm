<?php

namespace App\Livewire\Survey;

use App\Models\Community;
use App\Models\Question;
use App\Models\SurveyResponse;
use App\Services\SurveyResponseService;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\On;
use Livewire\Component;

class Form extends Component
{
    public SurveyResponse $response;

    public int $currentSectionIndex = 0;

    #[On('community-selected')]
    public function communitySelected(
        int $communityId,
        string $communityName
    ): void {
        $community = Community::query()
            ->where('active', true)
            ->findOrFail($communityId);

        $this->response->community_id = $community->id;
        $this->response->save();

        $this->response->setRelation('community', $community);
    }

    #[On('community-cleared')]
    public function communityCleared(): void
    {
        $this->response->community_id = null;
        $this->response->save();

        $this->response->unsetRelation('community');
    }

    public function mount(SurveyResponse $response): void
    {
        $this->response = $response->load([
            'questionnaire',
            'surveyVersion',
            'community',
        ]);
    }

    public function nextSection(): void
    {
        $sections = $this->getSections();

        if ($this->currentSectionIndex < $sections->count() - 1) {
            $this->currentSectionIndex++;
        }
    }

    public function previousSection(): void
    {
        if ($this->currentSectionIndex > 0) {
            $this->currentSectionIndex--;
        }
    }

    public function goToSection(int $index): void
    {
        $sections = $this->getSections();

        if ($index >= 0 && $index < $sections->count()) {
            $this->currentSectionIndex = $index;
        }
    }

    public function render()
    {
        $sections = $this->getSections();

        $currentSection = $sections->get($this->currentSectionIndex);

        return view('livewire.survey.form', [
            'sections' => $sections,
            'currentSection' => $currentSection,
            'questions' => $currentSection?->questions ?? collect(),
        ]);
    }

    public function saveSingleChoice(
        int $questionId,
        int $optionId,
        SurveyResponseService $surveyResponseService
    ): void {
        $question = Question::query()
            ->with('section')
            ->findOrFail($questionId);

        if ($question->code !== 'GENERAL_004') {
            abort(404);
        }

        $surveyResponseService->saveAnswer(
            response: $this->response,
            question: $question,
            value: $optionId,
        );

        $this->response->load('answers');
    }

    private function getSections(): Collection
    {
        return $this->response->surveyVersion
            ->sections()
            ->whereNull('parent_id')
            ->where('active', true)
            ->orderBy('sort_order')
            ->with([
                'questions' => function ($query) {
                    $query->where('active', true)
                        ->orderBy('sort_order')
                        ->with([
                            'questionType',
                            'options' => function ($query) {
                                $query->where('active', true)
                                    ->orderBy('sort_order');
                            },
                        ]);
                },
            ])
            ->get();
    }

    public function saveDate(
        int $questionId,
        string $value,
        SurveyResponseService $surveyResponseService
    ): void {
        $question = Question::query()
            ->with('section')
            ->findOrFail($questionId);

        if ($question->code !== 'GENERAL_001') {
            abort(404);
        }

        $surveyResponseService->saveAnswer(
            response: $this->response,
            question: $question,
            value: $value,
        );

        $this->response->load('answers');
    }

    public function saveText(
        int $questionId,
        string $value,
        SurveyResponseService $surveyResponseService
    ): void {
        $question = Question::query()
            ->with('section')
            ->findOrFail($questionId);

        if ($question->questionType->code !== 'text') {
            abort(404);
        }

        $surveyResponseService->saveAnswer(
            response: $this->response,
            question: $question,
            value: $value,
        );

        $this->response->load('answers');
    }

    public function saveNumber(
        int $questionId,
        string $value,
        SurveyResponseService $surveyResponseService
    ): void {
        $question = Question::query()
            ->with('questionType')
            ->findOrFail($questionId);

        if ($question->questionType->code !== 'number') {
            abort(404);
        }

        $surveyResponseService->saveAnswer(
            response: $this->response,
            question: $question,
            value: $value,
        );

        $this->response->load('answers');
    }
}

<?php

namespace App\Livewire\Survey;

use App\Models\Answer;
use App\Models\Community;
use App\Models\Question;
use App\Models\SurveyResponse;
use App\Services\SurveyResponseService;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\On;
use Livewire\Component;
use App\Models\Household;
use App\Models\HouseholdMember;
use App\Models\HouseholdRelationship;
use App\Services\HouseholdMemberService;

class Form extends Component
{
    public SurveyResponse $response;

    public int $currentSectionIndex = 0;
    public ?int $selectedMemberId = null;

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
            'household.householdMembers' => function ($query): void {
                $query->whereNull('deleted_at')
                    ->orderBy('id');
            },
        ]);

        $this->selectedMemberId = $this->response->household
            ?->householdMembers
            ->first()?->id;
    }

    public function nextSection(): void
    {
        $sections = $this->getSections();

        if ($this->currentSectionIndex >= $sections->count() - 1) {
            return;
        }

        $nextIndex = $this->currentSectionIndex + 1;
        $nextSection = $sections->get($nextIndex);

        if ($nextSection?->code === 'MEMBER') {
            $this->ensureHousehold();
        }

        $this->currentSectionIndex = $nextIndex;
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

        if ($index < 0 || $index >= $sections->count()) {
            return;
        }

        $section = $sections->get($index);

        if ($section?->code === 'MEMBER') {
            $this->ensureHousehold();
        }

        $this->currentSectionIndex = $index;
    }

    public function selectMember(int $memberId): void
    {
        $household = $this->response->household;

        if ($household === null) {
            return;
        }

        $memberExists = $household->householdMembers()
            ->whereKey($memberId)
            ->exists();

        if (! $memberExists) {
            return;
        }

        $this->selectedMemberId = $memberId;
    }

    public function startMemberCapture(
        int $memberId,
        HouseholdMemberService $householdMemberService
    ): void {
        $member = $this->response->household?->householdMembers
            ->firstWhere('id', $memberId);

        if ($member === null) {
            return;
        }

        $householdMemberService->startCapture(
            response: $this->response,
            householdMember: $member,
        );

        $this->response->load([
            'household.householdMembers',
        ]);

        $this->selectedMemberId = $memberId;
    }

    protected function getSelectedMember(): ?HouseholdMember
    {
        if ($this->selectedMemberId === null) {
            return null;
        }

        return $this->response->household?->householdMembers
            ->firstWhere('id', $this->selectedMemberId);
    }

    protected function getMemberContext(Question $question): ?HouseholdMember
    {
        if ($question->section?->code !== 'MEMBER') {
            return null;
        }

        $member = $this->getSelectedMember();

        if ($member === null) {
            throw new \InvalidArgumentException(
                'Debe seleccionar un miembro del hogar.'
            );
        }

        return $member;
    }

    protected function getAnswer(Question $question): ?Answer
    {
        $member = $this->getMemberContext($question);

        return $this->response->answers
            ->where('question_id', $question->id)
            ->filter(function (Answer $answer) use ($member): bool {
                return $answer->household_member_id === $member?->id;
            })
            ->first();
    }

    public function render()
    {
        $sections = $this->getSections();

        $currentSection = $sections->get($this->currentSectionIndex);

        $members = $this->response->household?->householdMembers ?? collect();

        return view('livewire.survey.form', [
            'sections' => $sections,
            'currentSection' => $currentSection,
            'questions' => $currentSection?->questions ?? collect(),
            'members' => $members,
        ]);
    }

    public function saveSingleChoice(
        int $questionId,
        int $optionId,
        SurveyResponseService $surveyResponseService
    ): void {
        $question = Question::query()
            ->with(['section', 'questionType'])
            ->findOrFail($questionId);

        if ($question->questionType->code !== 'single_choice') {
            abort(404);
        }

        $surveyResponseService->saveAnswer(
            response: $this->response,
            question: $question,
            value: $optionId,
            householdMember: $this->getMemberContext($question),
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
            ->with(['section', 'questionType'])
            ->findOrFail($questionId);

        if ($question->questionType->code !== 'text') {
            abort(404);
        }

        $surveyResponseService->saveAnswer(
            response: $this->response,
            question: $question,
            value: $value,
            householdMember: $this->getMemberContext($question),
        );

        $this->response->load('answers');
    }

    public function saveNumber(
        int $questionId,
        string $value,
        SurveyResponseService $surveyResponseService
    ): void {
        $question = Question::query()
            ->with(['section', 'questionType'])
            ->findOrFail($questionId);

        if ($question->questionType->code !== 'number') {
            abort(404);
        }

        $surveyResponseService->saveAnswer(
            response: $this->response,
            question: $question,
            value: $value,
            householdMember: $this->getMemberContext($question),
        );

        $this->response->load('answers');
    }

    public function saveBoolean(
        int $questionId,
        mixed $value,
        SurveyResponseService $surveyResponseService
    ): void {
        $question = Question::query()
            ->with(['section', 'questionType'])
            ->findOrFail($questionId);

        if ($question->questionType->code !== 'boolean') {
            abort(404);
        }

        $surveyResponseService->saveAnswer(
            response: $this->response,
            question: $question,
            value: $value,
            householdMember: $this->getMemberContext($question),
        );

        $this->response->load('answers');
    }

    //HouseHold protected
    protected function ensureHousehold(): Household
    {
        $household = $this->response->household()->first();

        if ($household !== null) {
            return $household;
        }

        $headRelationship = HouseholdRelationship::query()
            ->where('code', 'HEAD')
            ->where('active', true)
            ->firstOrFail();

        $household = $this->response->household()->create();

        $household->householdMembers()->create([
            'relationship_id' => $headRelationship->id,
            'name' => 'Jefe/a de hogar',
            'age' => null,
            'sex' => null,
            'dni' => null,
        ]);

        return $household->load('householdMembers');
    }
}

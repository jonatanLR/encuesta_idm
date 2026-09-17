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
    public bool $showAddMemberModal = false;

    public string $newMemberName = '';

    public ?int $newMemberRelationshipId = null;

    public ?string $memberAddedMessage = null;

    public array $memberForm = [];

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

        if ($this->selectedMemberId !== null) {
            $this->loadMemberForm($this->selectedMemberId);
        }
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

        $this->loadMemberForm($memberId);
    }

    protected function loadMemberForm(int $memberId): void
    {
        $member = $this->response->household?->householdMembers
            ->firstWhere('id', $memberId);

        if ($member === null) {
            return;
        }

        $this->memberForm = [
            'member_id' => $member->id,
            'name' => $member->name ?? '',
            'age' => $member->age !== null
                ? (string) $member->age
                : '',
            'sex' => $member->sex ?? '',
            'relationship_id' => $member->relationship_id,
            'dni' => $member->dni ?? '',
        ];
    }

    public function openAddMemberModal(): void
    {
        $this->newMemberName = '';
        $this->newMemberRelationshipId = null;
        $this->memberAddedMessage = null;
        $this->resetValidation();

        $this->showAddMemberModal = true;
    }

    public function closeAddMemberModal(): void
    {
        $this->showAddMemberModal = false;
    }

    public function addMember(): void
    {
        $this->validate([
            'newMemberName' => [
                'required',
                'string',
                'max:255',
            ],
            'newMemberRelationshipId' => [
                'required',
                'integer',
                'exists:household_relationships,id',
            ],
        ], [
            'newMemberName.required' => 'El nombre completo es obligatorio.',
            'newMemberName.max' => 'El nombre completo no puede superar los 255 caracteres.',
            'newMemberRelationshipId.required' => 'Debe seleccionar una relación.',
            'newMemberRelationshipId.exists' => 'La relación seleccionada no es válida.',
        ]);

        $household = $this->ensureHousehold();

        $relationship = HouseholdRelationship::query()
            ->whereKey($this->newMemberRelationshipId)
            ->where('active', true)
            ->where('code', '!=', 'HEAD')
            ->firstOrFail();

        $member = $household->householdMembers()->create([
            'relationship_id' => $relationship->id,
            'name' => trim($this->newMemberName),
            'age' => null,
            'sex' => null,
            'dni' => null,
            'capture_started_at' => null,
        ]);

        $this->response->load([
            'household.householdMembers',
        ]);

        $this->selectedMemberId = $member->id;

        $this->newMemberName = '';
        $this->newMemberRelationshipId = null;
        $this->showAddMemberModal = false;

        $this->memberAddedMessage = 'Miembro agregado correctamente.';
    }

    public function saveMemberName(
        int $memberId,
        string $value
    ): void {
        $member = $this->getMemberForEditing($memberId);

        $value = trim($value);

        if ($value === '') {
            return;
        }

        $member->update([
            'name' => $value,
        ]);

        $this->response->load('household.householdMembers');
    }

    public function saveMemberAge(
        int $memberId,
        mixed $value
    ): void {
        $member = $this->getMemberForEditing($memberId);

        if ($value === null || $value === '') {
            $member->update([
                'age' => null,
            ]);

            return;
        }

        if (! is_numeric($value)) {
            throw new \InvalidArgumentException(
                'La edad debe ser un número.'
            );
        }

        $age = (float) $value;

        if ($age < 0 || $age > 120) {
            throw new \InvalidArgumentException(
                'La edad debe estar entre 0 y 120 años.'
            );
        }

        $member->update([
            'age' => $age,
        ]);

        $this->response->load('household.householdMembers');
    }

    public function saveMemberSex(
        int $memberId,
        string $value
    ): void {
        $member = $this->getMemberForEditing($memberId);

        if (! in_array($value, ['femenino', 'masculino'], true)) {
            throw new \InvalidArgumentException(
                'El sexo seleccionado no es válido.'
            );
        }

        $member->update([
            'sex' => $value,
        ]);

        $this->response->load('household.householdMembers');
    }

    public function updatedMemberFormSex($value): void
    {
        if ($this->selectedMemberId === null) {
            return;
        }

        $this->saveMemberSex(
            $this->selectedMemberId,
            $value
        );
    }

    public function saveMemberRelationship(
        int $memberId,
        int $relationshipId
    ): void {
        $member = $this->getMemberForEditing($memberId);

        $relationship = HouseholdRelationship::query()
            ->whereKey($relationshipId)
            ->where('active', true)
            ->where('code', '!=', 'HEAD')
            ->first();

        if ($relationship === null) {
            throw new \InvalidArgumentException(
                'La relación seleccionada no es válida.'
            );
        }

        if ($member->householdRelationship?->code === 'HEAD') {
            throw new \InvalidArgumentException(
                'El jefe/a de hogar no puede cambiar su relación.'
            );
        }

        $member->update([
            'relationship_id' => $relationship->id,
        ]);

        $this->response->load('household.householdMembers.householdRelationship');
    }

    public function saveMemberDni(
        int $memberId,
        string $value
    ): void {
        $member = $this->getMemberForEditing($memberId);

        $value = trim($value);

        $member->update([
            'dni' => $value !== '' ? $value : null,
        ]);

        $this->response->load('household.householdMembers');
    }

    protected function getMemberForEditing(
        int $memberId
    ): HouseholdMember {
        $member = $this->response->household?->householdMembers
            ->firstWhere('id', $memberId);

        if ($member === null) {
            throw new \InvalidArgumentException(
                'El miembro no pertenece al hogar de esta encuesta.'
            );
        }

        if ($member->trashed()) {
            throw new \InvalidArgumentException(
                'El miembro eliminado no puede modificarse.'
            );
        }

        if ($member->capture_started_at === null) {
            throw new \InvalidArgumentException(
                'La captura de información del miembro aún no ha iniciado.'
            );
        }

        return $member;
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

        $relationships = HouseholdRelationship::query()
            ->where('active', true)
            ->where('code', '!=', 'HEAD')
            ->orderBy('sort_order')
            ->get();

        return view('livewire.survey.form', [
            'sections' => $sections,
            'currentSection' => $currentSection,
            'questions' => $currentSection?->questions ?? collect(),
            'members' => $members,
            'relationships' => $relationships,
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

<?php

namespace App\Livewire\Survey;

use App\Models\Answer;
use App\Models\Community;
use App\Models\Household;
use App\Models\HouseholdMember;
use App\Models\HouseholdRelationship;
use App\Models\Question;
use App\Models\Section;
use App\Models\SurveyFile;
use App\Models\SurveyResponse;
use App\Services\ConditionEvaluator;
use App\Services\HouseholdMemberService;
use App\Services\SurveyResponseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class Form extends Component
{
    use WithFileUploads;

    public SurveyResponse $response;

    public int $currentSectionIndex = 0;

    public ?int $selectedMemberId = null;

    public bool $showAddMemberModal = false;

    public string $newMemberName = '';

    public ?int $newMemberRelationshipId = null;

    public ?string $memberAddedMessage = null;

    public array $memberForm = [];

    public $dniFrontPhoto;

    public $dniBackPhoto;

    public array $imageFiles = [];

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
            'household.householdMembers.surveyFiles',
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
            'household.householdMembers.surveyFiles',
        ]);

        $this->selectedMemberId = $member->id;
        $this->loadMemberForm($member->id);

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

        $this->response->load('household.householdMembers.surveyFiles');
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

        $this->response->load('household.householdMembers.surveyFiles');
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

        $this->response->load('household.householdMembers.surveyFiles');
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

    public function updatedDniFrontPhoto($value): void
    {
        if (! $value instanceof TemporaryUploadedFile) {
            return;
        }

        $this->saveDniFrontPhoto();
    }

    public function updatedDniBackPhoto($value): void
    {
        if (! $value instanceof TemporaryUploadedFile) {
            return;
        }

        $this->saveDniBackPhoto();
    }

    public function saveDniFrontPhoto(): void
    {
        if (! $this->dniFrontPhoto instanceof TemporaryUploadedFile) {
            return;
        }

        $this->saveDniPhoto(
            file: $this->dniFrontPhoto,
            fileType: 'dni_front',
        );

        $this->dniFrontPhoto = null;
    }

    public function saveDniBackPhoto(): void
    {
        if (! $this->dniBackPhoto instanceof TemporaryUploadedFile) {
            return;
        }

        $this->saveDniPhoto(
            file: $this->dniBackPhoto,
            fileType: 'dni_back',
        );

        $this->dniBackPhoto = null;
    }

    public function updatedImageFiles($value, $questionId): void
    {
        if (! $value instanceof TemporaryUploadedFile) {
            return;
        }

        $this->saveImage(
            questionId: (int) $questionId,
            file: $value,
        );

        $this->imageFiles[$questionId] = null;
    }

    public function saveImage(
        int $questionId,
        TemporaryUploadedFile $file
    ): void {
        $question = Question::query()
            ->with(['section', 'questionType'])
            ->findOrFail($questionId);

        if ($question->questionType?->code !== 'image') {
            abort(404);
        }

        $member = $this->getMemberContext($question);

        if ($member !== null) {
            $member = $this->getMemberForEditing($member->id);
        }

        $imageProperty = "imageFiles.{$questionId}";

        $this->imageFiles[$questionId] = $file;
        $this->validate([
            $imageProperty => [
                'image',
                'mimes:jpg,jpeg,png',
                'max:5120',
            ],
        ], [
            "{$imageProperty}.image" => 'La fotografía debe ser una imagen.',
            "{$imageProperty}.mimes" => 'La fotografía debe estar en formato JPG o PNG.',
            "{$imageProperty}.max" => 'La fotografía no puede superar los 5 MB.',
        ]);

        $answer = Answer::firstOrNew([
            'survey_response_id' => $this->response->id,
            'question_id' => $question->id,
            'household_member_id' => $member?->id,
        ]);
        $answer->save();

        $existingFile = $answer->surveyFiles()->first();

        if ($existingFile !== null) {
            Storage::disk($existingFile->disk)
                ->delete($existingFile->path);

            $existingFile->delete();
        }

        $directory = $member === null
            ? sprintf(
                'surveys/%s/questions/%s',
                $this->response->public_id,
                $question->code,
            )
            : sprintf(
                'surveys/%s/members/%s/questions/%s',
                $this->response->public_id,
                $member->public_id,
                $question->code,
            );

        $path = $file->store($directory, 'public');

        $answer->surveyFiles()->create([
            'survey_response_id' => $this->response->id,
            'answer_id' => $answer->id,
            'household_member_id' => $member?->id,
            'file_type' => 'image',
            'disk' => 'public',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);

        $this->response->load('answers');
    }

    protected function saveDniPhoto(
        TemporaryUploadedFile $file,
        string $fileType
    ): void {
        if ($this->selectedMemberId === null) {
            throw new \InvalidArgumentException(
                'Debe seleccionar un miembro del hogar.'
            );
        }

        if (! in_array($fileType, ['dni_front', 'dni_back'], true)) {
            throw new \InvalidArgumentException(
                'El tipo de fotografía del DNI no es válido.'
            );
        }

        $member = $this->getMemberForEditing(
            $this->selectedMemberId
        );

        $rules = [
            'image',
            'mimes:jpg,jpeg,png',
            'max:5120',
        ];

        $validator = validator(
            ['file' => $file],
            ['file' => $rules],
            [
                'file.image' => 'La fotografía debe ser una imagen.',
                'file.mimes' => 'La fotografía debe estar en formato JPG o PNG.',
                'file.max' => 'La fotografía no puede superar los 5 MB.',
            ]
        );

        $validator->validate();

        $existingFile = $member->surveyFiles()
            ->where('file_type', $fileType)
            ->first();

        if ($existingFile !== null) {
            Storage::disk($existingFile->disk)
                ->delete($existingFile->path);

            $existingFile->delete();
        }

        $directory = sprintf(
            'surveys/%s/members/%s/dni',
            $this->response->public_id,
            $member->public_id,
        );

        $path = $file->store($directory, 'public');

        $member->surveyFiles()->create([
            'survey_response_id' => $this->response->id,
            'household_member_id' => $member->id,
            'file_type' => $fileType,
            'disk' => 'public',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);
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

        $this->response->load([
            'household.householdMembers.householdRelationship',
            'household.householdMembers.surveyFiles',
        ]);
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

        $this->response->load('household.householdMembers.surveyFiles');
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
            'household.householdMembers.surveyFiles',
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

    protected function shouldShowQuestion(Question $question): bool
    {
        $member = $this->getMemberContext($question);

        return app(ConditionEvaluator::class)->shouldShow(
            $this->response,
            $question,
            $member,
        );
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

    protected function getImageFile(Question $question): ?SurveyFile
    {
        return $this->getAnswer($question)?->surveyFiles()->first();
    }

    public function render()
    {
        $sections = $this->getSections();

        $currentSection = $sections->get($this->currentSectionIndex);
        $localSubsection = $this->getLocalSubsection($currentSection);

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
            'localSubsection' => $localSubsection,
            'localQuestions' => $localSubsection?->questions ?? collect(),
            'members' => $members,
            'relationships' => $relationships,
        ]);
    }

    private function getLocalSubsection(?Section $currentSection): ?Section
    {
        if ($currentSection?->code !== 'HOUSING') {
            return null;
        }

        return $this->response->surveyVersion
            ->sections()
            ->where('parent_id', $currentSection->id)
            ->where('code', 'LOCAL')
            ->where('active', true)
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
            ->first();
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

        if (! in_array($question->questionType->code, ['text', 'textarea'], true)) {
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

    public function saveLocation(float $latitude, float $longitude): void
    {
        if ($latitude < -90 || $latitude > 90) {
            abort(422, 'La latitud no es válida.');
        }

        if ($longitude < -180 || $longitude > 180) {
            abort(422, 'La longitud no es válida.');
        }

        $this->response->latitude = round($latitude, 7);
        $this->response->longitude = round($longitude, 7);
        $this->response->location_source = 'map';
        $this->response->save();
    }

    public function saveNumber(
        int $questionId,
        string $value,
        SurveyResponseService $surveyResponseService
    ): void {
        $question = Question::query()
            ->with(['section', 'questionType'])
            ->findOrFail($questionId);

        if (! in_array($question->questionType->code, ['number', 'decimal'], true)) {
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

    // HouseHold protected
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

    protected function getDniFrontPhoto(?HouseholdMember $member): ?SurveyFile
    {
        return $member?->surveyFiles
            ->firstWhere('file_type', 'dni_front');
    }

    protected function getDniBackPhoto(?HouseholdMember $member): ?SurveyFile
    {
        return $member?->surveyFiles
            ->firstWhere('file_type', 'dni_back');
    }
}

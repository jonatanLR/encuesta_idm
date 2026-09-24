<?php

namespace App\Livewire\SurveyManagement\Sections;

use App\Models\Questionnaire;
use App\Models\Section;
use App\Models\SurveyVersion;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Index extends Component
{
    public Questionnaire $questionnaire;

    public SurveyVersion $version;

    public bool $showFormModal = false;

    public ?int $editingSectionId = null;

    public string $code = '';

    public string $name = '';

    public ?string $description = null;

    public ?int $parentId = null;

    public int $sortOrder = 1;

    public bool $active = true;

    public function mount(
        Questionnaire $questionnaire,
        SurveyVersion $version
    ): void {
        if ($version->questionnaire_id !== $questionnaire->id) {
            abort(404);
        }

        $this->questionnaire = $questionnaire;
        $this->version = $version;
    }

    public function openCreateModal(): void
    {
        $this->authorize('create', Section::class);

        $this->resetForm();

        $this->sortOrder = $this->nextSortOrder();

        $this->showFormModal = true;
    }

    public function openEditModal(int $sectionId): void
    {
        $section = $this->findSection($sectionId);

        $this->authorize('update', $section);

        $this->editingSectionId = $section->id;
        $this->code = $section->code;
        $this->name = $section->name;
        $this->description = $section->description;
        $this->parentId = $section->parent_id;
        $this->sortOrder = $section->sort_order;
        $this->active = $section->active;

        $this->resetValidation();

        $this->showFormModal = true;
    }

    public function saveSection(): void
    {
        if ($this->editingSectionId === null) {
            $this->authorize('create', Section::class);
        } else {
            $section = $this->findSection($this->editingSectionId);

            $this->authorize('update', $section);
        }

        $validated = $this->validate([
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('sections', 'code')
                    ->where(
                        fn($query) =>
                        $query->where(
                            'survey_version_id',
                            $this->version->id
                        )
                    )
                    ->ignore($this->editingSectionId),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'description' => [
                'nullable',
                'string',
            ],
            'parentId' => [
                'nullable',
                'integer',
            ],
            'sortOrder' => [
                'required',
                'integer',
                'min:1',
            ],
            'active' => [
                'boolean',
            ],
        ]);

        $this->validateParent($validated['parentId']);

        if ($this->editingSectionId === null) {
            $this->version->sections()->create([
                'parent_id' => $validated['parentId'],
                'code' => $validated['code'],
                'name' => $validated['name'],
                'description' => $validated['description'],
                'sort_order' => $validated['sortOrder'],
                'active' => $validated['active'],
            ]);

            $message = 'La sección se creó correctamente.';
        } else {
            $section = $this->findSection($this->editingSectionId);

            $section->update([
                'parent_id' => $validated['parentId'],
                'code' => $validated['code'],
                'name' => $validated['name'],
                'description' => $validated['description'],
                'sort_order' => $validated['sortOrder'],
                'active' => $validated['active'],
            ]);

            $message = 'La sección se actualizó correctamente.';
        }

        $this->showFormModal = false;

        $this->resetForm();

        session()->flash('success', $message);
    }

    public function toggleActive(int $sectionId): void
    {
        $section = $this->findSection($sectionId);

        $this->authorize('activate', $section);

        $section->update([
            'active' => ! $section->active,
        ]);

        session()->flash(
            'success',
            $section->active
                ? 'La sección se activó correctamente.'
                : 'La sección se desactivó correctamente.'
        );
    }

    private function findSection(int $sectionId): Section
    {
        return $this->version
            ->sections()
            ->whereKey($sectionId)
            ->firstOrFail();
    }

    private function validateParent(?int $parentId): void
    {
        if ($parentId === null) {
            return;
        }

        if (
            $this->editingSectionId !== null
            && $parentId === $this->editingSectionId
        ) {
            $this->addError(
                'parentId',
                'Una sección no puede ser su propia sección padre.'
            );

            throw \Illuminate\Validation\ValidationException::withMessages([
                'parentId' => 'Una sección no puede ser su propia sección padre.',
            ]);
        }

        $parentExists = $this->version
            ->sections()
            ->whereKey($parentId)
            ->exists();

        if (! $parentExists) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'parentId' => 'La sección padre no pertenece a esta versión.',
            ]);
        }

        if (
            $this->editingSectionId !== null
            && $this->isDescendantOf($parentId, $this->editingSectionId)
        ) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'parentId' => 'No se puede seleccionar una sección descendiente como padre.',
            ]);
        }
    }

    private function isDescendantOf(
        int $candidateId,
        int $sectionId
    ): bool {
        $visited = [];

        while ($candidateId !== null) {
            if (in_array($candidateId, $visited, true)) {
                return false;
            }

            $visited[] = $candidateId;

            $section = $this->version
                ->sections()
                ->find($candidateId);

            if (! $section) {
                return false;
            }

            if ($section->parent_id === $sectionId) {
                return true;
            }

            $candidateId = $section->parent_id;
        }

        return false;
    }

    private function nextSortOrder(): int
    {
        return ((int) $this->version
            ->sections()
            ->max('sort_order')) + 1;
    }

    private function resetForm(): void
    {
        $this->reset([
            'editingSectionId',
            'code',
            'name',
            'description',
            'parentId',
            'sortOrder',
            'active',
        ]);

        $this->resetValidation();

        $this->active = true;
    }

    public function render(): View
    {
        $this->authorize('viewAny', Section::class);

        $sections = $this->version
            ->sections()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view(
            'livewire.survey-management.sections.index',
            compact('sections')
        );
    }
}

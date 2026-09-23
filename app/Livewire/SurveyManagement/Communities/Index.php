<?php

namespace App\Livewire\SurveyManagement\Communities;

use App\Models\Community;
use App\Models\Municipality;
use App\Support\CommunitySearch;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public bool $showCreateModal = false;

    public bool $showEditModal = false;

    public bool $showStatusModal = false;

    public ?int $statusCommunityId = null;

    public ?bool $statusTargetActive = null;

    public ?int $editingCommunityId = null;

    public string $source_code = '';

    public string $name = '';

    public string $type = '';

    public ?string $area = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->authorize('create', Community::class);

        $this->resetCreateForm();
        $this->showCreateModal = true;
    }

    public function openEditModal(int $communityId): void
    {
        $community = Community::findOrFail($communityId);

        $this->authorize('update', $community);

        $this->editingCommunityId = $community->id;
        $this->source_code = $community->source_code;
        $this->name = $community->name;
        $this->type = $community->type;
        $this->area = $community->area;

        $this->resetValidation();

        $this->showEditModal = true;
    }

    public function saveCommunity(): void
    {
        $this->authorize('create', Community::class);

        $validated = $this->validate([
            'source_code' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'type' => [
                'required',
                'in:colony,neighborhood,village,hamlet,residential,other',
            ],
            'area' => ['nullable', 'string', 'max:255'],
        ]);

        $municipality = Municipality::query()
            ->where('name', 'Distrito Central')
            ->firstOrFail();

        Community::create([
            'municipality_id' => $municipality->id,
            'source_code' => $validated['source_code'],
            'name' => $validated['name'],
            'search_name' => CommunitySearch::normalize($validated['name']),
            'type' => $validated['type'],
            'area' => $validated['area'],
            'active' => true,
        ]);

        $this->resetCreateForm();
        $this->showCreateModal = false;

        session()->flash(
            'success',
            'La comunidad se creó correctamente.'
        );
    }

    public function updateCommunity(): void
    {
        $community = Community::findOrFail(
            $this->editingCommunityId
        );

        $this->authorize('update', $community);

        $validated = $this->validate([
            'source_code' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'type' => [
                'required',
                'in:colony,neighborhood,village,hamlet,residential,other',
            ],
            'area' => ['nullable', 'string', 'max:255'],
        ]);

        $community->update([
            'source_code' => $validated['source_code'],
            'name' => $validated['name'],
            'search_name' => CommunitySearch::normalize(
                $validated['name']
            ),
            'type' => $validated['type'],
            'area' => $validated['area'],
        ]);

        $this->resetEditForm();
        $this->showEditModal = false;

        session()->flash(
            'success',
            'La comunidad se actualizó correctamente.'
        );
    }

    public function confirmToggleCommunity(int $communityId): void
    {
        $community = Community::findOrFail($communityId);

        $this->authorize('activate', $community);

        $this->statusCommunityId = $community->id;
        $this->statusTargetActive = ! $community->active;

        $this->resetValidation();

        $this->showStatusModal = true;
    }

    public function toggleCommunityStatus(): void
    {
        $community = Community::findOrFail(
            $this->statusCommunityId
        );

        $this->authorize('activate', $community);

        $community->update([
            'active' => $this->statusTargetActive,
        ]);

        $message = $this->statusTargetActive
            ? 'La comunidad se activó correctamente.'
            : 'La comunidad se desactivó correctamente.';

        $this->resetStatusForm();

        $this->showStatusModal = false;

        session()->flash('success', $message);
    }

    private function resetCreateForm(): void
    {
        $this->reset([
            'source_code',
            'name',
            'type',
            'area',
        ]);

        $this->resetValidation();
    }

    private function resetEditForm(): void
    {
        $this->reset([
            'editingCommunityId',
            'source_code',
            'name',
            'type',
            'area',
        ]);

        $this->resetValidation();
    }

    private function resetStatusForm(): void
    {
        $this->reset([
            'statusCommunityId',
            'statusTargetActive',
        ]);

        $this->resetValidation();
    }

    public function render(): View
    {
        $communities = Community::query()
            ->when(
                trim($this->search) !== '',
                function ($query) {
                    $search = trim($this->search);

                    $query->where(function ($query) use ($search) {
                        $query->where(
                            'name',
                            'like',
                            '%' . $search . '%'
                        )->orWhere(
                            'source_code',
                            'like',
                            '%' . $search . '%'
                        );
                    });
                }
            )
            ->orderBy('name')
            ->paginate(20);

        return view(
            'livewire.survey-management.communities.index',
            compact('communities')
        );
    }
}

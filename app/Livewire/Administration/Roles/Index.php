<?php

namespace App\Livewire\Administration\Roles;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Validation\Rule;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public bool $showCreateModal = false;

    public string $name = '';

    public string $slug = '';

    public array $selectedPermissions = [];

    /* variables for editing modal for roles */
    public bool $showEditModal = false;

    public ?int $editingRoleId = null;

    public string $editName = '';

    public string $editSlug = '';

    public array $editSelectedPermissions = [];

    public bool $showStatusModal = false;


    public ?int $statusRoleId = null;

    public string $statusAction = '';

    public string $statusRoleName = '';

    public int $statusUsersCount = 0;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedName(string $value): void
    {
        $this->slug = Str::slug($value);
        $this->resetValidation('slug');
    }

    public function openCreateModal(): void
    {
        $this->authorize('create', Role::class);

        $this->resetCreateForm();
        $this->showCreateModal = true;
    }

    public function saveRole(): void
    {
        $this->authorize('create', Role::class);

        $this->slug = Str::slug($this->name);

        $validated = $this->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name'),
            ],
            'slug' => ['required', 'string', 'max:255', 'unique:roles,slug'],
            'selectedPermissions' => ['array'],
            'selectedPermissions.*' => [
                'integer',
                'distinct',
                'exists:permissions,id',
            ],
        ]);

        DB::transaction(function () use ($validated): void {
            $role = new Role([
                'name' => $validated['name'],
                'slug' => $validated['slug'],
            ]);
            $role->active = true;
            $role->save();

            $role->permissions()->sync($validated['selectedPermissions']);
        });

        $this->resetCreateForm();
        $this->showCreateModal = false;

        session()->flash('success', 'El rol se creó correctamente.');
    }

    public function openEditModal(Role $role): void
    {
        $this->authorize('update', $role);

        if ($role->slug === 'admin') {
            return;
        }

        $this->editingRoleId = $role->id;
        $this->editName = $role->name;
        $this->editSlug = $role->slug;
        $this->editSelectedPermissions = $role->permissions()
            ->pluck('permissions.id')
            ->map(fn($id) => (int) $id)
            ->toArray();

        $this->resetValidation();
        $this->showEditModal = true;
    }

    public function updateRole(): void
    {
        $role = Role::query()->findOrFail($this->editingRoleId);

        $this->authorize('update', $role);

        if ($role->slug === 'admin') {
            return;
        }

        $validated = $this->validate([
            'editName' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')->ignore($role),
            ],
            'editSelectedPermissions' => ['array'],
            'editSelectedPermissions.*' => [
                'integer',
                'distinct',
                'exists:permissions,id',
            ],
        ]);

        DB::transaction(function () use ($role, $validated): void {
            $role->name = $validated['editName'];
            $role->save();

            $role->permissions()->sync($validated['editSelectedPermissions']);
        });

        $this->resetEditForm();
        $this->showEditModal = false;

        session()->flash('success', 'El rol se actualizó correctamente.');
    }

    public function activateRole(int $roleId): void
    {
        $role = Role::query()->findOrFail($roleId);

        $this->authorize('activate', $role);

        if (in_array($role->slug, ['admin', 'survey-admin', 'field-user'], true)) {
            return;
        }

        $role->active = true;
        $role->save();

        $this->showStatusModal = false;

        session()->flash('success', 'El rol se activó correctamente.');
    }

    public function deactivateRole(int $roleId): void
    {
        $role = Role::query()->findOrFail($roleId);

        $this->authorize('activate', $role);

        if (in_array($role->slug, ['admin', 'survey-admin', 'field-user'], true)) {
            return;
        }

        $role->active = false;
        $role->save();

        $this->showStatusModal = false;

        session()->flash('success', 'El rol se desactivó correctamente.');
    }

    public function openStatusModal(Role $role, string $action): void
    {
        $this->authorize('activate', $role);

        if (in_array($role->slug, ['admin', 'survey-admin', 'field-user'], true)) {
            return;
        }

        if (! in_array($action, ['activate', 'deactivate'], true)) {
            return;
        }

        $this->statusRoleId = $role->id;
        $this->statusAction = $action;
        $this->statusRoleName = $role->name;
        $this->statusUsersCount = $role->users()->count();

        $this->showStatusModal = true;
    }

    private function resetEditForm(): void
    {
        $this->reset([
            'editingRoleId',
            'editName',
            'editSlug',
            'editSelectedPermissions',
        ]);

        $this->resetValidation();
    }

    private function resetCreateForm(): void
    {
        $this->reset([
            'name',
            'slug',
            'selectedPermissions',
        ]);

        $this->resetValidation();
    }

    public function render(): View
    {
        $this->authorize('viewAny', Role::class);

        $roles = Role::query()
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
                            'slug',
                            'like',
                            '%' . $search . '%'
                        );
                    });
                }
            )
            ->withCount('users')
            ->orderBy('name')
            ->paginate(20);

        $moduleNames = [
            'community' => 'Comunidades',
            'question' => 'Preguntas',
            'question-condition' => 'Condiciones de preguntas',
            'question-option' => 'Opciones de preguntas',
            'questionnaire' => 'Cuestionarios',
            'section' => 'Secciones',
            'survey' => 'Encuestas',
            'user' => 'Usuarios',
        ];

        $permissionGroups = Permission::query()
            ->orderBy('slug')
            ->get()
            ->groupBy(fn(Permission $permission): string => Str::before($permission->slug, '.'))
            ->map(fn($permissions, string $module): array => [
                'name' => $moduleNames[$module] ?? Str::headline($module),
                'permissions' => $permissions,
            ]);

        return view(
            'livewire.administration.roles.index',
            compact('roles', 'permissionGroups')
        );
    }
}

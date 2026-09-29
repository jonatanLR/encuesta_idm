<?php

namespace App\Livewire\Administration\Users;

use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public bool $showCreateModal = false;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public ?int $role_id = null;

    public bool $active = true;

    /* propidades para editar usuario */
    public bool $showEditModal = false;

    public ?int $editingUserId = null;

    public string $editName = '';

    public string $editEmail = '';

    public string $editPassword = '';

    public string $editPasswordConfirmation = '';

    public ?int $editRoleId = null;

    public bool $editActive = true;

    /* propiedades para el cambio de estado */
    public bool $showStatusModal = false;

    public ?int $statusUserId = null;

    public ?bool $statusTargetActive = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->authorize('create', User::class);

        $this->resetCreateForm();

        $this->showCreateModal = true;
    }

    public function saveUser(): void
    {
        $this->authorize('create', User::class);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => [
                'required',
                'confirmed',
                Password::defaults(),
            ],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'active' => ['boolean'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'active' => $validated['active'],
        ]);

        $user->forceFill([
            'email_verified_at' => now(),
        ])->save();

        $user->roles()->sync([
            $validated['role_id'],
        ]);

        $this->resetCreateForm();

        $this->showCreateModal = false;

        session()->flash(
            'success',
            'El usuario se creó correctamente.'
        );
    }

    private function resetCreateForm(): void
    {
        $this->reset([
            'name',
            'email',
            'password',
            'password_confirmation',
            'role_id',
        ]);

        $this->active = true;

        $this->resetValidation();
    }

    public function render(): View
    {
        $users = User::query()
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
                            'email',
                            'like',
                            '%' . $search . '%'
                        );
                    });
                }
            )
            ->with('roles')
            ->orderBy('name')
            ->paginate(20);

        $roles = Role::query()
            ->orderBy('name')
            ->get();

        return view(
            'livewire.administration.users.index',
            compact('users', 'roles')
        );
    }

    /* funciones para administrar el modal de edición */
    public function openEditModal(int $userId): void
    {
        $user = User::findOrFail($userId);

        $this->authorize('update', $user);

        $this->editingUserId = $user->id;
        $this->editName = $user->name;
        $this->editEmail = $user->email;
        $this->editPassword = '';
        $this->editPasswordConfirmation = '';
        $this->editRoleId = $user->roles->first()?->id;
        $this->editActive = $user->active;

        $this->resetValidation();

        $this->showEditModal = true;
    }

    /* Actualizar usuario */
    public function updateUser(): void
    {
        $user = User::findOrFail($this->editingUserId);

        $this->authorize('update', $user);

        $validated = $this->validate([
            'editName' => ['required', 'string', 'max:255'],
            'editEmail' => [
                'required',
                'email',
                'max:255',
                'unique:users,email,' . $user->id,
            ],
            'editPassword' => [
                'nullable',
                'confirmed:editPasswordConfirmation',
                Password::defaults(),
            ],
            'editRoleId' => ['required', 'integer', 'exists:roles,id'],
            'editActive' => ['boolean'],
        ]);

        if ($validated['editActive'] !== $user->active) {
            $this->authorize('activate', $user);

            if (
                $user->active
                && ! $validated['editActive']
                && $this->isLastActiveAdmin($user)
            ) {
                $this->addError(
                    'editActive',
                    'No se puede desactivar al último administrador activo del sistema.'
                );

                return;
            }
        }

        $selectedRole = Role::findOrFail($validated['editRoleId']);

        if (
            $user->active
            && $user->hasRole('admin')
            && $selectedRole->slug !== 'admin'
            && $this->isLastActiveAdmin($user)
        ) {
            $this->addError(
                'editRoleId',
                'No se puede quitar el rol de administrador al último administrador activo del sistema.'
            );

            return;
        }

        $user->name = $validated['editName'];
        $user->email = $validated['editEmail'];
        $user->active = $validated['editActive'];

        if (filled($validated['editPassword'])) {
            $user->password = $validated['editPassword'];
        }

        $user->save();

        $user->roles()->sync([
            $validated['editRoleId'],
        ]);

        $this->resetEditForm();

        $this->showEditModal = false;

        session()->flash(
            'success',
            'El usuario se actualizó correctamente.'
        );
    }

    /* Reiniciar formulario de edición */
    private function resetEditForm(): void
    {
        $this->reset([
            'editingUserId',
            'editName',
            'editEmail',
            'editPassword',
            'editPasswordConfirmation',
            'editRoleId',
        ]);

        $this->editActive = true;

        $this->resetValidation();
    }

    /* Confirmar cambio de estado del usuario */
    public function confirmToggleUser(int $userId): void
    {
        $user = User::findOrFail($userId);

        $this->authorize('activate', $user);

        $this->statusUserId = $user->id;
        $this->statusTargetActive = ! $user->active;

        $this->resetValidation();

        $this->showStatusModal = true;
    }

    /* Cambiar estado del usuario */
    public function toggleUserStatus(): void
    {
        $user = User::findOrFail($this->statusUserId);

        $this->authorize('activate', $user);

        if (
            $user->active
            && ! $this->statusTargetActive
            && $this->isLastActiveAdmin($user)
        ) {
            $this->addError(
                'status',
                'No se puede desactivar al último administrador activo del sistema.'
            );

            return;
        }

        $user->update([
            'active' => $this->statusTargetActive,
        ]);

        $message = $this->statusTargetActive
            ? 'El usuario se activó correctamente.'
            : 'El usuario se desactivó correctamente.';

        $this->resetStatusForm();

        $this->showStatusModal = false;

        session()->flash('success', $message);
    }

    /* Verificar si es el último administrador activo */
    private function isLastActiveAdmin(User $user): bool
    {
        if (! $user->hasRole('admin')) {
            return false;
        }

        return User::query()
            ->where('active', true)
            ->whereHas('roles', function ($query) {
                $query->where('slug', 'admin');
            })
            ->whereKeyNot($user->id)
            ->doesntExist();
    }

    /* Reiniciar formulario de estado */
    private function resetStatusForm(): void
    {
        $this->reset([
            'statusUserId',
            'statusTargetActive',
        ]);

        $this->resetValidation();
    }
}

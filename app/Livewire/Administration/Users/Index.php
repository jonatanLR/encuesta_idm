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
            'email_verified_at' => now(),
        ]);

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
}

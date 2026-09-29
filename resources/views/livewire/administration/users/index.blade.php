<div class="space-y-6">

    @if (session('success'))
        <flux:callout variant="success" icon="check-circle">
            {{ session('success') }}
        </flux:callout>
    @endif

    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Usuarios</flux:heading>
            <flux:text class="mt-1">
                Gestiona los usuarios del sistema.
            </flux:text>
        </div>
        @can('create', \App\Models\User::class)
            <flux:button variant="outline" color="emerald" wire:click="openCreateModal">
                + Nuevo usuario
            </flux:button>
        @endcan

    </div>

    <div class="flex items-center gap-4">
        <div class="w-full max-w-md">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Buscar por nombre o correo..."
                icon="magnifying-glass" />
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
                    <tr>
                        <th class="px-4 py-3 font-medium">Nombre</th>
                        <th class="px-4 py-3 font-medium">Correo</th>
                        <th class="px-4 py-3 font-medium">Rol</th>
                        <th class="px-4 py-3 font-medium">Estado</th>
                        <th class="px-4 py-3 font-medium">Último acceso</th>
                        <th class="px-4 py-3 text-center font-medium">Acciones</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($users as $user)
                        <tr>
                            <td class="px-4 py-3 font-medium">
                                {{ $user->name }}
                            </td>

                            <td class="px-4 py-3">
                                {{ $user->email }}
                            </td>

                            <td class="px-4 py-3">
                                {{ $user->roles->first()?->name ?? 'Sin rol' }}
                            </td>

                            <td class="px-4 py-3">
                                @if ($user->active)
                                    <span
                                        class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-1 text-xs font-medium text-green-700">
                                        Activo
                                    </span>
                                @else
                                    <span
                                        class="inline-flex items-center rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-medium text-zinc-600">
                                        Inactivo
                                    </span>
                                @endif
                            </td>

                            <td class="px-4 py-3">
                                {{ $user->last_login_at?->format('d/m/Y H:i') ?? 'Nunca' }}
                            </td>

                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-center gap-2">

                                    @can('update', $user)
                                        <flux:button size="sm" variant="ghost" color="blue"
                                            wire:click="openEditModal({{ $user->id }})">
                                            Editar
                                        </flux:button>
                                    @endcan
                                    @can('activate', $user)
                                        @if ($user->active)
                                            <flux:button size="sm" variant="ghost" color="red"
                                                wire:click="confirmToggleUser({{ $user->id }})">
                                                Desactivar
                                            </flux:button>
                                        @else
                                            <flux:button size="sm" variant="ghost" color="green"
                                                wire:click="confirmToggleUser({{ $user->id }})">
                                                Activar
                                            </flux:button>
                                        @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-zinc-500">
                                No se encontraron usuarios.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($users->hasPages())
            <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">
                {{ $users->links() }}
            </div>
        @endif
    </div>

    {{-- modal para crear un nuevo usuario  --}}
    @if ($showCreateModal)
        <flux:modal wire:model="showCreateModal" name="create-user">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        Nuevo usuario
                    </flux:heading>

                    <flux:text class="mt-1">
                        Registra un nuevo usuario del sistema.
                    </flux:text>
                </div>

                <div class="space-y-4">
                    <flux:input wire:model="name" label="Nombre" placeholder="Ingrese el nombre" required />

                    <flux:input wire:model="email" type="email" label="Correo electrónico"
                        placeholder="Ingrese el correo electrónico" required />

                    <flux:input wire:model="password" type="password" label="Contraseña"
                        placeholder="Ingrese la contraseña" required />

                    <flux:input wire:model="password_confirmation" type="password" label="Confirmar contraseña"
                        placeholder="Repita la contraseña" required />

                    <flux:select wire:model="role_id" label="Rol" required>
                        <flux:select.option value="">
                            Seleccione un rol
                        </flux:select.option>

                        @foreach ($roles as $role)
                            <flux:select.option value="{{ $role->id }}">
                                {{ $role->name }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:checkbox wire:model="active" label="Usuario activo" />
                </div>

                <div class="flex justify-end gap-2">
                    <flux:button type="button" variant="ghost" x-on:click="$flux.modal('create-user').close()">
                        Cancelar
                    </flux:button>

                    <flux:button type="button" variant="primary" wire:click="saveUser" wire:loading.attr="disabled">
                        Guardar
                    </flux:button>
                </div>
            </div>
        </flux:modal>
    @endif


    {{-- modal para editar un usuario  --}}
    @if ($showEditModal)
        <flux:modal wire:model.self="showEditModal" name="edit-user">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        Editar usuario
                    </flux:heading>

                    <flux:text class="mt-1">
                        Modifica los datos del usuario seleccionado.
                    </flux:text>
                </div>

                <div class="space-y-4">
                    <flux:input wire:model="editName" label="Nombre" placeholder="Ingrese el nombre" required />

                    <flux:input wire:model="editEmail" type="email" label="Correo electrónico"
                        placeholder="Ingrese el correo electrónico" required />

                    <flux:input wire:model="editPassword" type="password" label="Nueva contraseña"
                        placeholder="Dejar vacío para conservar la actual" />

                    <flux:input wire:model="editPasswordConfirmation" type="password"
                        label="Confirmar nueva contraseña" placeholder="Repita la nueva contraseña" />

                    <flux:select wire:model="editRoleId" label="Rol" required>
                        <flux:select.option value="">
                            Seleccione un rol
                        </flux:select.option>

                        @foreach ($roles as $role)
                            <flux:select.option value="{{ $role->id }}">
                                {{ $role->name }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>


                    <flux:checkbox wire:model="editActive" label="Usuario activo" />

                </div>

                <div class="flex justify-end gap-2">
                    <flux:button type="button" variant="ghost" x-on:click="$flux.modal('edit-user').close()">
                        Cancelar
                    </flux:button>

                    <flux:button type="button" variant="primary" wire:click="updateUser"
                        wire:loading.attr="disabled">
                        Guardar cambios
                    </flux:button>
                </div>
            </div>
        </flux:modal>
    @endif

    {{-- Modal para cambiar el estado del usuario --}}
    @if ($showStatusModal)
        <flux:modal wire:model="showStatusModal" name="toggle-user-status">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        {{ $statusTargetActive ? 'Activar usuario' : 'Desactivar usuario' }}
                    </flux:heading>

                    <flux:text class="mt-2">
                        {{ $statusTargetActive
                            ? '¿Desea activar este usuario? Podrá iniciar sesión si sus credenciales son correctas.'
                            : '¿Desea desactivar este usuario? No podrá iniciar sesión mientras permanezca inactivo.' }}
                    </flux:text>
                </div>

                @error('status')
                    <flux:callout variant="danger">
                        {{ $message }}
                    </flux:callout>
                @enderror

                <div class="flex justify-end gap-2">
                    <flux:button variant="ghost" wire:click="$set('showStatusModal', false)">
                        Cancelar
                    </flux:button>

                    <flux:button variant="ghost" color="{{ $statusTargetActive ? 'green' : 'red' }}"
                        wire:click="toggleUserStatus">
                        {{ $statusTargetActive ? 'Activar' : 'Desactivar' }}
                    </flux:button>
                </div>
            </div>
        </flux:modal>
    @endif
</div>

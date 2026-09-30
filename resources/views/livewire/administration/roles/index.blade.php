<div>
    <div class="space-y-6">
        @if (session('success'))
            <flux:callout variant="success" icon="check-circle">
                {{ session('success') }}
            </flux:callout>
        @endif

        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl">Roles</flux:heading>
                <flux:text class="mt-1">
                    Consulta los roles del sistema.
                </flux:text>
            </div>

            @can('create', \App\Models\Role::class)
                <flux:button variant="outline" color="emerald" wire:click="openCreateModal">
                    + Nuevo rol
                </flux:button>
            @endcan
        </div>

        <div class="flex items-center gap-4">
            <div class="w-full max-w-md">
                <flux:input wire:model.live.debounce.300ms="search" placeholder="Buscar por nombre o slug..."
                    icon="magnifying-glass" />
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
                        <tr>
                            <th class="px-4 py-3 font-medium">Nombre</th>
                            <th class="px-4 py-3 font-medium">Slug</th>
                            <th class="px-4 py-3 font-medium">Usuarios</th>
                            <th class="px-4 py-3 font-medium">Estado</th>
                            <th class="px-4 py-3 text-center font-medium">Acción</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse ($roles as $role)
                            <tr>
                                <td class="px-4 py-3 font-medium">
                                    {{ $role->name }}
                                </td>

                                <td class="px-4 py-3">
                                    {{ $role->slug }}
                                </td>

                                <td class="px-4 py-3">
                                    {{ $role->users_count }}
                                </td>

                                <td class="px-4 py-3">
                                    @if ($role->active)
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
                                {{-- Acciones para editar rol segun usuario --}}
                                <td class="px-4 py-3 text-right">
                                    <div class="flex justify-center gap-2">
                                        @if ($role->slug === 'admin')
                                            <flux:button size="sm" variant="ghost" disabled>
                                                Protegido
                                            </flux:button>
                                        @else
                                            <flux:button size="sm" variant="ghost" color="blue"
                                                wire:click="openEditModal({{ $role->id }})">
                                                Editar
                                            </flux:button>
                                        @endif

                                        @if (in_array($role->slug, ['admin', 'survey-admin', 'field-user'], true))
                                            <flux:button size="sm" variant="ghost" disabled>
                                                Protegido
                                            </flux:button>
                                        @elseif ($role->active)
                                            <flux:button size="sm" variant="ghost" color="red"
                                                wire:click="openStatusModal({{ $role->id }}, 'deactivate')">
                                                Desactivar
                                            </flux:button>
                                        @else
                                            <flux:button size="sm" variant="ghost" color="emerald"
                                                wire:click="openStatusModal({{ $role->id }}, 'activate')">
                                                Activar
                                            </flux:button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-zinc-500">
                                    No se encontraron roles.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($roles->hasPages())
                <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">
                    {{ $roles->links() }}
                </div>
            @endif
        </div>

        @if ($showCreateModal)
            <flux:modal wire:model="showCreateModal" name="create-role" class="md:w-[42rem]">
                <div class="space-y-6">
                    <div>
                        <flux:heading size="lg">Nuevo rol</flux:heading>
                        <flux:text class="mt-1">
                            Registra un nuevo rol del sistema.
                        </flux:text>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <flux:input wire:model.live="name" label="Nombre" placeholder="Ingrese el nombre"
                                required />

                        </div>

                        <div>
                            <flux:input wire:model="slug" label="Slug" readonly />

                        </div>

                        <flux:checkbox.group wire:model="selectedPermissions" label="Permisos">
                            @forelse ($permissionGroups as $group)
                                <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                                    <flux:heading size="sm">{{ $group['name'] }}</flux:heading>

                                    <div class="mt-3 space-y-3">
                                        @foreach ($group['permissions'] as $permission)
                                            <flux:checkbox value="{{ $permission->id }}" :label="$permission->name" />
                                        @endforeach
                                    </div>
                                </div>
                            @empty
                                <flux:text>No hay permisos disponibles para asignar.</flux:text>
                            @endforelse
                        </flux:checkbox.group>

                        <flux:error name="selectedPermissions" />
                    </div>

                    <div class="flex justify-end gap-2">
                        <flux:button type="button" variant="ghost" x-on:click="$flux.modal('create-role').close()">
                            Cancelar
                        </flux:button>

                        <flux:button type="button" variant="primary" wire:click="saveRole"
                            wire:loading.attr="disabled">
                            Guardar
                        </flux:button>
                    </div>
                </div>
            </flux:modal>
        @endif

        {{-- modal de edición de roles --}}
        @if ($showEditModal)
            <flux:modal wire:model="showEditModal" name="edit-role" class="md:w-[42rem]">
                <div class="space-y-6">
                    <div>
                        <flux:heading size="lg">Editar rol</flux:heading>
                        <flux:text class="mt-1">
                            Modifica el nombre y los permisos del rol.
                        </flux:text>
                    </div>

                    <div class="space-y-4">
                        <flux:input wire:model.live="editName" label="Nombre" placeholder="Ingrese el nombre"
                            required />

                        <flux:input wire:model="editSlug" label="Slug" readonly />

                        <flux:checkbox.group wire:model="editSelectedPermissions" label="Permisos">
                            @forelse ($permissionGroups as $group)
                                <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                                    <flux:heading size="sm">
                                        {{ $group['name'] }}
                                    </flux:heading>

                                    <div class="mt-3 space-y-3">
                                        @foreach ($group['permissions'] as $permission)
                                            <flux:checkbox value="{{ $permission->id }}" :label="$permission->name" />
                                        @endforeach
                                    </div>
                                </div>
                            @empty
                                <flux:text>
                                    No hay permisos disponibles para asignar.
                                </flux:text>
                            @endforelse
                        </flux:checkbox.group>

                        <flux:error name="editSelectedPermissions" />
                    </div>

                    <div class="flex justify-end gap-2">
                        <flux:button type="button" variant="ghost" x-on:click="$flux.modal('edit-role').close()">
                            Cancelar
                        </flux:button>

                        <flux:button type="button" variant="primary" wire:click="updateRole"
                            wire:loading.attr="disabled">
                            Guardar cambios
                        </flux:button>
                    </div>
                </div>
            </flux:modal>
        @endif

        {{-- modal de estado --}}
        @if ($showStatusModal)
            <flux:modal wire:model="showStatusModal" name="role-status" class="md:w-[32rem]">
                <div class="space-y-6">
                    <div>
                        <flux:heading size="lg">
                            {{ $statusAction === 'deactivate' ? 'Desactivar rol' : 'Activar rol' }}
                        </flux:heading>

                        <flux:text class="mt-1">
                            Rol: <strong>{{ $statusRoleName }}</strong>
                        </flux:text>
                    </div>

                    @if ($statusAction === 'deactivate')
                        <div class="space-y-2">
                            @if ($statusUsersCount > 0)
                                <flux:text>
                                    Este rol está asignado a
                                    <strong>{{ $statusUsersCount }}</strong>
                                    {{ $statusUsersCount === 1 ? 'usuario' : 'usuarios' }}.
                                </flux:text>

                                <flux:text>
                                    Al desactivarlo, dejará de otorgar sus permisos a esos usuarios.
                                </flux:text>
                            @else
                                <flux:text>
                                    Este rol no está asignado actualmente a ningún usuario.
                                </flux:text>
                            @endif

                            <flux:text>
                                ¿Desea continuar?
                            </flux:text>
                        </div>
                    @else
                        <flux:text>
                            Al activar este rol, sus permisos volverán a estar vigentes
                            para los usuarios que lo tengan asignado.
                            ¿Desea continuar?
                        </flux:text>
                    @endif

                    <div class="flex justify-end gap-2">
                        <flux:button type="button" variant="ghost" x-on:click="$flux.modal('role-status').close()">
                            Cancelar
                        </flux:button>

                        @if ($statusAction === 'deactivate')
                            <flux:button type="button" variant="primary"
                                wire:click="deactivateRole({{ $statusRoleId }})" wire:loading.attr="disabled">
                                Desactivar
                            </flux:button>
                        @else
                            <flux:button type="button" variant="primary"
                                wire:click="activateRole({{ $statusRoleId }})" wire:loading.attr="disabled">
                                Activar
                            </flux:button>
                        @endif
                    </div>
                </div>
            </flux:modal>
        @endif
    </div>
</div>

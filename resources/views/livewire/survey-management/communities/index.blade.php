<div class="space-y-6">

    @if (session('success'))
        <flux:callout variant="success" icon="check-circle">
            {{ session('success') }}
        </flux:callout>
    @endif

    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Comunidades</flux:heading>
            <flux:text class="mt-1">
                Gestiona las comunidades disponibles para las encuestas.
            </flux:text>
        </div>

        {{-- boton para abrir el modal de crear una nueva comunidad --}}
        <flux:button variant="primary" wire:click="openCreateModal">
            + Nueva comunidad
        </flux:button>>
    </div>

    <div class="flex items-center gap-4">
        <div class="w-full max-w-md">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Buscar por nombre o código..."
                icon="magnifying-glass" />
        </div>
    </div>

    {{-- tabla para mostrar las comunidades --}}
    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
                    <tr>
                        <th class="px-4 py-3 font-medium">Código</th>
                        <th class="px-4 py-3 font-medium">Nombre</th>
                        <th class="px-4 py-3 font-medium">Tipo</th>
                        <th class="px-4 py-3 font-medium">Estado</th>
                        <th class="px-4 py-3 text-right font-medium">Acciones</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($communities as $community)
                        <tr>
                            <td class="px-4 py-3">
                                {{ $community->source_code }}
                            </td>

                            <td class="px-4 py-3 font-medium">
                                {{ $community->name }}
                            </td>

                            <td class="px-4 py-3">
                                {{ match ($community->type) {
                                    'colony' => 'Colonia',
                                    'neighborhood' => 'Barrio',
                                    'village' => 'Aldea',
                                    'hamlet' => 'Caserío',
                                    'residential' => 'Residencial',
                                    'other' => 'Otro',
                                    default => $community->type,
                                } }}
                            </td>

                            <td class="px-4 py-3">
                                @if ($community->active)
                                    <span
                                        class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-1 text-xs font-medium text-green-700">
                                        Activa
                                    </span>
                                @else
                                    <span
                                        class="inline-flex items-center rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-medium text-zinc-600">
                                        Inactiva
                                    </span>
                                @endif
                            </td>

                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-end gap-2">
                                    <flux:button size="sm" variant="primary" color="blue"
                                        wire:click="openEditModal({{ $community->id }})">
                                        Editar
                                    </flux:button>

                                    @if ($community->active)
                                        <flux:button size="sm" variant="primary" color="red"
                                            wire:click="confirmToggleCommunity({{ $community->id }})">
                                            Desactivar
                                        </flux:button>
                                    @else
                                        <flux:button size="sm" variant="primary" color="green"
                                            wire:click="confirmToggleCommunity({{ $community->id }})">
                                            Activar
                                        </flux:button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-zinc-500">
                                No se encontraron comunidades.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($communities->hasPages())
            <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">
                {{ $communities->links() }}
            </div>
        @endif
    </div>

    {{-- Modal para crear una nueva comunidad --}}
    @if ($showCreateModal)
        <flux:modal wire:model="showCreateModal" name="create-community">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        Nueva comunidad
                    </flux:heading>

                    <flux:text class="mt-1">
                        Registra una nueva comunidad del Distrito Central.
                    </flux:text>
                </div>

                <div class="space-y-4">
                    <flux:input wire:model="source_code" label="Código" placeholder="Ingrese el código" required />

                    <flux:input wire:model="name" label="Nombre" placeholder="Ingrese el nombre de la comunidad"
                        required />

                    <flux:select wire:model="type" label="Tipo" placeholder="Seleccione un tipo" required>
                        <flux:select.option value="colony">
                            Colonia
                        </flux:select.option>

                        <flux:select.option value="neighborhood">
                            Barrio
                        </flux:select.option>

                        <flux:select.option value="village">
                            Aldea
                        </flux:select.option>

                        <flux:select.option value="hamlet">
                            Caserío
                        </flux:select.option>

                        <flux:select.option value="residential">
                            Residencial
                        </flux:select.option>

                        <flux:select.option value="other">
                            Otro
                        </flux:select.option>
                    </flux:select>

                    <flux:input wire:model="area" label="Área" placeholder="Ingrese el área (opcional)" />
                </div>

                <div class="flex justify-end gap-2">
                    <flux:button type="button" variant="ghost" x-on:click="$flux.modal('create-community').close()">
                        Cancelar
                    </flux:button>

                    <flux:button type="button" variant="primary" wire:click="saveCommunity"
                        wire:loading.attr="disabled">
                        Guardar
                    </flux:button>
                </div>
            </div>
        </flux:modal>
    @endif

    {{-- modal para editar una comunidad existente --}}
    @if ($showEditModal)
        <flux:modal wire:model.self="showEditModal" name="edit-community">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        Editar comunidad
                    </flux:heading>

                    <flux:text class="mt-1">
                        Modifica los datos de la comunidad seleccionada.
                    </flux:text>
                </div>

                <div class="space-y-4">
                    <flux:input wire:model="source_code" label="Código" placeholder="Ingrese el código" required />

                    <flux:input wire:model="name" label="Nombre" placeholder="Ingrese el nombre de la comunidad"
                        required />

                    <flux:select wire:model="type" label="Tipo" placeholder="Seleccione un tipo" required>
                        <flux:select.option value="colony">
                            Colonia
                        </flux:select.option>

                        <flux:select.option value="neighborhood">
                            Barrio
                        </flux:select.option>

                        <flux:select.option value="village">
                            Aldea
                        </flux:select.option>

                        <flux:select.option value="hamlet">
                            Caserío
                        </flux:select.option>

                        <flux:select.option value="residential">
                            Residencial
                        </flux:select.option>

                        <flux:select.option value="other">
                            Otro
                        </flux:select.option>
                    </flux:select>

                    <flux:input wire:model="area" label="Área" placeholder="Ingrese el área (opcional)" />
                </div>

                <div class="flex justify-end gap-2">
                    <flux:button type="button" variant="ghost" x-on:click="$flux.modal('edit-community').close()">
                        Cancelar
                    </flux:button>

                    <flux:button type="button" variant="primary" wire:click="updateCommunity"
                        wire:loading.attr="disabled">
                        Guardar cambios
                    </flux:button>
                </div>
            </div>
        </flux:modal>
    @endif

    {{-- modal para confirmar la activación o desactivación de una comunidad --}}
    @if ($showStatusModal)
        <flux:modal wire:model="showStatusModal" name="toggle-community-status">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        {{ $statusTargetActive ? 'Activar comunidad' : 'Desactivar comunidad' }}
                    </flux:heading>

                    <flux:text class="mt-2">
                        {{ $statusTargetActive
                            ? '¿Desea activar esta comunidad? Volverá a estar disponible para la selección en las encuestas.'
                            : '¿Desea desactivar esta comunidad? Ya no estará disponible para nuevas encuestas, pero permanecerá en el sistema y en los registros históricos.' }}
                    </flux:text>
                </div>

                <div class="flex justify-end gap-2">
                    <flux:button variant="ghost" wire:click="$set('showStatusModal', false)">
                        Cancelar
                    </flux:button>

                    <flux:button variant="primary" color="{{ $statusTargetActive ? 'green' : 'red' }}"
                        wire:click="toggleCommunityStatus">
                        {{ $statusTargetActive ? 'Activar' : 'Desactivar' }}
                    </flux:button>
                </div>
            </div>
        </flux:modal>
    @endif

</div>

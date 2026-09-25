<div class="space-y-6">
    <div class="flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl">
                Secciones
            </flux:heading>

            <flux:text class="mt-1">
                {{ $questionnaire->name }} — Versión {{ $version->version }}
            </flux:text>
        </div>

        <div class="flex items-center gap-2">
            <flux:button variant="ghost" color="zinc" size="sm"
                :href="route('survey-management.versions', $questionnaire)">
                ← Volver
            </flux:button>

            <flux:button variant="primary" icon="plus" wire:click="openCreateModal">
                Nueva sección
            </flux:button>
        </div>
    </div>

    @if (session()->has('success'))
        <flux:callout variant="success" icon="check-circle">
            {{ session('success') }}
        </flux:callout>
    @endif

    <flux:card>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 dark:border-zinc-700">
                        <th class="px-4 py-3 text-left font-medium">
                            Orden
                        </th>

                        <th class="px-4 py-3 text-left font-medium">
                            Código
                        </th>

                        <th class="px-4 py-3 text-left font-medium">
                            Nombre
                        </th>

                        <th class="px-4 py-3 text-left font-medium">
                            Padre
                        </th>

                        <th class="px-4 py-3 text-left font-medium">
                            Estado
                        </th>

                        <th class="px-4 py-3 text-center font-medium">
                            Acciones
                        </th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($sections as $section)
                        <tr class="border-b border-zinc-100 dark:border-zinc-800">
                            <td class="px-4 py-3">
                                {{ $section->sort_order }}
                            </td>

                            <td class="px-4 py-3 font-medium">
                                {{ $section->code }}
                            </td>

                            <td class="px-4 py-3">
                                {{ $section->name }}
                            </td>

                            <td class="px-4 py-3">
                                {{ $section->parent?->code ?? '—' }}
                            </td>

                            <td class="px-4 py-3">
                                @if ($section->active)
                                    <flux:badge variant="success" color="green">
                                        Activa
                                    </flux:badge>
                                @else
                                    <flux:badge variant="danger" color="zinc">
                                        Inactiva
                                    </flux:badge>
                                @endif
                            </td>

                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <flux:button variant="ghost" color="blue" size="sm"
                                        wire:click="openEditModal({{ $section->id }})">
                                        Editar
                                    </flux:button>

                                    <flux:button variant="ghost" color="purple" size="sm"
                                        :href="route('survey-management.questions', [
                                                                    'questionnaire' => $questionnaire->id,
                                                                    'version' => $version->id,
                                                                    'section' => $section->id,
                                                                ])">
                                        Preguntas
                                    </flux:button>

                                    <flux:button variant="ghost" :color="$section->active ? 'amber' : 'green'"
                                        size="sm" wire:click="toggleActive({{ $section->id }})">
                                        {{ $section->active ? 'Desactivar' : 'Activar' }}
                                    </flux:button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-zinc-500">
                                No hay secciones registradas en esta versión.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </flux:card>

    <flux:modal name="section-form" wire:model="showFormModal" class="md:w-[40rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">
                    {{ $editingSectionId ? 'Editar sección' : 'Nueva sección' }}
                </flux:heading>

                <flux:text class="mt-1">
                    Configure la estructura de la sección.
                </flux:text>
            </div>

            <flux:field>
                <flux:label>Código</flux:label>

                <flux:input wire:model="code" placeholder="Ej. GENERAL" />

                <flux:error name="code" />
            </flux:field>

            <flux:field>
                <flux:label>Nombre</flux:label>

                <flux:input wire:model="name" placeholder="Nombre de la sección" />

                <flux:error name="name" />
            </flux:field>

            <flux:field>
                <flux:label>Descripción</flux:label>

                <flux:textarea wire:model="description" rows="3" />

                <flux:error name="description" />
            </flux:field>

            <flux:field>
                <flux:label>Sección padre</flux:label>

                <flux:select wire:model="parentId">
                    <option value="">Ninguna</option>

                    @foreach ($sections as $section)
                        @if ($section->id !== $editingSectionId)
                            <option value="{{ $section->id }}">
                                {{ $section->code }} — {{ $section->name }}
                            </option>
                        @endif
                    @endforeach
                </flux:select>

                <flux:error name="parentId" />
            </flux:field>

            <flux:field>
                <flux:label>Orden</flux:label>

                <flux:input type="number" min="1" wire:model="sortOrder" />

                <flux:error name="sortOrder" />
            </flux:field>

            <flux:switch wire:model="active" label="Sección activa" />

            <div class="flex justify-end gap-2">
                <flux:button type="button" variant="ghost" wire:click="$set('showFormModal', false)">
                    Cancelar
                </flux:button>

                <flux:button type="button" variant="primary" wire:click="saveSection" wire:loading.attr="disabled">
                    <span wire:loading.remove>
                        Guardar
                    </span>

                    <span wire:loading>
                        Guardando...
                    </span>
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>

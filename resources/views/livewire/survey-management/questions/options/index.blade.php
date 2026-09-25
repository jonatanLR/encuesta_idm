<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Opciones</flux:heading>

            <div class="mt-1 space-y-1 text-sm text-zinc-500 dark:text-zinc-400">
                <div>
                    <span class="font-medium">Pregunta:</span>
                    {{ $question->label }}
                </div>

                <div>
                    <span class="font-medium">Código:</span>
                    {{ $question->code }}
                </div>

                <div>
                    <span class="font-medium">Sección:</span>
                    {{ $section->name }}
                    <span class="mx-1">·</span>
                    <span class="font-medium">Versión:</span>
                    {{ $version->version }}
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <flux:button variant="ghost"
                href="{{ route('survey-management.questions', [$questionnaire, $version, $section]) }}">
                Volver a preguntas
            </flux:button>

            <flux:button variant="primary" wire:click="openCreateModal">
                + Nueva opción
            </flux:button>
        </div>
    </div>

    @if (session('success'))
        <flux:callout variant="success" icon="check-circle">
            {{ session('success') }}
        </flux:callout>
    @endif

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Orden</th>
                        <th class="px-4 py-3 text-left font-medium">Etiqueta</th>
                        <th class="px-4 py-3 text-left font-medium">Valor</th>
                        <th class="px-4 py-3 text-left font-medium">Estado</th>
                        <th class="px-4 py-3 text-right font-medium">Acciones</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($options as $option)
                        <tr>
                            <td class="px-4 py-3">
                                {{ $option->sort_order }}
                            </td>

                            <td class="px-4 py-3">
                                {{ $option->label }}
                            </td>

                            <td class="px-4 py-3">
                                {{ $option->value }}
                            </td>

                            <td class="px-4 py-3">
                                @if ($option->active)
                                    <flux:badge color="green">
                                        Activa
                                    </flux:badge>
                                @else
                                    <flux:badge color="zinc">
                                        Inactiva
                                    </flux:badge>
                                @endif
                            </td>

                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <flux:button variant="ghost" color="blue" size="sm"
                                        wire:click="openEditModal({{ $option->id }})">
                                        Editar
                                    </flux:button>

                                    <flux:button variant="ghost" color="{{ $option->active ? 'red' : 'green' }}"
                                        size="sm" wire:click="toggleActive({{ $option->id }})">
                                        {{ $option->active ? 'Desactivar' : 'Activar' }}
                                    </flux:button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-zinc-500 dark:text-zinc-400">
                                No hay opciones registradas para esta pregunta.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <flux:modal wire:model="showFormModal" class="md:w-[32rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">
                    {{ $editingOptionId ? 'Editar opción' : 'Nueva opción' }}
                </flux:heading>

                <flux:text class="mt-1">
                    {{ $question->code }} · {{ $question->label }}
                </flux:text>
            </div>

            <div class="space-y-4">
                <flux:field>
                    <flux:label>Etiqueta</flux:label>

                    <flux:input wire:model="label" type="text" />

                    <flux:error name="label" />
                </flux:field>

                <flux:field>
                    <flux:label>Valor</flux:label>

                    <flux:input wire:model="value" type="text" />

                    <flux:error name="value" />
                </flux:field>

                <flux:field>
                    <flux:label>Orden</flux:label>

                    <flux:input wire:model="sortOrder" type="number" min="1" />

                    <flux:error name="sortOrder" />
                </flux:field>

                <flux:checkbox wire:model="active" label="Activa" />
            </div>

            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="closeFormModal">
                    Cancelar
                </flux:button>

                <flux:button variant="primary" wire:click="saveOption">
                    Guardar
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>

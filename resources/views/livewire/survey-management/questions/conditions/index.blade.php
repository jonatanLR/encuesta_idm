<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Condiciones</flux:heading>

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
            <flux:button variant="outline" color="zinc"
                href="{{ route('survey-management.questions', [$questionnaire, $version, $section]) }}">
                <- Volver a preguntas </flux:button>

                    <flux:button variant="outline" color="lemon" wire:click="openCreateModal">
                        + Nueva condición
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
                        <th class="px-4 py-3 text-left font-medium">
                            Pregunta de referencia
                        </th>

                        <th class="px-4 py-3 text-left font-medium">
                            Operador
                        </th>

                        <th class="px-4 py-3 text-left font-medium">
                            Criterio
                        </th>

                        <th class="px-4 py-3 text-left font-medium">
                            Estado
                        </th>

                        <th class="px-4 py-3 text-center font-medium">
                            Acciones
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($conditions as $condition)
                        <tr>
                            <td class="px-4 py-3">
                                <div class="font-medium">
                                    {{ $condition->dependsOnQuestion?->code }}
                                </div>

                                <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ $condition->dependsOnQuestion?->label }}
                                </div>
                            </td>

                            <td class="px-4 py-3">
                                {{ $condition->operator === 'equals' ? 'Igual a' : 'No igual a' }}
                            </td>

                            <td class="px-4 py-3">
                                @if ($condition->dependsOnOption)
                                    {{ $condition->dependsOnOption->label }}
                                @else
                                    {{ $condition->expected_value }}
                                @endif
                            </td>

                            <td class="px-4 py-3">
                                @if ($condition->active)
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
                                <div class="flex justify-center gap-2">
                                    <flux:button variant="ghost" color="blue" size="sm"
                                        wire:click="openEditModal({{ $condition->id }})">
                                        Editar
                                    </flux:button>

                                    <flux:button variant="ghost" color="{{ $condition->active ? 'red' : 'green' }}"
                                        size="sm" wire:click="toggleActive({{ $condition->id }})">
                                        {{ $condition->active ? 'Desactivar' : 'Activar' }}
                                    </flux:button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-zinc-500 dark:text-zinc-400">
                                No hay condiciones registradas para esta pregunta.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <flux:modal wire:model="showFormModal" class="md:w-[36rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">
                    {{ $editingConditionId ? 'Editar condición' : 'Nueva condición' }}
                </flux:heading>

                <flux:text class="mt-1">
                    {{ $question->code }} · {{ $question->label }}
                </flux:text>
            </div>

            <div class="space-y-4">
                <flux:field>
                    <flux:label>Pregunta de referencia</flux:label>

                    <flux:select wire:model.live="dependsOnQuestionId">
                        <option value="">
                            Seleccione una pregunta
                        </option>

                        @foreach ($referenceQuestions as $item)
                            <option value="{{ $item['question']->id }}">
                                {{ $item['question']->code }}
                                — {{ $item['question']->label }}
                            </option>
                        @endforeach
                    </flux:select>

                    <flux:error name="dependsOnQuestionId" />
                </flux:field>

                <flux:field>
                    <flux:label>Operador</flux:label>

                    <flux:select wire:model="operator">
                        <option value="equals">
                            Igual a
                        </option>

                        <option value="not_equals">
                            No igual a
                        </option>
                    </flux:select>

                    <flux:error name="operator" />
                </flux:field>

                @if ($selectedReferenceQuestion)
                    @if ($referenceOptions->isNotEmpty())
                        <flux:field>
                            <flux:label>Opción</flux:label>

                            <flux:select wire:model="dependsOnOptionId">
                                <option value="">
                                    Seleccione una opción
                                </option>

                                @foreach ($referenceOptions as $option)
                                    <option value="{{ $option->id }}">
                                        {{ $option->label }}
                                    </option>
                                @endforeach
                            </flux:select>

                            <flux:error name="dependsOnOptionId" />
                        </flux:field>
                    @else
                        <flux:field>
                            <flux:label>Valor esperado</flux:label>

                            <flux:input wire:model="expectedValue" type="text" />

                            <flux:error name="expectedValue" />
                        </flux:field>
                    @endif
                @endif

                <flux:checkbox wire:model="active" label="Activa" />
            </div>

            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="closeFormModal">
                    Cancelar
                </flux:button>

                <flux:button variant="primary" wire:click="saveCondition">
                    Guardar
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>

<div class="space-y-6">
    <div class="flex items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">
                Preguntas
            </flux:heading>

            <flux:text class="mt-1">
                {{ $section->name }}
                · {{ $version->version }}
            </flux:text>
        </div>

        <div class="flex items-center gap-2">
            <flux:button variant="outline" color="zinc"
                :href="route('survey-management.sections', [
                            'questionnaire' => $questionnaire->id,
                            'version' => $version->id,
                        ])">
                <- Volver a secciones </flux:button>

                    <flux:button variant="primary" wire:click="openCreateModal">
                        + Nueva pregunta
                    </flux:button>
        </div>
    </div>

    @if (session('success'))
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
                            Pregunta
                        </th>

                        <th class="px-4 py-3 text-left font-medium">
                            Tipo
                        </th>

                        <th class="px-4 py-3 text-left font-medium">
                            Obligatoria
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
                    @forelse ($questions as $question)
                        <tr class="border-b border-zinc-100 dark:border-zinc-800">
                            <td class="px-4 py-3">
                                {{ $question->sort_order }}
                            </td>

                            <td class="px-4 py-3 font-medium">
                                {{ $question->code }}
                            </td>

                            <td class="px-4 py-3">
                                <div class="max-w-xl">
                                    {{ $question->label }}
                                </div>

                                @if ($question->description)
                                    <div class="mt-1 text-xs text-zinc-500">
                                        {{ $question->description }}
                                    </div>
                                @endif
                            </td>

                            <td class="px-4 py-3">
                                {{ $question->questionType?->name ?? '—' }}
                            </td>

                            <td class="px-4 py-3">
                                @if ($question->required)
                                    Sí
                                @else
                                    No
                                @endif
                            </td>

                            <td class="px-4 py-3">
                                @if ($question->active)
                                    <flux:badge variant="success" color="green">
                                        Activa
                                    </flux:badge>
                                @else
                                    <flux:badge variant="zinc" color="zinc">
                                        Inactiva
                                    </flux:badge>
                                @endif
                            </td>

                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <flux:button variant="ghost" color="blue" size="sm"
                                        wire:click="openEditModal({{ $question->id }})">
                                        Editar
                                    </flux:button>

                                    <flux:button variant="ghost" color="fuchsia" size="sm"
                                        href="{{ route('survey-management.question-options', [$questionnaire, $version, $section, $question]) }}">
                                        Opciones
                                    </flux:button>

                                    <flux:button variant="ghost" :color="$section->active ? 'amber' : 'green'"
                                        size="sm" wire:click="toggleActive({{ $question->id }})">
                                        {{ $question->active ? 'Desactivar' : 'Activar' }}
                                    </flux:button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-zinc-500">
                                No hay preguntas registradas en esta sección.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </flux:card>

    <flux:modal wire:model="showFormModal" class="md:w-[700px]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">
                    {{ $editingQuestionId ? 'Editar pregunta' : 'Nueva pregunta' }}
                </flux:heading>

                <flux:text class="mt-1">
                    Sección: {{ $section->name }}
                </flux:text>
            </div>

            <div class="grid gap-4">
                <flux:input wire:model="code" label="Código" placeholder="Ej. GENERAL_001"
                    :disabled="$editingQuestionId !== null" />

                <flux:textarea wire:model="label" label="Pregunta" placeholder="Escriba el texto de la pregunta"
                    rows="3" />

                <flux:textarea wire:model="description" label="Descripción" placeholder="Descripción opcional"
                    rows="2" />

                <flux:select wire:model="questionTypeId" label="Tipo de pregunta">
                    <option value="">Seleccione un tipo</option>

                    @foreach ($questionTypes as $questionType)
                        <option value="{{ $questionType->id }}">
                            {{ $questionType->name }}
                        </option>
                    @endforeach
                </flux:select>

                <flux:input wire:model="sortOrder" label="Orden" type="number" min="1" />

                <flux:switch wire:model="required" label="Obligatoria" />

                <flux:switch wire:model="active" label="Activa" />
            </div>

            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="closeFormModal">
                    Cancelar
                </flux:button>

                <flux:button variant="primary" wire:click="saveQuestion">
                    Guardar
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>

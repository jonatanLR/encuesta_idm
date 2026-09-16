<div class="mx-auto w-full max-w-6xl space-y-6">

    {{-- Encabezado --}}
    <div>
        <flux:heading size="xl">
            Encuesta de Situación Social
        </flux:heading>

        <flux:text class="mt-2">
            Captura de información de la encuesta
        </flux:text>
    </div>


    {{-- Información de la encuesta --}}
    <flux:card>
        <div class="grid gap-4 md:grid-cols-3">

            <div>
                <flux:text class="text-sm">
                    Referencia
                </flux:text>

                <flux:heading size="sm" class="mt-1">
                    {{ $response->reference }}
                </flux:heading>
            </div>

            <div>
                <flux:text class="text-sm">
                    Comunidad
                </flux:text>

                <flux:heading size="sm" class="mt-1">
                    {{ $response->community?->name ?? 'No seleccionada' }}
                </flux:heading>
            </div>

            <div>
                <flux:text class="text-sm">
                    Estado
                </flux:text>

                <flux:heading size="sm" class="mt-1">
                    {{ $response->status->value }}
                </flux:heading>
            </div>

        </div>
    </flux:card>


    {{-- Navegación compacta de secciones --}}
    <div class="flex flex-wrap gap-1.5">

        @foreach ($sections as $index => $section)
            <button type="button" wire:click="goToSection({{ $index }})"
                class="rounded-md border px-3 py-1.5 text-sm font-medium transition
            {{ $index === $currentSectionIndex
                ? 'border-blue-600 bg-blue-50 text-blue-700 ring-1 ring-blue-600'
                : 'border-zinc-200 bg-zinc-50 text-zinc-700 hover:border-zinc-400 hover:bg-zinc-100' }}">
                {{ $section->name }}
            </button>
        @endforeach

    </div>


    {{-- Sección actual --}}
    @if ($currentSection)

        <flux:card>

            <div>
                <flux:heading size="lg">
                    {{ $currentSection->sort_order }}.
                    {{ $currentSection->name }}
                </flux:heading>

                @if ($currentSection->description)
                    <flux:text class="mt-1">
                        {{ $currentSection->description }}
                    </flux:text>
                @endif
            </div>


            {{-- Preguntas de la sección --}}
            <div class="mt-6 space-y-6">

                @if ($currentSection?->code === 'MEMBER')
                    <div class="mb-6 rounded-xl border border-zinc-200 bg-zinc-50 p-4">
                        <div class="mb-3">
                            <h3 class="text-sm font-semibold text-zinc-900">
                                Miembros del hogar
                            </h3>
                            <p class="mt-1 text-xs text-zinc-500">
                                Seleccione el miembro cuya información desea capturar.
                            </p>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            @foreach ($members as $member)
                                <button type="button" wire:click="selectMember({{ $member->id }})"
                                    class="rounded-lg border px-4 py-2 text-sm font-medium transition
                        {{ $selectedMemberId === $member->id
                            ? 'border-blue-600 bg-blue-600 text-white'
                            : 'border-zinc-300 bg-white text-zinc-700 hover:border-zinc-400 hover:bg-zinc-100' }}">
                                    {{ $member->name }}
                                </button>
                            @endforeach

                            <button type="button"
                                class="rounded-lg border border-dashed border-zinc-400 bg-white px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-100">
                                + Agregar miembro
                            </button>
                        </div>
                        @php
                            $selectedMember = $members->firstWhere('id', $selectedMemberId);
                        @endphp

                        @if ($selectedMember)
                            <div class="mt-4 rounded-lg border border-zinc-200 bg-white p-4">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <p class="text-sm font-semibold text-zinc-900">
                                            {{ $selectedMember->name }}
                                        </p>

                                        <p class="text-xs text-zinc-500">
                                            {{ $selectedMember->householdRelationship?->name ?? 'Sin relación' }}
                                        </p>

                                        @if ($selectedMember->capture_started_at)
                                            <p class="mt-1 text-xs text-green-600">
                                                Captura iniciada
                                            </p>
                                        @else
                                            <p class="mt-1 text-xs text-amber-600">
                                                Captura no iniciada
                                            </p>
                                        @endif
                                    </div>

                                    @if (!$selectedMember->capture_started_at)
                                        <button type="button"
                                            wire:click="startMemberCapture({{ $selectedMember->id }})"
                                            class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white
                           transition hover:bg-blue-700">
                                            Capturar información
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                @endif


                @if ($currentSection?->code !== 'MEMBER' || $selectedMember?->capture_started_at)
                    @forelse ($questions as $question)
                        @if ($question->code === 'GENERAL_002')
                            @include('livewire.survey.questions.community', [
                                'question' => $question,
                                'response' => $response,
                            ])
                        @elseif ($question->questionType->code === 'text')
                            @include('livewire.survey.questions.text', [
                                'question' => $question,
                                'response' => $response,
                                'answer' => $this->getAnswer($question),
                            ])
                        @elseif ($question->questionType->code === 'single_choice')
                            @include('livewire.survey.questions.single-choice', [
                                'question' => $question,
                                'answer' => $this->getAnswer($question),
                            ])
                        @elseif ($question->questionType->code === 'date')
                            @include('livewire.survey.questions.date', [
                                'question' => $question,
                                'response' => $response,
                            ])
                        @elseif ($question->questionType->code === 'number')
                            @include('livewire.survey.questions.number', [
                                'question' => $question,
                                'response' => $response,
                                'answer' => $this->getAnswer($question),
                            ])
                        @elseif ($question->questionType->code === 'boolean')
                            @include('livewire.survey.questions.boolean', [
                                'question' => $question,
                                'answer' => $this->getAnswer($question),
                            ])
                        @else
                            <div class="rounded-lg border border-dashed p-4">
                                <flux:text>
                                    Tipo de pregunta aún no implementado:
                                    <strong>{{ $question->questionType->code }}</strong>
                                </flux:text>

                                <flux:text class="mt-1">
                                    {{ $question->label }}
                                </flux:text>
                            </div>
                        @endif

                    @empty

                        <flux:text>
                            Esta sección no tiene preguntas configuradas.
                        </flux:text>
                    @endforelse
                @elseif ($currentSection?->code === 'MEMBER')
                    <div class="rounded-lg border border-dashed border-amber-300 bg-amber-50 p-4">
                        <p class="text-sm font-medium text-amber-800">
                            La captura de información de este miembro aún no ha iniciado.
                        </p>

                        <p class="mt-1 text-sm text-amber-700">
                            Presione "Capturar información" para comenzar.
                        </p>
                    </div>
                @endif

            </div>

        </flux:card>

    @endif


    {{-- Navegación anterior / siguiente --}}
    <div class="flex items-center justify-between">

        <flux:button type="button" wire:click="previousSection" :disabled="$currentSectionIndex === 0">
            ← Anterior
        </flux:button>


        <flux:text>
            Sección {{ $currentSectionIndex + 1 }}
            de {{ $sections->count() }}
        </flux:text>


        <flux:button type="button" variant="primary" wire:click="nextSection"
            :disabled="$currentSectionIndex === $sections->count() - 1">
            Siguiente →
        </flux:button>

    </div>

</div>

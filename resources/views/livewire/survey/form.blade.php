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
                        ])
                    @elseif ($question->questionType->code === 'single_choice')
                        @include('livewire.survey.questions.single-choice', [
                            'question' => $question,
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

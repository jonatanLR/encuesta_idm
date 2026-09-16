@php
    $numberValue = $answer?->number_value;

    if ($numberValue !== null) {
        $numberValue = rtrim(rtrim((string) $numberValue, '0'), '.');

        if ($numberValue === '') {
            $numberValue = '0';
        }
    }
@endphp

<div class="space-y-2">
    <div>
        <label for="question-{{ $question->id }}" class="text-sm font-medium text-zinc-900">
            {{ $question->label }}

            @if ($question->required)
                <span class="text-red-600">*</span>
            @endif
        </label>

        @if ($question->description)
            <p class="mt-1 text-sm text-zinc-500">
                {{ $question->description }}
            </p>
        @endif
    </div>

    <input id="question-{{ $question->id }}" type="number" step="any" value="{{ $numberValue }}"
        wire:change="saveNumber({{ $question->id }}, $event.target.value)"
        class="w-full max-w-md rounded-lg border border-zinc-300 px-3 py-2 text-sm
               shadow-sm focus:border-blue-500 focus:ring-blue-500"
        placeholder="Ingrese un número">
</div>

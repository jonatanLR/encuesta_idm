@php
    $phoneQuestionCodes = [
        'INFORMANT_002',
        'HOUSING_010',
        'HOUSING_022',
        'LOCAL_008',
        'MEMBER_025',
    ];

    $isPhone = in_array($question->code, $phoneQuestionCodes, true);
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

    <input id="question-{{ $question->id }}" type="{{ $isPhone ? 'tel' : 'text' }}" value="{{ $answer?->text_value }}"
        @if ($isPhone) inputmode="numeric"
            maxlength="8"
            pattern="[0-9]{8}"
            oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 8)" @endif
        wire:change="saveText({{ $question->id }}, $event.target.value)"
        class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm
               shadow-sm focus:border-blue-500 focus:ring-blue-500"
        placeholder="{{ $isPhone ? 'Ingrese 8 dígitos' : 'Ingrese la respuesta' }}">
</div>

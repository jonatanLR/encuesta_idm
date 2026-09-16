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

    <input id="question-{{ $question->id }}" type="date"
        value="{{ optional($response->answers->firstWhere('question_id', $question->id)?->date_value)->format('Y-m-d') }}"
        wire:change="saveDate({{ $question->id }}, $event.target.value)"
        class="w-full max-w-md rounded-lg border border-zinc-300 px-3 py-2 text-sm
               shadow-sm focus:border-blue-500 focus:ring-blue-500">
</div>

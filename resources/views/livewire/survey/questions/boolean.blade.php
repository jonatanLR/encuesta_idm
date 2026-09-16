<div class="space-y-3">
    <div>
        <label class="text-sm font-medium text-zinc-900">
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

    <div class="flex gap-3">
        <label
            class="flex cursor-pointer items-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 py-3 transition hover:border-zinc-400 hover:bg-zinc-50">
            <input type="radio" name="question-{{ $question->id }}" value="1"
                wire:click="saveBoolean({{ $question->id }}, true)" @checked($answer?->boolean_value === true)
                class="size-4 border-zinc-300 text-blue-600 focus:ring-blue-500">

            <span class="text-sm text-zinc-800">
                Sí
            </span>
        </label>

        <label
            class="flex cursor-pointer items-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 py-3 transition hover:border-zinc-400 hover:bg-zinc-50">
            <input type="radio" name="question-{{ $question->id }}" value="0"
                wire:click="saveBoolean({{ $question->id }}, false)" @checked($answer?->boolean_value === false)
                class="size-4 border-zinc-300 text-blue-600 focus:ring-blue-500">

            <span class="text-sm text-zinc-800">
                No
            </span>
        </label>
    </div>
</div>

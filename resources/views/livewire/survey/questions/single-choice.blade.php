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

    <div class="space-y-2">
        @foreach ($question->options as $option)
            <label
                class="flex cursor-pointer items-center gap-3 rounded-lg border border-zinc-200
                       bg-white px-4 py-3 transition hover:border-zinc-400 hover:bg-zinc-50">
                <input type="radio" name="question-{{ $question->id }}" value="{{ $option->id }}"
                    wire:click="saveSingleChoice({{ $question->id }}, {{ $option->id }})" @checked($answer?->option_id === $option->id)
                    class="size-4 border-zinc-300 text-blue-600
                           focus:ring-blue-500">

                <span class="text-sm text-zinc-800">
                    {{ $option->label }}
                </span>
            </label>
        @endforeach
    </div>
</div>

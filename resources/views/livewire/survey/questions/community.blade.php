<div class="space-y-2">

    <label class="text-sm font-medium text-zinc-900">
        {{ $question->label }}

        @if ($question->required)
            <span class="text-red-600">*</span>
        @endif
    </label>

    @if ($readOnly ?? false)
        <p class="rounded-lg border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm text-zinc-700">
            {{ $response->community?->name ?? 'No seleccionada' }}
        </p>
    @else
        <livewire:community-search :initial-community-id="$response->community_id" :initial-community-name="$response->community?->name" :key="'community-search-' . $response->id" />
    @endif

</div>

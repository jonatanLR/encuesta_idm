<div class="space-y-2">

    <label class="text-sm font-medium text-zinc-900">
        {{ $question->label }}

        @if ($question->required)
            <span class="text-red-600">*</span>
        @endif
    </label>

    <livewire:community-search :initial-community-id="$response->community_id" :initial-community-name="$response->community?->name" :key="'community-search-' . $response->id" />

</div>

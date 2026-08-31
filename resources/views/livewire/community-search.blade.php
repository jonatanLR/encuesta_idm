

<div>
    {{-- The only way to do great work is to love what you do. - Steve Jobs --}}
    <div class="w-full max-w-xl">

        <label for="community-search" class="block text-sm font-medium text-gray-700">
            Seleccione el nombre de la comunidad
        </label>

        <div class="relative mt-2">

            <input id="community-search" type="text" wire:model.live.debounce.300ms="search"
                placeholder="Buscar comunidad..." autocomplete="off"
                class="w-full rounded-lg border border-gray-300 px-4 py-3 pr-10 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">

            @if ($search !== '')
                <button type="button" wire:click="clearSelection"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-700">
                    ✕
                </button>
            @endif

        </div>

        @if ($search !== '' && $selectedCommunityId === null)

            <div class="mt-2 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-lg">

                @forelse($communities as $community)
                    <button type="button" wire:click="selectCommunity({{ $community->id }})"
                        class="block w-full border-b border-gray-100 px-4 py-3 text-left hover:bg-gray-50">

                        <div class="font-medium text-gray-900">
                            {{ $community->name }}
                        </div>

                        <div class="mt-1 text-sm text-gray-500">
                            {{ ucfirst($community->type) }}
                        </div>

                    </button>

                @empty

                    <div class="px-4 py-4 text-sm text-gray-500">
                        No se encontró ninguna comunidad.
                    </div>
                @endforelse

            </div>

        @endif

        @if ($selectedCommunityId !== null)
            <div class="mt-2 rounded-lg border border-green-200 bg-green-50 px-4 py-3">

                <div class="text-sm text-green-700">
                    Comunidad seleccionada
                </div>

                <div class="font-medium text-green-900">
                    {{ $selectedCommunityName }}
                </div>

            </div>
        @endif

    </div>
</div>

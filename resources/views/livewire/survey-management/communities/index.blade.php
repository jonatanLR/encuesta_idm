<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Comunidades</flux:heading>
            <flux:text class="mt-1">
                Gestiona las comunidades disponibles para las encuestas.
            </flux:text>
        </div>

        <flux:button variant="primary">
            + Nueva comunidad
        </flux:button>
    </div>

    <div class="flex items-center gap-4">
        <div class="w-full max-w-md">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Buscar por nombre o código..."
                icon="magnifying-glass" />
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
                    <tr>
                        <th class="px-4 py-3 font-medium">Código</th>
                        <th class="px-4 py-3 font-medium">Nombre</th>
                        <th class="px-4 py-3 font-medium">Tipo</th>
                        <th class="px-4 py-3 font-medium">Estado</th>
                        <th class="px-4 py-3 text-right font-medium">Acciones</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($communities as $community)
                        <tr>
                            <td class="px-4 py-3">
                                {{ $community->source_code }}
                            </td>

                            <td class="px-4 py-3 font-medium">
                                {{ $community->name }}
                            </td>

                            <td class="px-4 py-3">
                                {{ match ($community->type) {
                                    'colony' => 'Colonia',
                                    'neighborhood' => 'Barrio',
                                    'village' => 'Aldea',
                                    'hamlet' => 'Caserío',
                                    'residential' => 'Residencial',
                                    'other' => 'Otro',
                                    default => $community->type,
                                } }}
                            </td>

                            <td class="px-4 py-3">
                                @if ($community->active)
                                    <span
                                        class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-1 text-xs font-medium text-green-700">
                                        Activa
                                    </span>
                                @else
                                    <span
                                        class="inline-flex items-center rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-medium text-zinc-600">
                                        Inactiva
                                    </span>
                                @endif
                            </td>

                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-end gap-2">
                                    <flux:button size="sm" variant="ghost">
                                        Editar
                                    </flux:button>

                                    @if ($community->active)
                                        <flux:button size="sm" variant="ghost">
                                            Desactivar
                                        </flux:button>
                                    @else
                                        <flux:button size="sm" variant="ghost">
                                            Activar
                                        </flux:button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-zinc-500">
                                No se encontraron comunidades.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($communities->hasPages())
            <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">
                {{ $communities->links() }}
            </div>
        @endif
    </div>
</div>

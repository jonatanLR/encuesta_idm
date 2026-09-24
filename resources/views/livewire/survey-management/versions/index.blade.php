<div class="mx-auto w-full max-w-6xl">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <flux:heading size="xl">
                Versiones
            </flux:heading>

            <flux:text class="mt-2">
                {{ $questionnaire->name }}
            </flux:text>
        </div>

        <flux:button variant="ghost" :href="route('survey-management.index')" wire:navigate>
            Volver a encuestas
        </flux:button>
    </div>

    <flux:card class="mt-6 overflow-hidden p-0">
        @if ($versions->isEmpty())
            <div class="px-6 py-10 text-center">
                <flux:heading size="lg">
                    No hay versiones registradas
                </flux:heading>

                <flux:text class="mt-2">
                    Este cuestionario todavía no tiene versiones.
                </flux:text>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-900/50">
                        <tr>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                                Versión
                            </th>

                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                                Estado
                            </th>

                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                                Publicación
                            </th>

                            <th scope="col"
                                class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                                Acciones
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-800">
                        @foreach ($versions as $version)
                            <tr>
                                <td
                                    class="whitespace-nowrap px-6 py-4 text-sm font-medium text-zinc-900 dark:text-white">
                                    {{ $version->version }}
                                </td>

                                <td class="whitespace-nowrap px-6 py-4 text-sm">
                                    @if ($version->active)
                                        <span
                                            class="inline-flex rounded-full bg-green-100 px-2.5 py-1 text-xs font-medium text-green-700 dark:bg-green-900/30 dark:text-green-300">
                                            Activa
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-medium text-zinc-700 dark:bg-zinc-700 dark:text-zinc-300">
                                            Inactiva
                                        </span>
                                    @endif
                                </td>

                                <td class="whitespace-nowrap px-6 py-4 text-sm text-zinc-600 dark:text-zinc-300">
                                    @if ($version->published_at)
                                        {{ $version->published_at->format('d/m/Y H:i') }}
                                    @else
                                        No publicada
                                    @endif
                                </td>

                                <td class="whitespace-nowrap px-6 py-4 text-right">
                                    <div class="flex justify-end gap-2">
                                        <flux:button variant="ghost" size="sm" disabled>
                                            Editar
                                        </flux:button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </flux:card>
</div>

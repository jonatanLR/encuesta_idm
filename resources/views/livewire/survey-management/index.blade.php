<div class="mx-auto w-full max-w-6xl">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <flux:heading size="xl">Administrar encuestas</flux:heading>

            <flux:text class="mt-2">
                Gestiona las encuestas y sus versiones.
            </flux:text>
        </div>

        <flux:button variant="primary" disabled>
            + Crear encuesta
        </flux:button>
    </div>

    <flux:card class="mt-6 overflow-hidden p-0">
        @if ($questionnaires->isEmpty())
            <div class="px-6 py-10 text-center">
                <flux:heading size="lg">No hay encuestas registradas</flux:heading>
                <flux:text class="mt-2">
                    Crea la primera encuesta para comenzar a administrarla.
                </flux:text>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-900/50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                                Encuesta
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                                Descripción
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                                Estado
                            </th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                                Acciones
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-800">
                        @foreach ($questionnaires as $questionnaire)
                            <tr>
                                <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-zinc-900 dark:text-white">
                                    {{ $questionnaire->name }}
                                </td>
                                <td class="max-w-md px-6 py-4 text-sm text-zinc-600 dark:text-zinc-300">
                                    {{ $questionnaire->description ?? 'Sin descripción' }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm">
                                    @if ($questionnaire->active)
                                        <span class="inline-flex rounded-full bg-green-100 px-2.5 py-1 text-xs font-medium text-green-700 dark:bg-green-900/30 dark:text-green-300">
                                            Activa
                                        </span>
                                    @else
                                        <span class="inline-flex rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-medium text-zinc-700 dark:bg-zinc-700 dark:text-zinc-300">
                                            Inactiva
                                        </span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right">
                                    <div class="flex justify-end gap-2">
                                        <flux:button variant="ghost" size="sm" disabled>
                                            Editar
                                        </flux:button>
                                        <flux:button variant="ghost" size="sm" disabled>
                                            Versiones
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
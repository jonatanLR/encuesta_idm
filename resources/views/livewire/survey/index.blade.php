<div class="mx-auto w-full max-w-6xl">
    <flux:heading size="xl">Encuestas</flux:heading>

    <flux:text class="mt-2">
        Selecciona una encuesta disponible para comenzar a responderla.
    </flux:text>

    <flux:card class="mt-6 overflow-hidden p-0">
        @if ($surveyVersions->isEmpty())
            <div class="px-6 py-10 text-center">
                <flux:heading size="lg">No hay encuestas disponibles</flux:heading>
                <flux:text class="mt-2">
                    En este momento no hay encuestas publicadas para responder.
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
                                Versión
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                                Descripción
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                                Estado
                            </th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                                Acción
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-800">
                        @foreach ($surveyVersions as $surveyVersion)
                            <tr>
                                <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-zinc-900 dark:text-white">
                                    {{ $surveyVersion->questionnaire->name }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-zinc-600 dark:text-zinc-300">
                                    {{ $surveyVersion->version }}
                                </td>
                                <td class="max-w-sm px-6 py-4 text-sm text-zinc-600 dark:text-zinc-300">
                                    {{ $surveyVersion->questionnaire->description ?? 'Sin descripción' }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm">
                                    <span class="inline-flex rounded-full bg-green-100 px-2.5 py-1 text-xs font-medium text-green-700 dark:bg-green-900/30 dark:text-green-300">
                                        Disponible
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right">
                                    <flux:button href="{{ route('survey.start') }}" wire:navigate variant="primary">
                                        Iniciar
                                    </flux:button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </flux:card>
</div>
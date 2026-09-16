<div class="mx-auto w-full max-w-6xl">

    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">

        <div>
            <flux:heading size="xl">
                Encuestas
            </flux:heading>

            <flux:text class="mt-2">
                Encuestas registradas por el usuario.
            </flux:text>
        </div>

        <flux:button variant="primary" wire:click="startSurvey" wire:loading.attr="disabled">
            <span wire:loading.remove>
                + Nueva encuesta
            </span>

            <span wire:loading>
                Creando...
            </span>
        </flux:button>

    </div>


    <flux:card class="mt-6 overflow-hidden p-0">

        @if ($responses->isEmpty())

            <div class="px-6 py-10 text-center">

                <flux:heading size="lg">
                    No hay encuestas registradas
                </flux:heading>

                <flux:text class="mt-2">
                    Cree una nueva encuesta para comenzar.
                </flux:text>

            </div>
        @else
            <div class="overflow-x-auto">

                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">

                    <thead class="bg-zinc-50 dark:bg-zinc-900/50">

                        <tr>

                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-zinc-500">
                                Folio
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-zinc-500">
                                Comunidad
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-zinc-500">
                                Fecha
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-zinc-500">
                                Tipo de inmueble
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-zinc-500">
                                Estado
                            </th>

                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wide text-zinc-500">
                                Acciones
                            </th>

                        </tr>

                    </thead>


                    <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-800">

                        @foreach ($responses as $response)
                            @php
                                $propertyTypeAnswer = $response->answers->first(function ($answer) {
                                    return $answer->question?->code === 'GENERAL_004';
                                });
                            @endphp

                            <tr>

                                <td
                                    class="whitespace-nowrap px-6 py-4 text-sm font-medium text-zinc-900 dark:text-white">
                                    {{ $response->reference ?? 'Sin folio' }}
                                </td>


                                <td class="px-6 py-4 text-sm text-zinc-600 dark:text-zinc-300">
                                    {{ $response->community?->name ?? 'No seleccionada' }}
                                </td>


                                <td class="whitespace-nowrap px-6 py-4 text-sm text-zinc-600 dark:text-zinc-300">
                                    {{ $response->created_at?->format('d/m/Y') }}
                                </td>


                                <td class="px-6 py-4 text-sm text-zinc-600 dark:text-zinc-300">
                                    {{ $propertyTypeAnswer?->option?->label ?? 'No especificado' }}
                                </td>


                                <td class="whitespace-nowrap px-6 py-4 text-sm">

                                    @switch($response->status->value)
                                        @case('draft')
                                            <span
                                                class="inline-flex rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-medium text-zinc-700">
                                                Borrador
                                            </span>
                                        @break

                                        @case('in_progress')
                                            <span
                                                class="inline-flex rounded-full bg-blue-100 px-2.5 py-1 text-xs font-medium text-blue-700">
                                                En progreso
                                            </span>
                                        @break

                                        @case('completed')
                                            <span
                                                class="inline-flex rounded-full bg-green-100 px-2.5 py-1 text-xs font-medium text-green-700">
                                                Completada
                                            </span>
                                        @break

                                        @case('cancelled')
                                            <span
                                                class="inline-flex rounded-full bg-red-100 px-2.5 py-1 text-xs font-medium text-red-700">
                                                Cancelada
                                            </span>
                                        @break

                                        @default
                                            {{ $response->status->value }}
                                    @endswitch

                                </td>


                                <td class="whitespace-nowrap px-6 py-4 text-right">

                                    <div class="flex justify-end gap-2">

                                        <flux:button href="{{ route('survey.form', ['response' => $response->id]) }}"
                                            wire:navigate variant="ghost" size="sm">
                                            Ver
                                        </flux:button>

                                        <flux:button href="{{ route('survey.form', ['response' => $response->id]) }}"
                                            wire:navigate variant="ghost" size="sm">
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

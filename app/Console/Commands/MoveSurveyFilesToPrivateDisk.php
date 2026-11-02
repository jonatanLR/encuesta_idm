<?php

namespace App\Console\Commands;

use App\Models\SurveyFile;
use App\Services\SurveyFileStorage;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Migra las fotografías heredadas del disco público al privado (Offline v1, D-07).
 *
 * Por cada registro en el disco público: copia el archivo a la misma ruta
 * del disco privado, verifica el tamaño, actualiza el registro y solo
 * entonces borra la copia pública. Es idempotente: se puede ejecutar varias
 * veces y retoma una ejecución interrumpida.
 *
 * Además reporta los archivos de surveys/ en el disco público que ningún
 * registro usa. Con --huerfanos los saca del disco público: las copias
 * sobrantes de un archivo ya migrado se borran y el resto se guarda en el
 * disco privado bajo huerfanos/ para revisión manual. Nunca se pierden.
 */
class MoveSurveyFilesToPrivateDisk extends Command
{
    protected $signature = 'offline:move-files-private
                            {--dry-run : Solo informa qué se haría, sin modificar archivos ni registros}
                            {--huerfanos : Saca del disco público los archivos sin registro (los guarda en privado bajo huerfanos/)}';

    protected $description = 'Mueve las fotografías de la encuesta del disco público al disco privado (D-07)';

    private const SOURCE_DISK = 'public';

    private const ROOT_DIRECTORY = 'surveys';

    private const ORPHANS_DIRECTORY = 'huerfanos';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $source = Storage::disk(self::SOURCE_DISK);
        $target = Storage::disk(SurveyFileStorage::PRIVATE_DISK);

        $this->info($dryRun
            ? 'Simulación (--dry-run): no se modificará nada.'
            : 'Moviendo fotografías al disco privado.');
        $this->newLine();

        /** @var array<string, int> $totals */
        $totals = [
            'moved' => 0,
            'relinked' => 0,
            'missing' => 0,
            'failed' => 0,
        ];

        /** @var array<int, array{0: int, 1: string, 2: string}> $details */
        $details = [];

        SurveyFile::query()
            ->where('disk', self::SOURCE_DISK)
            ->orderBy('id')
            ->chunkById(200, function ($files) use ($source, $target, $dryRun, &$totals, &$details): void {
                foreach ($files as $surveyFile) {
                    [$outcome, $message] = $this->process($surveyFile, $source, $target, $dryRun);

                    $totals[$outcome]++;
                    $details[] = [$surveyFile->id, $surveyFile->path, $message];
                }
            });

        if ($details !== []) {
            $this->table(['ID', 'Ruta', 'Resultado'], $details);
        }

        $orphanTotals = $this->handleOrphans(
            $source,
            $target,
            $dryRun || ! $this->option('huerfanos'),
        );

        if (! $dryRun) {
            $this->deleteEmptyDirectories($source);
        }

        $this->table(['Resultado', 'Cantidad'], [
            [$dryRun ? 'Se moverían' : 'Movidos', $totals['moved']],
            [$dryRun ? 'Se reasignarían (ya estaban en privado)' : 'Reasignados (ya estaban en privado)', $totals['relinked']],
            ['Faltantes en ambos discos (sin cambios)', $totals['missing']],
            ['Con error (sin cambios)', $totals['failed']],
            ['Archivos sin registro en el disco público', $orphanTotals['found']],
            ['Archivos sin registro retirados del disco público', $orphanTotals['handled']],
        ]);

        if ($totals['missing'] > 0) {
            $this->warn('Hay registros cuyo archivo no existe en ningún disco. Se dejaron sin cambios; el formulario los muestra como "no se encuentra en el servidor" y se pueden volver a cargar.');
        }

        if ($orphanTotals['found'] > $orphanTotals['handled'] && ! $dryRun && ! $this->option('huerfanos')) {
            $this->warn('Quedan archivos sin registro en el disco público. Ejecute el comando con --huerfanos para retirarlos.');
        }

        if ($totals['failed'] > 0 || $orphanTotals['failed'] > 0) {
            $this->error('Algunos archivos no se pudieron mover. Vuelva a ejecutar el comando después de revisar el error.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * @return array{0: 'moved'|'relinked'|'missing'|'failed', 1: string}
     */
    private function process(
        SurveyFile $surveyFile,
        Filesystem $source,
        Filesystem $target,
        bool $dryRun,
    ): array {
        $path = $surveyFile->path;

        $existsInSource = $source->exists($path);

        if (! $existsInSource && $target->exists($path)) {
            if (! $dryRun) {
                $this->markAsPrivate($surveyFile);
            }

            return ['relinked', 'Ya estaba en el disco privado; se actualiza el registro'];
        }

        if (! $existsInSource) {
            return ['missing', 'No existe en ningún disco'];
        }

        if ($dryRun) {
            return ['moved', 'Se movería al disco privado'];
        }

        try {
            $stream = $source->readStream($path);

            if ($stream === null || ! $target->writeStream($path, $stream)) {
                return ['failed', 'No se pudo copiar al disco privado'];
            }

            if (is_resource($stream)) {
                fclose($stream);
            }

            if ($target->size($path) !== $source->size($path)) {
                $target->delete($path);

                return ['failed', 'La copia no coincide en tamaño; se descartó'];
            }

            $this->markAsPrivate($surveyFile);

            if (! $source->delete($path)) {
                return ['failed', 'Copiado y registro actualizado, pero no se pudo borrar la copia pública; ejecute con --huerfanos'];
            }
        } catch (Throwable $exception) {
            return ['failed', 'Error: '.$exception->getMessage()];
        }

        return ['moved', 'Movido al disco privado'];
    }

    /**
     * Busca archivos en surveys/ del disco público que ningún registro usa.
     *
     * @return array{found: int, handled: int, failed: int}
     */
    private function handleOrphans(Filesystem $source, Filesystem $target, bool $reportOnly): array
    {
        $totals = ['found' => 0, 'handled' => 0, 'failed' => 0];

        if (! $source->exists(self::ROOT_DIRECTORY)) {
            return $totals;
        }

        $referencedPaths = SurveyFile::query()
            ->where('disk', self::SOURCE_DISK)
            ->pluck('path')
            ->flip();

        $rows = [];

        foreach ($source->allFiles(self::ROOT_DIRECTORY) as $path) {
            if ($referencedPaths->has($path)) {
                continue;
            }

            $totals['found']++;

            $isLeftoverCopy = SurveyFile::query()
                ->where('disk', SurveyFileStorage::PRIVATE_DISK)
                ->where('path', $path)
                ->exists()
                && $target->exists($path)
                && $target->size($path) === $source->size($path);

            if ($reportOnly) {
                $result = $isLeftoverCopy
                    ? 'Copia sobrante de un archivo ya migrado (se borraría con --huerfanos)'
                    : 'Sin registro (se guardaría en privado con --huerfanos)';
            } else {
                [$handled, $result] = $isLeftoverCopy
                    ? $this->deleteLeftoverCopy($source, $path)
                    : $this->quarantine($source, $target, $path);

                $totals[$handled ? 'handled' : 'failed']++;
            }

            $rows[] = [
                $path,
                number_format($source->exists($path) ? $source->size($path) : 0),
                $result,
            ];
        }

        if ($rows !== []) {
            $this->newLine();
            $this->line('Archivos sin registro en el disco público:');
            $this->table(['Ruta', 'Bytes', 'Resultado'], $rows);
        }

        return $totals;
    }

    /**
     * @return array{0: bool, 1: string}
     */
    private function deleteLeftoverCopy(Filesystem $source, string $path): array
    {
        return $source->delete($path)
            ? [true, 'Copia sobrante borrada (el registro ya usa el disco privado)']
            : [false, 'No se pudo borrar la copia sobrante'];
    }

    /**
     * Guarda el archivo en el disco privado bajo huerfanos/ y lo borra del
     * disco público solo si la copia quedó completa.
     *
     * @return array{0: bool, 1: string}
     */
    private function quarantine(Filesystem $source, Filesystem $target, string $path): array
    {
        $destination = self::ORPHANS_DIRECTORY.'/'.$path;

        try {
            $stream = $source->readStream($path);

            if ($stream === null || ! $target->writeStream($destination, $stream)) {
                return [false, 'No se pudo copiar al disco privado'];
            }

            if (is_resource($stream)) {
                fclose($stream);
            }

            if ($target->size($destination) !== $source->size($path)) {
                $target->delete($destination);

                return [false, 'La copia no coincide en tamaño; se descartó'];
            }

            if (! $source->delete($path)) {
                return [false, 'Copiado a privado, pero no se pudo borrar del disco público'];
            }
        } catch (Throwable $exception) {
            return [false, 'Error: '.$exception->getMessage()];
        }

        return [true, 'Guardado en privado: '.$destination];
    }

    /**
     * Cambia solo la columna disk, sin alterar updated_at.
     */
    private function markAsPrivate(SurveyFile $surveyFile): void
    {
        SurveyFile::query()
            ->whereKey($surveyFile->getKey())
            ->toBase()
            ->update(['disk' => SurveyFileStorage::PRIVATE_DISK]);
    }

    /**
     * Elimina, de la más profunda a la más externa, las carpetas vacías que
     * quedaron en el disco público bajo surveys/.
     */
    private function deleteEmptyDirectories(Filesystem $source): void
    {
        if (! $source->exists(self::ROOT_DIRECTORY)) {
            return;
        }

        $directories = collect($source->allDirectories(self::ROOT_DIRECTORY))
            ->push(self::ROOT_DIRECTORY)
            ->sortByDesc(fn (string $directory): int => substr_count($directory, '/'));

        foreach ($directories as $directory) {
            if ($source->files($directory) === [] && $source->directories($directory) === []) {
                $source->deleteDirectory($directory);
            }
        }
    }
}

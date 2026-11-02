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
 */
class MoveSurveyFilesToPrivateDisk extends Command
{
    protected $signature = 'offline:move-files-private
                            {--dry-run : Solo informa qué se haría, sin modificar archivos ni registros}';

    protected $description = 'Mueve las fotografías de la encuesta del disco público al disco privado (D-07)';

    private const SOURCE_DISK = 'public';

    private const ROOT_DIRECTORY = 'surveys';

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

        if (! $dryRun) {
            $this->deleteEmptyDirectories($source);
        }

        $this->table(['Resultado', 'Cantidad'], [
            [$dryRun ? 'Se moverían' : 'Movidos', $totals['moved']],
            [$dryRun ? 'Se reasignarían (ya estaban en privado)' : 'Reasignados (ya estaban en privado)', $totals['relinked']],
            ['Faltantes en ambos discos (sin cambios)', $totals['missing']],
            ['Con error (sin cambios)', $totals['failed']],
        ]);

        if ($totals['missing'] > 0) {
            $this->warn('Hay registros cuyo archivo no existe en ningún disco. Se dejaron sin cambios para revisión manual.');
        }

        if ($totals['failed'] > 0) {
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

            $source->delete($path);
        } catch (Throwable $exception) {
            return ['failed', 'Error: '.$exception->getMessage()];
        }

        return ['moved', 'Movido al disco privado'];
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

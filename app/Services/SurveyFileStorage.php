<?php

namespace App\Services;

use App\Models\SurveyFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

/**
 * Almacenamiento privado de fotografías de la encuesta (Offline v1, D-07).
 *
 * Toda fotografía nueva se guarda en el disco privado y solo se entrega a
 * través de la ruta autenticada `survey-files.show`. El reemplazo sigue un
 * orden seguro: primero se guarda el archivo nuevo, después se cambian los
 * registros en una transacción y, solo si esta se confirma, se elimina el
 * archivo anterior. Así un fallo nunca deja la respuesta sin fotografía.
 */
class SurveyFileStorage
{
    /**
     * Disco privado (storage/app/private), sin URL pública.
     */
    public const PRIVATE_DISK = 'local';

    /**
     * Discos desde los que se aceptan lecturas: el privado y el público
     * heredado, hasta que `offline:move-files-private` termine la migración.
     *
     * @var array<int, string>
     */
    public const READABLE_DISKS = [
        self::PRIVATE_DISK,
        'public',
    ];

    /**
     * Guarda la fotografía en el disco privado y, si se indica, reemplaza el
     * registro anterior.
     *
     * @param  array{survey_response_id: int, answer_id?: int|null, household_member_id?: int|null, file_type: string, client_id?: string|null}  $attributes
     */
    public function store(
        UploadedFile $file,
        string $directory,
        array $attributes,
        ?SurveyFile $replacing = null,
    ): SurveyFile {
        $path = $file->store($directory, self::PRIVATE_DISK);

        if (! is_string($path) || $path === '') {
            throw new RuntimeException('No se pudo guardar la fotografía. Intente nuevamente.');
        }

        try {
            $surveyFile = DB::transaction(function () use ($file, $path, $attributes, $replacing): SurveyFile {
                $replacing?->delete();

                return SurveyFile::create([
                    ...$attributes,
                    'disk' => self::PRIVATE_DISK,
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                ]);
            });
        } catch (Throwable $exception) {
            Storage::disk(self::PRIVATE_DISK)->delete($path);

            throw $exception;
        }

        if ($replacing !== null) {
            $this->deleteBlob($replacing);
        }

        return $surveyFile;
    }

    /**
     * Elimina el archivo físico de un registro, salvo que otro registro
     * vigente apunte al mismo disco y ruta.
     */
    public function deleteBlob(SurveyFile $surveyFile): void
    {
        $isShared = SurveyFile::query()
            ->whereKeyNot($surveyFile->getKey())
            ->where('disk', $surveyFile->disk)
            ->where('path', $surveyFile->path)
            ->exists();

        if ($isShared) {
            return;
        }

        Storage::disk($surveyFile->disk)->delete($surveyFile->path);
    }
}

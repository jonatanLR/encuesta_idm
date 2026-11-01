<?php

namespace App\Http\Controllers;

use App\Models\SurveyFile;
use App\Services\SurveyFileStorage;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Entrega autenticada de fotografías de la encuesta (Offline v1, D-07).
 *
 * Quien puede ver la encuesta puede ver sus fotografías. El archivo se lee
 * del disco que indica el registro, de modo que los archivos heredados del
 * disco público siguen funcionando mientras se migran al privado.
 */
class SurveyFileController extends Controller
{
    public function show(SurveyFile $surveyFile): StreamedResponse
    {
        $surveyResponse = $surveyFile->surveyResponse;

        abort_if($surveyResponse === null, 404);

        Gate::authorize('view', $surveyResponse);

        abort_unless(
            in_array($surveyFile->disk, SurveyFileStorage::READABLE_DISKS, true),
            404
        );

        $disk = Storage::disk($surveyFile->disk);

        abort_unless($disk->exists($surveyFile->path), 404);

        return $disk->response(
            $surveyFile->path,
            $surveyFile->original_name,
            [
                'Content-Type' => $surveyFile->mime_type ?: 'application/octet-stream',
                'Cache-Control' => 'private, max-age=3600',
                'X-Content-Type-Options' => 'nosniff',
            ],
        );
    }
}

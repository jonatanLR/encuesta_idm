<?php

namespace App\Http\Controllers;

use App\Models\SurveyFile;
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

        abort_unless($surveyFile->isAvailable(), 404);

        return Storage::disk($surveyFile->disk)->response(
            $surveyFile->path,
            $surveyFile->original_name,
            [
                'Content-Type' => $surveyFile->mime_type ?: 'application/octet-stream',
                'Cache-Control' => 'private, no-store, max-age=0',
                'X-Content-Type-Options' => 'nosniff',
            ],
        );
    }
}

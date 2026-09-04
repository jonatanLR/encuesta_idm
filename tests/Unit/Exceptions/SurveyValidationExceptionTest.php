<?php

use App\Exceptions\SurveyValidationException;

it('returns the validation errors provided to the exception', function () {
    $errors = [
        'GENERAL_001' => 'Esta pregunta es obligatoria.',
        'GENERAL_002' => 'Esta pregunta es obligatoria.',
    ];

    $exception = new SurveyValidationException($errors);

    expect($exception->errors())->toBe($errors);
});

//----------------------------------------------------------

it('uses the default validation message', function () {
    $exception = new SurveyValidationException([]);

    expect($exception->getMessage())
        ->toBe('La encuesta contiene preguntas obligatorias sin responder.');
});
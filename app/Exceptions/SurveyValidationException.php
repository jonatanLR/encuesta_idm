<?php

namespace App\Exceptions;

use Exception;

class SurveyValidationException extends Exception
{
    public function __construct(
        protected array $errors
    ) {
        parent::__construct(
            'La encuesta contiene preguntas obligatorias sin responder.'
        );
    }

    public function errors(): array
    {
        return $this->errors;
    }
}
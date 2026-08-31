<?php

namespace App\Services;

use App\Models\Question;
use App\Models\SurveyResponse;

class SurveyResponseValidator
{
    public function __construct(
        protected ConditionEvaluator $conditionEvaluator
    ) {
    }

    public function validate(
        SurveyResponse $response
    ): array {
        $errors = [];

        $questions = Question::query()
            ->whereHas('section', function ($query) use ($response) {
                $query->where(
                    'survey_version_id',
                    $response->survey_version_id
                );
            })
            ->where('active', true)
            ->with([
                'questionType',
                'conditions',
                'answers' => function ($query) use ($response) {
                    $query->where(
                        'survey_response_id',
                        $response->id
                    );
                },
            ])
            ->orderBy('sort_order')
            ->get();

        foreach ($questions as $question) {
            if (! $question->required) {
                continue;
            }

            if (
                ! $this->conditionEvaluator->shouldShow(
                    $response,
                    $question
                )
            ) {
                continue;
            }

            if (! $this->hasAnswer($question)) {
                $errors[$question->code] =
                    'Esta pregunta es obligatoria.';
            }
        }

        return $errors;
    }

    protected function hasAnswer(
        Question $question
    ): bool {
        $answer = $question->answers->first();

        if (! $answer) {
            return false;
        }

        return $answer->text_value !== null
            || $answer->number_value !== null
            || $answer->date_value !== null
            || $answer->option_id !== null
            || $answer->selectedOptions()->exists();
    }

    public function isValid(
        SurveyResponse $response
    ): bool {
        return $this->validate($response) === [];
    }
}
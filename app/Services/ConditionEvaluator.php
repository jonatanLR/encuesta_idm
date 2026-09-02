<?php

namespace App\Services;

use App\Models\Answer;
use App\Models\Question;
use App\Models\QuestionCondition;
use App\Models\SurveyResponse;

class ConditionEvaluator
{
    public function shouldShow(
        SurveyResponse $response,
        Question $question
    ): bool {
        $conditions = $question->conditions()
            ->where('active', true)
            ->get();

        if ($conditions->isEmpty()) {
            return true;
        }

        foreach ($conditions as $condition) {
            if (! $this->evaluateCondition(
                $response,
                $condition
            )) {
                return false;
            }
        }

        return true;
    }

    protected function evaluateCondition(
        SurveyResponse $response,
        QuestionCondition $condition
    ): bool {
        $answer = Answer::query()
            ->where(
                'survey_response_id',
                $response->id
            )
            ->where(
                'question_id',
                $condition->depends_on_question_id
            )
            ->first();

        if (! $answer) {
            return false;
        }

        return match ($condition->operator) {
            'equals' => $this->equals(
                $answer,
                $condition
            ),

            'not_equals' => ! $this->equals(
                $answer,
                $condition
            ),

            default => false,
        };
    }

    protected function equals(
        Answer $answer,
        QuestionCondition $condition
    ): bool {
        if ($condition->depends_on_option_id !== null) {
            return $answer->option_id ===
                $condition->depends_on_option_id;
        }

        if ($answer->text_value !== null) {
            return $answer->text_value ===
                $condition->expected_value;
        }

        if ($answer->number_value !== null) {
            return (float) $answer->number_value ===
                (float) $condition->expected_value;
        }

        if ($answer->date_value !== null) {
            return $answer->date_value->format('Y-m-d') ===
                $condition->expected_value;
        }

        return false;
    }
}

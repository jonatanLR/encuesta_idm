<?php

namespace App\Services;

use App\Models\Answer;
use App\Models\HouseholdMember;
use App\Models\Question;
use App\Models\QuestionCondition;
use App\Models\SurveyResponse;

class ConditionEvaluator
{
    public function shouldShow(
        SurveyResponse $response,
        Question $question,
        ?HouseholdMember $householdMember = null
    ): bool {
        return $this->evaluateQuestionVisibility(
            $response,
            $question,
            $householdMember,
            []
        );
    }

    protected function evaluateQuestionVisibility(
        SurveyResponse $response,
        Question $question,
        ?HouseholdMember $householdMember,
        array $visitedQuestionIds
    ): bool {
        $questionId = $question->id;

        if (in_array($questionId, $visitedQuestionIds, true)) {
            return false;
        }

        $visitedQuestionIds[] = $questionId;

        $conditions = $question->conditions()
            ->where('active', true)
            ->with('dependsOnQuestion')
            ->get();

        if ($conditions->isEmpty()) {
            return true;
        }

        foreach ($conditions as $condition) {
            $dependsOnQuestion = $condition->dependsOnQuestion;

            if ($dependsOnQuestion === null) {
                return false;
            }

            if (! $this->evaluateQuestionVisibility(
                $response,
                $dependsOnQuestion,
                $householdMember,
                $visitedQuestionIds
            )) {
                return false;
            }

            if (! $this->evaluateCondition(
                $response,
                $condition,
                $householdMember
            )) {
                return false;
            }
        }

        return true;
    }

    protected function evaluateCondition(
        SurveyResponse $response,
        QuestionCondition $condition,
        ?HouseholdMember $householdMember = null
    ): bool {
        $answerQuery = Answer::query()
            ->where(
                'survey_response_id',
                $response->id
            )
            ->where(
                'question_id',
                $condition->depends_on_question_id
            );

        if ($householdMember !== null) {
            $answerQuery->where(
                'household_member_id',
                $householdMember->id
            );
        } else {
            $answerQuery->whereNull('household_member_id');
        }

        $answer = $answerQuery->first();

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

        if ($answer->boolean_value !== null) {
            return $answer->boolean_value ===
                filter_var(
                    $condition->expected_value,
                    FILTER_VALIDATE_BOOLEAN
                );
        }

        return false;
    }
}

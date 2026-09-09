<?php

namespace App\Services;

use App\Models\HouseholdMember;
use App\Models\Question;
use App\Models\SurveyResponse;

class SurveyResponseValidator
{
    public function __construct(
        protected ConditionEvaluator $conditionEvaluator
    ) {}

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
                'section',
                'answers' => function ($query) use ($response) {
                    $query->where(
                        'survey_response_id',
                        $response->id
                    );
                },
            ])
            ->orderBy('sort_order')
            ->get();

        $generalQuestions = $questions
            ->filter(
                fn(Question $question) =>
                $question->section?->code !== 'MEMBER'
            );

        $memberQuestions = $questions
            ->filter(
                fn(Question $question) =>
                $question->section?->code === 'MEMBER'
            );

        foreach ($generalQuestions as $question) {
            $this->validateQuestion(
                $response,
                $question,
                null,
                $errors
            );
        }

        $startedMembers = $response->household
            ?->householdMembers()
            ->whereNotNull('capture_started_at')
            ->get() ?? collect();

        foreach ($startedMembers as $member) {
            foreach ($memberQuestions as $question) {
                $this->validateQuestion(
                    $response,
                    $question,
                    $member,
                    $errors
                );
            }
        }

        return $errors;
    }

    protected function validateQuestion(
        SurveyResponse $response,
        Question $question,
        ?HouseholdMember $householdMember,
        array &$errors
    ): void {
        if (! $question->required) {
            return;
        }

        if (
            ! $this->conditionEvaluator->shouldShow(
                $response,
                $question,
                $householdMember
            )
        ) {
            return;
        }

        if (
            $this->hasAnswer(
                $question,
                $householdMember
            )
        ) {
            return;
        }

        if ($householdMember === null) {
            $errors[$question->code] =
                'Esta pregunta es obligatoria.';

            return;
        }

        $errors['members'][$householdMember->public_id][$question->code] =
            'Esta pregunta es obligatoria.';
    }

    protected function hasAnswer(
        Question $question,
        ?HouseholdMember $householdMember
    ): bool {
        $answer = $question->answers
            ->first(
                fn($answer) =>
                $answer->household_member_id ===
                    $householdMember?->id
            );

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

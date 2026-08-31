<?php

namespace App\Services;

use App\Models\Answer;
use App\Models\AnswerOption;
use App\Enums\SurveyResponseStatus;
use App\Models\Question;
use App\Models\SurveyResponse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use App\Exceptions\SurveyValidationException;

class SurveyResponseService
{
    public function __construct(
        protected SurveyResponseValidator $validator
    ) {}


    public function createDraft(
        int $questionnaireId,
        int $surveyVersionId,
        int $userId,
        ?int $communityId = null
    ): SurveyResponse {
        return SurveyResponse::create([
            'questionnaire_id' => $questionnaireId,
            'survey_version_id' => $surveyVersionId,
            'community_id' => $communityId,
            'created_by' => $userId,
            'status' => SurveyResponseStatus::DRAFT,
        ]);
    }

    public function saveAnswer(
        SurveyResponse $response,
        Question $question,
        mixed $value
    ): Answer {
        return DB::transaction(function () use (
            $response,
            $question,
            $value
        ) {
            $answer = Answer::firstOrNew([
                'survey_response_id' => $response->id,
                'question_id' => $question->id,
            ]);

            $this->clearAnswerValues($answer);

            return match ($question->questionType->code) {
                'text',
                'textarea' => $this->saveTextAnswer(
                    $answer,
                    $value
                ),

                'number' => $this->saveNumberAnswer(
                    $answer,
                    $value
                ),

                'date' => $this->saveDateAnswer(
                    $answer,
                    $value
                ),

                'single_choice' => $this->saveSingleChoiceAnswer(
                    $answer,
                    $value
                ),
                'multiple_choice' => $this->saveMultipleChoiceAnswer(
                    $answer,
                    $value
                ),

                default => throw new InvalidArgumentException(
                    "Tipo de pregunta no soportado: {$question->questionType->code}"
                ),
            };
        });
    }

    protected function clearAnswerValues(
        Answer $answer
    ): void {
        $answer->text_value = null;
        $answer->number_value = null;
        $answer->date_value = null;
        $answer->option_id = null;
    }

    protected function saveTextAnswer(
        Answer $answer,
        mixed $value
    ): Answer {
        $answer->text_value = $value;
        $answer->save();

        return $answer;
    }

    protected function saveNumberAnswer(
        Answer $answer,
        mixed $value
    ): Answer {
        if (! is_numeric($value)) {
            throw new InvalidArgumentException(
                'El valor debe ser numérico.'
            );
        }

        $answer->number_value = $value;
        $answer->save();

        return $answer;
    }

    protected function saveDateAnswer(
        Answer $answer,
        mixed $value
    ): Answer {
        $answer->date_value = $value;
        $answer->save();

        return $answer;
    }

    protected function saveSingleChoiceAnswer(
        Answer $answer,
        mixed $value
    ): Answer {
        $optionId = (int) $value;

        if (
            ! $questionOption = $answer->question
                ->options()
                ->where('id', $optionId)
                ->where('active', true)
                ->first()
        ) {
            throw new InvalidArgumentException(
                'La opción seleccionada no pertenece a la pregunta.'
            );
        }

        $answer->option_id = $questionOption->id;
        $answer->save();

        return $answer;
    }

    public function start(
        SurveyResponse $response
    ): SurveyResponse {
        if ($response->status === SurveyResponseStatus::IN_PROGRESS) {
            return $response;
        }

        if ($response->status !== SurveyResponseStatus::DRAFT) {
            throw new InvalidArgumentException(
                'Solo una encuesta en estado draft puede iniciarse.'
            );
        }

        $response->update([
            'status' => SurveyResponseStatus::IN_PROGRESS,
            'started_at' => now(),
        ]);

        return $response->refresh();
    }

    public function complete(
        SurveyResponse $response
    ): SurveyResponse {
        if ($response->status === SurveyResponseStatus::COMPLETED) {
            return $response;
        }

        if ($response->status !== SurveyResponseStatus::IN_PROGRESS) {
            throw new InvalidArgumentException(
                'Solo una encuesta en progreso puede finalizarse.'
            );
        }

        $errors = $this->validator->validate(
            $response
        );

        if ($errors !== []) {
            throw new SurveyValidationException(
                $errors
            );
        }

        $response->update([
            'status' => SurveyResponseStatus::COMPLETED,
            'completed_at' => now(),
        ]);

        return $response->refresh();
    }

    public function cancel(
        SurveyResponse $response
    ): SurveyResponse {
        if ($response->status === SurveyResponseStatus::CANCELLED) {
            return $response;
        }

        if (! in_array(
            $response->status,
            [SurveyResponseStatus::DRAFT, SurveyResponseStatus::IN_PROGRESS],
            true
        )) {
            throw new InvalidArgumentException(
                'Solo una encuesta draft o en progreso puede cancelarse.'
            );
        }

        $response->update([
            'status' => SurveyResponseStatus::CANCELLED,
        ]);

        return $response->refresh();
    }

    protected function saveMultipleChoiceAnswer(
        Answer $answer,
        mixed $value
    ): Answer {
        if (! is_array($value)) {
            throw new InvalidArgumentException(
                'Las opciones seleccionadas deben enviarse como un arreglo.'
            );
        }

        $optionIds = array_values(
            array_unique(
                array_map('intval', $value)
            )
        );

        $validOptionIds = $answer->question
            ->options()
            ->where('active', true)
            ->whereIn('id', $optionIds)
            ->pluck('id')
            ->all();

        sort($optionIds);
        sort($validOptionIds);

        if ($optionIds !== $validOptionIds) {
            throw new InvalidArgumentException(
                'Una o más opciones no pertenecen a la pregunta.'
            );
        }

        $answer->save();

        $answer->selectedOptions()->delete();

        foreach ($optionIds as $optionId) {
            $answer->selectedOptions()->create([
                'question_option_id' => $optionId,
            ]);
        }

        return $answer->load(
            'selectedOptions.questionOption'
        );
    }
}

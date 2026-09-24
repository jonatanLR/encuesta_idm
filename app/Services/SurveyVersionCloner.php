<?php

namespace App\Services;

use App\Models\Question;
use App\Models\QuestionCondition;
use App\Models\Questionnaire;
use App\Models\QuestionOption;
use App\Models\Section;
use App\Models\SurveyVersion;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SurveyVersionCloner
{
    /**
     * Preguntas que forman parte de la versión de prueba inicial.
     */
    private const TEST_QUESTION_CODES = [
        'GENERAL_001',
        'GENERAL_004',
        'INFORMANT_001',
        'INFORMANT_002',
        'HOUSING_020',
        'LOCAL_003',
        'LOCAL_004',
        'TEST_MULTIPLE_001',
        'MEMBER_011',
        'MEMBER_012',
        'MEMBER_013',
        'CLOSURE_001',
    ];

    public function cloneTestVersion(
        Questionnaire $questionnaire,
        string $version
    ): SurveyVersion {
        return DB::transaction(function () use ($questionnaire, $version) {
            $this->ensureVersionDoesNotExist($questionnaire, $version);

            $sourceVersion = $questionnaire
                ->versions()
                ->where('active', true)
                ->latest('id')
                ->first();

            if (! $sourceVersion) {
                throw new RuntimeException(
                    'El cuestionario no tiene una versión activa para clonar.'
                );
            }

            $newVersion = $questionnaire->versions()->create([
                'version' => $version,
                'active' => false,
                'published_at' => null,
            ]);

            $sectionMap = $this->cloneSections(
                $sourceVersion,
                $newVersion
            );

            $questions = $sourceVersion
                ->sections()
                ->with('questions.options')
                ->get()
                ->flatMap(fn(Section $section) => $section->questions)
                ->filter(
                    fn(Question $question) =>
                    in_array($question->code, self::TEST_QUESTION_CODES, true)
                );

            $questionMap = [];
            $optionMap = [];

            foreach ($questions as $question) {
                $newSectionId = $sectionMap[$question->section_id] ?? null;

                if (! $newSectionId) {
                    throw new RuntimeException(
                        "No se encontró la sección clonada para la pregunta {$question->code}."
                    );
                }

                $newQuestion = $newVersion
                    ->sections()
                    ->find($newSectionId)
                    ->questions()
                    ->create([
                        'question_type_id' => $question->question_type_id,
                        'code' => $question->code,
                        'label' => $question->label,
                        'description' => $question->description,
                        'required' => $question->required,
                        'active' => $question->code === 'TEST_MULTIPLE_001'
                            ? true
                            : $question->active,
                        'sort_order' => $question->sort_order,
                        'data_source' => $question->data_source,
                        'data_source_table' => $question->data_source_table,
                    ]);

                $questionMap[$question->id] = $newQuestion->id;

                foreach ($question->options as $option) {
                    $newOption = $newQuestion->options()->create([
                        'label' => $option->label,
                        'value' => $option->value,
                        'sort_order' => $option->sort_order,
                        'active' => $option->active,
                    ]);

                    $optionMap[$option->id] = $newOption->id;
                }
            }

            $this->cloneConditions(
                $questions,
                $questionMap,
                $optionMap
            );

            return $newVersion->refresh();
        });
    }

    private function ensureVersionDoesNotExist(
        Questionnaire $questionnaire,
        string $version
    ): void {
        $exists = $questionnaire
            ->versions()
            ->where('version', $version)
            ->exists();

        if ($exists) {
            throw new RuntimeException(
                "La versión {$version} ya existe para este cuestionario."
            );
        }
    }

    /**
     * @return array<int, int>
     */
    private function cloneSections(
        SurveyVersion $sourceVersion,
        SurveyVersion $newVersion
    ): array {
        $sections = $sourceVersion
            ->sections()
            ->orderBy('sort_order')
            ->get();

        $sectionMap = [];

        foreach ($sections as $section) {
            $newSection = $newVersion->sections()->create([
                'parent_id' => null,
                'code' => $section->code,
                'name' => $section->name,
                'description' => $section->description,
                'sort_order' => $section->sort_order,
                'active' => $section->active,
            ]);

            $sectionMap[$section->id] = $newSection->id;
        }

        foreach ($sections as $section) {
            if ($section->parent_id === null) {
                continue;
            }

            $newSection = Section::findOrFail($sectionMap[$section->id]);

            $newSection->update([
                'parent_id' => $sectionMap[$section->parent_id] ?? null,
            ]);
        }

        return $sectionMap;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Question>  $questions
     * @param  array<int, int>  $questionMap
     * @param  array<int, int>  $optionMap
     */
    private function cloneConditions(
        $questions,
        array $questionMap,
        array $optionMap
    ): void {
        $conditions = QuestionCondition::query()
            ->whereIn('question_id', $questions->pluck('id'))
            ->get();

        foreach ($conditions as $condition) {
            if (! isset($questionMap[$condition->question_id])) {
                continue;
            }

            if (
                $condition->depends_on_question_id !== null
                && ! isset($questionMap[$condition->depends_on_question_id])
            ) {
                continue;
            }

            if (
                $condition->depends_on_option_id !== null
                && ! isset($optionMap[$condition->depends_on_option_id])
            ) {
                throw new RuntimeException(
                    "No se encontró la opción clonada para la condición {$condition->id}."
                );
            }

            QuestionCondition::create([
                'question_id' => $questionMap[$condition->question_id],
                'depends_on_question_id' => $condition->depends_on_question_id !== null
                    ? $questionMap[$condition->depends_on_question_id]
                    : null,
                'depends_on_option_id' => $condition->depends_on_option_id !== null
                    ? $optionMap[$condition->depends_on_option_id]
                    : null,
                'operator' => $condition->operator,
                'expected_value' => $condition->expected_value,
                'active' => $condition->active,
            ]);
        }
    }
}

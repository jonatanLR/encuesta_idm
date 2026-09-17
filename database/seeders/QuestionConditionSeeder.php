<?php

namespace Database\Seeders;

use App\Models\Question;
use App\Models\QuestionCondition;
use Illuminate\Database\Seeder;

class QuestionConditionSeeder extends Seeder
{
    public function run(): void
    {
        /*
         * GENERAL_006
         *
         * "Especifique"
         *
         * Se muestra cuando GENERAL_005 = "Otros".
         */

        $eventQuestion = Question::where(
            'code',
            'GENERAL_005'
        )->firstOrFail();

        $specifyQuestion = Question::where(
            'code',
            'GENERAL_006'
        )->firstOrFail();

        $otherOption = $eventQuestion->options()
            ->where('value', 'otros')
            ->firstOrFail();

        QuestionCondition::updateOrCreate(
            [
                'question_id' => $specifyQuestion->id,
                'depends_on_question_id' => $eventQuestion->id,
            ],
            [
                'depends_on_option_id' => $otherOption->id,
                'operator' => 'equals',
                'expected_value' => 'otros',
                'active' => true,
            ]
        );

        /*
         * V.B - Información complementaria del miembro
         *
         * MEMBER_010 se muestra cuando MEMBER_009 = "Otro".
         */

        $medicalCareQuestion = Question::where(
            'code',
            'MEMBER_009'
        )->firstOrFail();

        $medicalCareOtherQuestion = Question::where(
            'code',
            'MEMBER_010'
        )->firstOrFail();

        $medicalCareOtherOption = $medicalCareQuestion->options()
            ->where('value', 'otro')
            ->firstOrFail();

        QuestionCondition::updateOrCreate(
            [
                'question_id' => $medicalCareOtherQuestion->id,
                'depends_on_question_id' => $medicalCareQuestion->id,
            ],
            [
                'depends_on_option_id' => $medicalCareOtherOption->id,
                'operator' => 'equals',
                'expected_value' => 'otro',
                'active' => true,
            ]
        );

        /*
         * MEMBER_012 se muestra cuando MEMBER_011 = "Sí".
         */

        $disabilityQuestion = Question::where(
            'code',
            'MEMBER_011'
        )->firstOrFail();

        $disabilityTypeQuestion = Question::where(
            'code',
            'MEMBER_012'
        )->firstOrFail();

        QuestionCondition::updateOrCreate(
            [
                'question_id' => $disabilityTypeQuestion->id,
                'depends_on_question_id' => $disabilityQuestion->id,
            ],
            [
                'depends_on_option_id' => null,
                'operator' => 'equals',
                'expected_value' => 'true',
                'active' => true,
            ]
        );

        /*
         * MEMBER_013 se muestra cuando MEMBER_012 = "Otra".
         */

        $disabilityOtherQuestion = Question::where(
            'code',
            'MEMBER_013'
        )->firstOrFail();

        $disabilityOtherOption = $disabilityTypeQuestion->options()
            ->where('value', 'otra')
            ->firstOrFail();

        QuestionCondition::updateOrCreate(
            [
                'question_id' => $disabilityOtherQuestion->id,
                'depends_on_question_id' => $disabilityTypeQuestion->id,
            ],
            [
                'depends_on_option_id' => $disabilityOtherOption->id,
                'operator' => 'equals',
                'expected_value' => 'otra',
                'active' => true,
            ]
        );

        /*
         * MEMBER_015 se muestra cuando MEMBER_014 = "Sí".
         */

        $chronicDiseaseQuestion = Question::where(
            'code',
            'MEMBER_014'
        )->firstOrFail();

        $chronicDiseaseTypeQuestion = Question::where(
            'code',
            'MEMBER_015'
        )->firstOrFail();

        QuestionCondition::updateOrCreate(
            [
                'question_id' => $chronicDiseaseTypeQuestion->id,
                'depends_on_question_id' => $chronicDiseaseQuestion->id,
            ],
            [
                'depends_on_option_id' => null,
                'operator' => 'equals',
                'expected_value' => 'true',
                'active' => true,
            ]
        );

        /*
         * MEMBER_016 se muestra cuando MEMBER_015 = "Otra".
         */

        $chronicDiseaseOtherQuestion = Question::where(
            'code',
            'MEMBER_016'
        )->firstOrFail();

        $chronicDiseaseOtherOption = $chronicDiseaseTypeQuestion->options()
            ->where('value', 'otra')
            ->firstOrFail();

        QuestionCondition::updateOrCreate(
            [
                'question_id' => $chronicDiseaseOtherQuestion->id,
                'depends_on_question_id' => $chronicDiseaseTypeQuestion->id,
            ],
            [
                'depends_on_option_id' => $chronicDiseaseOtherOption->id,
                'operator' => 'equals',
                'expected_value' => 'otra',
                'active' => true,
            ]
        );
    }
}

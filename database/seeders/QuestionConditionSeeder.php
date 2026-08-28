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
         * GENERAL_003
         *
         * "En caso de que el nombre no aparezca en el listado..."
         *
         * Depende de GENERAL_002.
         *
         * Actualmente GENERAL_002 es un campo de texto, por lo que
         * todavía no podemos establecer una opción concreta.
         */

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
    }
}
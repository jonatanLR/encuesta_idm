<?php

namespace Database\Seeders;

use App\Models\Questionnaire;
use App\Models\Section;
use App\Models\SurveyVersion;
use Illuminate\Database\Seeder;

class SectionSeeder extends Seeder
{
    public function run(): void
    {
        $questionnaire = Questionnaire::where(
            'name',
            'Encuesta de Situación Social'
        )->firstOrFail();

        $version = SurveyVersion::where(
            'questionnaire_id',
            $questionnaire->id
        )
            ->where('version', '1.0')
            ->firstOrFail();

        $sections = [
            [
                'code' => 'GENERAL',
                'name' => 'I. Información General de la Encuesta',
                'sort_order' => 1,
            ],
            [
                'code' => 'INFORMANT',
                'name' => 'II. Información del Informante',
                'sort_order' => 2,
            ],
            [
                'code' => 'HOUSING',
                'name' => 'III. Información de Vivienda y Hábitat',
                'sort_order' => 3,
            ],
            [
                'code' => 'HOUSEHOLD',
                'name' => 'IV. Hogar',
                'sort_order' => 4,
            ],
            [
                'code' => 'MEMBER',
                'name' => 'V. Miembro',
                'sort_order' => 5,
            ],
            [
                'code' => 'CLOSURE',
                'name' => 'VI. Preguntas de Cierre',
                'sort_order' => 6,
            ],
        ];

        foreach ($sections as $section) {
            Section::updateOrCreate(
                [
                    'survey_version_id' => $version->id,
                    'code' => $section['code'],
                ],
                [
                    'name' => $section['name'],
                    'parent_id' => null,
                    'sort_order' => $section['sort_order'],
                    'active' => true,
                ]
            );
        }

        $housing = Section::where([
            'survey_version_id' => $version->id,
            'code' => 'HOUSING',
        ])->firstOrFail();

        Section::updateOrCreate(
            [
                'survey_version_id' => $version->id,
                'code' => 'LOCAL',
            ],
            [
                'name' => 'III.1 Información del Local',
                'parent_id' => $housing->id,
                'sort_order' => 1,
                'active' => true,
            ]
        );
    }
}

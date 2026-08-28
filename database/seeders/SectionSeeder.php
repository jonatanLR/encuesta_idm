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
                'name' => 'I. Información General de la Encuesta',
                'sort_order' => 1,
            ],
            [
                'name' => 'II. Información del Informante',
                'sort_order' => 2,
            ],
            [
                'name' => 'III. Información de Vivienda y Hábitat',
                'sort_order' => 3,
            ],
            [
                'name' => 'IV. Hogar',
                'sort_order' => 4,
            ],
            [
                'name' => 'V. Miembro',
                'sort_order' => 5,
            ],
            [
                'name' => 'VI. Preguntas de Cierre',
                'sort_order' => 6,
            ],
        ];

        foreach ($sections as $section) {
            Section::updateOrCreate(
                [
                    'survey_version_id' => $version->id,
                    'name' => $section['name'],
                ],
                [
                    'parent_id' => null,
                    'sort_order' => $section['sort_order'],
                    'active' => true,
                ]
            );
        }

        $housing = Section::where([
            'survey_version_id' => $version->id,
            'name' => 'III. Información de Vivienda y Hábitat',
        ])->firstOrFail();

        Section::updateOrCreate(
            [
                'survey_version_id' => $version->id,
                'name' => 'III.1 Información del Local',
            ],
            [
                'parent_id' => $housing->id,
                'sort_order' => 1,
                'active' => true,
            ]
        );
    }
}
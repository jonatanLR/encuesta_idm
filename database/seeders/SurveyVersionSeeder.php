<?php

namespace Database\Seeders;

use App\Models\Questionnaire;
use App\Models\SurveyVersion;
use Illuminate\Database\Seeder;

class SurveyVersionSeeder extends Seeder
{
    public function run(): void
    {
        $questionnaire = Questionnaire::where(
            'name',
            'Encuesta de Situación Social'
        )->firstOrFail();

        SurveyVersion::updateOrCreate(
            [
                'questionnaire_id' => $questionnaire->id,
                'version' => '1.0',
            ],
            [
                'active' => true,
                'published_at' => now(),
            ]
        );
    }
}
<?php

namespace Database\Seeders;

use App\Models\Questionnaire;
use Illuminate\Database\Seeder;

class QuestionnaireSeeder extends Seeder
{
    public function run(): void
    {
        Questionnaire::updateOrCreate(
            [
                'name' => 'Encuesta de Situación Social',
            ],
            [
                'description' => 'Encuesta para recopilar información sobre daños, condiciones de viviendas y negocios tras un evento natural en una comunidad.',
                'active' => true,
            ]
        );
    }
}
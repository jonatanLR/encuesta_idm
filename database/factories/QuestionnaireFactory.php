<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class QuestionnaireFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Encuesta de Prueba',
            'description' => 'Cuestionario generado para pruebas automatizadas.',
            'active' => true,
        ];
    }
}
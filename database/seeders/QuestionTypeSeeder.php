<?php

namespace Database\Seeders;

use App\Models\QuestionType;
use Illuminate\Database\Seeder;

class QuestionTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'code' => 'text',
                'name' => 'Texto',
            ],
            [
                'code' => 'textarea',
                'name' => 'Texto largo',
            ],
            [
                'code' => 'number',
                'name' => 'Número entero',
            ],
            [
                'code' => 'decimal',
                'name' => 'Número decimal',
            ],
            [
                'code' => 'date',
                'name' => 'Fecha',
            ],
            [
                'code' => 'datetime',
                'name' => 'Fecha y hora',
            ],
            [
                'code' => 'single_choice',
                'name' => 'Selección única',
            ],
            [
                'code' => 'multiple_choice',
                'name' => 'Selección múltiple',
            ],
            [
                'code' => 'boolean',
                'name' => 'Sí / No',
            ],
            [
                'code' => 'image',
                'name' => 'Imagen',
            ],
            [
                'code' => 'file',
                'name' => 'Archivo',
            ],
        ];

        foreach ($types as $type) {
            QuestionType::updateOrCreate(
                ['code' => $type['code']],
                $type
            );
        }
    }
}
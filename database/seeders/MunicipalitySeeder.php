<?php

namespace Database\Seeders;

use App\Models\Municipality;
use Illuminate\Database\Seeder;

class MunicipalitySeeder extends Seeder
{
    public function run(): void
    {
        Municipality::updateOrCreate(
            [
                'name' => 'Distrito Central',
            ],
            [
                'active' => true,
            ]
        );
    }
}

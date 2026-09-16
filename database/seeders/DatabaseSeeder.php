<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            QuestionTypeSeeder::class,
            QuestionnaireSeeder::class,
            SurveyVersionSeeder::class,
            SectionSeeder::class,
            HouseholdRelationshipSeeder::class,
            QuestionSeeder::class,
            QuestionOptionSeeder::class,
            QuestionConditionSeeder::class,

            MunicipalitySeeder::class,
            CommunitySeeder::class,
        ]);
    }
}

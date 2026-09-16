<?php

namespace Database\Seeders;

use App\Models\HouseholdRelationship;
use Illuminate\Database\Seeder;

class HouseholdRelationshipSeeder extends Seeder
{
    public function run(): void
    {
        $relationships = [
            ['code' => 'HEAD', 'name' => 'Jefe/a de hogar', 'sort_order' => 1],
            ['code' => 'SPOUSE', 'name' => 'Cónyuge', 'sort_order' => 2],
            ['code' => 'CHILD', 'name' => 'Hijo/a', 'sort_order' => 3],
            ['code' => 'SIBLING', 'name' => 'Hermano/a', 'sort_order' => 4],
            ['code' => 'PARENT', 'name' => 'Padre/madre', 'sort_order' => 5],
            ['code' => 'GRANDPARENT', 'name' => 'Abuelo/a', 'sort_order' => 6],
            ['code' => 'GRANDCHILD', 'name' => 'Nieto/a', 'sort_order' => 7],
            ['code' => 'UNCLE_AUNT', 'name' => 'Tío/a', 'sort_order' => 8],
            ['code' => 'COUSIN', 'name' => 'Primo/a', 'sort_order' => 9],
            ['code' => 'NEPHEW_NIECE', 'name' => 'Sobrino/a', 'sort_order' => 10],
            ['code' => 'PARENT_IN_LAW', 'name' => 'Suegro/a', 'sort_order' => 11],
            ['code' => 'CHILD_IN_LAW', 'name' => 'Yerno/Nuera', 'sort_order' => 12],
            ['code' => 'SIBLING_IN_LAW', 'name' => 'Cuñado/a', 'sort_order' => 13],
            ['code' => 'DOMESTIC_WORKER', 'name' => 'Empleada/o doméstica', 'sort_order' => 14],
            ['code' => 'OTHER_NON_RELATIVE', 'name' => 'Otro no pariente', 'sort_order' => 15],
        ];

        foreach ($relationships as $relationship) {
            HouseholdRelationship::updateOrCreate(
                ['code' => $relationship['code']],
                [
                    'name' => $relationship['name'],
                    'active' => true,
                    'sort_order' => $relationship['sort_order'],
                ]
            );
        }
    }
}

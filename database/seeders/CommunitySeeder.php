<?php

namespace Database\Seeders;

use App\Models\Community;
use App\Models\Municipality;
use Illuminate\Database\Seeder;
use App\Support\CommunitySearch;

class CommunitySeeder extends Seeder
{
    public function run(): void
    {
        $municipality = Municipality::where(
            'name',
            'Distrito Central'
        )->firstOrFail();

        $communities = require database_path(
            'data/distrito_central_communities.php'
        );

        foreach ($communities as $community) {
            Community::updateOrCreate(
                [
                    'municipality_id' => $municipality->id,
                    'name' => $community['name'],
                ],
                [
                    'source_code' => $community['source_code'] ?? null,
                    'search_name' => CommunitySearch::normalize(
                        $community['name']
                    ),
                    'type' => $community['type'] ?? 'other',
                    'area' => $community['area'] ?? null,
                    'active' => true,
                ]
            );
        }
    }
}

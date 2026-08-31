<?php

namespace App\Services;

use App\Models\Community;
use Illuminate\Database\Eloquent\Collection;

class CommunitySearchService
{
    public function search(
        string $term,
        int $municipalityId,
        int $limit = 10
    ): Collection {
        $term = trim($term);

        if ($term === '') {
            return new Collection();
        }

        return Community::query()
            ->where('municipality_id', $municipalityId)
            ->where('active', true)
            ->where(
                'search_name',
                'like',
                '%' . $term . '%'
            )
            ->orderBy('name')
            ->limit($limit)
            ->get();
    }
}
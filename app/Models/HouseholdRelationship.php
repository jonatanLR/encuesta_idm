<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HouseholdRelationship extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function householdMembers(): HasMany
    {
        return $this->hasMany(HouseholdMember::class, 'relationship_id');
    }
}

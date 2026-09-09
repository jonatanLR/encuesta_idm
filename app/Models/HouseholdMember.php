<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class HouseholdMember extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'public_id',
        'household_id',
        'relationship_id',
        'name',
        'age',
        'sex',
        'dni',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $householdMember): void {
            $householdMember->public_id ??= (string) Str::ulid();
        });
    }

    protected function casts(): array
    {
        return [
            'age' => 'decimal:2',
            'capture_started_at' => 'datetime',
        ];
    }

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    public function householdRelationship(): BelongsTo
    {
        return $this->belongsTo(HouseholdRelationship::class, 'relationship_id');
    }
}

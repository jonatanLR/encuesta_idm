<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Community extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'municipality_id',
        'source_code',
        'name',
        'search_name',
        'type',
        'area',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }

    public function surveyResponses()
    {
        return $this->hasMany(
            SurveyResponse::class
        );
    }
}

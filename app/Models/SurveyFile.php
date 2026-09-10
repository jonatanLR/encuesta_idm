<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SurveyFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'survey_response_id',
        'answer_id',
        'household_member_id',
        'file_type',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size',
    ];

    protected $casts = [
        'size' => 'integer',
    ];

    public function surveyResponse(): BelongsTo
    {
        return $this->belongsTo(
            SurveyResponse::class
        );
    }

    public function answer(): BelongsTo
    {
        return $this->belongsTo(
            Answer::class
        );
    }

    public function householdMember(): BelongsTo
    {
        return $this->belongsTo(
            HouseholdMember::class
        );
    }
}

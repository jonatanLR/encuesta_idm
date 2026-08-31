<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SurveyResponse extends Model
{
    use HasFactory;

    protected $fillable = [
        'questionnaire_id',
        'survey_version_id',
        'community_id',
        'created_by',
        'status',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function questionnaire()
    {
        return $this->belongsTo(
            Questionnaire::class
        );
    }

    public function surveyVersion()
    {
        return $this->belongsTo(
            SurveyVersion::class
        );
    }

    public function community()
    {
        return $this->belongsTo(
            Community::class
        );
    }

    public function creator()
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    public function answers()
    {
        return $this->hasMany(
            Answer::class
        );
    }
}

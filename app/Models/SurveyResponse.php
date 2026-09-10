<?php

namespace App\Models;

use App\Enums\SurveyResponseStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SurveyResponse extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference',
        'questionnaire_id',
        'survey_version_id',
        'community_id',
        'created_by',
        'status',
        'started_at',
        'completed_at',
        'public_id',
        'latitude',
        'longitude',
        'location_source',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $surveyResponse): void {
            $surveyResponse->public_id ??= (string) Str::ulid();
        });
    }

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

    public function surveyFiles(): HasMany
    {
        return $this->hasMany(
            SurveyFile::class
        );
    }

    public function household(): HasOne
    {
        return $this->hasOne(Household::class);
    }

    protected function casts(): array
    {
        return [
            'status' => SurveyResponseStatus::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }
}

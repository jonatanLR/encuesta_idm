<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Answer extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'survey_response_id',
        'question_id',
        'text_value',
        'number_value',
        'date_value',
        'option_id',
        'household_member_id',
        'boolean_value',
    ];

    protected $casts = [
        'number_value' => 'decimal:4',
        'date_value' => 'date',
        'boolean_value' => 'boolean',
    ];

    /**
     * Identificador estable para la sincronización offline (D-03).
     * El cliente puede enviarlo; si no viene, se genera aquí.
     */
    protected static function booted(): void
    {
        static::creating(function (self $answer): void {
            $answer->client_id ??= (string) Str::ulid();
        });
    }

    public function surveyResponse()
    {
        return $this->belongsTo(
            SurveyResponse::class
        );
    }

    public function question()
    {
        return $this->belongsTo(
            Question::class
        );
    }

    public function option()
    {
        return $this->belongsTo(
            QuestionOption::class,
            'option_id'
        );
    }

    public function householdMember(): BelongsTo
    {
        return $this->belongsTo(
            HouseholdMember::class,
            'household_member_id'
        );
    }

    public function selectedOptions()
    {
        return $this->hasMany(
            AnswerOption::class
        );
    }

    public function surveyFiles(): HasMany
    {
        return $this->hasMany(
            SurveyFile::class
        );
    }
}

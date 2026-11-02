<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class SurveyFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
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

    /**
     * Identificador estable para la sincronización offline (D-03).
     * El cliente puede enviarlo; si no viene, se genera aquí.
     */
    protected static function booted(): void
    {
        static::creating(function (self $surveyFile): void {
            $surveyFile->client_id ??= (string) Str::ulid();
        });
    }

    /**
     * URL autenticada para mostrar la fotografía (D-07). Nunca expone la
     * ruta física del archivo.
     */
    public function url(): string
    {
        return route('survey-files.show', $this);
    }

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

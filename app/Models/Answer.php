<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Answer extends Model
{
    use HasFactory;

    protected $fillable = [
        'survey_response_id',
        'question_id',
        'text_value',
        'number_value',
        'date_value',
        'option_id',
    ];

    protected $casts = [
        'number_value' => 'decimal:4',
        'date_value' => 'date',
    ];

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

    public function selectedOptions()
    {
        return $this->hasMany(
            AnswerOption::class
        );
    }
}

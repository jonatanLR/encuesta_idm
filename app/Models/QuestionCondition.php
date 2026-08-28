<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionCondition extends Model
{
    protected $fillable = [
        'question_id',
        'depends_on_question_id',
        'depends_on_option_id',
        'operator',
        'expected_value',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function dependsOnQuestion(): BelongsTo
    {
        return $this->belongsTo(
            Question::class,
            'depends_on_question_id'
        );
    }

    public function dependsOnOption(): BelongsTo
    {
        return $this->belongsTo(
            QuestionOption::class,
            'depends_on_option_id'
        );
    }
}
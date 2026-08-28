<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    protected $fillable = [
        'section_id',
        'question_type_id',
        'code',
        'label',
        'description',
        'required',
        'active',
        'sort_order',
    ];

    protected $casts = [
        'required' => 'boolean',
        'active' => 'boolean',
    ];

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function questionType()
    {
        return $this->belongsTo(QuestionType::class);
    }

    public function options()
    {
        return $this->hasMany(QuestionOption::class)
            ->orderBy('sort_order');
    }

    public function conditions(): HasMany
    {
        return $this->hasMany(QuestionCondition::class);
    }

    public function dependentQuestions(): HasMany
    {
        return $this->hasMany(
            QuestionCondition::class,
            'depends_on_question_id'
        );
    }

    public function dependencies(): HasMany
{
    return $this->hasMany(
        QuestionCondition::class,
        'question_id'
    );
}
}

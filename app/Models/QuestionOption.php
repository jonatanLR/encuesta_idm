<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuestionOption extends Model
{
     protected $fillable = [
        'question_id',
        'label',
        'value',
        'sort_order',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function question()
    {
        return $this->belongsTo(Question::class);
    }
}

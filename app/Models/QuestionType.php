<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuestionType extends Model
{
     protected $fillable = [
        'code',
        'name',
    ];

    public function questions()
    {
        return $this->hasMany(Question::class);
    }
}

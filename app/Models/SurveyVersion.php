<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SurveyVersion extends Model
{
    protected $fillable = [
        'questionnaire_id',
        'version',
        'active',
        'published_at',
    ];

    protected $casts = [
        'active' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function questionnaire()
    {
        return $this->belongsTo(Questionnaire::class);
    }

    public function sections()
    {
        return $this->hasMany(Section::class)
            ->orderBy('sort_order');
    }

    public function responses()
    {
        return $this->hasMany(
            SurveyResponse::class
        );
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'survey_version_id',
        'parent_id',
        'name',
        'description',
        'sort_order',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function surveyVersion()
    {
        return $this->belongsTo(SurveyVersion::class);
    }

    public function parent()
    {
        return $this->belongsTo(Section::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Section::class, 'parent_id')
            ->orderBy('sort_order');
    }

    public function questions()
    {
        return $this->hasMany(Question::class)
            ->orderBy('sort_order');
    }
}

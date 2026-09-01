<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Questionnaire extends Model
{
    use HasFactory;

     protected $fillable = [
        'name',
        'description',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function versions()
    {
        return $this->hasMany(SurveyVersion::class);
    }

    public function responses()
{
    return $this->hasMany(
        SurveyResponse::class
    );
}

}

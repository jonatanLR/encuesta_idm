<?php

namespace App\Policies;

use App\Models\Questionnaire;
use App\Models\User;

class QuestionnairePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('questionnaire.view');
    }

    public function view(User $user, Questionnaire $questionnaire): bool
    {
        return $user->hasPermission('questionnaire.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('questionnaire.create');
    }

    public function update(User $user, Questionnaire $questionnaire): bool
    {
        return $user->hasPermission('questionnaire.update');
    }
}

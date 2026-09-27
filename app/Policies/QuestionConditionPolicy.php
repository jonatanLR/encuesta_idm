<?php

namespace App\Policies;

use App\Models\QuestionCondition;
use App\Models\User;

class QuestionConditionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('question-condition.view');
    }

    public function view(
        User $user,
        QuestionCondition $questionCondition
    ): bool {
        return $user->hasPermission('question-condition.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('question-condition.create');
    }

    public function update(
        User $user,
        QuestionCondition $questionCondition
    ): bool {
        return $user->hasPermission('question-condition.update');
    }

    public function activate(
        User $user,
        QuestionCondition $questionCondition
    ): bool {
        return $user->hasPermission('question-condition.activate');
    }
}

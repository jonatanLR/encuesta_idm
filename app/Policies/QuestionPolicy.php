<?php

namespace App\Policies;

use App\Models\Question;
use App\Models\User;

class QuestionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('question.view');
    }

    public function view(User $user, Question $question): bool
    {
        return $user->hasPermission('question.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('question.create');
    }

    public function update(User $user, Question $question): bool
    {
        return $user->hasPermission('question.update');
    }

    public function activate(User $user, Question $question): bool
    {
        return $user->hasPermission('question.activate');
    }
}

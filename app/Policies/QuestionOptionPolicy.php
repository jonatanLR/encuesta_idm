<?php

namespace App\Policies;

use App\Models\QuestionOption;
use App\Models\User;

class QuestionOptionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('question-option.view');
    }

    public function view(User $user, QuestionOption $questionOption): bool
    {
        return $user->hasPermission('question-option.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('question-option.create');
    }

    public function update(User $user, QuestionOption $questionOption): bool
    {
        return $user->hasPermission('question-option.update');
    }

    public function activate(User $user, QuestionOption $questionOption): bool
    {
        return $user->hasPermission('question-option.activate');
    }
}

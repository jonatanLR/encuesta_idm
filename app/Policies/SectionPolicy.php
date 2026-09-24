<?php

namespace App\Policies;

use App\Models\Section;
use App\Models\User;

class SectionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('section.view');
    }

    public function view(User $user, Section $section): bool
    {
        return $user->hasPermission('section.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('section.create');
    }

    public function update(User $user, Section $section): bool
    {
        return $user->hasPermission('section.update');
    }

    public function activate(User $user, Section $section): bool
    {
        return $user->hasPermission('section.activate');
    }
}

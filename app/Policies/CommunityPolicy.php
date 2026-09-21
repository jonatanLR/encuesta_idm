<?php

namespace App\Policies;

use App\Models\Community;
use App\Models\User;

class CommunityPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('community.view');
    }

    public function view(User $user, Community $community): bool
    {
        return $user->hasPermission('community.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('community.create');
    }

    public function update(User $user, Community $community): bool
    {
        return $user->hasPermission('community.update');
    }

    public function activate(User $user, Community $community): bool
    {
        return $user->hasPermission('community.activate');
    }
}

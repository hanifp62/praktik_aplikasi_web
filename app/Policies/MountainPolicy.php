<?php

namespace App\Policies;

use App\Models\Mountain;
use App\Models\User;

class MountainPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Mountain $mountain): bool
    {
        return $user->isAdmin();
    }

    public function archive(User $user, Mountain $mountain): bool
    {
        return $user->isAdmin();
    }
}

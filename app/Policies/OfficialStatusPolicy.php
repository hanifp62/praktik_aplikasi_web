<?php

namespace App\Policies;

use App\Models\OfficialStatus;
use App\Models\User;

class OfficialStatusPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, OfficialStatus $status): bool
    {
        return $user->isAdmin();
    }
}

<?php

namespace App\Policies;

use App\Models\Trail;
use App\Models\User;

class TrailPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Trail $trail): bool
    {
        return $user->isAdmin();
    }

    public function publish(User $user, Trail $trail): bool
    {
        // PRD §110: publishing requires the minimum data quality set.
        return $user->isAdmin() && $trail->meetsPublishingRequirements();
    }

    public function archive(User $user, Trail $trail): bool
    {
        return $user->isAdmin();
    }
}

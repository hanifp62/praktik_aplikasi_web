<?php

namespace App\Policies;

use App\Models\DataSource;
use App\Models\User;

class DataSourcePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, DataSource $source): bool
    {
        return $user->isAdmin();
    }

    public function verify(User $user, DataSource $source): bool
    {
        return $user->isAdmin();
    }
}

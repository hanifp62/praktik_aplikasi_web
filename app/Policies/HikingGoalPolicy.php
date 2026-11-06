<?php

namespace App\Policies;

use App\Models\HikingGoal;
use App\Models\User;

class HikingGoalPolicy
{
    public function view(User $user, HikingGoal $goal): bool
    {
        return $goal->user_id === $user->id;
    }

    public function update(User $user, HikingGoal $goal): bool
    {
        return $goal->user_id === $user->id;
    }

    public function delete(User $user, HikingGoal $goal): bool
    {
        return $goal->user_id === $user->id;
    }
}

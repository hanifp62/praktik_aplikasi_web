<?php

namespace App\Policies;

use App\Models\TripPlan;
use App\Models\User;

/**
 * PRD §77 and §119: owning the URL is not owning the trip.
 */
class TripPlanPolicy
{
    public function view(User $user, TripPlan $trip): bool
    {
        return $trip->user_id === $user->id;
    }

    public function update(User $user, TripPlan $trip): bool
    {
        return $trip->user_id === $user->id;
    }

    public function delete(User $user, TripPlan $trip): bool
    {
        return $trip->user_id === $user->id;
    }
}

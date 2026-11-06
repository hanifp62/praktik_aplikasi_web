<?php

namespace App\Policies;

use App\Models\TrailConsideration;
use App\Models\User;

/**
 * Simpanan dan catatannya milik satu pendaki. Mengetahui id jalur tidak sama dengan
 * memiliki barisnya (PRD §77).
 */
class TrailConsiderationPolicy
{
    public function update(User $user, TrailConsideration $simpanan): bool
    {
        return $simpanan->user_id === $user->id;
    }
}

<?php

namespace App\Policies;

use App\Models\User;

/**
 * Aturan perizinan adalah data kurasi: hanya admin yang mencatat dan mengubahnya.
 *
 * Pendaki membacanya lewat Trail Detail dan checklist persiapan, bukan lewat model
 * ini langsung, sehingga tidak ada kemampuan baca di sini.
 */
class PermitRequirementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user): bool
    {
        return $user->isAdmin();
    }
}
